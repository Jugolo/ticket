<?php
namespace Lib\Bbcode;

class BNode implements BBNode{
  private $stack = [];
  
  public function appendNode(BBNode $node){
    $this->stack[] = $node;
  }
  
  public function toHtml() : string{
    $return = "<strong>";
    foreach($this->stack as $node){
      $return .= $node->toHtml();
    }
    return $return."</strong>";
  }
  
  public function tag() : string{
    return "b";
  }
}