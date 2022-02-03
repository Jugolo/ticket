<?php
namespace Lib\Database;

class MysqliResult implements DatabaseResult{
  private $mysqli;
  private $result;
  
  public function __construct(\mysqli $mysqli, \mysqli_result $result){
    $this->mysqli = $mysqli;
    $this->result = $result;
  }
  
  public function count(){
    return $this->result->num_rows;
  }
  
  public function fetch($callback = null){
    if(is_callable($callback)){
      while($row = $this->result->fetch_array(MYSQLI_NUM)){
        call_user_func_array($callback, $row);
      }
      return null;
    }
    $result = $this->result->fetch_assoc();
    if(!$result){
      return null;
    }
    
    return new DatabaseFetch($result);
  }
  
  public function render($callback, ...$arg){
    while($row = $this->fetch()){
      call_user_func_array($callback, array_merge([$row], $arg));
    }
  }
  
  public function free(){
    $this->result->free();
  }
}