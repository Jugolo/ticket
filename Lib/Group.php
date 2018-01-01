<?php
namespace Lib;

class Group{
  public static function create(string $name) : int{
    if(in_array($name, self::getGroupNames()))
      return -1;
    $db = Database::get();
    return $db->query("INSERT INTO `group` VALUES (NULL, '{$db->escape($name)}');");
  }
  
  public static function delete(int $id){
    $db = Database::get();
    //first wee ensure no user stay width out a group.
    $db->query("UPDATE `user` SET `groupid`='".Config::get("standart_group")."' WHERE `groupid`='{$id}'");
    //wee delete all access to the deleted group
    Access::deleteGroupAccess($id);
    //now we can delete the group
    $db->query("DELETE FROM `group` WHERE `id`='{$id}'");
  }
  
  public static function getGroupNames() : array{
    $query = Database::get()->query("SELECT `name` FROM `group`");
    $names = [];
    while($row = $query->fetch())
      $names[] = $row->name;
    return $names;
  }
}