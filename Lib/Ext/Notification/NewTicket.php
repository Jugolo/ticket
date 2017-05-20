<?php
namespace Lib\Ext\Notification;

use Lib\Database;
use Lib\Database\DatabaseFetch;

class NewTicket{
  public static function notify(int $id, string $name){
    $db = Database::get();
    $query = $db->query("SELECT user.id 
                         FROM `notify_setting`
                         LEFT JOIN `user` ON user.id=notify_setting.uid
                         LEFT JOIN `group` ON user.groupid=group.id
                         WHERE notify_setting.name='".$db->escape(__CLASS__)."'
                         AND group.showTicket='1'");
    $query->render(function(DatabaseFetch $row) use($id, $name){
      if($row->id != user["id"]){
        NewTicket::notifyUser($row->id, $id, $name);
      }
    });
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
      user["username"]." has just created a new ticket"
      );
  }
}
