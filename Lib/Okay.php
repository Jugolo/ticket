<?php
namespace Lib;

class Okay{
  public static function count(){
    return empty($_SESSION["okay"]) ? 0 : count($_SESSION["okay"]);
  }
  
  public static function report(string $msg){
    if(empty($_SESSION["okay"])){
      $_SESSION["okay"] = [];
    }
    
    $_SESSION["okay"][] = $msg;
  }
  
  public static function toJavascript(){
    if(self::count() == 0){
      return;
    }
    
    foreach($_SESSION["okay"] as $msg){
      echo "CowTicket.okay('".self::cleanHTML($msg)."');\r\n";
    }
    unset($_SESSION["okay"]);
  }
  
  public static function toArray(){
    $array = self::count() == 0 ? [] : $_SESSION["okay"];
    unset($_SESSION["okay"]);
    return $array;
  }
  
  private static function cleanHTML(string $msg){
    return str_replace("'", "\\'", $msg);
  }
}