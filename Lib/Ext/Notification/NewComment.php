<?php
namespace Lib\Ext\Notification;

use Lib\Database;
use Lib\Database\DatabaseFetch;

class NewComment{
  public static function createNotify(int $hid, int $creater, bool $isPublic){
    $db = Database::get();
    $query = $db->query("SELECT notify_setting.uid
                         FROM `notify_setting`
                         LEFT JOIN `comment` ON notify_setting.uid=comment.uid
                         WHERE `name`='{$db->escape(__CLASS__)}'
                         AND comment.tid='{$hid}'
                         GROUP BY comment.uid");
    $found = false;
    while($row = $query->fetch()){
      if($row->uid != user["id"]){
        if($row->uid == $creater){
          if(!$isPublic){
           continue; 
          }
          $found = true;
        }
        
        self::send($row->uid, $hid);
      }
    }
    
    if(!$found && $creater != user["id"] && $isPublic){
      $row = $db->query("SELECT COUNT(`uid`) AS uid
                           FROM `notify_setting` 
                           WHERE `uid`='{$creater}' 
                           AND `name`='{$db->escape(__CLASS__)}'")->fetch();
      if($row->uid == 1){
        self::send($creater, $hid);
      }
    }
  }
  
  public static function onTicketDelete(int $id){
    $db = Database::get();
    $db->query("DELETE FROM `notify` WHERE `name`='{$db->escape(__CLASS__)}' AND `item_id`='{$id}'");
  }
  
  public static function markRead(int $hid){
    Notification::markRead(user["id"], $hid, __CLASS__);
  }
  
  public static function send(int $uid, int $hid){
    Notification::create(
      $uid,
      $hid,
      __CLASS__,
      "?view=tickets&ticket_id=".$hid,
      user["username"]." has just comment on the ticket"
      );
  }
}