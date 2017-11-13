<?php
namespace Lib\Tempelate;

class CssNode implements TempelateNode{
  private $uri;
  
  public function __construct(string $uri){
    $this->uri = $uri;
  }
  
  public function toCode() : string{
    return "\$this->css->addFile('{$this->uri}.css');";
  }
}