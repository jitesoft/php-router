<?php
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
  Handler.php - Part of the php-router project.

  © - Jitesoft 2018
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
namespace Jitesoft\Router\Http;

use Jitesoft\Router\Contracts\MiddlewareInterface;
use Jitesoft\Router\Contracts\RouteHandlerInterface;
use Jitesoft\Router\Http\Middlewares\AnonymousMiddleware;

/**
 * Handler
 *
 * The handler class is a structure to keep the data of a given route action.
 * It keeps the pattern, method, callback and all the middleWares in it.
 *
 * @author Johannes Tegnér <johannes@jitesoft.com>
 * @version 1.0.0
 * @internal
 */
class Handler implements RouteHandlerInterface {

    protected $method;
    protected $callback;
    protected $function;
    protected $class;
    protected $middleWares;
    protected $pattern;

    /**
     * RouteHandlerInterface constructor.
     * @param string $method
     * @param string $pattern
     * @param string|callable $callback ClassName@functionName
     * @param array $middleWares
     */
    public function __construct(string $method, string $pattern, $callback, array $middleWares = []) {
        $this->method   = $method;
        $this->pattern  = $pattern;
        $this->callback = (is_string($callback) ? null : $callback);
        $this->class    = (is_string($callback) ? explode('@', $callback)[0] : null);
        $this->function = (is_string($callback) ? explode('@', $callback)[1] : null);

        $this->middleWares = [];

        foreach ($middleWares as $middleWare) {
            if (is_string($middleWare)) {
                $this->middleWares[] = $middleWare;
            } else if (is_callable($middleWare)) {
                $this->middleWares[] = new AnonymousMiddleware($middleWare);
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
     * Get a list of middle wares to invoke on call.
     *
     * @return array|MiddlewareInterface[]
     */
    public function getMiddleWares(): array {
        return $this->middleWares;
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
     * Get invocation callable if one exist.
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
