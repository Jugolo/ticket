<?php
namespace Lib\Tempelate;

class ExpresionNode implements TempelateNode{
  private $node;
  
  public function __construct(TempelateNode $node){
    $this->node = $node;
  }
  
  public function toCode() : string{
    return $this->node->toCode();
  }
}