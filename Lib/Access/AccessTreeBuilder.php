<?php
namespace Lib\Access;

use Lib\Tempelate;
use Lib\Language\Language;

class AccessTreeBuilder{
  private $tree = [];
  
  public function createCategory(string $cat){
    if(!array_key_exists($cat, $this->tree))
      $this->tree[$cat] = [];
  }
  
  public function setItem(string $cat, string $access, string $dec){
    if(array_key_exists($cat, $this->tree))
      $this->tree[$cat][] = ["access" => $access, "dec" => Language::get($dec)];
  }
  
  public function setTempelate(Tempelate $tempelate){
    $tempelate->put("cat", $this->tree);
  }
  
  public function accessKeys() : array{
    $keys = [];
    foreach($this->tree as $value){
      for($i=0;$i<count($value);$i++)
        $keys[] = $value[$i]["access"];
    }
    return $keys;
  }
}