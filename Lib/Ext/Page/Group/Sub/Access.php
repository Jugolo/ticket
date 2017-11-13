<?php
namespace Lib\Ext\Page\Group\Sub;

use Lib\Template;

use Lib\Database;
use Lib\Report;
use Lib\Tempelate;

class Access{
  public static function body(Tempelate $tempelate){
    if(empty($_GET["gid"])){
      notfound();
      return;
    }
    
    $ugroup = getUsergroup(user["groupid"]);
    $group = $ugroup["id"] == $_GET["gid"] ? $ugroup : getUsergroup($_GET["gid"]);
    if($ugroup["handleGroup"] != 1){
      notfound();
      return;
    }
  
    if(!empty($_POST["update"])){
      self::update_access($group);
    }
    
    $tempelate->put("group", $group);
    $tempelate->render("group_access");
  }
  
  private static function update_access(array $group){
    $update = [];
    if(!empty($_POST["showTicket"]) && $group["showTicket"] == 0){
      $update["showTicket"] = "1";
    }elseif(empty($_POST["showTicket"]) && $group["showTicket"] == 1){
      $update["showTicket"] = "0";
    }
  
    if(!empty($_POST["changeGroup"]) && $group["changeGroup"] == 0){
      $update["changeGroup"] = "1";
    }elseif(empty($_POST["changeGroup"]) && $group["changeGroup"] == 1){
      $update["changeGroup"] = "0";
    }
  
    if(!empty($_POST["handleGroup"]) && $group["handleGroup"] == 0){
      $update["handleGroup"] = "1";
    }elseif(empty($_POST["handleGroup"]) && $group["handleGroup"] == 1){
      $update["handleGroup"] = "0";
    }
  
    if(!empty($_POST["handleTickets"]) && $group["handleTickets"] == 0){
      $update["handleTickets"] = "1";
    }elseif(empty($_POST["handleTickets"]) && $group["handleTickets"] == 1){
      $update["handleTickets"] = "0";
    }
  
    if(!empty($_POST["showError"]) && $group["showError"] == 0){
      $update["showError"] = "1";
    }elseif(empty($_POST["showError"]) && $group["showError"] == 1){
      $update["showError"] = "0";
    }
    
    if(!empty($_POST["showProfile"]) && $group["showProfile"] == 0){
      $update["showProfile"] = "1";
    }elseif(empty($_POST["showProfile"]) && $group["showProfile"] == 1){
      $update["showProfile"] = "0";
    }
    
    if(!empty($_POST["closeTicket"]) && $group["closeTicket"] == 0){
      $update["closeTicket"] = "1";
    }elseif(empty($_POST["closeTicket"]) && $group["showProfile"] == 1){
      $update["closeTicket"] = "0";
    }
    
    if(!empty($_POST["changeFront"]) && $group["changeFront"] == 0){
      $update["changeFront"] = "1";
    }elseif(empty($_POST["changeFront"]) && $group["changeFront"] == 1){
      $update["changeFront"] = "0";
    }
    
    if(!empty($_POST["changeSystemName"]) && $group["changeSystemName"] == 0){
      $update["changeSystemName"] = "1";
    }elseif(empty($_POST["changeSystemName"]) && $group["changeSystemName"] == 1){
      $update["changeSystemName"] = "0";
    }
    
    if(!empty($_POST["showTicketLog"]) && $group["showTicketLog"] == 0){
      $update["showTicketLog"] = "1";
    }elseif(empty($_POST["showTicketLog"]) && $group["showTicketLog"] == 1){
      $update["showTicketLog"] = "0";
    }
    
    if(!empty($_POST["deleteTicket"]) && $group["deleteTicket"] == 0){
      $update["deleteTicket"] = "1";
    }elseif(empty($_POST["deleteTicket"]) && $group["deleteTicket"] == 1){
      $update["deleteTicket"] = "0";
    }
    
    if(!empty($_POST["deleteComment"]) && $group["deleteComment"] == 0){
      $update["deleteComment"] = "1";
    }else if(empty($_POST["deleteComment"]) && $group["deleteComment"] == 1){
      $update["deleteComment"] = "0";
    }
    
    if(!empty($_POST["activateUser"]) && $group["activateUser"] == 0)
      $update["activateUser"] = "1";
    elseif(empty($_POST["activateUser"]) && $group["activateUser"] == 1)
      $update["activateUser"] = "0";
    
    if(!empty($_POST["viewUserLog"]) && $group["viewUserLog"] == 0)
      $update["viewUserLog"] = "1";
    elseif(empty($_POST["viewUserLog"]) && $group["viewUserLog"] == 1)
      $update["viewUserLog"] = "0";
    
    if(!empty($_POST["viewSystemLog"]) && $group["viewSystemLog"] == 0)
      $update["viewSystemLog"] = "1";
    elseif(empty($_POST["viewSystemLog"]) && $group["viewSystemLog"] == 1)
      $update["viewSystemLog"] = "0";
    
    if(!empty($_POST["handleTempelate"]) && $group["handleTempelate"] == 0)
      $update["handleTempelate"] = "1";
    elseif(empty($_POST["handleTempelate"]) && $group["handleTempelate"] == 1)
      $update["handleTempelate"] = "0";
  
    if(count($update) > 0){
      $sql = [];
      foreach($update as $key => $value){
        $sql[] = "`".$key."`='".intval($value)."'";
      }
      Database::get()->query("UPDATE `group` SET ".implode(",", $sql)." WHERE `id`='".$group["id"]."'");
      Report::okay("Access updated");
    }else{
      Report::error("No update detected");
    }
  
    header("location: ?view=handleGroup&sub=Access&gid=".$group["id"]);
    exit;
  }
}