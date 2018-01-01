<?php
namespace Lib\Tempelate\Builder;

use Lib\Tempelate\TempelateBuilder;
use Lib\Tempelate\TempelateParser;
use Lib\Tempelate\TempelateNode;
use Lib\Tempelate\Tokenizer;
use Lib\Tempelate\Node\TempelatePluginNode;

class TempelatePluginBuilder implements TempelateBuilder{
  public function build(TempelateParser $parser) : TempelateNode{
    return new TempelatePluginNode($this->getData($parser->getTokenizer()));
  }
  
  private function getData(Tokenizer $token){
    $token->next()->expect("IDENTIFY");
    $data = [$token->current()->getContext()];
    while($token->next()->test("PUNCTOR", ".")){
      $token->next()->expect("IDENTIFY");
      $data[] = $token->current()->getContext();
    }
    return "tempelate_".implode("_", $data);
  }
}