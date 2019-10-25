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
 * 1. Block interpolation syntax
 * 
 * '{{' eval [ ':' [ index '>' ] var ] '}}' body '{{/}}'
 * 
 * 2. Conditional block
 * 
 * '{{' '?' expr '}}' if_body [ '{{:}}' else_body ] '{{/}}'
 * 
 * 3. Value interpolation syntax
 * 
 * '{{' eval '/}}'
 * 
 *  where,
 *    eval:  expr [ '|' pipe ]*
 *    expr:  ident | bool
 * 
 * 4. View file inclusion syntax
 * 
 * '{{' ':' file '/}}'
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
  use Reducible;
  
  /**
   * @var \View|null
   */
  private $parent;

  function getParent() {
    echo "getting parent";
    if (!$this->parent)
      return null;
      
    return $this->parent;
  }

  /**
   * @var array
   */
  protected $context;

  /**
   * @var string
   */
  protected $content;

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
  private function parse()
  {
    $content = preg_replace_callback('/{{\s*(?:(\?:)|\?|(\/)|(?=.+?(\/)?\s*}}))?/', function ($a) {
      static $i = 0;

      if ($a[3])
        return $a[0];

      return $a[0] . ($a[1] ? $i : ( $a[2] ? $i-- : ++$i )) . ';';
    }, $this->content);

    return preg_replace_callback(
      '~
          {{\s*
            (?<op>:|(?<i>\?))?
            (?<id>(\d+;)*)
            \s*(?<eval>.+?)
            \s*(?<asg>:\s*([a-zA-Z_]\w*\s*>\s*)?[a-zA-Z_]\w*)?
          \s*(?<v>/\s*)?}}

          (?(v)|\s*
            (?<body>.*?)
            (?(i)
              {{\?:\g{id}}}\s*
              (?<else>.*?)
            )?
            {{/\g{id}}}\s?
          )
      ~xs',
      function ($a)
      {
        extract ($a);

        if ($op)
        {
          if ($op == '?')
          {
            $result = $this->expr($eval);

            if ($result)
              return (new View($body, $this->context, $this->parent))->reduce();
            else
              return (new View($else, $this->context, $this->parent))->reduce();  
          }

          else if ($op == ':')
          {
            preg_match('/^(\'|\")(.+?)\g(1)$/', $expr, $file);

            if ($file[2])
            {
              $filename = get_include_path() . '/' . $file[2];
              if (file_exists($filename))
              {
                return (new View(file_get_contents($filename), $this->context, $this))->reduce();
              }
            }
          }
        }

        else
        {
          // extract pipes from eval
          preg_match_all('/\|\s*([^|\s]+)\s*/', $eval, $pipes);

          // extract expr from eval
          $expr = trim(str_replace(join('',$pipes[0]),'',$eval));

          // get eval value
          $value = $this->expr($expr);

          // convert view to its content
          if ($value instanceof View)
              $value = $value->reduce();

          // get callable value
          if (is_callable($value))
          {
            try {
              $value = $value();
            }
            catch(\ArgumentCountError $e) { return; }
          }

          // run it through pipeline
          if (!empty($pipes[1]))
              $value = $this->pipeline($value, $pipes[1]);

          if ($v)
          {
            if ($value !== false)
              return "$value";
          }

          else
          {
            preg_match('/^(?:\s*:\s*(?:(?<index>[a-zA-Z_]\w*)\s*>\s*)?(?<as>[a-zA-Z_]\w*))?$/', $asg, $match);

            $index = $match['index'];
            $as    = $match['as'];

            if (is_arrayable($value))
            {
              if (is_array($value))
              {
                // Note: one exception can cause a bug, if a hash has a numeric key in the last 
                //  position with the value count() - 1, this won't evaluate to true
                if(count($value) - 1 !== array_pop(array_keys($value))) {
                  return (new View($body, ($as ? [$as => $value] : $value), $this))->reduce();
                }
              }

              $output = '';

              foreach ($value as $key => $element)
              {
                $context = $element;

                if ($as)
                  $context = [$as => $element];

                $view = new View($body, $context, $this);

                if ($index)
                {
                  $view->set($index, $key);
                }
                else
                {
                  $view->set('_', $key);
                }

                $output .= $view->reduce();
              }

              return $output;
            }

            else if (is_object($value))
            {
              return (new View($body, ($as ? [$as => $value] : $value), $this))->reduce();
            }
          }
        }
      }, $content);
  }

  /**
   * Lookup complex name in all contexts
   * 
   * @param string
   * @return string
   */
  public function lookup(string $name)
  {
    // preprocess for nested [] and identify matching pairs
    $name = preg_replace_callback('/(\[)|\]/', function ($a) {
      static $i = 0; return $a[0] . ($a[1] ? $i++ : --$i) . ';';
    }, $name);

    // resolve all [] to a numeric constant
    $name = preg_replace_callback('/\[((?:\d+;)+)(.*?)\]\g{1}/', function ($a)
    {
      $value = $a[2];

      if (!is_numeric($value))
        $value = $this->lookup($value);

      if ($value === false)
        $value = $a[2];

      return "[{$value}]";
    }, $name);

    // resolve the full identifier
    $value = $this->resolve($name);

    if ($value !== false)
      return $value;

    if (!is_null($this->parent))
    {
      // visite all parent scopes up to global
      return $this->parent->lookup($name);
    }

    return false;
  }

  /**
   * Evaluate expression and return result
   * 
   * @param string
   * @return mixed
   */
  private function expr(string $expr)
  {
    // TODO: invoke expression parser!
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
    
    // resolve constant mame expression. all dynamic 
    // values have been resolved at this point.

    $parts = array_reverse(explode('.', $name));

    do
    {
      $part = array_pop($parts);

      if (!preg_match('/^([$a-zA-Z_]\w*)((?:\[\d+\])+)?$/', $part, $match))
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