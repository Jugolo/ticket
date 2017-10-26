<?php
namespace Lib\Bbcode;

class INode implements BBNode{
  private $stack = [];
  
  public function appendNode(BBNode $node){
    $this->stack[] = $node;
  }
  
  public function toHtml() : string{
    $return = "<i>";
    foreach($this->stack as $node){
      $return .= $node->toHtml();
    }
    return $return."</i>";
  }
  
  public function tag() : string{
    return "i";
  }
}