<?php
namespace Lib\Ticket;

use Lib\Database;
use Lib\Ajax;
use Lib\User\User;

class Track{
  public static function track(int $ticket_id, int $cat_id, int $user_id){
    $db = Database::get();
    $count = $db->query("SELECT COUNT(`tid`) AS number FROM `".DB_PREFIX."ticket_track` WHERE `tid`='".$ticket_id."' AND `uid`='".$user_id."'")->fetch();
    if($count->number == 0){
      $db->query("INSERT INTO `".DB_PREFIX."ticket_track` VALUES ('".$user_id."', '".$cat_id."', '".$ticket_id."', '".time()."');");
    }else{
      $db->query("UPDATE `".DB_PREFIX."ticket_track` SET `visit`='".time()."' WHERE `tid`='".$ticket_id."' AND `uid`='".$user_id."'");
    }
  }
  
  public static function ajaxUpdate(){
    Ajax::set("unread_ticket", self::unread());
  }
  
  public static function unread() : int{
	global $user;
    if(!$user->isLoggedIn()){
      return 0;
    }
    
    $db = Database::get();
    
    $cat = self::getAvailableCat($user);
    
    
    $sql = "SELECT COUNT(ticket.id) AS id FROM `".DB_PREFIX."ticket` AS ticket
           LEFT JOIN `".DB_PREFIX."ticket_track` AS track ON track.tid=ticket.id AND track.uid='{$user->id()}'
           WHERE (
             track.tid IS NULL AND (ticket.uid='{$user->id()}'".($cat ? " OR (".$cat.")" : "").")
             OR ticket.uid='{$user->id()}' AND track.visit<ticket.user_changed
             OR ticket.uid<>'{$user->id()}' AND track.visit<ticket.admin_changed".($cat ? " AND (".$cat.")" : "")."
           )";
    $data = $db->query($sql)->fetch();
    return $data ? intval($data->id) : 0;
  }
  
  private static function getAvailableCat(User $user) : string{
	  $query = Database::get()->query("SELECT cat.id
	                                   FROM `".DB_PREFIX."catogory` AS cat
	                                   LEFT JOIN `".DB_PREFIX."category_access` AS access ON cat.id=access.cid
	                                   LEFT JOIN `".DB_PREFIX."grup_member` AS member ON access.gid=member.gid
	                                   WHERE member.uid='{$user->id()}'
	                                   AND access.name='TICKET_OTHER'
	                                   GROUP BY cat.id");
	  $list = [];
	  while($row = $query->fetch())
	    $list[] = $row->id;
	  if(count($list) == 0)
		return "";
	  return "ticket.cid='".implode("' OR ticket.cid='", $list)."'";
  }
}
