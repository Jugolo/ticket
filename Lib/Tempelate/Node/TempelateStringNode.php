<?php
namespace Lib\Tempelate\Node;

use Lib\Tempelate\TempelateNode;

class TempelateStringNode implements TempelateNode{
  private $str;
  
  public function __construct(string $str){
    $this->str = $str;
  }
  
  public function toString() : string{
    return "\"{$this->str}\"";
  }
}