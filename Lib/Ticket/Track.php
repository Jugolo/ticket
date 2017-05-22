<?php
namespace Lib\Ticket;

use Lib\Database;

class Track{
  public static function track(int $ticket_id, int $user_id){
    $db = Database::get();
    $db->query("UPDATE `ticket_track` SET `visit`=NOW() WHERE `tid`='".$ticket_id."' AND `uid`='".$user_id."'");
    if($db->affected() == 0){
      $db->query("INSERT INTO `ticket_track` VALUES ('".$user_id."', '".$ticket_id."', NOW());");
    }
  }
  
  public static function unread() : int{
    if(!defined("user")){
      return 0;
    }
    $globel = getUsergroup(user["groupid"])["showTicket"] == 1;
    $db = Database::get();
    $sql = "SELECT COUNT(ticket.id) AS id
            FROM `ticket`
            LEFT JOIN `ticket_track` ON ticket_track.tid=ticket.id AND ticket_track.uid='".user["id"]."'
            WHERE ticket_track.tid IS NULL".($globel ? "" : " AND ticket.uid='".user["id"]."'")."
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