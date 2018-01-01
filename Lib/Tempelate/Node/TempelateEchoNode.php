<?php
namespace Lib\Tempelate\Node;

use Lib\Tempelate\TempelateNode;

class TempelateEchoNode implements TempelateNode{
  private $expresion;
  
  public function __construct(TempelateNode $exp){
    $this->expresion = $exp;
  }
  
  public function toString() : string{
    return "\$context .= {$this->expresion->toString()};";
  }
}