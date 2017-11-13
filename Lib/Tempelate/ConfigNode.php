<?php
namespace Lib\Tempelate;

class ConfigNode implements TempelateNode{
  private $name;
  
  public function __construct(string $name){
    $this->name = $name;
  }
  
  public function toCode() : string{
    return "Lib\\Config::get('{$this->name}')";
  }
}