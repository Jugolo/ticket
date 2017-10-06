<?php
namespace Lib\Ext\Page\ShowTickets;

use Lib\Database;
use Lib\Database\DatabaseFetch;
use Lib\Html\Table;

class TicketOverView{
  public static function body(){
    ?>
    <script>
      function changeClosedView(){
        if(CowUrl.get("showclosed")){
         window.location.href = "?view=tickets"; 
        }else{
         window.location.href = "?view=tickets&showclosed=true";
        }
      }
    </script>
    <?php
    $db = Database::get();
    $query = $db->query("SELECT ticket.id, ticket.open, ticket.user_changed, ticket.created, ticket.comments, catogory.name, ticket_track.visit
                         FROM `ticket`
                         LEFT JOIN `catogory` ON catogory.id=ticket.cid
                         LEFT JOIN `ticket_track` ON ticket_track.tid=ticket.id AND ticket_track.uid=ticket.uid
                         WHERE ticket.uid='".user["id"]."'
                         ".(!empty($_GET["showclosed"]) ? "" : "AND ticket.open='1'")."
                         GROUP BY ticket.id
                         ORDER BY ticket.user_changed DESC");
    echo "<fieldset>";
    echo "Show closed tickets: <input onclick='changeClosedView();' class='leave' type='checkbox'".(!empty($_GET["showclosed"]) ? " checked" : "").">";
    echo "<hr>";
    if($query->count() == 0){
      echo "<h3 class='error'>You have not writing any ticket yet</h3>";
    }else{
      echo "<legend>Youer tickets</legend>";
      $table = new Table();
      $table->className("ticket_overview");
      $query->render("Lib\\Ext\\Page\\ShowTickets\\TicketOverView::userTicket", $table);
      $table->output();
    }
    echo "</fieldset>";
    
    if(getUsergroup(user["groupid"])["showTicket"] == 1){
      $query = $db->query("SELECT ticket.id, ticket.open, ticket.admin_changed, ticket.created, ticket.comments, catogory.name, ticket_track.visit, user.username
                           FROM `ticket`
                           LEFT JOIN `catogory` ON catogory.id=ticket.cid
                           LEFT JOIN `ticket_track` ON ticket_track.tid=ticket.id AND ticket_track.uid='".user["id"]."'
                           LEFT JOIN `user` ON user.id=ticket.uid
                           WHERE ticket.uid<>'".user["id"]."'
                           GROUP BY ticket.id
                           ORDER BY ticket.admin_changed DESC");
      if($query->count() != 0){
        echo "<fieldset>";
          echo "<legend>Other ticket</legend>";
           $table = new Table();
           $table->className("ticket_overview");
           $query->render("Lib\\Ext\\Page\\ShowTickets\\TicketOverView::userTicket", $table);
           $table->output();
        echo "</fieldset>";
      }
    }
  }
  
  public static function userTicket(DatabaseFetch $data, Table $table){
    $table->newColummen();
    $status = $table->td("<div> </div>", true);
    $s = "has";
    
    if($data->open == 1){
      if(strtotime($data->visit) < strtotime($data->admin_changed ? : $data->user_changed)){
        $s = "not";
      }
    }else{
      $s = "close";
    }
    $status->setClass("status ".$s);
    $status->rowspan = 2;
    $table->td("<a href='?view=tickets&ticket_id={$data->id}'>".htmlentities($data->name)."</a>", true)->setClass("name");
    $table->newColummen();
    $table->td(($data->username ? "Creator: ".htmlentities($data->username) : "")." Created: ".$data->created." Changed: ".($data->admin_changed ? : $data->user_changed)." Comments: ".$data->comments)->setClass("timeline");
  }
}