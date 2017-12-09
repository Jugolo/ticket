<?php
namespace Lib\Ticket;

use Lib\Database;
use Lib\Ext\Notification\NewTicket;
use Lib\Ext\Notification\NewComment;
use Lib\Category;
use Lib\Email;
use Lib\Bbcode\Parser;
use Lib\Plugin\Plugin;
use Lib\Log;
use Lib\User\Info;

class Ticket{
  public static function createTicket(int $uid, int $head, array $fields){
    $db = Database::get();
    //wee need to create a ticket first
    $ticket_id = $db->query("INSERT INTO `ticket` VALUES (
                               NULL,
                               '{$head}',
                               '{$uid}',
                               '0',
                               NOW(),
                               NOW(),
                               NOW(),
                               '1'
                             );");
    //let us insert all fields in the database
    $sql = [];
    foreach($fields as $field){
      $sql[] = "(NULL, '{$ticket_id}', '{$db->escape($field["text"])}', '{$db->escape($field["type"])}', '{$db->escape($field["value"])}')";
    }
    
    $db->query("INSERT INTO `ticket_value` VALUES ".implode(", ", $sql).";");
    $name = Category::getNameFromId($head);
    NewTicket::notify($ticket_id, $name);
    self::sendEmailOnNewTicket($uid, $name, $ticket_id);
    return $ticket_id;
  }
  
  public static function open(int $id, int $uid){
    $username = Info::getUsername($uid);
    Database::get()->query("UPDATE `ticket` SET `open`='1' WHERE `id`='{$id}'");
    Log::ticket($id, "%s open the ticket", $username);
    Plugin::trigger_event("system.ticket.open", $id, $uid, $username);
  }
  
  public static function close(int $id, int $uid){
    $username = Info::getUsername($uid);
    Database::get()->query("UPDATE `ticket` SET `open`='0' WHERE `id`='{$id}'");
    Log::ticket($id, "%s closed the ticket", $username);
    Plugin::trigger_event("system.ticket.close", $id, $uid, $username);
  }
  
  public static function createComment(int $tid, int $uid, string $message, bool $public = true){
    $parser = new Parser($message);
    $db = Database::get();
    $isPublic = $public ? "1" : "0";
    $cid = $db->query("INSERT INTO `comment` VALUES (
                  NULL,
                  '{$tid}',
                  '{$uid}',
                  '{$isPublic}',
                  NOW(),
                  '{$db->escape($message)}',
                  '{$db->escape($parser->getHtml())}'
                );");
    $db->query("UPDATE `ticket` SET `admin_changed`=NOW(), `comments`=comments+1".($public ? ", `user_changed`=NOW()" : "")." WHERE `id`='{$tid}'");
    NewComment::createNotify($tid, $uid, $public);
    Plugin::trigger_event("system.comment.created", $tid, $uid, $message, $public);
    self::sendEmailOnNewComment($tid, $uid, $public);
  }
  
  private static function sendEmailOnNewComment(int $tid, int $uid, bool $public){
    $email = new Email();
    $db = Database::get();
    $query = $db->query("SELECT user.username, user.email, catogory.name
                         FROM `user`
                         LEFT JOIN `access` ON access.gid=user.groupid
                         LEFT JOIN `comment` ON comment.uid=user.id
                         LEFT JOIN `ticket` ON ticket.id=comment.tid
                         LEFT JOIN `catogory` ON catogory.id=ticket.cid
                         WHERE access.name='TICKET_OTHER'
                         AND comment.tid='{$tid}'
                         ".($public ? "" : "AND user.id <> ticket.uid")."
                         AND user.id<>'".$uid."'
                         GROUP BY user.id");
    while($row = $query->fetch()){
      $email->pushArg("creator", $row->username);
      $email->pushArg("category", $row->name);
      $email->pushArg("my_username", defined("user") ? user["username"] "unknown");
      $email->send("new_comment", $row->email);
    }
  }
  
  private static function sendEmailOnNewTicket(int $uid, string $catName, int $tid){
    $email = new Email();
    $db = Database::get();
    $query = $db->query("SELECT user.username, user.email
                         FROM `user`
                         LEFT JOIN `access` ON user.groupid=access.gid
                         WHERE access.name='TICKET_OTHER'
                         AND user.id<>'".user["id"]."'");
    while($row = $query->fetch()){
      $email->pushArg("username",        $row->username);
      $email->pushArg("ticket_category", $catName);
      $email->send("new_ticket",         $row->email);
    }
  }
}
