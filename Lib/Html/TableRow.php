<?php
namespace Lib\Html;

class TableRow{
  private $tag;
  private $value;
  private $clean;
  private $attribute = [];
  
  public function __construct(string $tag, string $value, bool $clean){
    $this->tag = $tag;
    $this->value = $value;
    $this->clean = $clean;
  }
  
  public function __set($key, $value){
    $this->attribute[$key] = $value;
  }
  
  public function output(){
    echo "<".$this->tag.$this->getAttribute().">".($this->clean ? $this->value : htmlentities($this->value))."</".$this->tag.">";
  }
  
  private function getAttribute() : string{
    $str = "";
    foreach($this->attribute as $key => $value){
      $str .= " ".$key."='".$value."'";
    }
    return $str;
  }
}