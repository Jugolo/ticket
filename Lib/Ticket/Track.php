<?php
namespace Lib\Ticket;

use Lib\Database;
use Lib\Ajax;
use Lib\Access;

class Track{
  public static function track(int $ticket_id, int $user_id){
    $db = Database::get();
    $count = $db->query("SELECT COUNT(`tid`) AS number FROM `ticket_track` WHERE `tid`='".$ticket_id."' AND `uid`='".$user_id."'")->fetch();
    if($count->number == 0){
      $db->query("INSERT INTO `ticket_track` VALUES ('".$user_id."', '".$ticket_id."', NOW());");
    }else{
      $db->query("UPDATE `ticket_track` SET `visit`=NOW() WHERE `tid`='".$ticket_id."' AND `uid`='".$user_id."'");
    }
  }
  
  public static function ajaxUpdate(){
    Ajax::set("unread_ticket", self::unread());
  }
  
  public static function unread() : int{
    if(!defined("user")){
      return 0;
    }
    $globel = Access::userHasAccess("TICKET_OTHER");
    $db = Database::get();
    $sql = "SELECT COUNT(ticket.id) AS id
            FROM `ticket`
            LEFT JOIN `ticket_track` ON ticket_track.tid=ticket.id AND ticket_track.uid='".user["id"]."'
            WHERE ticket_track.tid IS NULL".($globel ? "" : " AND ticket.uid='".user["id"]."'")."
            AND ticket.open='1'
            OR (ticket.uid='".user["id"]."'
                AND ticket_track.visit<ticket.user_changed";
    
    if($globel){
      $sql .= " OR ticket.uid<>'".user["id"]."' AND ticket_track.visit<ticket.admin_changed";
    }
    $sql .= ")";
    //exit(htmlentities($sql));
    $data = $db->query($sql)->fetch();
    return $data ? intval($data->id) : 0;
  }
}