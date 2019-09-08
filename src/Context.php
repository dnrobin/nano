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

/**
 * Context encapsulates local definitions and allows accessing
 * variables in object syntax ex. 'object.member.child'. The 
 * data types stored can be any, including objects themselves.
 */

/**
 * Context
 */
class Context implements \ArrayAccess
{
  const VERSION = 1.4;
  
  /**
   * @var array
   */
  private $_context = [];

  /**
   * Set value in context by name
   * 
   * @param string
   * @param mixed
   */
  public function set(string $name, $value)
  {
    if (is_object($value))
      $value = object_to_array($value);

    $parts = explode('.', $name);

    do
    {
      $name = array_pop($parts);
      $value = [$name => $value];
    } while (count($parts) > 0);

    $this->_context = array_merge($this->_context, $value);
  }

  /**
   * Get value in context by name
   * 
   * @param string
   * @return mixed
   */
  public function get(string $name)
  {
    $value = $this->_context;
    $parts = array_reverse(explode('.', $name));

    do
    {
      $name = array_pop($parts);
      if (!isset($value[$name]))
        return null;
      $value = $value[$name];
    } while (count($parts) > 0);

    if (is_array($value))
      return new Context($value);
    
    return $value;
  }

  /**
   * Create context from array or object
   */
  function __construct($context = [])
  {
    // allow copy construction
    if ($context instanceof Context)
    {
      $this->_context = $context->_context;
      return;
    }

    if (is_object($context))
      $context = object_to_array($context);

    if (!is_array($context))
      user_error("Cannot create context from value!", E_USER_ERROR);
    
    $this->_context = $context;
  }

  /**
   * ArrayAccess
   */
  public function offsetExists($name)
  {
    return isset($this->_context[$name]);
  }

  public function offsetSet($name, $value)
  {
    $this->set($name, $value);
  }

  public function offsetUnset($name)
  {
    unset($this->_context[$name]);
  }

  public function offsetGet($name)
  {
    return $this->get($name);
  }

  /**
   * Object accessors
   */
  function __get($name)
  {
    return $this->get($name);
  }

  function __set($name, $value)
  {
    return $this->set($name, $value);
  }

  /**
   * Format context for printing
   */
  function __toString()
  {
    return print_r($this->_context, true);
  }
}
