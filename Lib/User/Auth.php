<?php
namespace Lib\User;

use Lib\Database;
use Lib\Ext\Notification\Notification;
use Lib\Config;

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
  
  public static function createUser(string $username, string $raw_password, string $email, bool $isActivated){
    $salt = self::randomString(200);
    $gid = getStandartGroup()["id"];
    $db = Database::get();
    $id = $db->query("INSERT INTO `user` (
        `username`,
        `password`,
        `email`,
        `salt`,
        `isActivatet`,
        `groupid`
      ) VALUES (
        '".$db->escape($username)."',
        '".$db->escape(self::salt_password($raw_password, $salt))."',
        '".$db->escape($email)."',
        '".$db->escape($salt)."',
        '".($isActivated ? '1' : '0')."',
        '".Config::get("standart_group")."'
      );");
    Notification::getNotification(function(string $name) use($db, $id){
        $db->query("INSERT INTO `notify_setting` VALUES ('{$id}', '{$db->escape($name)}');");
    });
    if(!$isActivated){
     mail($email, "Please activate you new account", "Hallo ".$username."
You has just create an account and to be sure this email is belong to you, you need to confirm it with visit the link below.
If you dont has create an account you dont need to do anythink. 
".geturl()."?salt=".urlencode($salt)."&email=".urlencode($email)."
Best regards from us", implode("\r\n", [
        "MIME-Version: 1.0",
        "Content-Type: text/plain; charset=utf8",
        "from:support@".$_SERVER["SERVER_NAME"],
        ])); 
    }
    return $id;
  }
}