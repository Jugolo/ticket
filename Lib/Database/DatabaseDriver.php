<?php
namespace Lib\Database;

abstract class DatabaseDriver{
  public abstract function query(string $query);
  public abstract function multi_query(string $query) : bool;
  public abstract function hasError() : bool;
  public abstract function escape(string $str) : string;
  public abstract function close();
  
  public function sql_if(string $case, string $left, string $right){
    return "CASE WHEN ".$case." THEN ".$left." ELSE ".$right." END";
  }
  
  public function render(string $sql, $callable){
    $query = $this->query($sql);
    if($query){
      while($row = $query->fetch()){
        call_user_func($callable, $row);
      }
    }
  }
}
