<?php
namespace Lib\Ticket;

use Lib\Plugin\Plugin;
use Lib\Database\DatabaseFetch;
use Lib\Database;
use Lib\Plugin\Event;

class TicketDeleter{
  public static function onCommentDelete(Event $event, int $id){
    $db = Database::get();
    //we get the ticket id where the comment is comming from. We will so update the comment count.
    $data = $db->query("SELECT * FROM `comment` WHERE `id`='{$id}'")->fetch();
    $db->query("UPDATE `ticket` SET `comments`=comments-1 WHERE `id`='{$data->tid}'");
    $db->query("DELETE FROM `comment` WHERE `id`='{$id}'");
  }
  
  public static function onTicketDelete(Event $event, int $id){
    $db = Database::get();
    $db->query("DELETE FROM `ticket_track` WHERE `tid`='{$id}'");
    $db->query("DELETE FROM `ticket_value` WHERE `hid`='{$id}'");
    $db->query("DELETE FROM `comment` WHERE `tid`='{$id}'");
    $db->query("DELETE FROM `ticket` WHERE `id`='{$id}'");
  }
}