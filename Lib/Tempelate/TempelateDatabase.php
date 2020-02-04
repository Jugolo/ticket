<?php
namespace Lib\Tempelate;

class TempelateDatabase{
  private $data = [];
  private $db;
  
  public function __construct(?TempelateDatabase $db = null){
    $this->db = $db;
  }
  
  public function put(array $data){
    $this->data = array_merge($this->data, $data);
  }
  
  public function getRaw(string $key){
    return array_key_exists($key, $this->data) ? $this->data[$key] : ($this->db ? $this->db->getRaw($key) : "");
  }
  
  public function toArray($value) : array{
    return is_array($value) ? $value : [];
  }
  
  public function has(string $key) : bool{
    if(array_key_exists($key, $this->data))
      return $this->data[$key];
    
    if($this->db)
      return $this->db->has($key);
    
    return false;
  }
}