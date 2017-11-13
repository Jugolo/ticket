<?php
namespace Lib\Tempelate;

class BooleanBindNode implements TempelateNode{
  private $left, $right;
  private $bind;
  
  public function __construct(TempelateNode $left, string $bind, TempelateNode $right){
    $this->left  = $left;
    $this->bind  = $bind;
    $this->right = $right;
  }
  
  public function toCode() : string{
    return $this->left->toCode()." ".($this->bind == "and" ? "&&" : "||")." ".$this->right->toCode();
  }
}