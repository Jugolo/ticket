<?php
namespace Lib\Tempelate\Node;

use Lib\Tempelate\TempelateNode;
use Lib\Tempelate\TempelateParser;

class TempelateGetStyleNode implements TempelateNode{
  private $parser;
  
  public function __construct(TempelateParser $parser){
    $this->parser = $parser;
  }
  
  public function toString() : string{
    return "\$context .= \$data->getStyle('{$this->parser->getFile()}', {$this->parser->getLine()});";
  }
}