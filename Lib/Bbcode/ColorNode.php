<?php
namespace Lib\Bbcode;

class ColorNode implements BBNode{
  private $color;
  private $stack = [];
  
  public function __construct(string $color){
    $this->color = $color;
  }
  
  public function appendNode(BBNode $node){
    $this->stack[] = $node;
  }
  
  public function toHtml() : string{
    if(!$this->isValid()){
      $this->color = "black";
    }
    
    $str = "<span style='color: {$this->color};'>";
    foreach($this->stack as $node){
      $str .= $node->toHtml();
    }
    
    return $str."</span>";
  }
  
  public function tag() : string{
    return "color";
  }
  
  private function isValid(){
    return in_array($this->color, [
      "black",
      "yellow",
      "white",
      "blue",
      "red"
      ]);
  }
}