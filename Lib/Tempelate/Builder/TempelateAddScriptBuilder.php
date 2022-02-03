<?php
namespace Lib\Tempelate\Builder;

use Lib\Tempelate\TempelateBuilder;
use Lib\Tempelate\TempelateParser;
use Lib\Tempelate\TempelateNode;
use Lib\Tempelate\Node\TempelateAddScriptNode;
use Lib\Uri;

class TempelateAddScriptBuilder implements TempelateBuilder{
  public function build(TempelateParser $parser) : TempelateNode{
    return new TempelateAddScriptNode(Uri::getTempelateURIPath($parser->getPath(), $parser->getTokenizer()));
  }
}