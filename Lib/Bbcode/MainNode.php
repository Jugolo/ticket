<?php
namespace Lib\Bbcode;

class MainNode implements BBNode{
  private $stack = [];
  
  public function appendNode(BBNode $node){
    $this->stack[] = $node; 
  }
  
  public function toHtml() : string{
    $str = "";
    foreach($this->stack as $node){
      $str .= $node->toHtml();
    }
    return $str;
  }
  
  public function tag() : string{
    return "";
  }
}