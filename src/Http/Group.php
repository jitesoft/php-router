<?php
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
  Group.php - Part of the router project.

  © - Jitesoft 2018
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
namespace Jitesoft\Router\Http;

use Jitesoft\Exceptions\Logic\InvalidArgumentException;
use Jitesoft\Router\Contracts\MiddlewareInterface;
use Jitesoft\Router\Contracts\RouteActionInterface;
use Jitesoft\Router\Contracts\RouteGroupInterface;
use Jitesoft\Utilities\DataStructures\Lists\IndexedList;
use Jitesoft\Utilities\DataStructures\Maps\SimpleMap;


/**
 * Group
 * @author Johannes Tegnér <johannes@jitesoft.com>
 * @version 1.0.0
 *
 * Group class, contains actions and sub-groups.
 *
 * @codingStandardsIgnoreStart
 * @method RouteGroupInterface get(string $pattern, string|callable $handler, array $middleWares = []) Creates a handler for http GET method.
 * @method RouteGroupInterface head(string $pattern, string|callable $handler, array $middleWares = []) Creates a handler for http HEAD method.
 * @method RouteGroupInterface post(string $pattern, string|callable $handler, array $middleWares = []) Creates a handler for http POST method.
 * @method RouteGroupInterface put(string $pattern, string|callable $handler, array $middleWares = []) Creates a handler for http PUT method.
 * @method RouteGroupInterface delete(string $pattern, string|callable $handler, array $middleWares = []) Creates a handler for http DELETE method.
 * @method RouteGroupInterface connect(string $pattern, string|callable $handler, array $middleWares = []) Creates a handler for http CONNECT method.
 * @method RouteGroupInterface options(string $pattern, string|callable $handler, array $middleWares = []) Creates a handler for http OPTIONS method.
 * @method RouteGroupInterface trace(string $pattern, string|callable $handler, array $middleWares = []) Creates a handler for http TRACE method.
 * @method RouteGroupInterface patch(string $pattern, string|callable $handler, array $middleWares = []) Creates a handler for http PATCH method.
 * @codingStandardsIgnoreStop
 *
 * @see Method for information about the HTTP methods.
 */
class Group implements RouteGroupInterface {

    private $middleWares;
    private $pattern;
    private $actions;
    private $namespace;
    private $groups;

    /**
     * Group constructor.
     * @internal
     * @param string $namespace
     * @param string $pattern
     * @param array $middleWares
     */
    public function __construct(string $namespace, string $pattern, array $middleWares = []) {
        $this->namespace   = $namespace;
        $this->pattern     = $pattern;
        $this->middleWares = new IndexedList($middleWares);
        $this->actions     = new SimpleMap();
        $this->groups      = new IndexedList();
    }

    /**
     * Get the route group pattern. The pattern will be prepended to each route action
     * in the group.
     *
     * @return string
     */
    public function getPattern(): string {
        return $this->pattern;
    }

    /**
     * Get the middle wares that all the actions in the group should use.
     *
     * @return array|MiddlewareInterface
     */
    public function getMiddleWares(): array {
        return $this->middleWares->toArray();
    }

    /**
     * Get all actions in the group.
     *
     * @param string|null $method Set to a method to retrieve only actions with a given method.
     * @return array|RouteActionInterface[]
     */
    public function getActions(string $method = null): array {
        if ($method === null) {
            return array_merge(...$this->actions->map(function(IndexedList $list) {
                return $list->toArray();
            }));
        }

        if (!$this->actions->has($method)) {
            return [];
        }
        $this->actions[$method]->toArray();
    }

    /**
     * Get all subgroups of the group.
     *
     * @return array|RouteGroupInterface[]
     */
    public function getGroups(): array {
        return $this->groups->toArray();
    }

    /**
     * Namespace for the group.
     *
     * @return string
     */
    public function getNamespace(): string {
        return $this->namespace;
    }

    public function __call($name, $arguments) {
        $this->action($name, ...$arguments);
    }

    /**
     * @param string $method
     * @param $pattern
     * @param $handler
     * @param array $middleWares
     * @return Group
     * @throws InvalidArgumentException
     */
    public function action(string $method, $pattern, $handler, $middleWares = []): self {
        if (!$this->actions->has($method)) {
            $this->actions->add($method, new IndexedList());
        }
        $this->actions[$method]->add(new Action($method, $pattern, $handler, $middleWares));
        return $this;
    }

}
