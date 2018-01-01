<?php
namespace Lib\Tempelate\Node;

use Lib\Tempelate\TempelateNode;

class TempelateIfNode implements TempelateNode{
  private $test;
  private $block;
  private $end;
  
  public function __construct(TempelateNode $node, string $body, $else){
    $this->test  = $node;
    $this->block = $body;
    $this->end   = $else;
  }
  
  public function toString() : string{
    $return = "if({$this->test->toString()}){
      {$this->block}
    }";
    if($this->end){
      $return .= "else".$this->end->toString();
    }
    return $return;
  }
}