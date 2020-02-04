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
use Lib\File\FileExtension;

class Ticket{
  public static function createTicket(int $uid, int $head, array $fields){
    $db = Database::get();
    //wee need to create a ticket first
    $ticket_id = $db->query("INSERT INTO `".DB_PREFIX."ticket` VALUES (
                               NULL,
                               '{$head}',
                               '{$uid}',
                               '0',
                               '0',
                               ".time().",
                               ".time().",
                               ".time().",
                               '1'
                             );");
    //update the ticket count
    $db->query("UPDATE `".DB_PREFIX."catogory` SET `ticket_count`=ticket_count+1 WHERE `id`='{$head}'");
    //let us insert all fields in the database
    $sql = [];
    $f = new FileExtension();
    foreach($fields as $field){
      if($field["type"] == 4){
        if(is_array($field["value"])){
          list($name, $extension) = $field["value"];
        }else{
          $name = $field["value"];
          $extension = get_extension($field["value"]);
        }
        
        list($id, $nn) = $f->createFile($ticket_id, $extension);
        if(is_uploaded_file($name)){
          move_uploaded_file($name, "Lib/Uploaded/".$nn);
        }else{
          copy($name, "Lib/Uploaded/".$nn);
        }
        
        $field["value"] = $id;
      }
      $sql[] = "(NULL, '{$ticket_id}', '{$head}', '{$db->escape($field["text"])}', '{$db->escape($field["type"])}', '{$db->escape($field["value"])}')";
    }
    
    $db->query("INSERT INTO `".DB_PREFIX."ticket_value` VALUES ".implode(", ", $sql).";");
    $name = Category::getNameFromId($head);
    NewTicket::notify($ticket_id, $name);
    self::sendEmailOnNewTicket($uid, $name, $ticket_id);
    return $ticket_id;
  }
  
  public static function open(int $id, int $uid){
    $username = Info::getUsername($uid);
    Database::get()->query("UPDATE `".DB_PREFIX."ticket` SET `open`='1', `admin_changed`='".time()."', `user_changed`='".time()."' WHERE `id`='{$id}'");
    Log::ticket($id, "LOG_TICKET_OPEN", $username);
    Plugin::trigger_event("system.ticket.open", $id, $uid, $username);
  }
  
  public static function close(int $id, int $uid){
    $username = Info::getUsername($uid);
    Database::get()->query("UPDATE `".DB_PREFIX."ticket` SET `open`='0', `admin_changed`='".time()."', `user_changed`='".time()."' WHERE `id`='{$id}'");
    Log::ticket($id, "LOG_TICKET_CLOSE", $username);
    Plugin::trigger_event("system.ticket.close", $id, $uid, $username);
  }
  
  public static function createComment(int $tid, int $cid, int $uid, string $message, bool $public = true){
    $parser = new Parser($message);
    $db = Database::get();
    //exit("<!DOCTYPE html> <html><head><meta charset='utf8'></head><body>".$db->escape($parser->getHtml())."</body></html>");
    $isPublic = $public ? "1" : "0";
    $cid = $db->query("INSERT INTO `".DB_PREFIX."comment` VALUES (
                  NULL,
                  '{$tid}',
                  '{$cid}',
                  '{$uid}',
                  '{$isPublic}',
                  '".time()."',
                  '{$db->escape($message)}',
                  '{$db->escape($parser->getHtml())}'
                );");
    $db->query("UPDATE `".DB_PREFIX."ticket` SET `admin_changed`='".time()."', ".($public ? "`comments`=comments+1, `user_changed`='".time()."'" : "`admin_comments`=admin_comments+1")." WHERE `id`='{$tid}'");
    NewComment::createNotify($tid, $uid, $public);
    Plugin::trigger_event("system.comment.created", $tid, $uid, $message, $public);
    self::sendEmailOnNewComment($tid, $uid, $public);
  }
  
  private static function sendEmailOnNewComment(int $tid, int $uid, bool $public){
    $email = new Email("new_comment");
    $db = Database::get();
    $query = $db->query("SELECT user.username, user.email, catogory.name
                         FROM `".DB_PREFIX."user` AS user
                         LEFT JOIN `".DB_PREFIX."access` AS access ON access.gid=user.groupid
                         LEFT JOIN `".DB_PREFIX."comment` AS comment ON comment.uid=user.id
                         LEFT JOIN `".DB_PREFIX."ticket` AS ticket ON ticket.id=comment.tid
                         LEFT JOIN `".DB_PREFIX."catogory` AS catogory ON catogory.id=ticket.cid
                         WHERE access.name='TICKET_OTHER'
                         AND comment.tid='{$tid}'
                         ".($public ? "" : "AND user.id <> ticket.uid")."
                         AND user.id<>'".$uid."'
                         GROUP BY user.id");
    
    while($row = $query->fetch()){
      $email->pushArg("creator", $row->username);
      $email->pushArg("category", $row->name);
      $email->pushArg("my_username", defined("user") ? user["username"] : "unknown");
      $email->send($row->email);
    }
  }
  
  private static function sendEmailOnNewTicket(int $uid, string $catName, int $tid){
    $email = new Email("new_ticket");
    $db = Database::get();
    $query = $db->query("SELECT user.username, user.email
                         FROM `".DB_PREFIX."user` AS user
                         LEFT JOIN `".DB_PREFIX."access` AS access ON user.groupid=access.gid
                         WHERE access.name='TICKET_OTHER'
                         AND user.id<>'".user["id"]."'");
    $email->pushArg("ticket_category", $catName);
    while($row = $query->fetch()){
      $email->pushArg("username", $row->username);
      $email->send($row->email);
    }
  }
}