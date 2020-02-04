<?php
namespace Lib\Tempelate\Node;

use Lib\Tempelate\TempelateNode;

class TempelateAddScriptNode implements TempelateNode{
  private $path;
  
  public function __construct(string $path){
    $this->path = $path;
  }
  
  public function toString() : string{
    return "\$data->addScript('{$this->path}');";
  }
}