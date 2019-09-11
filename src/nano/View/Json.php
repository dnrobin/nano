<?php
/**
 * nano - a tiny server api framework
 * 
 * @author    Daniel Robin <danrobin113@github.com>
 * @version   1.4
 * 
 * last-update 09-2019
 */

namespace nano\View;

class Json implements \ArrayAccess
{
  /**
   * @var array
   */
  private $data;

  /**
   * Set content
   */
  public function set($json)
  {
    if (is_array($json)){
      $this->data = $json;
    }

    else if (is_object($json)) {
      $this->data = (array)$json;
    }

    else if (is_string($json)) {
      $this->data = json_decode($json, true);
    }

    else {
      $this->data = [$json];
    }
  }

  /**
   * Load from JSON file
   */
  public function load($filename)
  {
    $this->set(file_get_contents($filename, true));
  }

  /**
   * ArrayAccess
   */
  public function offsetExists ($offset)
  {
    return isset($this->data[$offset]);
  }

  public function offsetGet ($offset)
  {
    return new json(@$this->data[$offset]);
  }

  public function offsetSet ($offset, $value)
  {
    $this->data[$offset] = $value;
  }

  public function offsetUnset ($offset)
  {
    unset($this->data[$offset]);
  }

  /**
   * Construct from arbitrary
   */
  function __construct($json = null)
  {
    if ($json)
      $this->set($json);
  }

  /**
   * Accessors
   */
  function __get($name)
  {
    return $this[$name];
  }

  function __set($name, $value)
  {
    $this[$name] = $value;
  }

  function __toString()
  {
    return json_encode($this->data, JSON_PRETTY_PRINT);
  }

  function toArray()
  {
    return $this->data;
  }

  /**
   * Construct from file content
   */
  public static function fromFile($filename)
  {
    $j = new Json();
    $j->load($filename);
    return $j;
  }
}