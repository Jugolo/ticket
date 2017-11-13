<?php
namespace Lib\Tempelate;

class ArrayGetNode implements TempelateNode{
  private $array;
  private $key;
  
  public function __construct(TempelateNode $array, TempelateNode $key){
    $this->array = $array;
    $this->key   = $key;
  }
  
  public function toCode() : string{
    return "\$db->arrayGet({$this->array->toCode()}, {$this->key->toCode()})";
  }
}