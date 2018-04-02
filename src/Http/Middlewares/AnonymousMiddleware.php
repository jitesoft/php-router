<?php
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
  AnonymousMiddleware.php - Part of the php-router project.

  © - Jitesoft 2018
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
namespace Jitesoft\Router\Http\Middlewares;

use Jitesoft\Router\Contracts\MiddlewareInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * AnonymousMiddleware
 * @author Johannes Tegnér <johannes@jitesoft.com>
 * @version 1.0.0
 */
class AnonymousMiddleware implements MiddlewareInterface {

    protected $callable;

    /**
     * AnonymousMiddleware constructor.
     * @param callable $callable
     */
    public function __construct(callable $callable) {
        $this->callable = $callable;
    }

    /**
     * @param RequestInterface $request
     * @param callable $next
     * @return ResponseInterface
     */
    public function handle(RequestInterface $request, callable $next): ResponseInterface {
        return call_user_func($this->callable, $request, $next);
    }

}
