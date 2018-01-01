<?php
namespace Lib\Tempelate\Node;

use Lib\Tempelate\TempelateNode;
use Lib\Tempelate\TempelateParser;

class TempelateArrayGetNode implements TempelateNode{
  private $value;
  private $key;
  
  public function __construct(TempelateParser $parser, TempelateNode $node){
    $this->value = $node->toString();
    $token = $parser->getTokenizer();
    $token->next();
    $this->key = $parser->expresion()->toString();
    $token->current()->expect("PUNCTOR", "]");
    $token->next();
  }
  
  public function toString() : string{
    return "\$data->arrayGet({$this->value}, {$this->key})";
  }
}