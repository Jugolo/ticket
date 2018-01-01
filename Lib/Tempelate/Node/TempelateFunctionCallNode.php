<?php
namespace Lib\Tempelate\Node;

use Lib\Tempelate\TempelateNode;
use Lib\Tempelate\TempelateParser;

class TempelateFunctionCallNode implements TempelateNode{
  private $node;
  private $arg = [];
  
  public function __construct(TempelateParser $parser, TempelateNode $node){
    $this->node = $node;
    $token = $parser->getTokenizer();
    if(!$token->next()->test("PUNCTOR", ")")){
      $this->arg[] = $parser->expresion()->toString();
      while($token->current()->test("PUNCTOR", ",")){
        $token->next();
        $this->arg[] = $parser->expresion()->toString();
      }
      $token->current()->expect("PUNCTOR", ")");
    }
    $token->next();
  }
  
  public function toString() : string{
    $arg = count($this->arg) == 0 ? "" : ", ".implode(", ", $this->arg);
    return "call_user_func(\$data->toFunc({$this->node->toString()}){$arg})";
  }
}