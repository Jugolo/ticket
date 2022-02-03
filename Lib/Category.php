<?php
namespace Lib;

use Lib\Exception\CategoryNotFound;
use Lib\Plugin\Plugin;
use Lib\Email;

class Category{
  public static function getNames() : array{
    if(Cache::exists("category_names"))
      return Cache::get("category_names");
    $names = [];
    $query = Database::get()->query("SELECT `name` FROM `".DB_PREFIX."catogory`");
    while($row = $query->fetch())
      $names[] = $row->name;
    Cache::create("category_names", $names);
    return $names;
  }
  
  public static function getNameFromId(int $id){
    $db = Database::get();
    $row = $db->query("SELECT `name` FROM `".DB_PREFIX."catogory` WHERE `id`='{$db->escape($id)}'")->fetch();
    return $row ? $row->name : "";
  }
  
  public static function create(string $name, bool $isOpen = false, int $age = -1){
    $names = self::getNames();
    if(in_array($name, $names))
       return;
    if(Plugin::trigger_event("system.category.create", $name, $isOpen, $age)){
      $db = Database::get();
      $id = $db->query("INSERT INTO `".DB_PREFIX."catogory` VALUES (NULL, '{$db->escape($name)}', ".($isOpen ? "1" : "0").", ".($age == -1 ? "NULL" : "'{$age}'").", 0, 0, ".count($names).");");
      Log::system("LOG_CAT_CREATED", defined("user") ? user["username"] : "unknown", $name);
      if(Cache::exists("category_names"))
        Cache::delete("category_names");
      return $id;
    }
  }
  
  public static function delete(int $id) : bool{
    $db = Database::get();
    $data = $db->query("SELECT * FROM `".DB_PREFIX."catogory` WHERE `id`='".$id."'")->fetch();
    if(!$data){
      throw new CategoryNotFound();
    }
    if(!Plugin::trigger_event("system.category.delete", $data->id, $data->name))
      return false;
    
    if($data->open == 1){
      Config::set("cat_open", (int)Config::get("cat_open")-1);
    }
    
    if(Cache::exists("category_names"))
      Cache::delete("category_names");
    
    $db->query("DELETE FROM `".DB_PREFIX."ticket_track` WHERE `cid`='{$id}'");
    $db->query("DELETE FROM `".DB_PREFIX."ticket_value` WHERE `cid`='{$id}'");
    $db->query("DELETE FROM `".DB_PREFIX."ticket` WHERE `cid`='{$id}'");
    $db->query("DELETE FROM `".DB_PREFIX."comment` WHERE `cid`='{$id}'");
    $db->query("DELETE FROM `".DB_PREFIX."catogory` WHERE `id`='{$id}'");
    $db->query("DELETE FROM `".DB_PREFIX."category_item` WHERE `cid`='{$id}'");
    return true;
  }
}