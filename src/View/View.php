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

class View
{
  /**
   * @var Parser
   */
  private $_parser;

  /**
   * @var Context
   */
  private $_context;

  /**
   * @var string
   */
  private $_content = "";

  /**
   * Set external parser
   */
  public function setParser(Parser $parser)
  {
    $this->_parser = $parser;
  }

  /**
   * Load view template file
   */
  public function load($filename)
  {
    $path = $this->_context->basedir . $filename;

    if (!file_exists($path))
      error("View file '$filename' not found");
    
    $content = file_get_contents($path);

    if (is_null($content)) {
      $this->_content = "";
    }

    else {
      $this->_content = $content;
    }
  }

  /**
   * Parse and return html result
   */
  public function produce()
  {
    // Set current context state
    $this->_parser->setContext($this->_context);

    // Parse content to html
    $html = $this->_parser->parse($this->_content);

    return $html;
  }

  /**
   * Ctor
   */
  function __construct(Context $context = null)
  {
    $this->_context = $context;
    $this->_parser = new Parser();
  }

  function __toString()
  {
    return $this->produce();
  }

  /**
   * Construct from file and context
   */
  static function fromFile($filename, Context $context = null)
  {
    $v = new View($context);
    $v->load($filename);
    return $v;
  }
}