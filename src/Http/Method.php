<?php
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
  Method.php - Part of the php-router project.

  © - Jitesoft 2018
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
namespace Jitesoft\Router\Http;

/**
 * Method
 * @author Johannes Tegnér <johannes@jitesoft.com>
 * @version 1.0.0
 *
 * The Method class consists of a set of HTTP method constants.
 * The constant descriptions are acquired from https://developer.mozilla.org/en-US/docs/Web/HTTP/Methods
 * Observe that not all HTTP request methods are supported on all web servers.
 */
final class Method {

    /** The GET method requests a representation of the specified resource.
     *  Requests using GET should only retrieve data. */
    public const GET = 'get';
    /** The HEAD method asks for a response identical to that of a GET request, but without the response body. */
    public const HEAD = 'head';
    /** The POST method is used to submit an entity to the specified resource,
     *  often causing a change in state or side effects on the server */
    public const POST = 'post';
    /** The PUT method replaces all current representations of the target resource with the request payload. */
    public const PUT = 'put';
    /** The DELETE method deletes the specified resource. */
    public const DELETE = 'delete';
    /** The CONNECT method establishes a tunnel to the server identified by the target resource. */
    public const CONNECT = 'connect';
    /** The OPTIONS method is used to describe the communication options for the target resource. */
    public const OPTIONS = 'options';
    /** The TRACE method performs a message loop-back test along the path to the target resource. */
    public const TRACE = 'trace';
    /** The PATCH method is used to apply partial modifications to a resource. */
    public const PATCH = 'patch';

    private function __construct() {
        // Not a instantiable class!
    }

}
