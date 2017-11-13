<?php
namespace Lib\Tempelate;

class ScriptNode implements TempelateNode{
  private $uri;
  
  public function __construct(string $uri){
    $this->uri = $uri;
  }
  
  public function toCode() : string{
    return "\$this->scriptFile[] = '{$this->uri}.js';";
  }
}