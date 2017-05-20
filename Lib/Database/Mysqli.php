<?php
namespace Lib\Database;

class Mysqli extends DatabaseDriver{
  private $db;
  
  public function __construct(){
    $this->db = new \mysqli(DB_HOST, DB_USER, DB_PASS, DB_TABLE);
    if(!$this->hasError()){
      $this->db->set_charset("utf8"); 
    }else{
      exit("Database error");
    }
  }
  
  public function close(){
    $this->db->close();
  }
  
  public function query(string $query){
    if(!defined("ERROR") && $this->hasError()){
      return false;
    }
    
    $q = $this->db->query($query);
    if(!$q && $this->hasError()){
      $this->report();
    }
    if(is_bool($q)){
      if($q && strpos($query, "INSERT INTO") === 0){
        return $this->db->insert_id;
      }
      return $q;
    }
    
    return new MysqliResult($this->db, $q);
  }
  
  public function multi_query(string $query) : bool{
    if($this->db->multi_query($query)){
      while($this->db->more_results() && $this->db->next_result()){
        $this->db->store_result();
      }
      return true;
    }
    $this->report();
    return false;
  }
  
  public function hasError() : bool{
    return $this->db->connect_error || $this->db->error;
  }
  
  private function report(){
    if($this->db->connect_error){
      trigger_error($this->db->connect_error, E_USER_ERROR);
    }elseif($this->db->error){
      trigger_error($this->db->error, E_USER_ERROR);
    }
  }
  
  public function escape(String $str) : string{
    if(!$this->hasError()){
      return $this->db->real_escape_string($str);
    }
    
    return "";
  }
  
  public function affected(){
    return $this->db->affected_rows;
  }
}
