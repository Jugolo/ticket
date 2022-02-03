<?php
namespace Lib\Tempelate\Builder;

use Lib\Tempelate\TempelateBuilder;
use Lib\Tempelate\TempelateParser;
use Lib\Tempelate\TempelateNode;
use Lib\Tempelate\Node\TempelateIncludeNode;
use Lib\Tempelate;
use Lib\Uri;

class TempelateIncludeBuilder implements TempelateBuilder{
  private $tempelate;
  
  public function __construct(Tempelate $tempelate){
    $this->tempelate = $tempelate;
  }
  
  public function build(TempelateParser $parser) : TempelateNode{
    return new TempelateIncludeNode(Uri::getTempelateURIPath("", $parser->getTokenizer()), $parser->getPath(), $parser->getControler(), $this->tempelate, $parser->getStackTrace());
  }
}