<?php
namespace Lib\Tempelate\Node;

use Lib\Tempelate\TempelateNode;
use Lib\Tempelate\Tokenizer;

class TempelateConfigExpresion implements TempelateNode{
  private $name;
  
  public function __construct(Tokenizer $token){
    if(!$token->current()->test("IDENTIFY") && !$token->current()->test("STRING")){
      throw new TempelateException("After config keyword there must be a identify or string");
    }
    $this->name = $token->current()->getContext();
    $token->next();
  }
  
  public function toString() : string{
    return "\\Lib\\Config::get('{$this->name}')";
  }
}