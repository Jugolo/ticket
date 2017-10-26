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
  
  public static function set(string $name, string $value){
    $db = Database::get();
    if(array_key_exists($name, self::$item)){
      $db->query("UPDATE `config` SET `value`='{$db->escape($value)}' WHERE `name`='{$db->escape($name)}'");
    }else{
      $db->query("INSERT INTO `config` VALUES ('{$db->escape($name)}', '{$db->escape($value)}');");
    }
    self::$item[$name] = $value;
  }
  
  private static function ensureInit(){
    if(!self::$item){
      $query = Database::get()->query("SELECT `name`, `value` FROM `config`");
      self::$item = [];
      if(!$query){
        return;
      }
      while($row = $query->fetch()){
        self::$item[$row->name] = $row->value;
      }
    }
  }
}