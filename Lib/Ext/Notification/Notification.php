<?php
namespace Lib\Ext\Notification;

use Lib\Database;
use Lib\Ajax;
use Lib\Language\Language;

class Notification{
  public static function create(int $uid, int $item_id, string $name, string $link, string $message, array $arg = []){
    $db = Database::get();
    $arg = json_encode($arg);
    $db->query("INSERT INTO `notify` VALUES (NULL, '{$db->escape($uid)}', '{$db->escape($item_id)}', '{$db->escape($name)}', '{$db->escape($link)}', '{$db->escape($message)}', '{$db->escape($arg)}', NOW(), 0);");
  }
  
  public static function markRead(int $uid, int $item_id, string $name){
    $db = Database::get();
    $db->query("UPDATE `notify` SET `seen`='1' WHERE `uid`='".$db->escape($uid)."' AND `name`='{$db->escape($name)}' AND `item_id`='".$db->escape($item_id)."'");
  }
  
  public static function getNotification($callback){
    $pages = "Lib/Ext/Notification/";
    $dir = opendir($pages);
    while($name = readdir($dir)){
      if($name != "Notification.php" && $name != "." && $name != ".." && is_file($pages.$name)){
        call_user_func($callback, "Lib\\Ext\\Notification\\".substr($name, 0, strrpos($name, ".")));
      }
    }
    closedir($dir);
  }
  
  public static function ajax(){
    $ajax = [];
    
    if(defined("user")){
      if(!defined("NOTIFY_LANG")){
        Language::load("notifi");
        define("NOTIFY_LANG", true);
      }
      $db = Database::get();
      $query = $db->query("SELECT `id`, `link`, `message`, `arg`, `seen`
                           FROM `notify`                          
                           WHERE `uid`='".user["id"]."'
                           AND DATE_SUB(`created`, INTERVAL 1 MONTH) < NOW()
                           ".(!empty($_POST["notify_id"]) ? " AND `id`>'".$db->escape($_POST["notify_id"])."'" : "")."
                           ORDER BY `id` DESC");
      while($row = $query->fetch()){
        $ajax[] = [
          "id"      => $row->id,
          "link"    => $row->link,
          "message" => Language::get($row->message, json_decode($row->arg, true)),
          "seen"    => $row->seen
          ];
      }
    }
    
    Ajax::set("notify", $ajax);
  }
}