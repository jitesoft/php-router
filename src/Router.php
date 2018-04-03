<?php
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
  Router.php - Part of the php-router project.

  © - Jitesoft 2018
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
namespace Jitesoft\Router;

use Exception;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Jitesoft\Container\Container;
use Jitesoft\Container\Injector;
use Jitesoft\Exceptions\Http\Client\HttpMethodNotAllowedException;
use Jitesoft\Exceptions\Http\Client\HttpNotFoundException;
use Jitesoft\Exceptions\Http\Server\HttpInternalServerErrorException;
use Jitesoft\Exceptions\Psr\Container\ContainerException;
use Jitesoft\Router\Contracts\MiddlewareInterface;
use Jitesoft\Router\Contracts\RouteHandlerInterface;
use Jitesoft\Router\Http\Handler;
use Jitesoft\Router\Http\Method;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use ReflectionClass;
use ReflectionException;
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
 * proper structure to easily and quickly fetch the desired request route.
 *
 * @codingStandardsIgnoreStart
 * @method Router get(string $pattern, string|callable $handler, array $middleWares = []) Creates a handler for http GET method.
 * @method Router head(string $pattern, string|callable $handler, array $middleWares = []) Creates a handler for http HEAD method.
 * @method Router post(string $pattern, string|callable $handler, array $middleWares = []) Creates a handler for http POST method.
 * @method Router put(string $pattern, string|callable $handler, array $middleWares = []) Creates a handler for http PUT method.
 * @method Router delete(string $pattern, string|callable $handler, array $middleWares = []) Creates a handler for http DELETE method.
 * @method Router connect(string $pattern, string|callable $handler, array $middleWares = []) Creates a handler for http CONNECT method.
 * @method Router options(string $pattern, string|callable $handler, array $middleWares = []) Creates a handler for http OPTIONS method.
 * @method Router trace(string $pattern, string|callable $handler, array $middleWares = []) Creates a handler for http TRACE method.
 * @method Router patch(string $pattern, string|callable $handler, array $middleWares = []) Creates a handler for http PATCH method.
 * @codingStandardsIgnoreStop
 *
 * @see Method for information about the HTTP methods.
 */
class Router implements LoggerAwareInterface {
    // @todo Implement route groups to stack multiple actions under one namespace.

    private $logger;
    /** @var ContainerInterface */
    private $container;
    /** @var array|RouteHandlerInterface[][] */
    private $actions = [];

    private const LOG_TAG = 'Router';

    /**
     * Router constructor.
     * @param ContainerInterface|null $container
     * @throws ContainerExceptionInterface
     */
    public function __construct(ContainerInterface $container = null) {
        if ($container === null) {
            $container = new Container();
        }

        $this->container = $container;

        if ($this->container->has(LoggerInterface::class)) {
            $this->logger = $this->container->get(LoggerInterface::class);
        } else {
            $this->logger = new NullLogger();
        }
    }

    public function __call($name, $arguments): self {
        return $this->action($name, ...$arguments);
    }

    /**
     * Get the endpoints as an array.
     */
    public function getEndpoints() {
        $out = [];
        foreach ($this->actions as $method => $actions) {
            foreach ($actions as $action) {
                $out[] = [
                    'endpoint' => $action->getPattern(),
                    'method' => $method
                ];
            }
        }
        return $out;
    }

    /**
     * Create a handler for a given http method.
     * It's preferable to use one of the http-method calls instead of this one.
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
     *
     * @throws ContainerExceptionInterface
     */
    public function registerMiddleWares(array $middleWares = []): self {
        foreach ($middleWares as $mw) {
            if (!$this->container->has($mw)) {
                if ($this->container instanceof Container) {
                    $this->container->set($mw, $mw, true);
                } else {
                    throw new ContainerException('Unknown container type.');
                }
            }
        }

        return $this;
    }

    /**
     * @param RequestInterface|null $request
     * @return ResponseInterface
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws HttpInternalServerErrorException
     * @throws HttpMethodNotAllowedException
     * @throws HttpNotFoundException
     */
    public function handle(RequestInterface $request = null): ResponseInterface {
        $request = $request ?? ServerRequestFactory::fromGlobals();

        $dispatcher = \FastRoute\simpleDispatcher(function (RouteCollector $routeCollector) use ($request) {
            /** @var Handler $action */
            foreach ($this->actions as $method => $actions) {
                foreach ($actions as $id => $action) {
                    $routeCollector->addRoute(strtoupper($method), $action->getPattern(), $id);
                }
            }
        });

        $this->logger->debug('Request method: {method}. Target: {target}', [
            'method' => $request->getMethod(),
            'target' => $request->getRequestTarget(),
            'tag' => self::LOG_TAG
        ]);

        $result = $dispatcher->dispatch($request->getMethod(), $request->getRequestTarget());
        $this->logger->debug('Dispatch complete, result: {result}.', [
            'result' => (['not found', 'found', 'method not allowed', 'unknown'])[$result[0]],
            'tag' => self::LOG_TAG
        ]);

        if ($result[0] === Dispatcher::FOUND) {
            $id   = $result[1];
            $args = $result[2];
            try {
                return $this->handleInvocation($request, $id, $args);
            } catch (ReflectionException $e) {
                throw new HttpInternalServerErrorException('Failed to initialize handler.');
            }
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
     *
     * @throws HttpNotFoundException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    private function handleInvocation(RequestInterface $request, int $id, array $arguments): ResponseInterface {
        // Find the handler.
        /** @var Handler $handler */
        $handler = $this->actions[strtolower($request->getMethod())][$id];
        if (!array_key_exists(strtolower($request->getMethod()), $this->actions)
            ||
            !array_key_exists($id, $this->actions[strtolower($request->getMethod())])) {
            throw new HttpNotFoundException('Route not specified.');
        }

        $actions = array_map(function($middleware) {
            if ($middleware instanceof MiddlewareInterface) {
                $this->logger->debug('{tag}: Middleware was of MiddlewareInterface type.', ['tag' => self::LOG_TAG]);
                return $middleware;
            }

            if (is_string($middleware)) {
                $this->logger->debug('{tag}: Middleware was a string, fetching from container.', ['tag' => self::LOG_TAG]);
                if ($this->container->has($middleware)) {
                    return $this->container->get($middleware);
                }
                $this->logger->error('{tag}: Failed to fetch middleware.', ['tag' => self::LOG_TAG]);
            }

            throw new HttpInternalServerErrorException(
                'Failed to handle request. Middleware was not found available.'
            );

        }, $handler->getMiddleWares());

        $final = null;
        if ($handler->getCallback() !== null) {
            $this->logger->debug('{tag}: Request handler was a callable.', ['tag' => self::LOG_TAG]);
            $callback = $handler->getCallback();
            $final    = function($request) use ($arguments, $callback) {
                return $callback($request, ...array_values($arguments));
            };
        } else {
            $this->logger->debug('{tag}: Request handler was not a callable, fetching from container.', ['tag' => self::LOG_TAG]);
            if ($this->container->has($handler->getClass())) {
                $class  = $this->container->get($handler->getClass());
            } else {
                // Try resolve with the container resolver.
                $resolver = new Injector($this->container);
                try {
                    $class = $resolver->create($handler->getClass());
                } catch (Exception $ex) {
                    $this->logger->notice('Failed to initialize class via injection. Creating without constructor.');
                    $class = (new ReflectionClass($handler->getClass()))->newInstanceWithoutConstructor();
                }
            }

            $method = $handler->getFunction();
            $this->logger->debug('{tag}: Class {exists}', [ 'exists' => ($class === null ? 'Does not exist.' : 'exists'), 'tag' => self::LOG_TAG ]);

            $final = function ($request) use ($arguments, $class, $method) {
                return $class->{$method}($request, ...array_values($arguments));
            };
        }

        $this->logger->debug('{tag}: Total amount of actions: {actions}', [
            'actions' => count($actions) + 1,
            'tag' => self::LOG_TAG
        ]);
        $result = $this->callChain($request, array_reverse($actions), $final);
        $this->logger->debug('{tag}: Call chain complete. Returning result.', ['tag' => self::LOG_TAG]);
        return $result;
    }

    /**
     * @param RequestInterface $request
     * @param array $middlewares
     * @param callable $action
     * @return ResponseInterface
     */
    private function callChain(RequestInterface $request, array $middlewares, callable $action): ResponseInterface {
        /** @var MiddlewareInterface $action */
        if (count($middlewares) === 0) {
            return $action($request);
        }

        $mw = array_pop($middlewares);
        return $mw->handle($request, function($request) use ($middlewares, $action) {
            return $this->callChain($request, $middlewares, $action);
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
