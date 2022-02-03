<?php
namespace Lib\Ext\Notification;

use Lib\Database;
use Lib\Database\DatabaseFetch;
use Lib\Plugin\Event;

class NewTicket{
  public static function notify(int $id, int $cid, string $name){
	global $user;
    $db = Database::get();
    $query = $db->query("SELECT u.id 
                         FROM `".DB_PREFIX."notify_setting` AS n
                         LEFT JOIN `".DB_PREFIX."user` AS u ON n.uid=u.id
                         LEFT JOIN `".DB_PREFIX."grup_member` AS member ON u.id=member.uid
                         LEFT JOIN `".DB_PREFIX."category_access` AS a ON member.gid=a.gid
                         WHERE n.name='".$db->escape(__CLASS__)."'
                         AND a.cid='{$cid}'
                         AND a.name='TICKET_OTHER'");
    $query->render(function(DatabaseFetch $row) use($id, $name, $user){
      if(!$user->isLoggedIn() || $row->id != $user->id()){
        NewTicket::notifyUser($row->id, $id, $name);
      }
    });
  }
  
  public static function onTicketDelete(Event $event, int $id){
    $db = Database::get();
    $db->query("DELETE FROM `".DB_PREFIX."notify` WHERE `name`='{$db->escape(__CLASS__)}' AND `item_id`='{$id}'");
  }
  
  public static function markRead(int $item_id){
	global $user;
    Notification::markRead($user->id(), $item_id, __CLASS__);
  }
  
  public static function notifyUser(int $uid, int $tid, string $name){
	global $user;
    Notification::create(
      $uid,
      $tid,
      __CLASS__,
      "?view=tickets&ticket_id={$tid}",
      "NOTIFY_CREATE_TICKET",
      [$user->username()]
      );
  }
}
