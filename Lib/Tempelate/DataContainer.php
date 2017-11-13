<?php
namespace Lib\Tempelate;

class DataContainer{
  private $data = [];
  
  public function put(string $name, $data){
    $this->data[$name] = $data;
  }
  
  public function arrayGet($data, string $key){
    if(!is_array($data))
      return null;
    if(!array_key_exists($key, $data))
      return null;
    return $data[$key];
  }
  
  public function getArray(string $name) : array{
    if(empty($this->data[$name]))
      return [];
    
    if(!is_array($this->data[$name]))
      return [$this->data[$name]];
    
    return $this->data[$name];
  }
  
  public function toArray($data){
    return is_array($data) ? $data : [];
  }
  
  public function getString(string $name) : string{
    if(empty($this->data[$name]) || !is_string($this->data[$name]))
      return "";
    return $this->data[$name];
  }
  
  public function escapePrint(string $name) : string{
    if(empty($this->data[$name]))
      return "";
    return htmlentities(strval($this->data[$name]));
  }
  
  public function getRaw(string $key){
    if(empty($this->data[$key]))
      return null;
    return $this->data[$key];
  }
}