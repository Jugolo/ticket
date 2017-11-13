<?php
namespace Lib\Tempelate;

class BooleanCompareExpresion implements TempelateNode{
  private $left, $right;
  private $type;
  
  public function __construct(TempelateNode $left, string $type, TempelateNode $right){
    $this->left = $left;
    $this->right = $right;
    $this->type = $type;
  }
  
  public function toCode() : string{
    return $this->left->toCode()." ".$this->type." ".$this->right->toCode();
  }
}