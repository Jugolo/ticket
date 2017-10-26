<?php
namespace Lib\Bbcode;

class UNode implements BBNode{
  private $stack = [];
  
  public function appendNode(BBNode $node){
    $this->stack[] = $node;
  }
  
  public function toHtml() : string{
    $return = "<u>";
    foreach($this->stack as $node){
      $return .= $node->toHtml();
    }
    return $return."</u>";
  }
  
  public function tag() : string{
    return "u";
  }
}