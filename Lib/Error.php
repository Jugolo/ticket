<?php
namespace Lib;

class Error{
  public static function count() : int{
    return empty($_SESSION["error"]) ? 0 : count($_SESSION["error"]);
  }
  
  public static function report(string $msg){
    if(empty($_SESSION["error"])){
      $_SESSION["error"] = [];
    }
    
    $_SESSION["error"][] = $msg;
  }
  
  public static function toJavascript(){
    if(empty($_SESSION["error"])){
      return;
    }
    
    foreach($_SESSION["error"] as $msg){
      echo "CowTicket.error('".self::cleanHTML($msg)."');\r\n";
    }
    unset($_SESSION["error"]);
  }
  
  public static function toArray(){
    if(empty($_SESSION["error"])){
      return [];
    }
    $array = $_SESSION["error"];
    unset($_SESSION["error"]);
    return $array;
  }
  
  private static function cleanHTML(string $msg) : string{
    return str_replace("'", "\\'", $msg);
  }
}