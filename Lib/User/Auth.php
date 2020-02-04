<?php
namespace Lib\User;

use Lib\Database;
use Lib\Ext\Notification\Notification;
use Lib\Config;
use Lib\Email;
use Lib\Report;
use Lib\Ajax;
use Lib\Plugin\Plugin;
use Lib\Language\LanguageDetector;
use Lib\Language\Language;

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
                         FROM `".DB_PREFIX."user` 
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
    $id = $db->query("INSERT INTO `".DB_PREFIX."user` (
        `username`,
        `password`,
        `email`,
        `salt`,
        `isActivatet`,
        `groupid`,
        `lang`
      ) VALUES (
        '".$db->escape($username)."',
        '".$db->escape(self::salt_password($raw_password, $salt))."',
        '".$db->escape($email)."',
        '".$db->escape($salt)."',
        '".($isActivated ? '1' : '0')."',
        '".Config::get("standart_group")."',
        '".(defined("force_lang") ? force_lang : Language::getCode())."'
      );");
    Notification::getNotification(function(string $name) use($db, $id){
        $db->query("INSERT INTO `".DB_PREFIX."notify_setting` VALUES ('{$id}', '{$db->escape($name)}');");
    });
    if(!$isActivated){
      $emails = new Email("account_create");
      $emails->pushArg("username", $username);
      $emails->pushArg("link", geturl()."?salt=".urlencode($salt)."&email=".urlencode($email));
      $emails->send($email);
    }
    return $id;
  }
  
  public static function deleteUser(int $id){
    $db = Database::get();
    $db->query("DELETE FROM `".DB_PREFIX."comment` WHERE `uid`=".$id);
    $db->query("DELETE FROM `".DB_PREFIX."ticket` WHERE `uid`='".$id."'");
    $db->query("DELETE FROM `".DB_PREFIX."user` WHERE `id`='".$id."'");
    $db->query("DELETE FROM `".DB_PREFIX."ticket_track` WHERE `uid`='{$id}'");
    $db->query("DELETE FROM `".DB_PREFIX."notify_setting` WHERE `uid`='{$id}'");
  }
  
  private static function doLogin(){
    LanguageDetector::detect();
    Language::load("auth");
    $count = Report::count("ERROR");
    
    if(empty($_POST["username"]) || !trim($_POST["username"]))
      Report::error(Language::get("MISSING_USERNAME"));
    
    if(empty($_POST["password"]) || !trim($_POST["password"]))
      Report::error(Language::get("MISSING_PASSWORD"));
    
    if($count == Report::count("ERROR")){
      $db = Database::get();
      $data = $db->query("SELECT `id`, `password`, `salt`, `isActivatet`
                          FROM `".DB_PREFIX."user`
                          WHERE LOWER(`username`)='{$db->escape(strtolower($_POST["username"]))}'")->fetch();
      if(!$data || self::salt_password($_POST["password"], $data->salt) != $data->password)
        Report::error(Language::get("USER_N_FOUND"));
      elseif($data->isActivatet != 1)
        Report::error(Language::get("USER_N_ACTIV"));
      else{
        Report::okay(Language::get("USER_LOGEDIN"));
        if(empty($_COOKIE["accept_cookie"])){
          session_start();
          setcookie("accept_cookie", "true", time() + (86400 * 30), "/");
        }
        $_SESSION["uid"] = $data->id;
      }
    }
    header("location: #");
    exit;
  }
  
  private static function doCreate(){
    $count = Report::count("ERROR");
    LanguageDetector::detect();
    Language::load("auth");
    
    if(empty($_POST["username"]) || !trim($_POST["username"]))
      Report::error(Language::get("MISSING_USERNAME"));
    
    $p = true;
    if(empty($_POST["password"]) || !trim($_POST["password"])){
      Report::error(Language::get("MISSING_PASSWORD"));
      $p = false;
    }
    
    if(empty($_POST["repeat_password"]) || !trim($_POST["repeat_password"])){
      Report::error(Language::get("MISSING_R_PASSWORD"));
      $p = false;
    }
    
    if($p && $_POST["repeat_password"] != $_POST["password"])
      Report::error(Language::get("PASSWORD_N_EQUEL"));
    
    if(empty($_POST["email"]) || !trim($_POST["email"]))
      Report::error(Language::get("MISSING_EMAIL"));
    
    if($count == Report::count("ERROR")){
      if(self::controleDetail($_POST["username"], $_POST["email"]) != null)
        Report::error(Language::get("DATA_TAKEN"));
      else{
        self::createUser(
          $_POST["username"],
          $_POST["password"],
          $_POST["email"],
          false
          );
        Report::okay(Language::get("ACCOUNT_CREATET"));
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
    
    $user = $db->query("SELECT * FROM `".DB_PREFIX."user` WHERE `id`='".intval($_SESSION["uid"])."' AND `isActivatet`='1'")->fetch();
    if(!$user){
      unset($_SESSION["uid"]);
      return false;
    }
    define("user", $user->toArray());
    return true;
  }
  
  private static function doActivate(){
    $db = Database::get();
    LanguageDetector::detect();
    Language::load("auth");
    $data = $db->query("SELECT `id`
                        FROM `".DB_PREFIX."user`
                        WHERE `email`='{$db->escape($_GET["email"])}'
                        AND `salt`='{$db->escape($_GET["salt"])}'
                        AND `isActivatet`='0';")->fetch();
    if($data){
      $db->query("UPDATE `user` SET `isActivatet`='1' WHERE `id`='{$data->id}';");
      Report::okay(Language::get("ACCOUNT_ACTIVATED"));
    }else
      Report::error(Language::get("UNKNOWN_ACCOUNT"));
  }
}
