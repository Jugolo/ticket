<?php
namespace Lib\Tempelate\Node;

use Lib\Tempelate\TempelateNode;

class TempelatePluginNode implements TempelateNode{
  private $path;
  
  public function __construct(string $path){
    $this->path = $path;
  }
  
  public function toString() : string{
    return "\$context .= \\Lib\\Plugin\\Plugin::trigger_tempelate(\$tempelate, '{$this->path}');";
  }
}