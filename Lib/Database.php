<?php
namespace Lib;

use Lib\Database\DatabaseDriver;

class Database{
  private static $db;
  
  public static function isInit(){
    return self::$db !== null;
  }
  
  public static function get(){
    if(self::controle()){
      return self::$db;
    }
    return null;
  }
  
  private static function controle() : bool{
    if(self::$db){
      return true;
    }
    
    if(!defined("db_driver")){
      trigger_error("Missing defined value 'db_driver' to tell wich database driver to use", E_USER_ERROR);
      return false;
    }
    
    if(!file_exists(BASE."Lib/Database/".db_driver.".php")){
      trigger_error("Missing database driver '".db_driver."'", E_USER_ERROR);
      return false;
    }
    
    $name = "Lib\\Database\\".db_driver;
    $driver = new $name();
    if($driver instanceof DatabaseDriver){
      self::$db = $driver;
      return true;
    }
    
    trigger_error(get_class($driver)." is not instance of DatabaseDriver");
    return false;
  }
}