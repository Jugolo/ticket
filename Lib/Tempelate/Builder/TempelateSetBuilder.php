<?php
namespace Lib\Tempelate\Builder;

use Lib\Tempelate\TempelateBuilder;
use Lib\Tempelate\TempelateNode;
use Lib\Tempelate\TempelateParser;
use Lib\Tempelate\Node\TempelateSetNode;

class TempelateSetBuilder implements TempelateBuilder{
  public function build(TempelateParser $parser) : TempelateNode{
    $token = $parser->getTokenizer();
    $token->next()->expect("IDENTIFY");
    $variabel = $token->current()->getContext();
    $token->next()->expect("PUNCTOR", "=");
    $token->next();
    return new TempelateSetNode($variabel, $parser->expresion());
  }
}