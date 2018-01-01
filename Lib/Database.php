<?php
namespace Lib;

use Lib\Database\DatabaseDriver;
use Lib\Language\Language;

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
    
    if(!defined("IN_SETUP") && !defined("db_driver")){
      trigger_error(Language::get("DB_DRIVER"), E_USER_ERROR);
      return false;
    }
    $driver = defined("IN_SETUP") ? $_SESSION["setup"]["db_driver"] : db_driver;
    if(!file_exists(BASE."Lib/Database/".$driver.".php")){
      trigger_error(Language::get("UNKNOWN_DB_DRIVER", $driver), E_USER_ERROR);
      return false;
    }
    
    $name = "Lib\\Database\\".$driver;
    $driver = new $name();
    if($driver instanceof DatabaseDriver){
      self::$db = $driver;
      return true;
    }
    
    trigger_error(Language::get("INVALID_DB_DRIVER", get_class($driver)));
    return false;
  }
}