<?php
namespace Lib\Ticket;

use Lib\Plugin\Plugin;
use Lib\Database\DatabaseFetch;
use Lib\Database;
use Lib\Plugin\Event;

class TicketDeleter{
  public static function gc(){
    //in fact wee should do this in the log class but hey let us try
    $db = Database::get();
    $db->query("SELECT log.id
                FROM `".DB_PREFIX."log` AS log
                LEFT JOIN `".DB_PREFIX."ticket` AS ticket ON ticket.id=log.tid
                WHERE ticket.id IS NULL AND log.type='TICKET'")->fetch(function($id) use($db){
      $db->query("DELETE FROM `".DB_PREFIX."log` WHERE `id`='{$id}'");
    });
  }
  
  public static function onCommentDelete(Event $event, int $id){
    $db = Database::get();
    //we get the ticket id where the comment is comming from. We will so update the comment count.
    $data = $db->query("SELECT * FROM `".DB_PREFIX."comment` WHERE `id`='{$id}'")->fetch();
    $db->query("UPDATE `".DB_PREFIX."ticket` SET `comments`=comments-1 WHERE `id`='{$data->tid}'");
    $db->query("DELETE FROM `".DB_PREFIX."comment` WHERE `id`='{$id}'");
  }
}