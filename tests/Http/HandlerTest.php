<?php
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
  HandlerTest.php - Part of the php-router project.

  © - Jitesoft 2018
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
namespace Jitesoft\Router\Tests\Http;

use function is_callable;
use Jitesoft\Router\Contracts\MiddlewareInterface;
use Jitesoft\Router\Http\Action;
use Jitesoft\Router\Http\Method;
use Jitesoft\Router\Tests\AbstractTestCase;

/**
 * HandlerTest
 * @author Johannes Tegnér <johannes@jitesoft.com>
 * @version 1.0.0
 */
class HandlerTest extends AbstractTestCase {

    public function testGetMethod() {
        $handler = new Action(Method::GET, '', function() {}, []);
        $this->assertEquals('get', $handler->getMethod());
        $handler = new Action(Method::POST, '', function() {}, []);
        $this->assertEquals('post', $handler->getMethod());
    }

    public function testGetMiddleware() {
        $handler = new Action(Method::POST, '', function() {}, []);
        $this->assertEmpty($handler->getMiddleWares());

        $handler = new Action(Method::POST, '', function() {}, [
            function($request, $next) {
                return 'middleware';
            },
            function ($request, $next) {
                return 'middleware2';
            }
        ]);

        $this->assertCount(2, $handler->getMiddleWares());
        $this->assertInstanceOf(MiddlewareInterface::class, $handler->getMiddleWares()[0]);
    }

    public function testGetClassAndFunction() {
        $handler = new Action(Method::POST, '', 'Controller@action', []);
        $this->assertEquals('Controller', $handler->getClass());
        $this->assertEquals('action', $handler->getFunction());
    }

    public function testGetCallback() {
        $handler = new Action(Method::POST, '', function () {
            return 'abc';
        });

        $this->assertTrue(is_callable($handler->getCallback()));
    }

    public function testGetPattern() {
        $handler = new Action(Method::POST, 'a/b/c/{d}', function() {}, []);
        $this->assertEquals('a/b/c/{d}', $handler->getPattern());
    }

}
