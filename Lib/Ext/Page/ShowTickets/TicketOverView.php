<?php
namespace Lib\Ext\Page\ShowTickets;

use Lib\Database;
use Lib\Database\DatabaseFetch;

class TicketOverView{
  public static function body(){
    $db = Database::get();
    $query = $db->query("SELECT ticket.id, ticket.user_changed, ticket.created, ticket.comments, catogory.name, ticket_track.visit
                         FROM `ticket`
                         LEFT JOIN `catogory` ON catogory.id=ticket.cid
                         LEFT JOIN `ticket_track` ON ticket_track.tid=ticket.id AND ticket_track.uid=ticket.uid
                         WHERE ticket.uid='".user["id"]."'
                         GROUP BY ticket.id
                         ORDER BY ticket.user_changed DESC");
    if($query->count() == 0){
      echo "<h3 class='error'>You have not writing any ticket yet</h3>";
    }else{
      echo "<fieldset class='ticket_overview'>";
      echo "<legend>Youer tickets</legend>";
      $query->render("Lib\\Ext\\Page\\ShowTickets\\TicketOverView::userTicket");
      echo "</fieldset>";
    }
    
    if(getUsergroup(user["groupid"])["showTicket"] == 1){
      $query = $db->query("SELECT ticket.id, ticket.admin_changed, ticket.created, ticket.comments, catogory.name, ticket_track.visit, user.username
                           FROM `ticket`
                           LEFT JOIN `catogory` ON catogory.id=ticket.cid
                           LEFT JOIN `ticket_track` ON ticket_track.tid=ticket.id AND ticket_track.uid='".user["id"]."'
                           LEFT JOIN `user` ON user.id=ticket.uid
                           WHERE ticket.uid<>'".user["id"]."'
                           ORDER BY ticket.admin_changed DESC");
      if($query->count() != 0){
        echo "<fieldset class='ticket_overview'>";
          echo "<legend>Other ticket</legend>";
          $query->render("Lib\\Ext\\Page\\ShowTickets\\TicketOverView::userTicket");
        echo "</fieldset>";
      }
    }
  }
  
  public static function userTicket(DatabaseFetch $data){
    echo "<div class='item'>";
    echo "<div class='see_state'>";
      echo "<div class='".(strtotime($data->visit) < strtotime($data->admin_changed ? : $data->user_changed) ? "not" : "has")."'> </div>";
    echo "</div>";
     echo "<div class='information'>";
      echo "<div class='username'>";
       echo "<a href='?view=tickets&ticket_id={$data->id}'>";
        echo $data->name;
       echo "</a>";
      echo"</div>";
      echo "<div class='timeline'>";
       if($data->username){
         echo "Creator: {$data->username} ";
       }
       echo "Created: ".$data->created;
       echo " Changed: ".($data->admin_changed ? : $data->user_changed);
       echo " Comments: ".$data->comments;
      echo "</div>";
     echo "</div>";
     echo "<div class='clear'></div>";
    echo "</div>";
  }
}