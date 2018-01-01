<?php
namespace Lib\Tempelate;

class TempelateDatabase{
  private $data = [];
  
  public function put(array $data){
    $this->data = array_merge($this->data, $data);
  }
  
  public function getRaw(string $key){
    return array_key_exists($key, $this->data) ? $this->data[$key] : "";
  }
  
  public function toArray($value) : array{
    return is_array($value) ? $value : [];
  }
}