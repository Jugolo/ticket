<?php
namespace Lib\Ext\Page\ShowTickets;

use Lib\Database;
use Lib\Database\DatabaseFetch;
use Lib\Tempelate;
use Lib\Page;
use Lib\Access;
use Lib\Language\Language;
use Lib\Request;

class TicketOverView{
  public static function body(Tempelate $tempelate, Page $page){
    Language::load("ticket_overview");
    $db = Database::get();
    $sql = "SELECT ticket.id, ticket.uid, catogory.name, ticket.open, ticket_track.visit, ticket.created, ticket.user_changed, ticket.admin_changed, user.username
            FROM `".DB_PREFIX."ticket` AS ticket
            LEFT JOIN `".DB_PREFIX."catogory` AS catogory ON catogory.id=ticket.cid
            LEFT JOIN `".DB_PREFIX."ticket_track` AS ticket_track ON ticket_track.tid=ticket.id AND ticket_track.uid='".user["id"]."'
            LEFT JOIN `".DB_PREFIX."user` AS user ON user.id=ticket.uid";
    
    
    if(!Access::userHasAccess("TICKET_OTHER")){
      $sql .= " WHERE ticket.uid='".user["id"]."'";
    }
    
    $page = self::getPage();
    
    self::pageSelect($tempelate, $sql, $page);
    
    $sql .= " ORDER BY IF(ticket.uid='".user["id"]."', ticket.user_changed, ticket.admin_changed) DESC";
    
    $query = $db->query($sql." LIMIT ".ceil($page * 30).", 30");
    $result = [];
    while($row = $query->fetch()){
      $data = $row->toArray();
      $data["read"]    = $row->visit >= ($row->uid == user["id"] ? $row->user_changed : $row->admin_changed);
      $data["changed"] = date("H:i d/m/y", $row->uid == user["id"] ? $row->user_changed : $row->admin_changed);
      $data["created"] = date("H:i d/m/y", $row->created);
      $result[] = $data;
    }
    $tempelate->put("tickets", $result);
    
    $tempelate->render('ticket_list');
  }
  
  private static function pageSelect(Tempelate $tempelate, string $sql, int $page){
    $tempelate->put("p_number", $page);
    $s = explode("\n", $sql);
    $s[0] = "SELECT COUNT(ticket.id) AS id";
    $size = Database::get()->query(implode("\n", $s))->fetch()->id;
    $pages = ceil($size / 30);
    $tempelate->put("p_last", $pages);
    if($pages < 10){
      if($pages == 1)
        return;
      $tempelate->put("back", false);
      $tempelate->put("forward", false);
      $min = 0;
      $max = $pages;
    }else{
      if($page - 5 < 0){
        $tempelate->put("back", false);
        $tempelate->put("forward", $pages > $page + 5);
        $min = 0;
        $max = 10;
      }else{
        if($pages < $page + 5){
          $tempelate->put("back", true);
          $tempelate->put("forward", false);
          $min = $pages - 10;
          $max = $pages;
        }else{
          $tempelate->put("back", true);
          $tempelate->put("forward", true);
          $min = $page - 5;
          $max = $page + 5;
        }
      }
    }
    $pages = [];
    for($i=$min;$i<$max;$i++){
      $pages[] = [
        "page"    => $i,
        "show"    => $i+1,
        "current" => $i == $page
        ];
    }
    $tempelate->put("pages", $pages);
  }
  
  private static function getPage() : int{
    $current = Request::toInt(Request::GET, "page");
    if($current == -1)
      return 0;
    
    return $current;
  }
}