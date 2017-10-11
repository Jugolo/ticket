<?php
namespace Lib\User;

class Info{
  public static function userLink(int $id, string $name){
    if(getUsergroup(user["groupid"])["showProfile"] == 1)
      return "<a href='?view=profile&user={$id}'>{$name}</a>";
    return $name;
  }
}