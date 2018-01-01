<?php
namespace Lib\Tempelate\Builder;

use Lib\Tempelate\TempelateBuilder;
use Lib\Tempelate\TempelateParser;
use Lib\Tempelate\TempelateNode;
use Lib\Tempelate\Node\TempelateGetStyleNode;

class TempelateGetStyleBuilder implements TempelateBuilder{
  public function build(TempelateParser $parser) : TempelateNode{
    $parser->getTokenizer()->next();
    return new TempelateGetStyleNode($parser);
  }
}