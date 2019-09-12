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

// TODO: explain pattern syntax

class Route
{
  /**
   * @var string[]
   */
  private $methods = [];

  /**
   * @var string
   */
  private $pattern;

  /**
   * @var mixed
   */
  private $handler;

  /**
   * @var array
   */
  private $arguments;

  /**
   * @return string[]
   */
  public function getMethods()
  {
    return $this->methods;
  }

  /**
   * @return mixed
   */
  public function getHandler()
  {
    return $this->handler;
  }

  /**
   * @return array
   */
  public function getArguments()
  {
    return $this->arguments;
  }

  /**
   * @param nano\Http\Request
   * @return bool
   */
  public function resolves(Request $request)
  {
    $arguments = [];

    if (!in_array('ANY', $this->methods))
    {
      if (!in_array(upper($request->getMethod()), $this->methods))
        return false;
    }

    if ($this->pattern !== '/*')
    {
      $path = $request->getPath();

      if (strlen($path) > 1 && $path[strlen($path) - 1] == '/') {
        $path = substr($path, 0, strlen($path) - 1);
      }
      
			if (!preg_match("~^{$this->pattern}$~", $path, $arguments)) {
				return false;
      }
      
      array_walk($arguments, 
        function ($v,$k) use (&$arguments) { 
          if(is_numeric($k)) unset($arguments[$k]); 
        });
    }

    if (is_array($this->handler))
    {
      if (in_array('headers', $this->handler)) {
        $headers = $this->handler['headers'];
        if (!is_array($headers))
          $headers = [$headers];
        
        foreach ($headers as $name => $value) {
          if (!preg_match("/$value/", $request->getHeader(ucwords($name)), $match))
            return false;
        }
      }
    }
    
    // grab placeholder values
    $this->arguments = array_merge($this->arguments, $arguments);

    return true;
  }

  /**
   * Construct route object
   * 
   * @param string|string[] $method   HTTP method names, ex. 'GET', 'GET | PUT', ['GET', 'PUT'], or 'ANY'
   * @param string          $pattern  uri pattern with optional placeholders, ex. '/path/to/:resource[/:id]'
   * @param mixed           $handler  handler can be of type callable, Closure or an array with more definitions
   * @param array           $arguments    route arguments passed along (placeholder values, etc...)
   * @return void
   */
  public function __construct($methods, string $pattern, $handler, $arguments = [])
  {
    if (is_array($methods))
    {
      foreach ($methods as $method) {
        if (!in_array(upper($method), ['*', 'ANY', Request::HTTP_METHODS]))
          error("Unrecognized method for route");

        if ($method === 'ANY' || $method === '*') {
          $this->methods = ['ANY'];
          break;
        }

        $this->methods[] = upper($method);
      }
    }

    else {
      if (!is_string($methods))
        error("Invalid argument supplied as method");
      
      $this->methods = [ upper($methods) ];
    }

    // create regular expression from pattern
    $this->pattern = '/'.preg_replace(
      [
        '~^/+|/+$~',
        '~/~',
        '~:(\w+)(\{.+?\})~',
        '~\[(.+?)\]~',
        '~:(\w+)~',
        '~\{(.+?)\}~',
        '~\*$~'
      ],
      [
        '',
        '\\/',
        '(?<$1>$2)',
        '(?|$1)?',
        '(?<$1>[^\/]+)',
        '$1',
        '.*'
      ],
    trim($pattern));
    
    // create placeholder arguments
    $placeholders = [];
    if (preg_match_all('/:(\w+)/', $pattern, $matches)) {
      $placeholders = array_combine(
        array_values($matches[1]), 
        array_fill(null, count($matches[1]), null)
      );
    }

    $this->arguments = array_merge($placeholders, $arguments);
    $this->handler = $handler;
  }
}