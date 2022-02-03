<?php
namespace Lib\Tempelate\Node;

use Lib\Tempelate\TempelateNode;

class TempelateHTMLNode implements TempelateNode{
  private $str;
  
  public function __construct(string $code){
    $this->str = $code;
  }
  
  public function toString() : string{
    return "\$context .= '".str_replace(["'"], ["\\'"], $this->str)."';";
  }
}