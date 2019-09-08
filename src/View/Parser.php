<?php
/**
 * nano - lazy server app framework
 *
 * @author	  Daniel Robin <daniel.robin.1@ulaval.ca>
 * @version		1.4
 *
 * last updated: 09-2019
 */

namespace nano\View;

use nano\Context as Context;

/**
 * Parser syntax: TODO
 */

// TODO: move this to config?
define('DEBUG_LEAVE_UNPARSED_TAGS', true);

class Parser
{
  const INLINE_OPERATORS = ['i', '\!'];
  const BLOCK_OPERATORS = ['\=', '\*', '\#'];
  
  /**
   * @var Context
   */
  private $_context;

  /**
   * @var array, user defined operators
   */
  private $_ops = [];

  /**
   * @var array, user defined procedures
   */
  private $_proc = [];

  /**
   * Set context
   */
  public function setContext($context)
  {
    $this->_context = new Context($context);
  }

  /**
   * Register user defined operator
   */
  public function registerOperator($op, callable $callback, bool $block = false)
  {
    $this->_ops[$op] = [
      'handler' => $callback, 
      'is_block' => $block
    ];
  }

  /**
	 * Register user defined procedure
	 */
	public function registerProcedure($proc, callable $callback)
	{
		$this->_proc[$proc] = $callback;
  }

  /**
   * Parse content and return html result
   * 
   * @param string, raw view file
   * @return string, resulting html file
   */
  public function parse(string $content)
  {
    $inlOps = self::INLINE_OPERATORS;
    $blkOps = self::BLOCK_OPERATORS;

    foreach ($this->_ops as $op => $def) {
      if ($def['block'])
        $blkOps[] = '\\'.$op;
      else
        $inlOps[] = '\\'.$op;
    }

    $inlOps = join('', $inlOps);
    $blkOps = join('', $blkOps);

    $preprocessed = preg_replace_callback(
      "~\{\{\s*(?:(/)|(?:(:)|[?$blkOps]))~",
      function ($a) {
        static $i = 1; return $a[0] . '%' . (@$a[1] ? --$i : (@$a[2] ? $i-1 : $i++));
      }, $content);

    return preg_replace_callback(
      "~
        \{\{\s*
          (?:
            # operator syntax
            (?<op>
              (?<inl>[$inlOps])
              |
              (?<blk>(?<is_if>\?)|[$blkOps])
            )
            (?<id>[%\d]+)?
            (?<expr>[^}]+)
            |
            # interpolation
            (?<name>[^|}]+)
            (?<pipe>(?:\|[^|}]+)*)
          )
        \s*\}\}

        # block body
        (?(blk)
          (?<body>.*?
            (?(is_if)
              # else if
              (?:
                \{\{\s*:\g{id}\?
                (?<elif_expr>[^}]+)
                \}\}
                (?<elif_block>.*?)
              )*
              |
            )?
          )
          (?(is_if)
            # else
            \{\{\s*:\g{id}\s*\}\}
            (?<else>.*?)
          )?
          \{\{\s*/\g{id}\s*\g{blk}\s*\}\}
        )
      ~xsJ",
    function ($a) { extract($a);
        // //////////////////////////////////////////////////////////////////////////////
        array_walk($a, function ($v, $k) use (&$a) { if (is_numeric($k)) unset($a[$k]); });
        print_r($a);
        // //////////////////////////////////////////////////////////////////////////////

      if ($op)
      {
        /* conditional block */

        if ($is_if)
        {
          if ($this->_expr(trim($expr)))
          {
            return $this->parse(trim($body));
          }

          else {
            // foreach ($elif as $elif)
            // {
            //   if ($this->_expr($elif))
            //     return $this->parse($elif_block);
            // }

            if ($else) {
              return $this->parse(trim($else));
            }
          }
        }

        else {

          foreach ($this->_ops as $_op => $def)
          {
            if (strcmp($op, $_op) == 0) {
              $callback = $def['handler'];
              if ($def['is_block'])
              {
                return $callback($expr, $this->_context, trim($body));
              }
              else
              {
                return $callback($expr, $this->_context);
              }
            }
          }
        }
      }

      /* interpolation */

      else 
      {
        return $this->_var(trim($name));
      }

      if (DEBUG_LEAVE_UNPARSED_TAGS)
        return $a[0];

    }, $preprocessed);
  }

  /**
   * Lookup context variable by name
   */
  private function _var($name)
  {
    $value = $this->_context->get($name);

    if (!$value)
      warn("Variable '$name' might not be defined");
    
    return $value;
  }

  /**
   * Evaluate expression to true or false
   */
  private function _expr($expr)
	{
		$expr = preg_replace_callback(
      '/".*?"|(?<name>[a-zA-Z_][\w\.]*)/', 
      function ($a) {
        if (isset($a['name'])) {
          $value = $this->_var($a['name']);

          if ($value === null) {
            $value = 0;
          }
          
          else if (is_string($value)) {
            $value = '"'.$value.'"';
          }

          return $value;
        }
      }, $expr);

		return @eval("return (($expr) ? true : false);");
  }
}