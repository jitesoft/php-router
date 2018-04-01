<?php
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
  RouteHandlerInterface.php - Part of the php-router project.

  © - Jitesoft 2018
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
namespace Jitesoft\Router\Contracts;

/**
 * RouteHandlerInterface
 * @author Johannes Tegnér <johannes@jitesoft.com>
 * @version 1.0.0
 */
interface RouteHandlerInterface {

    /**
     * RouteHandlerInterface constructor.
     * @param string $method
     * @param string $pattern
     * @param string|callable $callback ClassName@functionName
     * @param array $middlewares
     */
    public function __construct(string $method, string $pattern, $callback, array $middlewares = []);

    /**
     * Get the request method.
     *
     * @return string
     */
    public function getMethod(): string;

    /**
     * Get a list of middlewares to invoke on call.
     *
     * @return array|MiddlewareInterface[]
     */
    public function getMiddlewares(): array;

    /**
     * Get the class which will be invoked on call.
     *
     * @return string
     */
    public function getClass(): string;

    /**
     * Get the class method/function to be invoked on call.
     *
     * @return string
     */
    public function getFunction(): string;

    /**
     * Get invokation callable if one exist.
     *
     * @return callable|null
     */
    public function getCallback(): ?callable;

    /**
     * Get the pattern used by the handler.
     *
     * @return string
     */
    public function getPattern(): string;

}
