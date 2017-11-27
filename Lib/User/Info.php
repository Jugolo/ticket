<?php
namespace Lib\User;

use Lib\Database;

class Info{
  public static function userLink(int $id, string $name){
    if(getUsergroup(user["groupid"])["showProfile"] == 1)
      return "<a href='?view=profile&user={$id}'>{$name}</a>";
    return $name;
  }
  
  public static function getUsername(int $id) : string{
    $data = Database::get()->query("SELECT `username` FROM `user` WHERE `id`='{$id}';")->fetch();
    return $data ? $data->username : "";
  }
}