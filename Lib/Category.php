<?php
namespace Lib;

use Lib\Exception\CategoryNotFound;
use Lib\Plugin\Plugin;
use Lib\Email;

class Category{
  public static function getNames() : array{
    if(Cache::exists("category_names"))
      return json_decode(Cache::get("category_names"), true);
    $names = [];
    $query = Database::get()->query("SELECT `name` FROM `catogory`");
    while($row = $query->fetch())
      $names[] = $row->name;
    Cache::create("category_names", json_encode($names));
    return $names;
  }
  
  public static function getNameFromId(int $id){
    $db = Database::get();
    $row = $db->query("SELECT `name` FROM `catogory` WHERE `id`='{$db->escape($id)}'")->fetch();
    return $row ? $row->name : "";
  }
  
  public static function create(string $name, bool $isOpen = false, int $age = -1){
    if(in_array($name, self::getNames()))
       return;
    $db = Database::get();
    $id = $db->query("INSERT INTO `catogory` VALUES (NULL, '{$db->escape($name)}', ".($isOpen ? "1" : "0").", ".($age == -1 ? "NULL" : "'{$age}'").");");
    if(Cache::exists("category_names"))
      Cache::delete("category_names");
    return $id;
  }
  
  public static function delete(int $id){
    $db = Database::get();
    $data = $db->query("SELECT * FROM `catogory` WHERE `id`='".$id."'")->fetch();
    if(!$data){
      throw new CategoryNotFound();
      Report::error("No catogroy found to delete");
      return;
    }
    if($data->open != 0){
      Config::set("cat_open", intval(Config::get("cat_open"))-1);
    }
    if(Cache::exists("category_names"))
      Cache::delete("category_names");
    Log::system("%s deletede the category '%s'", defined("user") ? user["username"] : "unknown", $data->name);
    Plugin::trigger_event("system.category.delete", $data);
  }
}