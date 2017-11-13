<?php
namespace Lib\Tempelate;

class IfNode implements TempelateNode{
  private $expresion;
  private $isElseif;
  
  public function __construct(TempelateNode $expresion, bool $isElseif){
    $this->expresion = $expresion;
    $this->isElseif = $isElseif;
  }
  
  public function toCode() : string{
    return ($this->isElseif ? "}else" : "")."if({$this->expresion->toCode()}){";
  }
}