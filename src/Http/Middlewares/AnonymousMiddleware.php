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

    public function __construct(callable $callable) {
    }

    public function handle(RequestInterface $request, callable $next): ResponseInterface {
        return $this->callable($request, $next);
    }
}
