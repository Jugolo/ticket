<?php
namespace Lib;

class Report{
  /**
  *Get count of a report catogory 
  *@param string $type the type of report you want to get the count of
  *@return int the count of the types report
  */
  public static function count(string $type) : int{
    self::init();
    return empty($_SESSION["reports"][$type]) ? 0 : count($_SESSION["reports"][$type]);
  }
  
  /**
  *Save okay message to show soner to the user
  *@param $msg the message to show the user
  */
  public static function okay(string $msg){
    self::init();
    self::save("OKAY", $msg);
  }
  
  /**
  *Save error message to show soner to the user
  *@param string $msg the message to save
  */
  public static function error(string $msg){
    self::init();
    self::save("ERROR", $msg);
  }
  
  /**
  *Get the reports as array
  *@return reports as array
  */
  public static function toArray(){
    self::init();
    return $_SESSION["reports"];
  }
  
  /**
  *Append message to tempelate object.
  */
  public static function toTempelate(Tempelate $tempelate){
    self::init();
    if(!empty($_SESSION["reports"]["OKAY"]))
      $tempelate->put("reportOkay", self::htmlenscape($_SESSION["reports"]["OKAY"]));
    
    if(!empty($_SESSION["reports"]["ERROR"]))
      $tempelate->put("reportError", self::htmlenscape($_SESSION["reports"]["ERROR"]));
    unset($_SESSION["reports"]);
  }
  
  /**
  *Unset (delete all reports) leave this as empty report list
  */
  public static function unset(){
    self::init();
    unset($_SESSION["reports"]);
  }
  
  private static function save(string $type, string $message){
    if(!in_array($message, $_SESSION["reports"][$type]))
      $_SESSION["reports"][$type][] = $message;
  }
  
  private static function init(){
    if(empty($_SESSION["reports"])){
      $_SESSION["reports"] = ["OKAY" => [], "ERROR" => []];
    }
  }
  
  private static function htmlenscape(array $msg){
    $data = [];
    foreach($msg as $m){
      $data[] = str_replace([
        "'"
        ], [
        "\\'"
        ], $m);
    }
    return $data;
  }
}