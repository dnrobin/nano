<?php
/**
 * nano - a lazy server api framework
 * 
 * @author    Daniel Robin <danrobin113@github.com>
 * @version   1.4
 * 
 * last-update 09-2019
 */

namespace nano\Routing;

use nano\Http\Request as Request;
use nano\Http\Response as Response;

class ContextContainer { /* used as value object */ }

class RouteStrategy
{
  /**
   * @var \Closure
   */
  private $closure;

  /**
   * @var array
   */
  private $arguments;

  /**
   * Execute strategy
   * 
   * @return mixed
   */
  public function execute()
  {
    return ($this->closure)(...($this->arguments));
  }

  /**
   * Construct route strategy
   *
   * @param nano\Routing\Route
   * @param nano\Http\Request
   * @param nano\Http\Response
   * @return void
   */
  public function __construct(Route $route, Request $request, Response $response)
  {
    $arguments = $route->getArguments();

    $handler = $route->getHandler();

    if (!is_callable($handler)) {
      if (!is_array($handler))
        error("Invalid handler supplied for route");
      
      $handler = @$handler['handler'];
    }

    $newthis = new ContextContainer();
    $newthis->request = $request;
    $newthis->response = $response;
    $newthis->argc = count($arguments);
    $newthis->argv = $arguments;

    $this->closure = \Closure::bind(\Closure::fromCallable($handler), $newthis);

    $this->arguments = array_values($arguments);
  }
}