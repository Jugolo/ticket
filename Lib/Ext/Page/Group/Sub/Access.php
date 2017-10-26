<?php
namespace Lib\Ext\Page\Group\Sub;

use Lib\Database;
use Lib\Report;

class Access{
  public static function body(){
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
  
    echo "<h3>Change access for {$group["name"]}</h3>";
    echo "<form method='post' action='#'>";
    echo two_container("Show other tickets", "<input type='checkbox' name='showticket'".($group["showTicket"] == 1 ? " checked" : "").">");
    echo two_container("Change group", "<input type='checkbox' name='changegroup'".($group["changeGroup"] == 1 ? " checked" : "").">");
    echo two_container("Handle group", "<input type='checkbox' name='handleGroup'".($group["handleGroup"] == 1 ? " checked" : "").">");
    echo two_container("Admin ticket", "<input type='checkbox' name='handleTickets'".($group["handleTickets"] == 1 ? " checked" : "").">");
    echo two_container("Show error", "<input type='checkbox' name='showError'".($group["showError"] == 1 ? " checked" : "").">");
    echo two_container("Show other profile", "<input type='checkbox' name='showProfile'".($group["showProfile"] == 1 ? " checked" : "").">");
    echo two_container("Close/open tickets", "<input type='checkbox' name='closeTicket'.".($group["closeTicket"] == 1 ? " checked" : "").">");
    echo two_container("Change front page", "<input type='checkbox' name='changeFront'".($group["changeFront"] == 1 ? " checked" : "").">");
    echo two_container("Change system name", "<input type='checkbox' name='changeSystemName'".($group["changeSystemName"] == 1 ? " checked" : "").">");
    echo two_container("Show ticket log",    "<input type='checkbox' name='showTicketLog'".($group["showTicketLog"] == 1 ? " checked" : "").">");
    echo two_container("Delete ticket",      "<input type='checkbox' name='deleteTicket'".($group["deleteTicket"] == 1 ? " checked" : "").">");
    echo two_container("Delete comments",    "<input type='checkbox' name='deleteComment'".($group["deleteComment"] == 1 ? " checked" : "").">");
    echo two_container("Activate users",     "<input type='checkbox' name='activateUser'".($group["activateUser"] == 1 ? " checked" : "").">");
    echo two_container("View user log",      "<input type='checkbox' name='viewUserLog'".($group["viewUserLog"] == 1 ? " checked" : "").">");
    echo "<div><input type='submit' name='update' value='Update access'></div>";
    echo "</form>";
  }
  
  private static function update_access(array $group){
    $update = [];
    if(!empty($_POST["showticket"]) && $group["showTicket"] == 0){
      $update["showTicket"] = "1";
    }elseif(empty($_POST["showticket"]) && $group["showTicket"] == 1){
      $update["showTicket"] = "0";
    }
  
    if(!empty($_POST["changegroup"]) && $group["changeGroup"] == 0){
      $update["changeGroup"] = "1";
    }elseif(empty($_POST["changegroup"]) && $group["changeGroup"] == 1){
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