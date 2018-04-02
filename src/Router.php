<?php
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
  Router.php - Part of the php-router project.

  © - Jitesoft 2018
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
namespace Jitesoft\Router;

use function array_key_exists;
use Exception;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;
use function is_callable;
use Jitesoft\Container\Container;
use Jitesoft\Exceptions\Http\Client\HttpMethodNotAllowedException;
use Jitesoft\Exceptions\Http\Client\HttpNotFoundException;
use Jitesoft\Exceptions\Http\Server\HttpInternalServerErrorException;
use Jitesoft\Exceptions\Psr\Container\ContainerException;
use Jitesoft\Log\FileLogger;
use Jitesoft\Log\StdLogger;
use Jitesoft\Router\Contracts\MiddlewareInterface;
use Jitesoft\Router\Http\Handler;
use Jitesoft\Router\Http\Method;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use function var_dump;
use Zend\Diactoros\ServerRequestFactory;

/**
 * Router
 * @author Johannes Tegnér <johannes@jitesoft.com>
 * @version 1.0.0
 *
 * Router class, handling the actual routing of the package.
 *
 * The Router class takes care of storing and invoking different handlers and their middleWares.
 * It creates a new handler for each action and stores it in a p
 * roper structure to easily and quickly fetch the desired request route.
 *
 * @method Router get(string $pattern, string|callable $handler, array $middleWares = []) Creates a handler for http GET method.
 * @method Router head(string $pattern, string|callable $handler, array $middleWares = []) Creates a handler for http HEAD method.
 * @method Router post(string $pattern, string|callable $handler, array $middleWares = []) Creates a handler for http POST method.
 * @method Router put(string $pattern, string|callable $handler, array $middleWares = []) Creates a handler for http PUT method.
 * @method Router delete(string $pattern, string|callable $handler, array $middleWares = []) Creates a handler for http DELETE method.
 * @method Router connect(string $pattern, string|callable $handler, array $middleWares = []) Creates a handler for http CONNECT method.
 * @method Router options(string $pattern, string|callable $handler, array $middleWares = []) Creates a handler for http OPTIONS method.
 * @method Router trace(string $pattern, string|callable $handler, array $middleWares = []) Creates a handler for http TRACE method.
 * @method Router patch(string $pattern, string|callable $handler, array $middleWares = []) Creates a handler for http PATCH method.
 *
 * @see Method for information about the HTTP methods.
 *
 * @todo Implement route groups to stack multiple actions under one namespace.
 */
class Router implements LoggerAwareInterface {

    private $logger;
    private $container;
    private $actions = [];

    /**
     * Router constructor.
     *
     * @throws Exception
     */
    public function __construct() {
        try {
            $this->container = new Container();
        } catch (ContainerException $e) {
            $this->logger->error('Failed to create router, container was not possible to initialize.');
            throw new Exception('Failed to create router.');
        }

        $this->logger = new StdLogger();
    }

    public function __call($name, $arguments): self {
        return $this->action($name, ...$arguments);
    }

    /**
     * Create a handler for a given http method.
     *
     * @param string $method
     * @param string $pattern
     * @param string|callable $handler
     * @param array|string[] $middleWares
     * @return Router
     */
    public function action(string $method, $pattern, $handler, $middleWares = []): self {
        if (!array_key_exists($method, $this->actions)) {
            $this->actions[$method] = [];
        }

        $this->actions[$method][] = new Handler($method, $pattern, $handler, $middleWares);
        return $this;
    }

    /**
     * Registers the MiddleWares that the router can make use of.
     * If a middleware is required by a handler and it does not exist, an exception will be thrown.
     *
     * The expected format is a list of middleware classes.
     *
     * @param array|string[] $middleWares
     * @return Router
     */
    public function registerMiddleWares(array $middleWares = []): self {
        foreach ($middleWares as $mw) {
            if (!$this->container->has($mw)) {
                try {
                    $this->container->set($mw, $mw, true);
                } catch (ContainerException $e) {
                    $this->logger->error(
                        'Failed to fetch middleware from the container even though it should exist.'
                    );
                    // This should not be possible.
                    continue;
                }
            }
        }

        return $this;
    }

    /**
     * @param RequestInterface|null $request
     * @return ResponseInterface
     * @throws HttpInternalServerErrorException
     * @throws HttpMethodNotAllowedException
     * @throws HttpNotFoundException
     */
    public function handle(RequestInterface $request = null): ResponseInterface {
        $request = $request ?? ServerRequestFactory::fromGlobals();

        $dispatcher = simpleDispatcher(function (RouteCollector $routeCollector) use ($request) {
            if (!array_key_exists(strtolower($request->getMethod()), $this->actions)) {
                $this->logger->warning('No action with method {method} found.',
                    [
                        'method' => $request->getMethod()
                    ]
                );
                throw new HttpNotFoundException();
            }

            /** @var Handler $action */
            foreach ($this->actions[strtolower($request->getMethod())] as $id => $action) {
                $routeCollector->addRoute(strtoupper($request->getMethod()), $action->getPattern(), $id);
            }
        });

        $this->logger->debug('Request method: {method}. Target: {target}', [
            'method' => $request->getMethod(),
            'target' => $request->getRequestTarget()
        ]);

        $result = $dispatcher->dispatch($request->getMethod(), $request->getRequestTarget());
        $this->logger->debug('Dispatch complete, result: {result}.', [
            'result' => (['not found', 'found', 'method not allowed', 'unknown'])[$result[0]]
        ]);

        if ($result[0] === Dispatcher::FOUND) {
            $id   = $result[1];
            $args = $result[2];
            return $this->handleInvocation($request, $id, $args);
        }

        if ($result[0] === Dispatcher::NOT_FOUND) {
            throw new HttpNotFoundException();
        }

        if ($result[0] === Dispatcher::METHOD_NOT_ALLOWED) {
            throw new HttpMethodNotAllowedException();
        }

        throw new HttpInternalServerErrorException();
    }

    /**
     * @param RequestInterface $request
     * @param int $id
     * @param array $arguments
     * @return ResponseInterface
     * @throws HttpNotFoundException
     */
    private function handleInvocation(RequestInterface $request, int $id, array $arguments): ResponseInterface {
        // Find the handler.
        /** @var Handler $handler */
        $handler = $this->actions[strtolower($request->getMethod())][$id];
        if (!array_key_exists(strtolower($request->getMethod()), $this->actions)
            || !array_key_exists($id, $this->actions[strtolower($request->getMethod())])) {

            throw new HttpNotFoundException('Route not specified.');
        }

        $this->actions = array_map(function($middleware) {
            if ($middleware instanceof MiddlewareInterface) {
                $this->logger->debug('Middleware was of MiddlewareInterface type.');
                return $middleware;
            }

            if (is_string($middleware)) {
                $this->logger->debug('Middleware was a string, fetching from container.');
                if ($this->container->has($middleware)) {
                    return $this->container->get($middleware);
                }
            }

            throw new HttpInternalServerErrorException(
                'Failed to handle request. Middleware was not found available.'
            );

        }, $handler->getMiddlewares());

        // Create handler for the last action.
        $this->actions[] = function(RequestInterface $request) use ($arguments, $handler) {
            if ($handler->getCallback() !== null) {
                $this->logger->debug('Request handler was a callable.');
                return $handler->getCallback()($request, ...array_values($arguments));
            }

            $this->logger->debug('Request handler was not a callable.');
            $class = $this->container->get($handler->getClass());
            return $class->{$handler->getMethod()}($request, ...array_values($arguments));
        };

        return $this->callChain($request, $this->actions);
    }

    /**
     * @param RequestInterface $request
     * @param array $chain
     * @return ResponseInterface
     */
    private function callChain(RequestInterface $request, array $chain): ResponseInterface {

        /** @var MiddlewareInterface $action */
        $action = array_splice($chain, 0, 1)[0]; // Get the first in the chain and remove from list.
        if (is_callable($action)) { // The callable should always be the handler.
            return $action($request);
        }
        // The last part of the call chain is the actual controller/handling class.
        // It should return a ResponseInterface object which will then be passed up the line and returned to the router.
        return $action->handle($request, function($request) use ($chain) {
            return $this->callChain($request, $chain);
        });
    }

    /**
     * Sets a logger instance on the object.
     *
     * @param LoggerInterface $logger
     *
     * @return void
     */
    public function setLogger(LoggerInterface $logger) {
        $this->logger = $logger;
    }

}
