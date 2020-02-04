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
      $db->query("UPDATE `".DB_PREFIX."config` SET `value`='{$db->escape($value)}' WHERE `name`='{$db->escape($name)}'");
    }else{
      $db->query("INSERT INTO `".DB_PREFIX."config` VALUES ('{$db->escape($name)}', '{$db->escape($value)}');");
    }
    self::$item[$name] = $value;
  }
  
  public static function delete(string $name){
    if(array_key_exists($name, self::$item)){
      $db = Database::get();
      $db->query("DELETE FROM `".DB_PREFIX."config` WHERE `name`='{$db->escape($name)}';");
      unset(self::$item[$name]);
    }
  }
  
  private static function ensureInit(){
    if(!self::$item){
      $query = Database::get()->query("SELECT `name`, `value` FROM `".DB_PREFIX."config`");
      self::$item = [];
      if(!$query){
        Error::systemError("System failed to get config data from database");
      }
      while($row = $query->fetch()){
        self::$item[$row->name] = $row->value;
      }
    }
  }
}