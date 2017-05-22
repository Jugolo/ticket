<?php
namespace Lib\Ext\Notification;

use Lib\Database;

class Notification{
  public static function create(int $uid, int $item_id, string $name, string $link, string $message){
    $db = Database::get();
    $db->query("INSERT INTO `notify` VALUES (NULL, '{$db->escape($uid)}', '{$db->escape($item_id)}', '{$db->escape($name)}', '{$db->escape($link)}', '{$db->escape($message)}', NOW(), 0);");
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
        call_user_func($callback, "Lib\\Ext\\Notification\\".$name);
      }
    }
    closedir($dir);
  }
  
  public static function ajax(){
    $ajax = [];
    
    if(defined("user")){
      $db = Database::get();
      $query = $db->query("SELECT `id`, `link`, `message`, `seen`
                           FROM `notify`                          
                           WHERE `uid`='".user["id"]."'
                           AND DATE_SUB(`created`, INTERVAL 1 MONTH) < NOW()
                           ".(!empty($_POST["notify_id"]) ? " AND `id`>'".$db->escape($_POST["notify_id"])."'" : "")."
                           ORDER BY `id` DESC");
      while($row = $query->fetch()){
        $ajax[] = $row->toArray();
      }
    }
    
    return $ajax;
  }
}