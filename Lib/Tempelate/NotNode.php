<?php
namespace Lib\Tempelate;

class NotNode implements TempelateNode{
  private $expresion;
  
  public function __construct(TempelateNode $expresion){
    $this->expresion = $expresion;
  }
  
  public function toCode() : string{
    return "!".$this->expresion->toCode();
  }
}