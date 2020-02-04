<?php
namespace Lib\Tempelate\Builder;

use Lib\Tempelate\TempelateBuilder;
use Lib\Tempelate\TempelateParser;
use Lib\Tempelate\TempelateNode;
use Lib\Tempelate\Node\TempelateIfNode;
use Lib\Tempelate\Node\TempelateElseNode;


class TempelateIfBuilder implements TempelateBuilder{
  public function build(TempelateParser $parser) : TempelateNode{
    return $this->get($parser);
  }
  
  private function get(TempelateParser $parser) : TempelateNode{
    $token = $parser->getTokenizer();
    $token->next();
    $exp = $parser->expresion();
    if($token->current()->test("ECB")){
      $parser->setFlag(TempelateParser::OUTLINE_CODE);
      $block = $parser->getBlock(["elseif", "else"]);
      if($token->current()->test("KEYWORD", "elseif")){
        return new TempelateIfNode($exp, $block, $this->get($parser));
      }elseif($token->current()->test("KEYWORD", "else")){
        return new TempelateIfNode($exp, $block, $this->getElse($parser));
      }
      
      return new TempelateIfNode($exp, $block, null);
    }else
      return new TempelateIfNode($exp, $parser->expresion()->toString(), null);
  }
  
  private function getElse(TempelateParser $parser){
    $token = $parser->getTokenizer()->next();
    if(!$token->test("ECB"))
      throw new TempelateException("After else there must be a end code block after else");
    $parser->setFlag(TempelateParser::OUTLINE_CODE);
    
    return new TempelateElseNode($parser->getBlock());
  }
}