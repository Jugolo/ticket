<?php
namespace Lib\User;

use Lib\Database;
use Lib\Ext\Notification\Notification;
use Lib\Config;
use Lib\Email;
use Lib\Report;
use Lib\Ajax;
use Lib\Plugin\Plugin;

class Auth{
  
  public static function controleAuth(){
    if(!self::autologin()){
      if(!empty($_POST["login"]))
        self::doLogin();
      elseif(!empty($_POST["createaccount"]))
        self::doCreate();
      elseif(!empty($_GET["salt"]) && !empty($_GET["email"]))
        self::doActivate();
    }
  }
  
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
      $emails = new Email();
      $emails->pushArg("username", $username);
      $emails->pushArg("link", geturl()."?salt=".urlencode($salt)."&email=".urlencode($email));
      $emails->send("account_create", $email);
    }
    return $id;
  }
  
  public static function deleteUser(int $id){
    $db = Database::get();
    $db->query("DELETE FROM `comment` WHERE `uid`=".$id);
    $db->query("SELECT `id` FROM `ticket` WHERE `uid`='".$id."'")->fetch(function($id){
      Plugin::trigger_event("system.ticket.delete", $id);
    });
    $db->query("DELETE FROM `user` WHERE `id`='".$id."'");
    $db->query("DELETE FROM `notify_setting` WHERE `uid`='{$id}'");
  }
  
  private static function doLogin(){
    $count = Report::count("ERROR");
    
    if(empty($_POST["username"]) || !trim($_POST["username"]))
      Report::error("Missing username");
    
    if(empty($_POST["password"]) || !trim($_POST["password"]))
      Report::error("Missing password");
    
    if($count == Report::count("ERROR")){
      $db = Database::get();
      $data = $db->query("SELECT `id`, `password`, `salt`, `isActivatet`
                          FROM `user`
                          WHERE LOWER(`username`)='{$db->escape(strtolower($_POST["username"]))}'")->fetch();
      if(!$data || self::salt_password($_POST["password"], $data->salt) != $data->password)
        Report::error("Could not finde the username or/and password");
      elseif($data->isActivatet != 1)
        Report::error("You account is not activated yet");
      else{
        Report::okay("You are now logged in");
        $_SESSION["uid"] = $data->id;
      }
    }
    header("location: #");
    exit;
  }
  
  private static function doCreate(){
    $count = Report::count("ERROR");
    
    if(empty($_POST["username"]) || !trim($_POST["username"]))
      Report::error("Missing username");
    
    $p = true;
    if(empty($_POST["password"]) || !trim($_POST["password"])){
      Report::error("Missing password");
      $p = false;
    }
    
    if(empty($_POST["repeat_password"]) || !trim($_POST["repeat_password"])){
      Report::error("Missing repeat password");
      $p = false;
    }
    
    if($p && $_POST["repeat_password"] != $_POST["password"])
      Report::error("The two passowrd is not equel");
    
    if(empty($_POST["email"]) || !trim($_POST["email"]))
      Report::error("Missing email");
    
    if($count == Report::count("ERROR")){
      if(self::controleDetail($_POST["username"], $_POST["email"]) != null)
        Report::error("Username or/and email is taken");
      else{
        self::createUser(
          $_POST["username"],
          $_POST["password"],
          $_POST["email"],
          false
          );
        Report::okay("You account is created. Please look in you email for activate it");
      }
    }
    
    if(Ajax::isAjaxRequest())
      Ajax::set("create", $count == Report::count("ERROR"));
    else{
      header("location: #");
      exit;
    }
  }
  
  private static function autologin() : bool{
    if(empty($_SESSION["uid"]) || !is_numeric($_SESSION["uid"])){
      return false;
    }
    
    $db = Database::get();
    
    $user = $db->query("SELECT * FROM `user` WHERE `id`='".intval($_SESSION["uid"])."' AND `isActivatet`='1'")->fetch();
    if(!$user){
      unset($_SESSION["uid"]);
      return false;
    }
    define("user", $user->toArray());
    return true;
  }
  
  private static function doActivate(){
    $db = Database::get();
    $data = $db->query("SELECT `id`
                        FROM `user`
                        WHERE `email`='{$db->escape($_GET["email"])}'
                        AND `salt`='{$db->escape($_GET["salt"])}'
                        AND `isActivatet`='0';")->fetch();
    if($data){
      $db->query("UPDATE `user` SET `isActivatet`='1' WHERE `id`='{$data->id}';");
      Report::okay("You account is now activated and can use the account");
    }else
      Report::error("Could not find the account");
  }
}
