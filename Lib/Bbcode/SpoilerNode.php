<?php
namespace Lib\Bbcode;

class SpoilerNode implements BBNode{
  private $title;
  private $stack;
  
  public function __construct(string $title){
    $this->title = $title;
  }
  
  public function appendNode(BBNode $node){
    $this->stack[] = $node;
  }
  
  public function toHtml() : string{
    $str = "<div class='spoiler'><div class='spoiler_title' onclick='spoiler_click(this);'>".htmlentities($this->title)."</div><div class='spoiler_context'>";
    foreach($this->stack as $node){
      $str .= $node->toHtml();
    }
    $str .= "</div></div>";
    return $str;
  }
  
  public function tag() : string{
    return "spoiler";
  }
}