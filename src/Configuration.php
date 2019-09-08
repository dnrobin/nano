<?php
/**
 * nano - lazy server app framework
 *
 * @author	  Daniel Robin <daniel.robin.1@ulaval.ca>
 * @version		1.4
 *
 * last updated: 09-2019
 */

namespace nano;

class Configuration implements \ArrayAccess
{
  /**
   * @var array
   */
  private $_config = [];

  /**
   * Load configuration array
   */
  public function load(array $config)
  {
    $this->_config = array_merge($this->_config, $config);
  }

  /**
   * ArrayAccess
   */
  function offsetExists($offset)
  {
    return isset($this->_config[$offset]);
  }

  function offsetSet($offset, $value)
  {
    $this->_config[$offset] = $value;
  }

  function offsetUnset($offset)
  {
    unset($this->_config[$offset]);
  }

  function offsetGet($offset)
  {
    return @$this->_config[$offset];
  }

  function __get($name)
  {
    if (!isset($this->_config[$name])) {
      return null;
    }

    return $this->_config[$name];
  }

  function __set($name, $value)
  {
    $this->_config[$name] = $value;
  }
}