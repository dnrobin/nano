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

class Parser extends View
{
  /**
   * Parse view content against its context
   * 
   * @param View
   * @return string
   */
  public function parse(View $view)
  {
    $inl = implode('|', ['view']);
    $blk = implode('|', ['if','for','with']);

    $content = preg_replace_callback(
      "~<(/?)n(?:ano)?:(?:$blk|(elif|else))~",
      function ($a) {
        static $i = 1;
        return $a[0] . '[' . ($a[2] ? $i - 1 : ($a[1] ? --$i : $i++)) . ']';
      }, $view->content
    );

    $this->local = $view->local;
    $that = $this;
    
    return preg_replace_callback(
      "~
        <n(?:ano)?:
        (?:
          (?<op>
            $inl
            |
            (:?
              (?<b>(?<i>if)|$blk)
              (?<n>(?:\[\d+\])+)
            )
          )
          |
          (?!$blk|else)
          (?<ident>
            (?<name>\$?\w[\w\d]*)
            |
            \$?\w[\w\d.\[\]]*
          )
        )
        \s*
        (?<attr>
          (?:
            \w+\s*
            (?:=\s*(?:\".*?\"|\d+))?\s*
          )*
        )
        \s*(?(b)|/?)>
        (?(b)
          (?<body>.*?)
          (?(i)
            (?:
              <n(?:ano)?:else\g{n}\s*>
              (?<else>.*?)
            )?
          )
          </n(?:ano)?:\g{op}\s*>
        )
      ~xsJ",
    function($a) use ($that) { extract ($a);

      $attr = [];

      if ($a['attr'])
      {
        if (!preg_match_all('/(?<name>\w[\w-]*)(?:\s*=\s*(?<value>".*?"|\d+))?/', $a['attr'], $matches))
          return $a[0];

        foreach ($matches[0] as $k => $v) {
          $attr[$matches['name'][$k]] = preg_replace('/^"|"$/', '', ($matches['value'][$k] ?: false));
        }
      }

      if ($op)
      {
        $op = preg_replace('/\[\d+\]/', '', $op);

        /*
        --------------------------

          conditional block

        --------------------------
        */
        if ($op == 'if')
        {
          if (count ($attr) != 1)
            return;

          $type = reset(array_keys($attr));

          $true = false;

          switch ($type)
          {
            case 'exist':
              $value = $that->lookup($attr['exist']);
              $true = ($value !== false);
              break;
            
            case 'empty':
              $value = $that->lookup($attr['empty']);
              $true = (is_null($value) || empty($value));
              break;
          }

          if ($true)
            return view(rtrim($body), $that->local);

          return view(rtrim($else), $that->local);
        }

        /*
        --------------------------

          repeat body over array

        --------------------------
        */
        else if ($op == 'for')
        {
          if (isset($attr['each']))
          {
            list($each, $as, $index) = $this->attributes(['each','as'=>null,'index'=>null], $attr);

            $arry = $that->lookup($each);

            if (!is_array($arry))
              return;

            $result = '';

            foreach($arry as $key => $value)
            {
              $context = $that->local;

              if ($index)
                $context[$index] = $key;

              if ($as)
                $context[$as] = $value;

              $result .= view(rtrim($body), $context);
            }

            return $result;
          }

          else if (isset($attr['range']))
          {
            list($range, $index) = $this->attributes(['range','index'=>null], $attr);
            
            if (!preg_match('/^([\d-]+)(?:\.\.|:([\d-]+):)([\d-]+)$/', $attr['range'], $m))
              return;

            $range = @range($m[1], $m[3], $m[2] ?: 1) ?: [];

            $result = '';

            foreach($range as $value)
            {
              $context = $that->local;

              if ($index)
                $context[$index] = $key;

              $result .= view(rtrim($body), $context);
            }

            return $result;
          }
        }

        /*
        --------------------------

          unpack value into body

        --------------------------
        */
        else if ($op == 'with')
        {
          list($name) = $this->attributes(['context'], $attr);

          $value = $that->lookup($name);

          if ($value === false)
            return;
          
          if (!is_array($value))
            return;

          $context = array_merge($that->local, $value);

          return view($body, $context);
        }

        /*
        --------------------------

          include view

        --------------------------
        */
        else if ($op == 'view')
        {
          if (!isset($attr['src']))
            return;

          $src = $attr['src']; unset($attr['src']);

          $attr = $this->attributes_lookup($attr);

          $view = view(rtrim(@file_get_contents($src, true)), array_merge($attr, ['parent' => $that->local]));

          return $view->reduce();
        }
      }

      /*
      --------------------------

        interpolate value expr

      --------------------------
      */

      $value = $that->lookup($a['ident']);

      if ($value === false)
        return $a[0];
      
      if (! $value instanceof View)
        return $value;

      /*
      --------------------------

        reduce view instance

      --------------------------
      */

      $attr = $this->attributes_lookup($attr);

      $value->local = array_merge($attr, ['parent' => $that->local]);

      return $value->reduce();
      
    }, $content);
  }

/**
   * Lookup name in local context using object syntax
   * 
   * var:  \w+([\d+])*
   * name:  var(\.var)*
   * 
   * @param string
   * @return mixed
   */
  protected function lookup($name)
  {
    if ($name[0] == '$') {
      $value = self::$global; // global state is accessed by prefixing name wih '$'
      $name = substr($name, 1);
    }

    else {
      $value = $this->local;
    }

    $parts = array_reverse(explode('.', $name));

    do
    {
      $part = array_pop($parts);

      preg_match('/^(\w+)((?:\[\d+\])*)$/', $part, $m);

      $name = $m[1];

      if (!is_array($value))
        return false;
      
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
   * Parse attributes array against requested attributes
   * 
   * @param array
   * @param array
   * @return array
   */
  private function attributes(array $want, array $given)
  {
    $attr = [];
    
    foreach ($want as $k => $v)
    {
      if (is_numeric($k))
      {
        if (!isset($given[$v]))
          parse_error("missing required attribute '$v'");

        $attr[] = $given[$v];
        unset($given[$v]);
      }

      else
      {
        if (isset($given[$k])) {
          $attr[] = $given[$k];
          unset($given[$k]);
        }
        else
          $attr[] = $v;
      }
    }

    if (count($given) > 0)
      parse_error("unknown attribute '".reset(array_keys($given))."'");

    return $attr;
  }

  /**
   * Lookup references and insert values in attributes
   * 
   * @param array
   * @return array
   */
  private function attributes_lookup($attr)
  { 
    foreach ($attr as $name => &$value)
    {
      $local = $this->lookup($value);
      
      if ($local !== false)
        $value = $local;
    }

    return $attr;
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

      $value = $this->pipe($value, $m['pipes']);

      return $value;
  }

  /**
   * Run value through pipes
   * 
   * @param mixed
   * @param string $pipexpr
   * @return mixed
   */
  protected function pipe($value, $pipexpr)
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
        if (!isset($_pipes[rtrim($pipe)]))
          return false;

        $value = $_pipes[rtrim($pipe)]($value);
      }

      return $value;
  }
}