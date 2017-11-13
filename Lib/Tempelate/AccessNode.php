<?php
namespace Lib\Tempelate;

class AccessNode implements TempelateNode{
  private $name;
  
  public function __construct(string $name){
    $this->name = $name;
  }
  
  public function toCode() : string{
    return "(defined('group') && group['{$this->name}'] == 1)";
  }
}