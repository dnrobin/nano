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
  public static function constructFromName($name, $context = [], $namespace = '')
  {
    $full = preg_replace('/^\/+|\/(?=\/+)/', '', $namespace . '/' . $name);

    $filename = get_include_path() . '/' . $full . '.html';
    $namespace = dirname($full) . '/';

    if (!file_exists($filename))
      error("view '$name' not found");

    $contents = file_get_contents($filename);

    return new View($contents, $context, $namespace);
  }

  public static function constructFromObject($object, $props = [], $context = [], $namespace = '')
  {
    if (! $object instanceof View)
      error("view object of type '$object' must be instance of View");

    $contents = "";
    
    if (property_exists($object, 'template'))
    {
      $template = $object->template;

      if (is_array($template))
      {
        if (!isset($template['file']))
          error("Template must define a source file");

        $filename = get_include_path() . '/' . $template['file'];

        if (!file_exists($filename))
          error("view file '{$template['file']}' not found");
        
        $contents = file_get_contents($filename);
      }

      else {
        $contents = $object->template;
      }
    }

    $object->set($contents);

    if (property_exists($object, 'data'))
    {
      $data = $object->data;

      if (!is_array($data))
        error("wrong format for 'data' in view");

      foreach ($data as $name => $value) {
        if (!is_string($name))
          warn("datum without a name is ignored");
        
        $object[$name] = $value;
      }
    }

    if (property_exists($object, 'views'))
    {
      $views = $object->views;

      if (!is_array($views))
        error("wrond format for 'views' in view");

      foreach ($views as $view => $def)
      {
        $object->register($view, $def);
      }
    }

    if (method_exists($object, 'created'))
    {
      call_user_func_array([$object, 'created'], $props);
    }

    return $object;
  }
}