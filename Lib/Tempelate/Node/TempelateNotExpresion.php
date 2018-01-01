<?php
namespace Lib\Tempelate\Node;

use Lib\Tempelate\TempelateNode;

class TempelateNotExpresion implements TempelateNode{
  private $exp;
  
  public function __construct(TempelateNode $exp){
    $this->exp = $exp;
  }
  
  public function toString() : string{
    return "!".$this->exp->toString();
  }
}