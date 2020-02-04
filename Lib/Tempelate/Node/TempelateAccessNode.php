<?php
namespace Lib\Tempelate\Node;

use Lib\Tempelate\TempelateNode;
use Lib\Tempelate\Tokenizer;
use Lib\Exception\TempelateException;

class TempelateAccessNode implements TempelateNode{
  private $access;
  
  public function __construct(Tokenizer $token){
    if(!$token->current()->test("IDENTIFY") && !$token->current()->test("STRING"))
      throw new TempalteExpresion("After access keyword there must be a identify or string");
    $this->access = $token->current()->getContext();
    $token->next();
  }
  
  public function toString() : string{
    return "Lib\Access::userHasAccess('{$this->access}')";
  }
}