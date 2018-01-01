<?php
namespace Lib\Tempelate\Node;

use Lib\Tempelate\TempelateNode;

class TempelateLanguageNode implements TempelateNode{
  private $key;
  private $arg;
  
  public function __construct(string $key, array $arg){
    $this->key = $key;
    $this->arg = $arg;
  }
  
  public function toString() : string{
    return "\$context .= \\Lib\\Language\\Language::get('{$this->key}', [".implode(", ", $this->arg)."]);";
  }
}