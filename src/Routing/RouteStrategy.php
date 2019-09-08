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
use nano\Http\Response as Response;
use nano\Context as Context;

class RouteStrategy
{
  const VERSION = '1.4';
  
  /**
   * @var Closure
   */
  private $_handler;

  /**
   * @var Context, root execution context
   */
  private $_context;

  /**
   * @var array, route params passed in
   */
  private $_params;

  /**
   * Execute the chain of command to produce a response
   */
  public function execute(Request $request, Response $response)
  {
    // Provide execution context with Request and Response objects
    $this->_context->request = $request;
    $this->_context->response = $response;

    // Grab the closure
    $h = $this->_handler;

    set_include_path($this->_context->basedir);

    // Call the closure in context passing route params
    $return = $h(...array_values($this->_params));

    // If closure returns content, use that instead of output buffer
    if ($return !== null)
    {
      $response->setBody($return);
    }
  }

  /**
   * Ctor
   */
  function __construct(Route $route, Context $context)
  {
    $this->_context = $context;

    $options = $route->getOptions();

    if (is_callable($options['handler'])) {

      $this->_handler = 
        \Closure::bind(\Closure::fromCallable($options['handler']), $this->_context, Context::class);
    }

    elseif (is_array($options['handler'])) {

      // TODO: have own component server strategy
      
      $classname = $options['handler']['component'];
      $methodname = $options['handler']['method'];

      $this->obj = new $classname($this->_context);

      // life-cycle hooks
      $this->obj->create();

      if (!($this->obj instanceof \nano\Component))
        \user_error("Controller class must be instance of nano\Controller!");
      
      $this->_handler = \Closure::fromCallable([$this->obj, $methodname]);
    }

    $this->_params = $route->getParams();
  }
}