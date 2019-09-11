<?php
/**
 * nano - a tiny server api framework
 * 
 * @author    Daniel Robin <danrobin113@github.com>
 * @version   1.4
 * 
 * last-update 09-2019
 */

namespace nano\Http;

/**
 * HTTP Request Value Object (Lazy container)
 *
 */
class Request
{
	const PROTOCOL_VERSIONS = ["1.0", "1.1"];

	const CONTENT_TYPES = [
		"application/json",
		"application/xml",
		"text/html",
		"text/xml"
	];

	const HTTP_METHODS = [
		"GET", "PUT", "PATCH", "POST", "DELETE", "OPTIONS", "HEAD"
	];

	/**
	 * @var string, ex. "1.0" or "1.1"
	 */
	protected $version;

	/**
	 * @var string (uppercase), ex. 'GET'
	 */
	protected $method;

	/**
	 * @var Restful\Http\Uri
	 */
	protected $uri;

	/**
	 * @var Restful\Http\Headers
	 */
	protected $headers;

	/**
	 * @var array, deserialized query args
	 */
	protected $query = null;

	/**
	 * @var array
	 */
	protected $cookie = null;

	/**
	 * @var mixed, check headers for content-type if any
	 */
	protected $content = null;

	/**
	 * @var array, server-side request attributes
	 */
	public $attributes = [];

	/**
	 * Lazy accessors
	 */
	public function getVersion()
	{
		if (!$this->version) {
        	$p = explode('/', self::$_SERVER['SERVER_PROTOCOL'], 2);
        	$this->version = $p[1];
        }

        return $this->version;
	}

	public function getMethod()
	{
		if (!$this->method) {
        	$this->method = self::$_SERVER['REQUEST_METHOD'];
        }
        
        return $this->method;
	}

	public function getUri()
	{   
        return $this->uri;
	}

	public function getScheme()
	{
        return $this->uri->getScheme();
	}

	public function getUser()
	{
		return $this->uri->getUser();
	}

	public function getPassword()
	{
		return $this->uri->getPassword();
	}

	public function getUserInfoString()
	{
		return $this->uri->getUserInfoString();
	}

	public function getHost()
	{
		return $this->uri->getHost();
	}

	public function getPort()
	{
		return $this->uri->getPort();
	}

	public function getAuthorityString()
	{
        return $this->uri->getAuthorityString();
	}

	public function getPath()
	{
		return $this->uri->getPath();
	}

	public function getQueryString()
	{
		return $this->uri->getQueryString();
	}

	public function getHeaders()
	{
		return $this->headers;
	}

	public function getHeader($name)
	{
		return $this->headers->get($name);
	}

	public function getQueryArgs()
	{
		if ($this->query === null) {
			if( isset(self::$_SERVER['QUERY_STRING']) ) {
			    parse_str(self::$_SERVER['QUERY_STRING'], $this->query);
			}
		}

		return $this->query;
	}

	public function getCookieArgs()
	{
		if ($this->cookie === null) {
			$this->cookie = $_COOKIE;
		}

		return $this->cookie;
	}

	public function getContent()
	{
		if ($this->content === null) {
			$this->content = file_get_contents("php://input");
		}

		return $this->content;
	}

	/**
	 * Factory to eagerly construct from server vars
	 * @return Restful\Http\Request
	 */
	public static function fromContext(array $server = [])
	{
		$req = new Request;

			if (empty($server)) {
				$server = self::$_SERVER;
			}

			// Protocol version
            $p = explode('/', $server['SERVER_PROTOCOL'], 2);
            $req->version = $p[1];

            // Request method
            $req->method = $server['REQUEST_METHOD'];

            // Deserialized query
            $req->query = [];
            if( isset($server['QUERY_STRING']) ) {
                parse_str($server['QUERY_STRING'], $req->query);
            }

            // Request uri
            $req->uri = Uri::fromContext($server);

            // Request body
            $req->content = file_get_contents("php://input");

            // Request attributes
            $req->attributes = [];

            // Request headers
            $req->headers = Headers::fromContext($server);

		return $req;
  }

	/**
	 * Cache server vars at construct time
	 */
	private static $_SERVER = [];
	function __construct()
	{
		if (empty(self::$_SERVER))
			self::$_SERVER = $_SERVER;
		
		$this->uri = new Uri;
		$this->headers = Headers::fromContext();
	}
}
