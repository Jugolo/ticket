<?php
namespace Lib\Tempelate;

class IdentifyNode implements TempelateNode{
  private $name;
  
  public function __construct(string $name){
    $this->name = $name;
  }
  
  public function toCode() : string{
    return "\$db->getRaw('{$this->name}')";
  }
}