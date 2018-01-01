<?php
namespace Lib\Tempelate\Node;

use Lib\Tempelate\TempelateNode;

class TempelateBoolbindExpresion implements TempelateNode{
  private $left, $right;
  private $bind;
  
  public function __construct(TempelateNode $left, string $bind, TempelateNode $right){
    $this->left  = $left;
    $this->bind  = $bind;
    $this->right = $right;
  }
  
  public function toString() : string{
    return $this->left->toString()." ".($this->bind == "and" ? "&&" : "||")." ".$this->right->toString();
  }
}