<?php
namespace Lib\Bbcode;

class UrlNode implements BBNode{
  private $url;
  private $stack = [];
  
  public function __construct(string $url){
    $this->url = $url;
  }
  public function appendNode(BBNode $node){
    if($node instanceof UrlNode){
      return;
    }
    $this->stack[] = $node;
  }
  
  public function toHtml() : string{
    $raw = "<a href='{$this->url}' target='_blank'>";
    foreach($this->stack as $node){
      $raw .= $node->toHtml(); 
    }
    return $raw."</a>";
  }
  
  public function tag() : string{
    return "url";
  }
}