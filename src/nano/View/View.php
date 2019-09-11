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

// TODO : doc template syntax

class View
{
  const INL_OPERATORS = ['@'];
  const BLK_OPERATORS = ['#'];
  const IDENT_EXPR    = "(?<name>\w[\w\d\.\[\]]*)(?<pipes>(?:\s*\|\s*\w+)*)";

  /**
   * Basepath for relative views
   * 
   * @var array
   */
  private $basepath;

  /**
   * Local template scope
   * 
   * @var array
   */
  private $context;

  /**
   * Template content
   * 
   * @var string
   */
  private $content;

  /**
   * Subviews registered by this view
   * 
   * @var array
   */
  private $subviews = [];

  /**
   * Register a subview
   * 
   * @param string
   * @param string
   * @return void
   */
  public function register($name, $class)
  {
    $this->subviews[$name] = $class;
  }

  /**
   * Process view template code
   * 
   * @return string
   */
  public function reduce()
  {
    $inl = implode('', self::INL_OPERATORS);
    $blk = implode('', self::BLK_OPERATORS);

    $preproc = preg_replace_callback(
      "~\{\{\s*(?:(:)|(/)?[?$blk])~",
      function ($a) {
        static $i = 1;
        return $a[0] . ($a[1] ? $i - 1 : ($a[2] ? --$i : $i++)) . '%';
      }, $this->content
    );

    return preg_replace_callback(
      "~
        \{\{\s*
          (?<op>
            [$inl]
            |(?<b>(?<if>\?)|[$blk])
          )?
          (?<i>
            (\d+\%)+
          )?\s*
          (?<expr>
            (?<iden>".self::IDENT_EXPR.")
            .*?
          )
        \s*\}\}

        (?(b)
          (?<body>.*?)
          (?(if)
            \{\{\s*:\g{i}\s*\}\}
            (?<else>.*?)
          )?
          \{\{\s*/\g{op}\g{i}\s*\}\}
        )
      ~xsJ",
    [$this, 'parse'], 
    $preproc);
  }

  /**
   * Lookup name in context using object syntax
   * 
   * var:  \w+([\d+])*
   * name:  var(\.var)*
   * 
   * @param string
   * @return mixed
   */
  public function lookup($name)
  {
    $parts = array_reverse(explode('.', $name));
    $value = $this->context;

    do
    {
      $part = array_pop($parts);

      preg_match('/^(\w+)((?:\[\d+\])*)$/', $part, $m);

      $name = $m[1];
      
      if (!isset($value[$name]))
        return false;

      $value = $value[$name];

      if (preg_match_all('/\[(\d+)\]/', $m[2], $idx))
      {
        foreach ($idx[1] as $i)
        {
          if (!isset($value[$i]))
            return false;

          $value = $value[$i];
        }
      }

    } while (count($parts) > 0);

    return $value;
  }

  /**
   * Parse matches expressions
   */
  private function parse($input)
  {
    extract($input);

    // TODO: outputing errors this way should be remove...
    $error = "";

    if ($op)
    {
      switch ($op)
      {
        /*
        ------------------------

          conditional

          syntax: '?' expr

        ------------------------
        */

        case '?':
          
          // TODO: check expression is a valid boolean expression
          // if (!preg_match('/.../', $expr, $m)) break;

          $expr = preg_replace_callback('/\w[\w\d\.\[\]]*/', function($a) {
            
            $value = $this->piped_lookup($a[0]);

            if (false === $value)
              return $a[0];
            
            if (is_string($value))
              return '"' . $value . '"';

            if (is_object($value))
              return $a[0];

            echo "other";
            return $value;
          }, $expr);

          $result = @eval("return ($expr) ? true : false;");

          if ($result) {
            return (new View($body, $this->context, $this->basepath))->reduce();
          }

          else if ($else) {
            return (new View($else, $this->context, $this->basepath))->reduce();
          }

          return "";

         break;

        /*
        ------------------------

          foreach

          syntax: '#' iden (':' (name '>' )? name)?

        ------------------------
        */

        case '#':

          if (!preg_match(
            "~^
              ".self::IDENT_EXPR."
              (?:
                \s*:\s*
                (?:
                  (?<index>\w[\w\d]*)\s*>\s*
                )?
                (?<var>\w[\w\d]*)
              )?
            $~xsJ", $expr, $m)) break;
        
          $value = $this->piped_lookup($iden);

          if ($value === false) {
            $error = "$name not found";
            break;
          }

          if (is_object($value))
          {
            if (method_exists($value, 'toArray'))
              $value = $value->toArray();
            else
              $value = object_to_array($value);
          }

          if (!is_array($value)) {
            $error = "$iden is not an array";
            break;
          }
          
          $replace = '';
          foreach (array_values($value) as $index => $item)
          {
            $context = $this->context;

            if (@$m['index'])
              $context[$m['index']] = $index;

            if (@$m['var'])
              $context[$m['var']] = $item;
            
            $replace .= (new View($body, $context, $this->basepath))->reduce();
          }

          return $replace;
          
        break;

        /*
        ------------------------

          include subview

          syntax: '@' iden (':' iden (',' iden)* )?

        ------------------------
        */
        case '@':

            if (!preg_match(
            "~^
              ".self::IDENT_EXPR."
              (?:
                \s*:\s*
                (?<args>
                  \w[\w\d\.\[\]\|\s]*
                  (?:\s*,\s*\w[\w\d\.\[\]\|\s]*)*
                )
              )?
            $~xsJ", $expr, $m)) break;

          if (preg_match('/\[|\]|\./', $name)) {
            $error = "invalid name for subview";
            break;
          }

          if (!isset($this->subviews[$name])) {
            $error = "subview does not exist";
            break;
          }
          
          $viewClass = $this->subviews[$name];

          if (!class_exists($viewClass)) {
            $error = "view instance does not exist";
            break;
          }

          $args = explode(',', $m['args']); 

          $props = [];
          if ($args[0])
          {
            foreach ($args as $arg)
            {
              $value = $this->piped_lookup(trim($arg));

              if (false === $value)
                return $input[0];

              $props[] = $value;
            }
          }

          $view = new $viewClass('', ['parent' => $this->context], $this->basepath);

          if (! $view instanceof View) {
            $error = "invalid view instance";
            break;
          }

          if (@$view->data) {
            foreach (@$view->data as $name => $value)
            {
              $view->$name = $value;
            }
          }

          if (@$view->template) {
            if (is_array($view->template)) {
              $filename = $this->basepath . $view->template['file'];

              $view->basepath = $this->basepath . dirname($view->template['file']);

              if (file_exists($filename))
                $view->content = file_get_contents($filename);
            }

            else {
              $view->content = $view->template;
            }
          }

          if (@$view->views) {
            foreach (@$view->views as $def)
            {
              $view->register($def[0], $def[1]);
            }
          }

          if (method_exists($view, 'create')) {
            $view->create(...$props);
          }

          $reduce = $view->reduce();

          if ($pipes)
          {
            $reduce = $this->piped($reduce, $pipes);
          }

          return $reduce;

        break;
      }
    }

    /*
    ------------------------

      interpolation

    ------------------------
    */

    else {

      $value = $this->piped_lookup($iden);

      if ($value !== false) {

        if (is_array($value))
          return print_r($value, true);

        return $value;
      }
    }

    // TODO: outputing errors this way should be remove...
    if ($error)
      return "#" . $error . ": " . $input[0];

    return $input[0];
  }

  /**
   * Process identifier
   * 
   * var:   \w+([\d+])*
   * name:  var(\.var)*
   * pipe:  \w+
   * iden:  name ('|' pipe)*
   * 
   * @param string
   * @return mixed
   */
  private function piped_lookup($iden)
  {
      if (!preg_match("/^".self::IDENT_EXPR."$/", $iden, $m))
        return false;

      $value = $this->lookup($m['name']);

      if (false === $value)
        return false;

      $value = $this->piped($value, $m['pipes']);

      return $value;
  }

  /**
   * Run value through pipes
   * 
   * @param mixed
   * @param string $pipexpr
   * @return mixed
   */
  private function piped($value, $pipexpr)
  {
    $_pipes = [
        'inc'   => function ($value) { return ++$value; },
        'dec'   => function ($value) { return --$value; },
        'snake' => function ($value) { return snake($value); },
        'camel' => function ($value) { return camel($value); },
        'kebab' => function ($value) { return kebab($value); },
        'title' => function ($value) { return title($value); },
        'pascal'=> function ($value) { return pascal($value); },
        'upper' => function ($value) { return strtoupper($value); },
        'lower' => function ($value) { return strtolower($value); },
        'rev'   => function ($value) { return array_reverse($value); },
        'front' => function ($value) { return array_shift($value); },
        'back'  => function ($value) { return array_pop($value); },
        'sum'   => function ($value) { return array_sum($value); },
        'count' => function ($value) { return count($value); },
      ];

      $pipes = explode('|', $pipexpr); unset($pipes[0]);

      foreach ($pipes as $pipe)
      {
        if (!isset($_pipes[trim($pipe)]))
          return false;

        $value = $_pipes[trim($pipe)]($value);
      }

      return $value;
  }

  /**
   * Construct from constituents
   */
  public function __construct($content = "", $context = [], $basepath = '/')
  {
    $this->content = $content;
    $this->context = $context;
    $this->basepath = $basepath;
  }

  /**
   * accessors
   */
  public function __get($name)
  {
    return @$this->context[$name];
  }

  public function __set($name, $value)
  {
    $this->context[$name] = $value;
  }

  public function __toString()
  {
    return $this->reduce();
  }
}