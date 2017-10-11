<?php
namespace Lib\Bbcode;

class TitleNode implements BBNode{
  private $stack = [];
  
  public function appendNode(BBNode $node){
    $this->stack[] = $node;
  }
  
  public function toHtml() : string{
    $str = "<h3>";
    foreach($this->stack as $node)
      $str .= $node->toHtml();
    return $str."</h3>";
  }
  
  public function tag() : string{
    return "title";
  }
}