<?php
function two_container(string $first, string $two, array $options = []) : string{
  $tag = !empty($options["tag"]) ? $option["tag"] : "span";
  $tag2class = !empty($options["tag2class"]) ? " class='".$options["tag2class"]."'" : "";
  return "<div class='two_container'><{$tag}>{$first}</{$tag}><{$tag}{$tag2class}>{$two}</{$tag}></div>";
}

function html_okay(string $message){
  if(empty($_SESSION["okay"])){
    $_SESSION["okay"] = [];
  }
  $_SESSION["okay"][] = $message;
}

function html_okay_count(){
  return !empty($_SESSION["okay"]) ? count($_SESSION["okay"]) : 0;
}

function html_error(string $message){
  if(empty($_SESSION["error"])){
     $_SESSION["error"] = [];
  }
  $_SESSION["error"][] = $message;
}

function html_error_count(){
  return empty($_SESSION["error"]) ? 0 : count($_SESSION["error"]);
}

function getHtmlError(){
  function cleanHtmlError(string $msg) : string{
    return str_replace("'", "\\'", $msg);
  }
  if(!empty($_SESSION["error"])){
    foreach($_SESSION["error"] as $error){
      echo "CowTicket.error('".cleanHtmlError($error)."');";
    }
    unset($_SESSION["error"]);
  }
}

function getHtmlOkay(){
  if(!empty($_SESSION["okay"])){
    foreach($_SESSION["okay"] as $okay)
      echo "CowTicket.okay('{$okay}');";
    unset($_SESSION["okay"]);
  }
}

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
