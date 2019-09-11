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
    $file = array_pop(explode('.', $name));
		$namespace = preg_replace('/\.\w+$/','',$namespace . '.' . $name);
		$filename = str_replace('.','/',$namespace) . '/' . $file . '.html';

    if (!file_exists($filename))
      error("view file '$filename' not found");

    $contents = file_get_contents(get_include_path() . '/' . $filename);

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
        
        $file = str_replace('.','/',$namespace) . '/' . $template['file'];
        $filename = get_include_path() . '/' . $file;

        if (!file_exists($filename))
          error("View file '$file' not found");
        
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

      foreach ($views as $view)
      {
        $object->register($view[0], $view[1]);
      }
    }

    if (method_exists($object, 'created'))
    {
      call_user_func_array([$object, 'created'], $props);
    }

    return $object;
  }
}