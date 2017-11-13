<?php
namespace Lib\Tempelate;

class EchoNode implements TempelateNode{
  private $node;
  
  public function __construct(TempelateNode $node){
    $this->node = $node;
  }
  
  public function toCode() : string{
    return "echo ".$this->node->toCode().";";
  }
}