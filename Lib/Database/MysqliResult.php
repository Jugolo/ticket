<?php
namespace Lib\Database;

class MysqliResult{
  private $mysqli;
  private $result;
  
  public function __construct(\mysqli $mysqli, \mysqli_result $result){
    $this->mysqli = $mysqli;
    $this->result = $result;
  }
  
  public function count(){
    return $this->result->num_rows;
  }
  
  public function fetch(){
    $result = $this->result->fetch_assoc();
    if(!$result){
      return null;
    }
    
    return new DatabaseFetch($result);
  }
  
  public function render($callback){
    while($row = $this->fetch()){
      call_user_func($callback, $row);
    }
  }
  
  public function free(){
    $this->result->free();
  }
}
