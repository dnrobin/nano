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
   * @var nano\Routing\Router[]
   */
  private $groups = [];

  /**
   * @var nano\Routing\Route[]
   */
  private $routes = [];

  /**
   * Router options
   * 
   * @var array
   */
  private $options = [];

  /**
   * Shared attributes for all routes
   * 
   * @var array
   */
  private $attributes = [];

  /**
   * Method aliases
   * 
   * @param string|string[] $method   HTTP method names, ex. 'GET', 'GET | PUT', ['GET', 'PUT'], or 'ANY'
   * @param string          $pattern  uri pattern with optional placeholders, ex. '/path/to/:resource[/:id]'
   * @param mixed           $handler  handler can be of type callable, Closure or an array with more definitions
   * @param array           $arguments    route arguments passed along (placeholder values, etc...)
   * @return void
   */
  public function any(string $pattern, $handler, $arguments = [])
  {
    $this->route('any', $pattern, $handler, $arguments);
  }

  public function get(string $pattern, $handler, $arguments = [])
  {
    $this->route('get', $pattern, $handler, $arguments);
  }

  public function put(string $pattern, $handler, $arguments = [])
  {
    $this->route('put', $pattern, $handler, $arguments);
  }

  public function patch(string $pattern, $handler, $arguments = [])
  {
    $this->route('patch', $pattern, $handler, $arguments);
  }

  public function post(string $pattern, $handler, $arguments = [])
  {
    $this->route('post', $pattern, $handler, $arguments);
  }

  public function delete(string $pattern, $handler, $arguments = [])
  {
    $this->route('delete', $pattern, $handler, $arguments);
  }

  public function options(string $pattern, $handler, $arguments = [])
  {
    $this->route('options', $pattern, $handler, $arguments);
  }

  public function head(string $pattern, $handler, $arguments = [])
  {
    $this->route('head', $pattern, $handler, $arguments);
  }
  
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
    if (isset($this->attributes['prefix']))
      $pattern = $this->attributes['prefix'] . $pattern;

    if (!empty($this->options))
    {
      $handler = [
        'handler' => $handler,
        'options' => $this->options
      ];
    }

    $this->routes[] = new Route($method, $pattern, $handler, $arguments);
  }

  /**
   * Add route group
   * 
   * @param string
   * @param array
   * @param array
   * @return nano\Routing\Router
   */
  public function group($attributes = [], $options = [])
  {
    $router = new Router($attributes, $options);
    $this->groups[] = $router;

    return $router;
  }

  /**
   * Try to resolve request
   * 
   * @param nano\Http\Request
   * @return nano\Http\Route|null
   */
  public function resolve(Request $request)
  {
    // check subroutes first
    foreach ($this->groups as $router)
    {
      $route = $router->resolve($request);

      if ($route)
        return $route;
    }

    // check routes
    foreach ($this->routes as $route)
    {
      if ($route->resolves($request))
        return $route;
    }

    return null;
  }

  /**
   * Construct with shared attributes and options
   */
  public function __construct($attributes = [], $options = [])
  {
    $this->attributes = $attributes;
    $this->options = $options;
  }
}