<?php
/**
 * nano - lazy server app framework
 *
 * @author	  Daniel Robin <daniel.robin.1@ulaval.ca>
 * @version		1.4
 *
 * last updated: 09-2019
 */

namespace nano\Routing;

use nano\Http\Request as Request;

class Router
{
  const VERSION = '1.4';
  
  /**
   * @var Route[]
   */
  private $_routes;

  /**
   * Create new route
   * 
   * @param array, HTTP method names, ex. ['GET', 'PUT']
   * @param string, uri expression, ex. '/path/to/:resource[/:id]' or '/*' to match everything
   * @param array, route options (see Route)
   * @param array, route parameters hash (default values and more)
   */
  public function route(array $methods, string $uri, array $options, $params = [])
  {
    // make sure methods are lowercase
    array_walk($methods, function(&$v) { $v = strtolower($v); });

    $this->_routes[] = new Route($methods, $uri, $options, $params);
  }

  /**
   * Try to resolve request
   * 
   * @param Request
   * @return Routing\Strategy
   */
  public function resolves(Request $request)
  {
    foreach ($this->_routes as $route)
    {
      if ($route->resolves($request))
      {
        return $route;
      }
    }
    
    return null;
  }
}