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

trait Reducible
{
  /**
   * Apply reductions to produce string output
   * 
   * @return string
   */
  abstract public function reduce();
}