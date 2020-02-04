<?php
namespace Lib\Tempelate\Node;

use Lib\Tempelate\TempelateNode;

class TempelateElseNode implements TempelateNode{
  private $block;
  
  public function __construct(string $block){
    $this->block = $block;
  }
  
  public function toString() : string{
    return "{
      {$this->block}
    }";
  }
}