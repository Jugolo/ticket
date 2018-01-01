<?php
namespace Lib\Tempelate\Builder;

use Lib\Tempelate\TempelateBuilder;
use Lib\Tempelate\TempelateNode;
use Lib\Tempelate\TempelateParser;
use Lib\Tempelate\Node\TempelateAddCssNode;
use Lib\Uri;

class TempelateAddCssBuilder implements TempelateBuilder{
  public function build(TempelateParser $parser) : TempelateNode{
    global $tempelate;
    $token = $parser->getTokenizer();
    if($token->next()->test("PUNCTOR", "@")){
      $token->next();
      $dir = BASE."Lib/Tempelate/Style/{$tempelate->getMainName()}/";
    }else{
      $dir = $parser->getPath();
    }
    return new TempelateAddCssNode(Uri::getTempelateURIPath($dir, $token, false));
  }
}