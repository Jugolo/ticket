<?php
namespace Lib\Tempelate;

class AccessNode implements TempelateNode{
  private $name;
  
  public function __construct(string $name){
    $this->name = $name;
  }
  
  public function toCode() : string{
    return "Lib\Access::userHasAccess('{$this->name}')";
  }
}