<?php
/**
 * nano - lazy server app framework
 *
 * @author	  Daniel Robin <daniel.robin.1@ulaval.ca>
 * @version		1.4
 *
 * last updated: 09-2019
 */

namespace nano\Http;

/**
 * Headers container class
 */
class Headers
{
	/**
	 * @var array
	 */
	protected $headers;

	/**
	 * @param string
	 * @return bool
	 */ 
	public function has($name)
	{
		return array_key_exists($name, $this->headers);
	}

	/**
	 * @param string
	 * @return array
	 */ 
	public function get($name)
	{
		if (array_key_exists($name, $this->headers))
			return $this->headers[$name];

		return null;
	}

	/**
	 * @param string
	 * @param mixed
	 */ 
	public function set($name, $value)
	{
		$this->headers[$name] = $value;
		// immediately set the header object
		header("$name: $value\n\r", true);
	}

	/**
	 * @param string
	 * @param string
	 */ 
	public function add($name, $value)
	{
		// TODO: Allow mutlivalue strings stored as array instead of serialized
		// if (!is_array($value)) {
		// 	if (!preg_match_all('/(\w+:) ([^;]+)+/', $value, $match)) {
		// 		return null;
		// 	}
		//  ...
		// }

		if (array_key_exists($name, $this->headers)) {
			$this->headers[$name] .= $value;
		}
		else {
			$this->headers[$name] = $value;
		}
	}

	/**
	 * @param string
	 */
	public function remove($name)
	{
		if (array_key_exists($name, $this->headers)) {
			header_remove("$name: {$this->headers[$name]}\n\r");
			unset($this->headers[$name]);
		}
	}

	/**
	 * Send headers on server
	 */
	public function send()
	{
		if (headers_sent())
			return;
		
		foreach ($this->headers as $name => $value) {
		    header("$name: $value\n\r", true);
		}
	}

	/**
	 * Factory to eagerly construct from server vars
	 * @return nano\Http\Headers
	 */
	static public function fromContext()
	{
		$headers = new Headers;

		foreach ((array)self::$_HEADERS as $name => $value) {
			$headers->set($name, $value);
		}

		return $headers;
	}

	// Cash headers
	static $_HEADERS = [];
	function __construct()
	{
		if (empty(self::$_HEADERS)) {
			self::$_HEADERS = getallheaders();
		}
	}
}
