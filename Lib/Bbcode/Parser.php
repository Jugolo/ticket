<?php
namespace Lib\Bbcode;

class Parser{
  private $dom;
  private $source = "";
  
  public function __construct(string $code){
    $this->dom = Lexer::toDom($code);
  }
  
  public function getHtml(){
    return $this->dom->toHtml();
  }
}