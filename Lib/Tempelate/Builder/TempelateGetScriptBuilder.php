<?php
namespace Lib\Tempelate\Builder;

use Lib\Tempelate\TempelateBuilder;
use Lib\Tempelate\TempelateNode;
use Lib\Tempelate\TempelateParser;
use Lib\Tempelate\Node\TempelateGetScriptNode;

class TempelateGetScriptBuilder implements TempelateBuilder{
  public function build(TempelateParser $parser) : TempelateNode{
    $file = $parser->getFile();
    $line = $parser->getLine();
    $parser->getTokenizer()->next();
    return new TempelateGetScriptNode($file, $line);
  }
}