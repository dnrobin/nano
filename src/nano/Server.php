<?php
/**
 * nano - a tiny server api framework
 * 
 * @author    Daniel Robin <danrobin113@github.com>
 * @version   1.4
 * 
 * last-update 09-2019
 */

namespace nano;

require_once __DIR__ . '/Helpers/helpers.php';

class Server
{
  /**
   * Internal request router
   * 
   * @var nano\Routing\Router
   */
  private $router;

  /**
   * Http request object
   * 
   * @var nano\Http\Request
   */
  public static $request;

  /**
   * Http response object
   * 
   * @var nano\Http\Response
   */
  private $response;

  /**
   * Define api routes the server can handle
   * 
   * @param string|string[] $method   HTTP method names, ex. 'GET', 'GET | PUT', ['GET', 'PUT'], or 'ANY'
   * @param string          $pattern  uri pattern with optional placeholders, ex. '/path/to/:resource[/:id]'
   * @param mixed           $handler  handler can be of type callable, Closure or an array with more definitions
   * @param array           $arguments    route arguments passed along (placeholder values, etc...)
   * @return void
   */
  public function serve($method, string $pattern, $handler, $arguments = [])
  {
    $this->router->route($method, $pattern, $handler, $arguments);
  }

  /**
   * Common method aliases
   * 
   * @param string  $pattern
   * @param mixed   $handler
   * @param array   $arguments
   * @return void
   */
  public function any(string $pattern, $handler, $arguments = [])
  {
    $this->serve('any', $pattern, $handler, $arguments);
  }

  public function get(string $pattern, $handler, $arguments = [])
  {
    $this->serve('get', $pattern, $handler, $arguments);
  }

  public function put(string $pattern, $handler, $arguments = [])
  {
    $this->serve('put', $pattern, $handler, $arguments);
  }

  public function post(string $pattern, $handler, $arguments = [])
  {
    $this->serve('post', $pattern, $handler, $arguments);
  }

  public function delete(string $pattern, $handler, $arguments = [])
  {
    $this->serve('delete', $pattern, $handler, $arguments);
  }

  /**
   * Try immediate route (does not defer to run() method)
   * 
   * @param string|string[]   $method
   * @param string            $pattern
   * @param mixed             $handler
   * @param array             $arguments
   * @return void
   */
  public function try($method, string $pattern, $handler, $arguments = [])
  {
    $route = new Routing\Route($method, $pattern, $handler, $arguments);

    if ($route->resolves(self::$request))
    {
      $this->response->setStatus(Http\Response::OK);

      $result = (new Routing\RouteStrategy($route, self::$request, $this->response))->execute();

      if ($result !== null)
        $this->response->set($result);

      $this->response->send();
      
      exit;
    }
  }

  /**
   * Run api server
   * 
   * @return void
   */
  public function run()
  {
    $route = $this->router->resolve(self::$request);

    if ($route)
    {
      $this->response->setStatus(Http\Response::OK);

      $result = (new Routing\RouteStrategy($route, self::$request, $this->response))->execute();

      if ($result !== null)
        $this->response->set($result);
    }

    $this->response->send();
    
    exit;
  }

  /**
   * Construct api server
   * 
   * @param array, optional configuration
   * @return void
   */
  public function __construct($config = [])
  {
    $default = [
      'basepath' => '',
      'app-env' => 'production',
      'content-type' => 'application/json'
    ];

    $config = array_merge($default, $config);

    // get request and response objects
    self::$request = Http\Request::fromContext($_SERVER);
    $this->response = Http\Response::fromRequest(self::$request);

    // set response default status and content type
    $this->response->setStatus(Http\Response::NOT_FOUND);

    if (isset($config['content-type']))
      $this->response->setContentType($config['content-type']);

    // prevent direct output in production mode
    $this->response->captureOutput();

    $this->router = new Routing\Router();

    // set new default path
    set_include_path(realpath($config['basepath']));

    // TODO: find a better way to construct responses based pipeline node events!
    $_ENV['request'] = self::$request;
    $_ENV['response'] = $this->response;
  }

  /**
   * Make sure a default response is sent back in case the server was not run
   */
  function __destruct()
  {
    if (!$this->response->isSent())
      $this->response->send();
  }
}