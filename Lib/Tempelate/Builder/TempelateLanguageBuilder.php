<?php
namespace Lib\Tempelate\Builder;

use Lib\Tempelate\TempelateBuilder;
use Lib\Tempelate\TempelateParser;
use Lib\Tempelate\TempelateNode;
use Lib\Tempelate\Node\TempelateLanguageNode;
use Lib\Exception\TempelateException;

class TempelateLanguageBuilder implements TempelateBuilder{
  public function build(TempelateParser $parser) : TempelateNode{
    $token = $parser->getTokenizer();
    if($token->next()->getType() != "IDENTIFY" && $token->current()->getType() != "STRING")
      throw new TempelateException("After language keyword there must be a identify or string");
    $key = $token->current()->getContext();
    
    $keys = [];
    if(!$token->next()->test("ECB")){
      $keys[] = $parser->expresion()->toString();
      while($token->current()->test("PUNCTOR", ",")){
        $token->next();
        $keys[] = $parser->expresion()->toString();
      }
      $token->current()->expect("ECB");
    }
    
    return new TempelateLanguageNode($key, $keys);
  }
}