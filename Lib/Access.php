<?php
namespace Lib;

use Lib\Cache;
use Lib\Database;

class Access{
  private static $cache = [];
  
  public static function deleteAccesses(int $gid, array $accesses){
    $sql = [];
    $db = Database::get();
    foreach($accesses as $access)
      $sql[] = "`gid`='{$gid}' AND `name`='{$db->escape($access)}'";
    $db->query("DELETE FROM `access` WHERE ".implode(" OR ", $sql).";");
    if(Cache::exists("access_".$gid))
      Cache::delete("access_".$gid);
  }
  
  public static function appendAccesses(int $gid, array $accesses){
    $sql = [];
    $db = Database::get();
    foreach($accesses as $access)
      $sql[] = "('{$gid}', '{$db->escape($access)}')";
    $db->query("INSERT INTO `access` VALUES ".implode(", ", $sql).";");
    if(Cache::exists("access_".$gid))
      Cache::delete("access_".$gid);
  }
  
  public static function getRawAccess(int $gid){
    self::ensureData($gid);
    return !array_key_exists($gid, self::$cache) ? null : self::$cache[$gid];
  }
  
  public static function userHasAccesses(array $keys) : bool{
    if(!defined("user"))
      return false;
    foreach($keys as $key){
      if(self::hasAccess(user["groupid"], $key))
        return true;
    }
    return false;
  }
  
  public static function deleteGroupAccess(int $id){
    //delete saved cache
    if(Cache::exists("access_".$id))
      Cache::delete("access_".$id);
    Database::get()->query("DELETE FROM `access` WHERE `gid`='{$id}';");
  }
  
  public static function userHasAccess(string $key) : bool{
    if(!defined("user"))
      return false;
    return self::hasAccess(user["groupid"], $key);
  }
  
  public static function hasAccess(int $gid, string $key) : bool{
    self::ensureData($gid);
    return !empty(self::$cache[$gid]) && in_array($key, self::$cache[$gid]);
  }
  
  private static function ensureData(int $gid){
    if(!empty(self::$cache[$gid]))
      return;
    
    if(Cache::exists("access_".$gid)){
       self::$cache[$gid] = Cache::get("access_".$gid);
      return;
    }
    
    $query = Database::get()->query("SELECT `name` FROM `access` WHERE `gid`='{$gid}'");
    self::$cache[$gid] = [];
    while($row = $query->fetch()){
      self::$cache[$gid][] = $row->name;
    }
    Cache::create("access_".$gid, self::$cache[$gid]);
  }
}