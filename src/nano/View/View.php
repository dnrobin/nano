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
 * template syntax offers much runtime control via pipes.
 * 
 * The syntax is as follows:
 * 
 * '{{' expr [ '|' pipe ]+ [ ':' [ index '>' ] var ] '}}' [ body '{{' 'end' '}}' ]
 * 
 * if the expression result is a single value, the body is ignored and
 * value interpolation is performed in-place. if it is list, an arrayable
 * object or a hash, the body is repeated N times with the element as 
 * context. it is also possible to specify variables for the index and
 * context directly using ': i > n' syntax. a block must be closed with '{{ end }}'.
 */

class View
implements \ArrayAccess
{
  /**
   * @var \View|null
   */
  private $parent;

  /**
   * @var array
   */
  private $context;

  /**
   * @var string
   */
  private $content;

  /**
   * Set context variable
   * 
   * @param string
   * @param mixed
   * @return void
   */
  public function set(string $name, $value)
  {
    $this->context[$name] = $value;
  }

  /**
   * Apply reductions to view and produce output
   * 
   * @return string
   */
  public function reduce()
  {
    // TODO: pre/post process?

    return $this->parse();
  }

  /**
   * Parse template syntax in content string
   * 
   * @param 
   * @return string
   */
  const INLOPS = ['\@'];
  const BLKOPS = ['\#'];
  private function parse()
  {
    $inlOps = implode('|', static::INLOPS);
    $blkOps = implode('|', static::BLKOPS);

    // preprocess identify block scopes
    $content = preg_replace_callback("/\{\{\s*(\/)?($blkOps|(\?:)|\?)/", function ($a)
    {
      static $i = 0; return $a[0] . ($a[3] ? $i - 1 : ($a[1] ? --$i : $i++)) . ';';
    }, $this->content);

    return preg_replace_callback(
      "~
        \{\{\s*
          (?:
            (?:
              (?<op>$inlOps|(?<b>$blkOps|(?<i>\?)))
              (?<id>(\d+;)+)
              \s*(?<opexpr>.+?)
            )
            |
            \s*(?<expr>.+?)
          )
        \s*\}\}
        (?(b)\s*
          (?<body>.*?)
          (?(i)
            \{\{\s*\?:\g{id}\s*\}\}
            (?<else>.*?)
          )?
          \{\{\s*/\g{op}\g{id}\s*\}\}
        )
      ~sxJ",
      function ($a)
      {
        extract ($a);

        if ($op)
        {
          if ($op == '?')
          {
            $result = $this->eval($opexpr);

            if ($result)
              return (new View($body, $this->context, $this->parent))->reduce();
            else
              return (new View($else, $this->context, $this->parent))->reduce();  
          }

          // TODO: treat expr result contextually removing even this op
          else if ($op == '#')
          {

            if (!preg_match('/^(?<name>[a-zA-Z_]\w*)(?:\s*:\s*(?:(?<index>[a-zA-Z_]\w*)\s*>\s*)?(?<as>[a-zA-Z_]\w*))?$/', $opexpr, $match))
              return '';

            $value = $this->eval($match['name']);

            if (is_callable($value))
            {
              try {
                $value = $value();
              }
              catch(\ArgumentCountError $e) { return; }
            }

            if (is_array($value))
            {
              if(array_keys($value) !== range(0, count($value) - 1))
              {
                return (new View($body, $value, $this->parent))->reduce();
              }

              else
              {
                $output = '';

                foreach ($value as $index => $element)
                {
                  $context = $element;

                  if ($match['as'])
                    $context = [$match['as'] => $element];

                  $view = new View($body, $context, $this->parent);

                  if ($match['index'])
                  {
                    $view->set($match['index'], $index);
                  }
                  else
                  {
                    $view->set('_', $index);
                  }

                  $output .= $view->reduce();
                }

                return $output;
              }
            }

            else if (is_object($value))
            {
              return (new View($body, $value, $this->parent))->reduce();
            }
          }
        }

        else
        {
          // extract pipes from expression
          preg_match_all('/\|\s*(?<pipes>[^|\s]+)\s*/', $expr, $matches);

          // remove pipes from expr string
          $expr = str_replace(join('',$matches[0]),'',$expr);

          $value = $this->eval($expr);

          if ($value instanceof View)
            $value = $value->reduce();

          if ($matches['pipes'])
            $value = $this->pipeline($value, $matches['pipes']);

          if ($value !== false)
            return "$value";
        }
      }
    , $content);
  }

  /**
   * Lookup complex name in all contexts
   * 
   * @param string
   * @return string
   */
  public function lookup(string $name)
  {
    $value = $this->resolve(trim($name));

    if ($value !== false)
      return $value;

    if (!is_null($this->parent))
    {
      // visite all parent scopes up to global
      return $this->parent->lookup(trim($name));
    }

    return false;
  }

  /**
   * Evaluate expression and return result
   * 
   * @param string
   * @return mixed
   */
  private function eval(string $expr)
  {
    // TODO: invoke expression parser
    return $this->lookup($expr);
  }

  /**
   * Resolve name expression
   * 
   * @var string
   * @return mixed
   */
  private function resolve(string $name, $context = null)
  {
    if (!$context)
      $context = $this->context;

    // preprocess for nested [] and identify matching pairs
    $name = preg_replace_callback('/(\[)|\]/', function ($a)
    {
      static $i = 0; return $a[0] . ($a[1] ? $i++ : --$i) . ';';
    }, $name);

    // resolve all [] to a numeric constant
    $name = preg_replace_callback('/\[((?:\d+;)+)(.*?)\]\g{1}/', function ($a)
    {
      $value = $a[2];

      if (!is_numeric($value))
        $value = $this->resolve($value);

      return "[{$value}]";
    }, $name);
    
    // resolve constant mame expression. all dynamic 
    // values have been resolved at this point.

    $parts = array_reverse(explode('.', $name));

    do
    {
      $part = array_pop($parts);

      if (!preg_match('/^([a-zA-Z_]\w*)((?:\[\d+\])+)?$/', $part, $match))
        return false;

      if (!isset($context[$match[1]]))
        return false;

      $value = $context[$match[1]];

      // accept callables
      if (is_callable($value))
      {
        try {
          $value = $value( /* no args */ );
        }
        catch(\ArgumentCountError $e)
        {
          return false;
        }
      }

      // accept sub views
      else if (is_subclass_of($value, self::class, true))
      {
        // static class name, create instance
        if (is_string($value)) {
          try {
            $value = new $value();
          }
          catch(\ArgumentCountError $e)
          {
            return false;
          }
        }

        $value = $value->reduce();
      }

      // parse array access
      if (isset($match[2]))
      {
        preg_match_all('/\[(\d+)\]/', $match[2], $index);

        foreach ($index[1] as $i)
        {
          if (!is_arrayable($value))
            return false;

          if (!isset($value[$i]))
            return false;

          $value = $value[$i];
        }
      }

      $context = $value;

    } while (count($parts) > 0);
    
    return $value;
  }

  /**
   * Send value to through pipes
   * 
   * @param mixed
   * @param string
   * @return mixed
   */
  private function pipeline($value, $pipes)
  {
    global $_PIPES;

    if (empty($pipes))
      return $value;

    foreach ($pipes as $pipe)
    {
      if (!isset($_PIPES[$pipe]))
        return false;
      
      $value = $_PIPES[$pipe]($value);
    }

    return $value;
  }

  /**
   * Construct view from constituents. Context may be an array,
   * an object or a class from which an object context is created.
   * 
   * @param string
   * @param string|object|array
   * @param \View|null
   */
  public function __construct($content = '', $context = [], View $parent = null)
  {
    $this->content = $content;
    $this->parent = $parent;
    $this->context = [];

    if (is_array($context))
    {
      /* remove numeric key elements */
      foreach ($context as $k => $v)
        if (is_numeric($k))
          unset($context[$k]);

      $this->context = $context;
    }

    if (is_string($context))
    {
      if (class_exists($context))
      {
        try {
          $context = new $context();
        }
        catch(\ArgumentCountError $e) {}
      }
    }

    if (is_object($context))
    {
      foreach (get_object_vars($context) as $name => $value)
      {
        $this->context[$name] = $value;
      }

      foreach (get_class_methods(get_class($context)) as $name)
      {
        /* skip magic methods */
        if (preg_match('/^__/', $name))
          continue;
        
        $this->context[$name] = \Closure::fromCallable([$context, $name]);
      }
    }

    // add parent context to local 'parent' identifier
    if (!is_null($parent))
      $this->context['parent'] = $parent->context;
  }

  /**
   * Accessors
   */
  public function __isset($name)
  {
    return isset($this->context[$name]);
  }

  public function __get($name)
  {
    return $this->lookup($name);
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
    return isset($this->context[$name]);
  }

  public function offsetGet($name)
  {
    return $this->lookup($name);
  }
  
  public function offsetSet($name, $value)
  {
    $this->set($name, $value);
  }

  public function offsetUnset($name)
  {
    if (isset($this->context[$name]))
      unset($this->context[$name]);
  }
}