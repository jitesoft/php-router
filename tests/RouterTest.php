<?php
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
  RouterTest.php - Part of the php-router project.

  © - Jitesoft 2018
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
namespace Jitesoft\Router\Tests;

use Jitesoft\Exceptions\Http\Client\HttpMethodNotAllowedException;
use Jitesoft\Exceptions\Http\Client\HttpNotFoundException;
use Jitesoft\Router\Router;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

/**
 * RouterTest
 * @author Johannes Tegnér <johannes@jitesoft.com>
 * @version 1.0.0
 */
class RouterTest extends AbstractTestCase {

    public function testGet() {
        $r = new Router();
        $r->get('/a/b', function () { }, []);
        $this->assertEquals([[
            'method' => 'get',
            'endpoint' => '/a/b'
        ]], $r->getEndpoints());
    }

    public function testHead() {
        $r = new Router();
        $r->head('/a/b', function () { }, []);
        $this->assertEquals([[
            'method' => 'head',
            'endpoint' => '/a/b'
        ]], $r->getEndpoints());
    }

    public function testPost() {
        $r = new Router();
        $r->post('/a/b', function () { }, []);
        $this->assertEquals([[
            'method' => 'post',
            'endpoint' => '/a/b'
        ]], $r->getEndpoints());
    }

    public function testPut() {
        $r = new Router();
        $r->put('/a/b', function () { }, []);
        $this->assertEquals([[
            'method' => 'put',
            'endpoint' => '/a/b'
        ]], $r->getEndpoints());
    }

    public function testDelete() {
        $r = new Router();
        $r->delete('/a/b', function () { }, []);
        $this->assertEquals([[
            'method' => 'delete',
            'endpoint' => '/a/b'
        ]], $r->getEndpoints());
    }

    public function testConnect() {
        $r = new Router();
        $r->connect('/a/b', function () { }, []);
        $this->assertEquals([[
            'method' => 'connect',
            'endpoint' => '/a/b'
        ]], $r->getEndpoints());
    }

    public function testOptions() {
        $r = new Router();
        $r->options('/a/b', function () { }, []);
        $this->assertEquals([[
            'method' => 'options',
            'endpoint' => '/a/b'
        ]], $r->getEndpoints());
    }

    public function testTrace() {
        $r = new Router();
        $r->trace('/a/b', function () { }, []);
        $this->assertEquals([[
            'method' => 'trace',
            'endpoint' => '/a/b'
        ]], $r->getEndpoints());
    }

    public function testPatch() {
        $r = new Router();
        $r->patch('/a/b', function () { }, []);
        $this->assertEquals([[
            'method' => 'patch',
            'endpoint' => '/a/b'
        ]], $r->getEndpoints());
    }

    public function testHandleWithCallback() {
        $r = new Router();
        $isCalled = false;
        $r->get('/a/b', function() use(&$isCalled) {
            $isCalled = true;
            return new JsonResponse([]);
        });

        $result = $r->handle(new ServerRequest([], [], '/a/b', 'GET'));
        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertTrue($isCalled);
    }

    public function testHandleWithClass() {
        $r = new Router();
        TestController::$isCalled = false;
        $r->get('/a/b', TestController::class . '@handle');
        $result = $r->handle(new ServerRequest([], [], '/a/b', 'GET'));
        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertTrue(TestController::$isCalled);
    }

    public function testHandleNotFound() {
        $r = new Router();
        $this->expectException(HttpNotFoundException::class);
        $r->handle(new ServerRequest([], [], '/a/b', 'POST'));
    }

    public function testHandleMethodNotAllowed() {
        $r = new Router();
        $r->get('/a/b', function() {}, []);

        $this->expectException(HttpMethodNotAllowedException::class);
        $r->handle(new ServerRequest([], [], '/a/b', 'POST'));
    }

}

class TestController {
    public static $isCalled = false;

    public function handle() {
        self::$isCalled = true;
        return new JsonResponse([]);
    }
}
