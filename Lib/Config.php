<?php
namespace Lib;

class Config{
  private static $item;
  
  public static function get(string $name) : string{
    self::ensureInit();
    if(!empty(self::$item[$name])){
      return self::$item[$name];
    }
    return "";
  }
  
  private static function ensureInit(){
    if(!self::$item){
      $query = Database::get()->query("SELECT `key`, `value` FROM `config`");
      self::$item = [];
      while($row = $query->fetch()){
        self::$item[$row->key] = $row->value;
      }
    }
  }
}
