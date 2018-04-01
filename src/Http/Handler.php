<?php
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
  Handler.php - Part of the php-router project.

  © - Jitesoft 2018
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
namespace Jitesoft\Router\Http;

use function explode;
use function is_callable;
use function is_string;
use Jitesoft\Router\Contracts\MiddlewareInterface;
use Jitesoft\Router\Contracts\RouteHandlerInterface;
use Jitesoft\Router\Http\Middlewares\AnonymousMiddleware;
use Jitesoft\Router\Kernel;

/**
 * Handler
 * @author Johannes Tegnér <johannes@jitesoft.com>
 * @version 1.0.0
 */
class Handler implements RouteHandlerInterface {

    protected $method;
    protected $callback;
    protected $function;
    protected $class;
    protected $middlewares;
    protected $pattern;

    /**
     * RouteHandlerInterface constructor.
     * @param string $method
     * @param string $pattern
     * @param string|callable $callback ClassName@functionName
     * @param array $middlewares
     */
    public function __construct(string $method, string $pattern, $callback, array $middlewares = []) {
        $this->method   = $method;
        $this->pattern  = $pattern;
        $this->callback = (is_string($callback) ? null : $callback);
        $this->class    = (is_string($callback) ? explode('@', $callback)[0] : null);
        $this->function = (is_string($callback) ? explode('@', $callback)[1] : null);

        $this->middlewares = [];

        foreach ($middlewares as $middleware) {
            if (is_string($middleware)) {
                $this->middlewares[] = Kernel::middleware($middleware);
            } else if (is_callable($middleware)) {
                $this->middlewares[] = new AnonymousMiddleware($middleware);
            }
        }
    }

    /**
     * Get the request method.
     *
     * @return string
     */
    public function getMethod(): string {
        return $this->method;
    }

    /**
     * Get a list of middlewares to invoke on call.
     *
     * @return array|MiddlewareInterface[]
     */
    public function getMiddlewares(): array {
        return $this->middlewares;
    }

    /**
     * Get the class which will be invoked on call.
     *
     * @return string
     */
    public function getClass(): string {
        return $this->class;
    }

    /**
     * Get the class method/function to be invoked on call.
     *
     * @return string
     */
    public function getFunction(): string {
        return $this->function;
    }

    /**
     * Get invokation callable if one exist.
     *
     * @return callable|null
     */
    public function getCallback(): ?callable {
        return $this->callback;
    }

    /**
     * Get the pattern used by the handler.
     *
     * @return string
     */
    public function getPattern(): string {
        return $this->pattern;
    }
}
