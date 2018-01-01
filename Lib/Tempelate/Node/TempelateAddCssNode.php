<?php
namespace Lib\Tempelate\Node;

use Lib\Tempelate\TempelateNode;

class TempelateAddCssNode implements TempelateNode{
  private $path;
  
  public function __construct(string $path){
    $this->path = $path;
  }
  
  public function toString() : string{
    return "\$data->addStyle('{$this->path}');";
  }
}