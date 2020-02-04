<?php
namespace Lib\Ext\Page\Cookie;

class CookieListAdd{
  private $data = [];
  
  public function add(string $key, string $value){
    $this->data[$key] = $value;
  }
  
  public function toArray() : array{
    return $this->data;
  }
}