<?php
namespace Lib\Database;

class DatabaseFetch{
  private $arg = [];
  
  public function __construct(array $data){
    $this->arg = $data;
  }
  
  public function get(string $key){
    if(array_key_exists($key, $this->arg)){
      return $this->arg[$key];
    }
    return "";
  }
  
  public function __get($key){
    return $this->get($key);
  }
  
  public function toArray() : array{
    return $this->arg ? : [];
  }
}
