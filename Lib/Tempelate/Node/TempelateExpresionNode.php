<?php
namespace Lib\Tempelate\Node;

use Lib\Tempelate\TempelateNode;

class TempelateExpresionNode implements TempelateNode{
  private $node;
  
  public function __construct(TempelateNode $node){
    $this->node = $node;
  }
  
  public function toString() : string{
    return "\$context .= htmlentities({$this->node->toString()});";
  }
}