<?php
namespace Lib\Ext\Notification;

use Lib\Database;
use Lib\Database\DatabaseFetch;
use Lib\Plugin\Event;

class NewTicket{
  public static function notify(int $id, string $name){
    $db = Database::get();
    $query = $db->query("SELECT u.id 
                         FROM `".DB_PREFIX."notify_setting` AS n
                         LEFT JOIN `".DB_PREFIX."user` AS u ON u.id=n.uid
                         LEFT JOIN `".DB_PREFIX."access` AS a ON u.groupid=a.gid
                         WHERE n.name='".$db->escape(__CLASS__)."'
                         AND a.name='TICKET_OTHER'");
    $query->render(function(DatabaseFetch $row) use($id, $name){
      if(!defined("user") || $row->id != user["id"]){
        NewTicket::notifyUser($row->id, $id, $name);
      }
    });
  }
  
  public static function onTicketDelete(Event $event, int $id){
    $db = Database::get();
    $db->query("DELETE FROM `".DB_PREFIX."notify` WHERE `name`='{$db->escape(__CLASS__)}' AND `item_id`='{$id}'");
  }
  
  public static function markRead(int $item_id){
    Notification::markRead(user["id"], $item_id, __CLASS__);
  }
  
  public static function notifyUser(int $uid, int $tid, string $name){
    Notification::create(
      $uid,
      $tid,
      __CLASS__,
      "?view=tickets&ticket_id={$tid}",
      "NOTIFY_CREATE_TICKET",
      [defined("user") ? user["username"] : "unknown"]
      );
  }
}