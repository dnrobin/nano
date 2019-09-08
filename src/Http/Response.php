<?php
/**
 * nano - lazy server app framework
 *
 * @author      Daniel Robin <daniel.robin.1@ulaval.ca>
 * @version		1.4
 *
 * last updated: 09-2019
 */

namespace nano\Http;

/**
 * HTTP Response Value Object
 */
class Response
{
    const CONTENT_TYPES = [
        "application/json",
        "application/xml",
        "text/html",
        "text/xml"
    ];

    /**
     * @var string, ex. "1.0" or "1.1"
     */
    protected $version;

    /**
     * @var integer
     */
    protected $status;

    /**
     * @var string
     */
    protected $reason;

    /**
     * @var nano\Http\Headers
     */
    protected $headers;

    /**
     * @var string
     */
    protected $contentType;

    /**
     * @var string
     */
    protected $cacheControl;

    /**
     * @var mixed
     */
    protected $body;

    /**
     * @var bool
     */
    private $_sent;


    const HTTP_CONTINUE         = 100;
    const SWITCHING_PROTOCOLS   = 101;
    const PROCESSING            = 102;
    const OK                    = 200;
    const CREATED               = 201;
    const ACCEPTED              = 202;
    const NON_AUTHORITATIVE_INFORMATION = 203;
    const NO_CONTENT            = 204;
    const RESET_CONTENT         = 205;
    const PARTIAL_CONTENT       = 206;
    const MULTI_STATUS          = 207;
    const ALREADY_REPORTED      = 208;
    const IM_USED               = 226;
    const MULTIPLE_CHOICES      = 300;
    const MOVED_PERMANENTLY     = 301;
    const FOUND                 = 302;
    const SEE_OTHER             = 303;
    const NOT_MODIFIED          = 304;
    const USE_PROXY             = 305;
    const TEMPORARY_REDIRECT    = 307;
    const PERMANENT_REDIRECT    = 308;
    const BAD_REQUEST           = 400;
    const UNAUTHORIZED          = 401;
    const PAYMENT_REQUIRED      = 402;
    const FORBIDDEN             = 403;
    const NOT_FOUND             = 404;
    const METHOD_NOT_ALLOWED    = 405;
    const NOT_ACCEPTABLE        = 406;
    const PROXY_AUTHENTICATION_REQUIRED = 407;
    const REQUEST_TIMEOUT       = 408;
    const CONFLICT              = 409;
    const GONE                  = 410;
    const LENGTH_REQUIRED       = 411;
    const PRECONDITION_FAILED   = 412;
    const PAYLOAD_TOO_LARGE     = 413;
    const REQUEST_URI_TOO_LONG  = 414;
    const UNSUPPORTED_MEDIA_TYPE= 415;
    const REQUESTED_RANGE_NOT_SATISFIABLE = 416;
    const EXPECTATION_FAILED    = 417;
    const IM_A_TEAPOT           = 418;
    const MISDIRECTED_REQUEST   = 421;
    const UNPROCESSABLE_ENTITY  = 422;
    const LOCKED                = 423;
    const FAILED_DEPENDENCY     = 424;
    const UPGRADE_REQUIRED      = 426;
    const PRECONDITION_REQUIRED = 428;
    const TOO_MANY_REQUESTS     = 429;
    const REQUEST_HEADER_FIELDS_TOO_LARGE = 431;
    const CONNECTION_CLOSED_WITHOUT_RESPONSE = 444;
    const UNAVAILABLE_FOR_LEGAL_REASONS = 451;
    const CLIENT_CLOSED_REQUEST = 499;
    const INTERNAL_SERVER_ERROR = 500;
    const NOT_IMPLEMENTED       = 501;
    const BAD_GATEWAY           = 502;
    const SERVICE_UNAVAILABLE   = 503;
    const GATEWAY_TIMEOUT       = 504;
    const HTTP_VERSION_NOT_SUPPORTED = 505;
    const VARIANT_ALSO_NEGOTIATES = 506;
    const INSUFFICIENT_STORAGE  = 507;
    const LOOP_DETECTED         = 508;
    const NOT_EXTENDED          = 510;
    const NETWORK_AUTHENTICATION_REQUIRED = 511;
    const NETWORK_CONNECT_TIMEOUT_ERROR = 599;

    /**
     * Standard response code reason phrases
     */
    private $statusReasons = [
        100 => "Continue",
        101 => "Switching Protocols",
        102 => "Processing",
        200 => "OK",
        201 => "Created",
        202 => "Accepted",
        203 => "Non-authoritative Information",
        204 => "No Content",
        205 => "Reset Content",
        206 => "Partial Content",
        207 => "Multi-Status",
        208 => "Already Reported",
        226 => "IM Used",
        300 => "Multiple Choices",
        301 => "Moved Permanently",
        302 => "Found",
        303 => "See Other",
        304 => "Not Modified",
        305 => "Use Proxy",
        307 => "Temporary Redirect",
        308 => "Permanent Redirect",
        400 => "Bad Request",
        401 => "Unauthorized",
        402 => "Payment Required",
        403 => "Forbidden",
        404 => "Not Found",
        405 => "Method Not Allowed",
        406 => "Not Acceptable",
        407 => "Proxy Authentication Required",
        408 => "Request Timeout",
        409 => "Conflict",
        410 => "Gone",
        411 => "Length Required",
        412 => "Precondition Failed",
        413 => "Payload Too Large",
        414 => "Request-URI Too Long",
        415 => "Unsupported Media Type",
        416 => "Requested Range Not Satisfiable",
        417 => "Expectation Failed",
        418 => "I'm a teapot",
        421 => "Misdirected Request",
        422 => "Unprocessable Entity",
        423 => "Locked",
        424 => "Failed Dependency",
        426 => "Upgrade Required",
        428 => "Precondition Required",
        429 => "Too Many Requests",
        431 => "Request Header Fields Too Large",
        444 => "Connection Closed Without Response",
        451 => "Unavailable For Legal Reasons",
        499 => "Client Closed Request",
        500 => "Internal Server Error",
        501 => "Not Implemented",
        502 => "Bad Gateway",
        503 => "Service Unavailable",
        504 => "Gateway Timeout",
        505 => "HTTP Version Not Supported",
        506 => "Variant Also Negotiates",
        507 => "Insufficient Storage",
        508 => "Loop Detected",
        510 => "Not Extended",
        511 => "Network Authentication Required",
        599 => "Network Connect Timeout Error"
    ];

    /**
     * Accessors
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function setStatus($status)
    {
        $this->status = $status;
        $this->reason = $this->statusReasons[$status];
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setContentType($type)
    {
        if (!in_array($type, self::CONTENT_TYPES, true))
            \user_error("Unsuported content type '$type' for response!", E_USER_ERROR);
        
        $this->contentType = $type;
    }

    public function getContentType()
    {
        return $this->contentType;
    }

    public function setHeader($name, $value)
    {
        $this->headers->set($name, $value);
    }

    public function getHeader($name)
    {
        return $this->headers->get($name);
    }

    public function setBody($body)
    {
        $this->body = $body;

        // clear buffer
        ob_clean();
    }

    public function clearBody()
    {
        while (@ob_end_clean());
        $this->body = '';
    }

    public function getBody()
    {
        return $this->body;
    }

    /**
     * House keeping
     */
    public function captureOutput()
    {
        ob_start([$this, 'doPostFiltering']);
    }

    public function send($status = 0)
    {
        if ($this->_sent)
            return;

        if ($status) {
            $this->setStatus($status);
        }

    	$this->sendHeaders();
    	$this->sendBody();

        $this->_sent = true;
    }

    public function wasSent()
    {
        return $this->_sent;
    }

    /**
     * Private methods
     */
    private function sendHeaders()
    {
        if (headers_sent()) {
            die(500);
        }

        header_remove();
        header("HTTP/{$this->version} {$this->status} {$this->reason}\n\r");
        $this->headers->set('Content-Type', $this->contentType);
        $this->headers->set('Connection', 'Closed');
        $this->headers->set('Cache-Control', 'no-cache');
        $this->headers->send();
    }

    // only output if status allows and if body is set
    private function sendBody()
    {
        if ($this->status < 300) {
            if (ob_get_length() > 0) {
                $this->body = ob_get_contents();
                while(ob_end_clean());
            }

            // TODO: CHECK CONTENT TYPE IS RESPECTED!!!!

            // Note: body is not maintained by response!
            //  make sure the content is ready to be sent
            // according to the content-type specified
            echo $this->body;
        }
    }

	/**
	 * Factory to construct from request
	 * @return nano\Http\Request
	 */
	public static function fromRequest(Request $req, $status = 200)
	{
		$res = new Response(null, $status);

        // Match the request protocol version
        $res->setVersion($req->getVersion());

        // Match the request content type
        $contentType = $req->getHeader('Content-Type');
        if (!$contentType) $contentType = 'application/json';
        $res->setContentType($contentType);
        
        return $res;
	}

    /**
     * Output buffering callback function
     * 
     * @param string buffer
     * @return string buffer
     */
    public function doPostFiltering($buffer, $phase)
    {
        // Do nothing for now...
        return $buffer;
    }

    public function __construct($body, $status = 200)
    {
        $this->headers = new Headers;

        $this->setVersion('1.1'); // default version is highest
        $this->setStatus($status);
        $this->setBody($body);
    }
}
