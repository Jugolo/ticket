<?php
namespace Lib\Tempelate\Node;

use Lib\Tempelate\TempelateNode;

class TempelateNumberNode implements TempelateNode{
  private $number;
  
  public function __construct($number){
    $this->number = $number;
  }
  
  public function toString() : string{
    return $this->number;
  }
}