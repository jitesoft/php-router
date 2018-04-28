<?php
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
  RouteGroupInterface.php - Part of the php-router project.

  © - Jitesoft 2018
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
namespace Jitesoft\Router\Contracts;

/**
 * RouteGroupInterface
 *
 * Interface for route groups, a group is a url pattern under which multiple actions (or more groups) can be added.
 *
 * @author Johannes Tegnér <johannes@jitesoft.com>
 * @version 1.0.0
 */
interface RouteGroupInterface {

    public function action(string $method, $pattern, $handler, $middleWares = []): self;

    /**
     * Get the route group pattern. The pattern will be prepended to each route action
     * in the group.
     *
     * @return string
     */
    public function getPattern(): string;

    /**
     * Get the middle wares that all the actions in the group should use.
     *
     * @return array|MiddlewareInterface
     */
    public function getMiddleWares() : array;

    /**
     * Get all actions in the group.
     *
     * @param string|null $method Set to a method to retrieve only actions with a given method.
     * @return array|RouteActionInterface[]
     */
    public function getActions(string $method = null): array;

    /**
     * Get all subgroups of the group.
     *
     * @return array|RouteGroupInterface[]
     */
    public function getGroups(): array;

    /**
     * Namespace for the group.
     *
     * @return string
     */
    public function getNamespace(): string;

}
