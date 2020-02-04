<?php
namespace Lib\Tempelate\Node;

use Lib\Tempelate\TempelateNode;

class TempelateIdentifyNode implements TempelateNode{
  private $name;
  
  public function __construct(string $name){
    $this->name = $name;
  }
  
  public function toString() : string{
    return "\$db->getRaw('{$this->name}')";
  }
}