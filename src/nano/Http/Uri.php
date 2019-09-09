<?php
/**
 * nano - a lazy server api framework
 * 
 * @author    Daniel Robin <danrobin113@github.com>
 * @version   1.4
 * 
 * last-update 09-2019
 */

namespace nano\Http;

class Uri
{
	/**
	 * @var string
	 */
	protected $scheme;

	/**
	 * @var string
	 */
	protected $user = null;

	/**
	 * @var string
	 */
	protected $password = null;

	/**
	 * @var string
	 */
	protected $host;

	/**
	 * @var string
	 */
	protected $port = null;

	/**
	 * @var string
	 */
	protected $path = null;

	/**
	 * @var string
	 */
	protected $queryString = null;

	/**
	 * Lazy accessors
	 */
	public function getScheme()
	{
		if (!$this->scheme) {
			$is_ssl = (!empty(self::$_SERVER['HTTPS']) && self::$_SERVER['HTTPS'] == 'on');
			$sp  = strtolower(self::$_SERVER['SERVER_PROTOCOL']);
			$this->scheme = substr($sp, 0, strpos($sp, '/')) . (($is_ssl) ? 's' : '');
		}

		return $this->scheme;
	}

	public function getUser()
	{
		if ($this->user === null) {
			$this->user = @self::$_SERVER['PHP_AUTH_USER'] ?: '';
		}

		return $this->user;
	}

	public function getPassword()
	{
		if ($this->password === null) {
			$this->password = @self::$_SERVER['PHP_AUTH_PW'] ?: null;
		}

		return $this->password;
	}

	public function getUserInfoString()
	{
		$user = '';
		if ($user = $this->getUser()) {
			if ($pass = $this->getPassword())
				$user .= ':'.$this->password;
		}

		return $user;
	}

	public function getHost()
	{
		if (!$this->host) {
			$host = isset(self::$_SERVER['HTTP_X_FORWARDED_HOST']) ? self::$_SERVER['HTTP_X_FORWARDED_HOST']
			    : ( isset(self::$_SERVER['HTTP_HOST']) ? self::$_SERVER['HTTP_HOST'] : null );
			$this->host = $host ?: self::$_SERVER['SERVER_NAME'];
		}

		return $this->host;
	}

	public function getPort()
	{
		if ($this->port === null) {
			$port = @self::$_SERVER['SERVER_PORT'] ?: '';
			$has_port = $port ? ((!$is_ssl && $port!='80' ) || ( $is_ssl && $port!='443' )) : false;
			if ($has_port) {
			    $this->port = $port;
			}
		}

		return $this->port;
	}

	public function getAuthorityString()
	{
		$auth = '';
		if ($host = $this->getHost()) {
			if ($user = $this->getUserInfoString()) {
				$auth .= $user.'@';
			}
			$auth .= $host;
			if ($port = $this->getPort()) {
				$auth .= ':'.$port;
			}
		}

		return $auth;
	}

	public function getPath()
	{
		if ($this->path === null) {
			$this->path = preg_replace('/(\?|#).*$/', '', self::$_SERVER['REQUEST_URI']);
		}

		return $this->path;
	}

	public function getQueryString()
	{
		if ($this->queryString === null) {
			if (isset(self::$_SERVER['QUERY_STRING']) && self::$_SERVER['QUERY_STRING']!='') {
			    $this->queryString = self::$_SERVER['QUERY_STRING'] ?: '';
			}
		}

		return $this->queryString;
	}

	/**
	 * Factory to eagerly construct from server vars
	 * @return nano\Http\Uri
	 */
	static public function fromContext(array $server = [])
	{
		$uri = new Uri;

			if (empty($server)) {
				$server = self::$_SERVER;
			}

			// Resolve the scheme
			$is_ssl = (!empty($server['HTTPS']) && $server['HTTPS'] == 'on');
			$sp  = strtolower($server['SERVER_PROTOCOL']);
			$uri->scheme = substr($sp, 0, strpos($sp, '/')) . (($is_ssl) ? 's' : '');

			// Resolve user info
			$uri->user = @$server['PHP_AUTH_USER'] ?: '';
			$uri->password = @$server['PHP_AUTH_PW'] ?: null;

			// Resolve the host
			$host = isset($server['HTTP_X_FORWARDED_HOST']) ? $server['HTTP_X_FORWARDED_HOST']
			    : ( isset($server['HTTP_HOST']) ? $server['HTTP_HOST'] : null );
			    // remove extraneous port info
				$host = preg_replace('/:\d+/', '', $host);
			$uri->host = $host ?: $server['SERVER_NAME'];

			// Resolve the port
			$port = @$server['SERVER_PORT'] ?: '';
			$has_port = $port ? ((!$is_ssl && $port!='80' ) || ( $is_ssl && $port!='443' )) : false;
			if ($has_port) {
			    $uri->port = $port;
			}

			// Resolve the path
			$uri->path = preg_replace('/(\?|#).*$/', '', $server['REQUEST_URI']);

			// Resolve the query
			if (isset($server['QUERY_STRING']) && $server['QUERY_STRING']!='') {
			    $uri->queryString = @$server['QUERY_STRING'] ?: '';
			}

		return $uri;
	}

	/**
	 * Stringify URI components
	 */
	public function __toString()
	{
		$uri = '';

		if ($this->scheme) {
			$uri .= $this->scheme.':';
		}

		$user = '';
		if ($this->user) {
			$user .= $this->user;
			if ($this->password)
				$user .= ':'.$this->password;
		}
		
		$authority = '';
		if ($this->host) {
			if ($user) {
				$authority .= $user.'@';
			}
			$authority .= $this->host;
			if ($this->port) {
				$authority .= ':'.$this->port;
			}
		}

		if ($authority) {
			$uri .= '//'.$authority;
		}

		if ($this->path) {
			$path = $this->path;
			while ($path[0] == '/') {
				$path = substr($path, 1);
			}
			$uri .= '/'.$path;
		}

		if ($this->queryString) {
			$uri .= '?'.$this->queryString;
		}

		return $uri;
	}

	/**
	 * Cache server vars at construct time
	 */
	private static $_SERVER = [];
	function __construct()
	{
		if (empty(self::$_SERVER))
			self::$_SERVER = $_SERVER;
	}
}
