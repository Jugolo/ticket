<?php
namespace Lib\Tempelate\Node;

use Lib\Tempelate\TempelateNode;
use Lib\Tempelate\Tokenizer;

class TempelatePageAccessNode implements TempelateNode{
  private $page;
  
  public function __construct(Tokenizer $token){
    if(!$token->current()->test("IDENTIFY") && !$token->current()->test("STRING")){
      throw new TempelateException("after page access there must be a string or identify");
    }
    $this->page = $token->current()->getContext();
    $token->next();
  }
  
  public function toString() : string{
    return "\$data->hasAccessTo('{$this->page}')";
  }
}