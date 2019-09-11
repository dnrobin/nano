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

class ViewFactory
{
  /**
   * Construct a valid view instance
   * 
   * @param mixed
   * @param array
   * @param string
   * @return nano\View\View
   */
  public static function make($whatever, $context = [], $basepath = '/')
  {
    if (is_object($whatever))
      return self::makeFromInstance($whatever, $context, $basepath);
    
    if (is_string($whatever))
    {
      $filename = get_include_path() . $basepath . $whatever;

      if (file_exists($filename))
        return self::makeFromFile($whatever, $context, $basepath);

      if (class_exists($whatever))
        return self::makeFromClass($whatever, $context, $basepath);

      return new View($whatever, $context, $basepath);
    }

    error("Invalid argument to view factory");
  }

  /**
   * Treat input as template filename
   */
  private static function makeFromFile($file, $context = [], $basepath = '/')
  {
    $filename = get_include_path() . $basepath . $file;

    if (!file_exists($filename))
      error("View template file '$file' not found");
    
    $contents = file_get_contents($filename);

    return new View($contents, $context, $basepath . dirname($file));
  }

  /**
   * Treat input as view class name
   */
  private static function makeFromClass($class, $context = [], $basepath = '/')
  {
    if (!class_exists($class))
      error("Unknown view '$class'");
    
    $instance = new $class('', $context, $basepath);

    return self::makeFromInstance($instance);
  }

  /**
   * Treat input as view instance
   */
  private static function makeFromInstance($instance, $context = [], $basepath = '/')
  {
    if (! $instance instanceof View)
      error("Object of type $class must be an instance of View");

    $contents = "";
    
    if (property_exists($instance, 'template'))
    {
      $template = $instance->template;

      if (is_array($template))
      {
        if (!isset($template['file']))
          error("Template must define a source file");
        
        $file = $template['file'];
        $filename = get_include_path() . $basepath . $file;

        if (!file_exists($filename))
          error("View file '$file' not found");
        
        $contents = file_get_contents($filename);

        // TODO: process other options
      }

      else {
        $contents = $template;
      }
    }

    $instance->set($contents);

    if (property_exists($instance, 'data'))
    {
      $data = $instance->data;

      if (!is_array($data))
        error("Wrong format for 'data' in view");

      foreach ($data as $name => $value)
      {
        if (!is_string($name))
          warn("Datum without a name is ignored");
        
        $instance->$name = $value;
      }
    }

    if (property_exists($instance, 'views'))
    {
      $views = $instance->views;

      if (!is_array($views))
        error("Wrond format for 'views' in view");

      foreach ($views as $view)
      {
        $instance->register($view[0], $view[1]);
      }
    }

    return $instance;
  }
}