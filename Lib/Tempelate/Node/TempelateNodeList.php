<?php
namespace Lib\Tempelate\Node;

use Lib\Tempelate\TempelateNode;

class TempelateNodeList implements TempelateNode{
  private $stack = [];
  
  public function append(TempelateNode $node){
    $this->stack[] = $node;
  }
  
  public function toString() : string{
    $result = "";
    foreach($this->stack as $node)
      $result .= $node->toString();
    return $result;
  }
}