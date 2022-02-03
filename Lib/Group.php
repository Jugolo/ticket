<?php
namespace Lib;

class Group{
  public static function create(string $name) : int{
    if(in_array($name, self::getGroupNames()))
      return -1;
    $db = Database::get();
    return $db->query("INSERT INTO `".DB_PREFIX."group` VALUES (NULL, '{$db->escape($name)}');");
  }
  
  public static function delete(int $id){
    $db = Database::get();
    //first wee ensure no user stay width out a group.
    $db->query("DELETE FROM `".DB_PREFIX."grup_member` WHERE `gid`='".$id."'");
    //wee delete all access to the deleted group
    $db->query("DELETE FROM `".DB_PREFIX."access` WHERE `gid`='".$id."'");
    //now we can delete the group
    $db->query("DELETE FROM `".DB_PREFIX."group` WHERE `id`='{$id}'");
  }
  
  public static function getGroupNames() : array{
    $query = Database::get()->query("SELECT `name` FROM `".DB_PREFIX."group`");
    $names = [];
    while($row = $query->fetch())
      $names[] = $row->name;
    return $names;
  }
  
  private $data;
  
  public function __construct(int $id){
    
  }
}
