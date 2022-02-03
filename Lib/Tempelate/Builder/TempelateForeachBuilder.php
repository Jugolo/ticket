<?php
namespace Lib\Tempelate\Builder;

use Lib\Tempelate\TempelateBuilder;
use Lib\Tempelate\TempelateParser;
use Lib\Tempelate\TempelateNode;
use Lib\Tempelate\Node\TempelateForeachNode;

class TempelateForeachBuilder implements TempelateBuilder{
  public function build(TempelateParser $parser) : TempelateNode{
    $token = $parser->getTokenizer();
    $token->next();
    $exp   = $parser->expresion();
    $token->current()->expect("KEYWORD", "as");
    $token->next()->expect("IDENTIFY");
    $identify = $token->current()->getContext();
    $val = "";
    if(!$token->next()->test("ECB")){
      $token->current()->expect("PUNCTOR", ":");
      $token->next()->expect("IDENTIFY");
      $val = $token->current()->getContext();
      $token->next();
    }
    $token->current()->expect("ECB");
    $parser->setFlag(TempelateParser::OUTLINE_CODE);
    $block = $parser->getBlock();
    return new TempelateForeachNode($exp, $identify, $val, $block);
  }
}