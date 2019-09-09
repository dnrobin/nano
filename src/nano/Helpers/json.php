<?php
/**
 * nano - a lazy server api framework
 * 
 * @author    Daniel Robin <danrobin113@github.com>
 * @version   1.4
 * 
 * last-update 09-2019
 */

class json implements ArrayAccess
{
  /**
   * @var array
   */
  private $_data;

  /**
   * Set content
   */
  public function set($json)
  {
    if (is_array($json)){
      $this->_data = $json;
    }

    else if (is_object($json)) {
      $this->_data = (array)$json;
    }

    else if (is_string($json)) {
      $this->_data = json_decode($json, true);
    }

    else {
      $this->_data = [$json];
    }
  }

  /**
   * Get JSON string
   */
  public function string()
  {
    return json_encode($this->_data, JSON_PRETTY_PRINT);
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
    return isset($this->_data[$offset]);
  }

  public function offsetGet ($offset)
  {
    return new json(@$this->_data[$offset]);
  }

  public function offsetSet ($offset, $value)
  {
    $this->_data[$offset] = $value;
  }

  public function offsetUnset ($offset)
  {
    unset($this->_data[$offset]);
  }

  function __construct($json = null)
  {
    if ($json)
      $this->set($json);
  }

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
    return $this->string();
  }

  public static function fromFile($filename)
  {
    $j = new json();
    $j->load($filename);
    return $j;
  }
}