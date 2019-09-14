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

/**
 * The view helper class is a stateful context driven template parser
 * able to reduce a hierarchy of views to a single html output. The 
 * template syntax offers many runtime options via pipes.
 */

class View implements \ArrayAccess
{
  /**
   * Global context
   * 
   * @var array
   */
  protected static $global = [];

  /**
   * Local context
   * 
   * @var array
   */
  protected $local;

  /**
   * View template content
   * 
   * @var string
   */
  protected $content = '';

  /**
   * Set global context variable
   */
  public static function global($name, $value)
  {
    self::$global[$name] = $value;
  }

  /**
   * Set local context variable
   */
  public function set(string $name, $value)
  {
    $this->local[$name] = $value;
  }

  /**
   * Get local context variable
   */
  public function get(string $name)
  {
    if (isset($this->local[$name]))
      return $this->local[$name];

    if (isset(self::$global[$name]))
      return self::$global[$name];

    return null;
  }

  /**
   * Return rendered representation of view
   * 
   * @return mixed
   */
  public function reduce()
  {
    $parser = new Parser();

    return $parser->parse($this);
  }

  /**
   * accessors
   */
  public function __isset($name)
  {
    return (isset($this->local[$name]) 
      || isset(self::$global[$name]));
  }

  public function __get($name)
  {
    return $this->get($name);
  }

  public function __set($name, $value)
  {
    $this->set($name, $value);
  }
  
  public function __toString()
  {
    return $this->reduce();
  }

  public function offsetExists($name)
  {
    return (isset($this->local[$name]) 
      || isset(self::$global[$name]));
  }

  public function offsetGet($name)
  {
    return $this->get($name);
  }

  public function offsetSet($name, $value)
  {
    $this->set($name, $value);
  }

  public function offsetUnset($name)
  {
    if (isset($this->local[$name]))
      unset($this->local[$name]);

    if (isset(self::$global[$name]))
      unset(self::$global[$name]);
  }

  /**
   * Construct from constituents
   */
  public function __construct($content = '', $context = [])
  {
    $this->content = $content;
    $this->local = $context;

    if (is_object($context))
      $this->local = object_to_array($context);
  }
}