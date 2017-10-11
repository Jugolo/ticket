<?php
namespace Lib\Ticket;

use Lib\Plugin\Plugin;
use Lib\Database\DatabaseFetch;
use Lib\Database;

class TicketDeleter{
  public static function onCategoryDelete(DatabaseFetch $cat){
    //wee go every ticket in this category for delete it ;). 
    //We let this plugin system do the deleting thinks so wee only find them to the plugin system
    $db = Database::get();
    $db->query("SELECT `id` FROM `ticket` WHERE `cid`='{$cat->id}'")->render(function($row){
      Plugin::trigger_event("system.ticket.delete", $row->id);
    });
    $db->query("DELETE FROM `ticket` WHERE `cid`='{$cat->id}'");
    $db->query("DELETE FROM `catogory` WHERE `id`='{$cat->id}'");
    $db->query("DELETE FROM `category_item` WHERE `cid`='{$cat->id}'");
  }
  
  public static function onTicketDelete(int $id){
    $db = Database::get();
    $db->query("DELETE FROM `ticket_track` WHERE `tid`='{$id}'");
    $db->query("DELETE FROM `ticket_value` WHERE `hid`='{$id}'");
    $db->query("DELETE FROM `comment` WHERE `tid`='{$id}'");
  }
}