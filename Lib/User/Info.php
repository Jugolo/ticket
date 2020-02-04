<?php
namespace Lib\User;

use Lib\Database;

class Info{
  public static function getUsername(int $id) : string{
    $data = Database::get()->query("SELECT `username` FROM `".DB_PREFIX."user` WHERE `id`='{$id}';")->fetch();
    return $data ? $data->username : "";
  }
}