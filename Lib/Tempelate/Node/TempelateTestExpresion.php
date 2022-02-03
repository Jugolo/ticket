<?php
namespace Lib\Tempelate\Node;

use Lib\Tempelate\TempelateNode;

class TempelateTestExpresion implements TempelateNode{
  private $test, $t, $f;
  
  public function __construct(TempelateNode $test, TempelateNode $true, TempelateNode $false){
    $this->test = $test;
    $this->t    = $true;
    $this->f    = $false;
  }
  
  public function toString(){
    return $this->test->toString()." ? ".$this->t->toString()." : ".$this->f->toString();
  }
}