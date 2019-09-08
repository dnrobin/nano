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

class Route
{  
  const VERSION = '1.4';
  
  /**
   * @var array
   */
  private $_methods;

  /**
   * @var array
   */
  private $_regex;

  /**
   * @var array
   */
  private $_options;

  /**
   * @var array
   */
  private $_params;

  /**
   * Try to resolve the request
   * @param Http\Request
   * @return bool
   */
  public function resolves(Request $request)
  {
    // match HTTP method
		if (array_search('any', $this->_methods) === false) {
			if (array_search(strtolower($request->getMethod()), $this->_methods) === false)
				return false;
		}
		
		// match the path
		if ($this->_regex != '/*') {
			$path = $request->getPath();
			if (strlen($path) > 1 && $path[strlen($path)-1] == '/') {
				$path = substr($path, 0, strlen($path) - 1);
			}

      $params = [];
			if (!preg_match('~^'.$this->_regex.'$~', $path, $params)) {
				return false;
			}
		}
		
    // apply header filters
    if (in_array('headers', $this->_options)) {

      $headers = $this->_options['headers'];
      if (!is_array($headers))
        $headers = [$headers];
      
      foreach ($headers as $name => $value) {
        if (!preg_match("~$value~", $request->getHeader(ucwords($name)))) {
          return false;
        }
      }
    }

		// set uri parameter values
		foreach (array_keys($this->_params) as $key => $name) {
			if (@$params[$key + 1]) {
				$this->_params[$name] = $params[$key + 1];
			}
    }
    
    return true;
  }

  /**
   * Getters
   */
  public function getOptions()
  {
    return $this->_options;
  }

  public function getParams()
  {
    return $this->_params;
  }

  /**
   * Ctor
   */
  function __construct(array $methods, string $uri, array $options, $params = [])
  {
    $this->_methods = $methods;
    $this->_options = $options;

    // create route uri regular expression
		$this->_regex = '/'.preg_replace(
			[
				'~^\/+|\/+$~',
				'~\/~',
				'~:\w+(\{.+?\})~',
				'~\[(.+?)\]~',
				'~:\w+~',
				'~\{(.+?)\}~'
			],
			[
				'',
				'\\/',
				'($1)',
				'(?|$1)?',
				'([^\/]+)',
				'$1'
			],
    trim($uri));

    // create all params from placeholders
    $_params = [];
		if (preg_match_all('~:(\w+)~', $uri, $matches)) {
      // fill values with 'null' by default
			$_params = array_combine(array_values($matches[1]), array_fill(0, count($matches[1]), null));
		}

		// change 'null' to provided defaults
		$this->_params = array_merge($_params, $params);
  }
}