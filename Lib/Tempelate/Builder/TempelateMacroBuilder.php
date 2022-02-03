<?php
namespace Lib\Tempelate\Builder;

use Lib\Tempelate\TempelateParser;
use Lib\Tempelate\TempelateBuilder;
use Lib\Tempelate\TempelateNode;
use Lib\Tempelate\Node\TempelateMacroNode;

class TempelateMacroBuilder implements TempelateBuilder{
  public function build(TempelateParser $parser) : TempelateNode{
    $token = $parser->getTokenizer();
    $token->next()->expect("IDENTIFY");
    $name = $token->current()->getContext();
    $token->next()->expect("PUNCTOR", "(");
    $args = [];
    if(!$token->next()->test("PUNCTOR", ")")){
      $token->current()->expect("IDENTIFY");
      $args[] = $token->current()->getContext();
      while($token->next()->test("PUNCTOR", ",")){
        $token->next()->expect("IDENTIFY");
        $args[] = $token->current()->getContext();
      }
      $token->current()->expect("PUNCTOR", ")");
    }
    
    $token->next()->expect("ECB");
    $parser->setFlag(TempelateParser::OUTLINE_CODE);
    return new TempelateMacroNode($name, $args, $parser->getBlock());
  }
}