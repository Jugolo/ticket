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
    $str = "<div class='spoiler'><div class='spoiler_title' onclick='var sd = this.parentNode.getElementsByClassName(\"spoiler_context\")[0]; sd.style.display = sd.offsetParent == null ? \"block\" : \"none\";'>".htmlentities($this->title)."</div><div class='spoiler_context'>";
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