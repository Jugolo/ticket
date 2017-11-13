<?php
namespace Lib\Tempelate;

class NumberNode implements TempelateNode{
  private $number;
  
  public function __construct(string $number){
    $this->number = $number;
  }
  
  public function toCode() : string{
    return intval($this->number);
  }
}