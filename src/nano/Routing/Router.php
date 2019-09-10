<?php
/**
 * nano - a tiny server api framework
 * 
 * @author    Daniel Robin <danrobin113@github.com>
 * @version   1.4
 * 
 * last-update 09-2019
 */

namespace nano\Routing;

use nano\Http\Request as Request;

class Router
{
  /**
   * @var nano\Routing\Route[]
   */
  private $routes = [];
  
  /**
   * Add route
   * 
   * @param string|string[] $method   HTTP method names, ex. 'GET', 'GET | PUT', ['GET', 'PUT'], or 'ANY'
   * @param string          $pattern  uri pattern with optional placeholders, ex. '/path/to/:resource[/:id]'
   * @param mixed           $handler  handler can be of type callable, Closure or an array with more definitions
   * @param array           $arguments    route arguments passed along (placeholder values, etc...)
   * @return void
   */
  public function route($method, string $pattern, $handler, $arguments = [])
  {
    $this->routes[] = new Route($method, $pattern, $handler, $arguments);
  }

  /**
   * Try to resolve request
   * 
   * @param nano\Http\Request
   * @return nano\Http\Route|null
   */
  public function resolve(Request $request)
  {
    foreach ($this->routes as $route)
    {
      if ($route->resolves($request))
        return $route;
    }

    return null;
  }
}