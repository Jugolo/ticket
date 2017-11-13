<?php
namespace Lib\Tempelate;

class SetNode implements TempelateNode{
  private $variabel;
  private $value;
  
  public function __construct(string $variabel, TempelateNode $value){
    $this->variabel = $variabel;
    $this->value    = $value;
  }
  
  public function toCode() : string{
    return "\$db->put('{$this->variabel}', {$this->value->toCode()});";
  }
}