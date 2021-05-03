<?php
/**
 * nano - a tiny server api framework
 * 
 * @author    Daniel Robin <dnrobin@github.com>
 * @version   1.4
 * 
 * last-update 09-2019
 */

namespace nano\Http;

class Body
{
  /**
   * @var string
   */
  private $contentType;

  /**
   * @var string
   */
  private $content;

  /**
   * ContentType accessors
   * 
   * @param string
   * @return void
   */
  public function setContentType($contentType)
  { 
    $this->contentType = $contentType;
  }

  /**
   * @return string
   */
  public function getContentType()
  {
    return $this->contentType;
  }
  
  /**
   * Content accessors
   * 
   * @param string
   * @return void
   */
  public function set($body)
  {
    $this->content = $body;
  }

  public function clear()
  {
    $this->content = null;
  }

  public function get()
  {
    $body = $this->body;
    
    switch ($this->contentType)
    {
      case "application/json":
        if (is_array($body))
          $body = json_encode($body, JSON_PRETTY_PRINT);
        break;
      
      case "application/xml": // TODO
        break;
      
      case "text/html":
        break;

      case "text/xml": // TODO
        break;
    }

    return $body;
  }

  public function __toString()
  {
    return "" . $this->get();
  }
}