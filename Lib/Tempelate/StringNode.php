<?php
namespace Lib\Tempelate;

class StringNode implements TempelateNode{
  private $str;
  
  public function __construct(string $str){
    $this->str = $str;
  }
  
  public function toCode() : string{
    return "\"{$this->str}\"";
  }
}