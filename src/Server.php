<?php
/**
 * nano - lazy server app framework
 *
 * @author	  Daniel Robin <daniel.robin.1@ulaval.ca>
 * @version		1.4
 *
 * last updated: 09-2019
 */

namespace nano;

require_once __DIR__ . '/utils/utils.php';

use nano\Http\Request as Request;
use nano\Http\Response as Response;

class Server
{
  const VERSION = '1.4';
  
  /**
   * @var Request
   */
  private $_request;

  /**
   * @var Response
   */
  private $_response;

  /**
   * @var Router
   */
  private $_router;

  /**
   * @var Configuration
   */
  private $_config;

  /**
   * Serve method aliases
   */
  public function any(string $uri, $options, $params = [])
  {
    $this->serve('any', $uri, $options, $params);
  }

  public function get(string $uri, $options, $params = [])
  {
    $this->serve('get', $uri, $options, $params);
  }

  public function post(string $uri, $options, $params = [])
  {
    $this->serve('post', $uri, $options, $params);
  }

  public function put(string $uri, $options, $params = [])
  {
    $this->serve('put', $uri, $options, $params);
  }

  public function patch(string $uri, $options, $params = [])
  {
    $this->serve('patch', $uri, $options, $params);
  }

  public function delete(string $uri, $options, $params = [])
  {
    $this->serve('delete', $uri, $options, $params);
  }

  /**
   * Create internal route server understands
   * 
   * @param, HTTP method names, ex. 'GET', 'GET | PUT', ['GET', 'PUT'], or 'ANY'
   * @param string, uri expression, ex. '/path/to/:resource[/:id]'
   * @param, route options, can be callable or array (see Route)
   * @param array, route parameters hash (default values and more)
   */
  public function serve($methods, string $uri, $options, $params = [])
  {
    $methods = $this->_parseMethods($methods);
    $options = $this->_parseOptions($options);
    
    // Add route
    $this->_router->route($methods, $uri, $options, $params);
  }

  /**
   * Immediately try routing the request
   * Note: by using the 'serve' function, a route table is created and
   * routing is postponed to the 'run' call. This allows no delay.
   */
  public function try($methods, string $uri, $options, $params = [])
  {
    $methods = $this->_parseMethods($methods);
    $options = $this->_parseOptions($options);

    $route = new Routing\Route($methods, $uri, $options, $params);

    if ($route->resolves($this->_request))
    {
      $this->_response->setStatus(Response::OK);
      
      $strategy = new Routing\RouteStrategy($route, new Context(['basedir' => $this->_config->basedir]));
      $strategy->execute($this->_request, $this->_response);

      // send whatever response
      $this->_response->send();

      exit;
    }
  }

  /**
   * Run server on request
   */
  public function run()
  {
    // default response is not found
    $this->_response->setStatus(Response::NOT_FOUND);

    // try to resolve request
    $route = $this->_router->resolves($this->_request);

    if ($route)
    {
      $this->_response->setStatus(Response::OK);

      $strategy = new Routing\Strategy($route, new Context(['basedir' => $this->_config->basedir]));
      $strategy->execute($this->_request, $this->_response);
    }

    // send whatever response
    $this->_response->send();

    exit;
  }

  /**
   * Parse methods to produce array
   */
  private function _parseMethods($methods)
  {
    if (is_string($methods)) {
      if (!preg_match_all('/[A-Za-z]+|\*/', $methods, $matches))
          \user_error("Invalid method name provided for route.");
      
      $methods = array_map(function ($v) {
        if ($v == '*')
          return 'any'; 
        return $v;
      }, $matches[0]);
    }

    if (!is_array($methods))
      \user_error("Invalid argument type supplied for methods.");

    return $methods;
  }

  /**
   * Parse options to produce array
   */
  private function _parseOptions($options)
  {
    if (is_callable($options)) {
      $options = ['handler' => $options];
    }

    if (!is_array($options))
      \user_error("Invalid options supplied for route.");
    
    // validate options
    if (!isset($options['handler']))
      \user_error("Routing options must include a 'handler' directive!");
    
    return $options;
  }

  /**
   * Ctors
   */
  function __construct($mixed)
  {
    // default configuration
    $config = new Configuration();
    $config['basedir'] = './';
    $config['app_mode'] = 'DEVELOPMENT';
    $config['contentType'] = 'application/json';

    $router = null;

    if (is_array($mixed)) {
      $config->load($mixed);
    }

    elseif (is_object($mixed)) {
      $router = $mixed;
    }
    
    if (!$router)
      $router = new Routing\Router();

    // Build request object from global context
    $request = Request::fromContext();

    // Build response object from request (makes compatible)
    $response = Response::fromRequest($request, Response::NOT_FOUND);

    // Override default 
    if ($config['contentType'])
      $response->setContentType($config['contentType']);
    
    // Start buffering output
    $response->captureOutput();

    // Set autoloader for app context
    set_include_path(get_include_path() . $config['basedir']);
    spl_autoload_register(function ($classname) {
      require $classname . '.php';
    });

    // Set environment
    $_ENV['APP_MODE'] = $config['app_mode'];

    // Save configuration
    $this->_config = $config;
    $this->_router = $router;
    $this->_request = $request;
    $this->_response = $response;
  }

  function __destruct() {
		if (!$this->_response->wasSent()) {
			$this->_response->send();
		}
	}
}