<?php
namespace Lib\Html;

class Table{
  private $item      = [];
  private $className = null;
  private $attribute = [];
  
  public function __set($key, $value){
    $this->attribute[$key] = $value;
  }
  
  public function newColummen(){
    $this->item[] = [];
  }
  
  public function className(string $name){
    $this->className = $name;
  }
  
  public function th(string $value, bool $clean = false){
    if(count($this->item) == 0){
      trigger_error("Use Table->newColumen to be allow to use Table->th()", E_USER_ERROR);
      return;
    }
    return $this->item[count($this->item)-1][] = new TableRow("th", $value, $clean);
  }
  
  public function td(string $value, bool $clean = false){
    if(count($this->item) == 0){
      trigger_error("Use Table->newColumen() to be allow to use Table->td()", E_USER_ERROR);
      return;
    }
    return $this->item[count($this->item)-1][] = new TableRow("td", $value, $clean);
  }
  
  public function output(){
    if(count($this->item) == 0){
      return;
    }
    echo "<table".($this->className ? " class='".$this->className."'" : "").$this->getAttribute().">";
    foreach($this->item as $col){
      echo "<tr>";
      foreach($col as $row){
        $row->output();
      }
      echo "</tr>";
    }
    echo "</table>";
  }
  
  private function getAttribute() : string{
    $buffer = "";
    foreach($this->attribute as $key => $value){
      $buffer .= " ".$key."='".$value."'";
    }
    return $buffer;
  }
}