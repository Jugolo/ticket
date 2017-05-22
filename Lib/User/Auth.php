<?php
namespace Lib\User;

use Lib\Database;

class Auth{
  public static function controleDetail(string $username, string $email){
    $db = Database::get();
    $query = $db->query("SELECT LOWER(`username`) AS username, LOWER(`email`) AS email
                         FROM `user` 
                         WHERE (LOWER(`email`)='{$db->escape(strtolower($email))}' OR LOWER(`username`)='{$db->escape(strtolower($username))}')
                         ".(defined("user") ? " AND `id`<>'".user["id"]."'" : ""));
    if($query->count() != 0){
      $row = $query->fetch();
      $query->free();
      return $row->username == strtolower($username) ? "Username" : "Email";
    }
    return null;
  }
  
  public static function randomString(int $length) : string{
    $buffer = "";
    for($i=0;$i<$length;$i++){
      $buffer .= chr(mt_rand(33, 126));
    }
    return $buffer;
  }
  
  public static function salt_password(string $password, string $salt){
    return sha1($salt.$password.$salt);
  }
}