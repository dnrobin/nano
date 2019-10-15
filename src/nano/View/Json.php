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

class Json implements \ArrayAccess, \IteratorAggregate
{
  use Reducible;
  
  /**
   * @var array
   */
  private $data;

  /**
   * Load from file
   */
  public function load($filename)
  {
    if (!file_exists($filename))
      error("json file '$filename' does not exist");

    $this->set(file_get_contents($filename, true));
  }

  /**
   * Save to file
   */
  public function save($filename)
  {
    file_put_contents($filename, $this->__toString());
  }

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
   * Get name in content
   */
  private function get($name)
  {
    if (!isset($this->data[$name]))
      return null;
    
    if (is_array($this->data[$name]))
      return new json($this->data[$name]);

    return $this->data[$name];
  }

  /**
   * Overload accessors
   */
  function __get($name)
  {
    return $this->get($name);
  }

  function __set($name, $value)
  {
    $this->data[$name] = $value;
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
   * ArrayAccess
   */
  public function offsetExists ($name)
  {
    return isset($this->data[$name]);
  }

  public function offsetGet ($name)
  {
    return $this->get($name);
  }

  public function offsetSet ($name, $value)
  {
    $this->data[$name] = $value;
  }

  public function offsetUnset ($name)
  {
    unset($this->data[$name]);
  }

  /**
   * IteratorAggregate
   */
  public function getIterator() {
      return new \ArrayIterator($this->data);
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
   * Construct from file content
   */
  public static function fromFile($filename)
  {
    $j = new Json();
    $j->load($filename);

    return $j;
  }
}