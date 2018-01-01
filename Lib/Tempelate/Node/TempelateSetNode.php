<?php
namespace Lib\Tempelate\Node;

use Lib\Tempelate\TempelateNode;

class TempelateSetNode implements TempelateNode{
  private $variabel;
  private $value;
  
  public function __construct(string $variabel, TempelateNode $value){
    $this->variabel = $variabel;
    $this->value    = $value;
  }
  
  public function toString() : string{
    return "\$db->put(['{$this->variabel}' => {$this->value->toString()}]);";
  }
}