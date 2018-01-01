<?php
namespace Lib\Ext\Page\ShowTickets;

use Lib\Database;
use Lib\Database\DatabaseFetch;
use Lib\Tempelate;
use Lib\Page;
use Lib\Access;
use Lib\Language\Language;

class TicketOverView{
  public static function body(Tempelate $tempelate, Page $page){
    Language::load("ticket_overview");
    $db = Database::get();
    $query = $db->query("SELECT ticket.id, ticket.open, ticket.user_changed, ticket.created, ticket.comments, catogory.name, ticket_track.visit
                         FROM `ticket`
                         LEFT JOIN `catogory` ON catogory.id=ticket.cid
                         LEFT JOIN `ticket_track` ON ticket_track.tid=ticket.id AND ticket_track.uid=ticket.uid
                         WHERE ticket.uid='".user["id"]."'
                         ".(!empty($_GET["showclosed"]) ? "" : "AND ticket.open='1'")."
                         GROUP BY ticket.id
                         ORDER BY ticket.user_changed DESC");
    $owen = [];
    while($data = $query->fetch()){
      $owen[] = [
        "closed"   => $data->open == 0,
        "readed"   => strtotime($data->visit) >= strtotime($data->user_changed),
        "id"       => $data->id,
        "cat_name" => $data->name,
        "created"  => $data->created,
        "changed"  => $data->user_changed,
        "comments" => $data->comments
      ];
    }
    $tempelate->put("owen_ticket", $owen);
    if(Access::userHasAccess("TICKET_OTHER")){
      $query = $db->query("SELECT ticket.id, ticket.open, ticket.admin_changed, ticket.created, ticket.comments, ticket.admin_comments, catogory.name, ticket_track.visit, user.username
                           FROM `ticket`
                           LEFT JOIN `catogory` ON catogory.id=ticket.cid
                           LEFT JOIN `ticket_track` ON ticket_track.tid=ticket.id AND ticket_track.uid='".user["id"]."'
                           LEFT JOIN `user` ON user.id=ticket.uid
                           WHERE ticket.uid<>'".user["id"]."'
                           GROUP BY ticket.id
                           ORDER BY ticket.admin_changed DESC");
      $other_ticket = [];
      while($data = $query->fetch()){
        $other_ticket[] = [
          "closed"   => $data->open == 0,
          "readed"   => strtotime($data->visit) >= strtotime($data->admin_changed),
          "id"       => $data->id,
          "cat_name" => $data->name,
          "created"  => $data->created,
          "changed"  => $data->admin_changed,
          "comments" => $data->comments+$data->admin_comments,
          "username" => $data->username
        ];
      }
      $tempelate->put("other_ticket", $other_ticket);
    }
    
    $tempelate->put("closedTicket", !empty($_GET["showclosed"]));
    
    $tempelate->render("ticket_list");
  }
}