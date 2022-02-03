<?php
namespace Lib\Ext\Page\ShowTickets;

use Lib\Controler\Page\PageView as P;
use Lib\Tempelate;
use Lib\Page;
use Lib\Access;
use Lib\Database;
use Lib\User\User;
use Lib\CatAccess;

class PageView implements P{
  public function loginNeeded() : string{
    return "YES";
  }
  
  public function identify() : string{
    return "tickets";
  }
  
  public function access() : array{
    return [];
  }
  
  public function body(Tempelate $tempelate, Page $page, User $user){
    if(!empty($_GET["ticket_id"]) && ($data = $this->getTicketData(intval($_GET["ticket_id"]), $user))){
      TicketView::body($data, $tempelate, $page, $user);
    }else{
      TicketOverView::body($tempelate, $page, $user);
    }
  }
  
  private function getTicketData(int $id, User $user){
    $data = Database::get()->query("SELECT ticket.id, ticket.cid, ticket.open, ticket.uid, catogory.name, catogory.age, user.username, user.birth_day, user.birth_month, user.birth_year
    FROM `".DB_PREFIX."ticket` AS ticket
    LEFT JOIN `".DB_PREFIX."catogory` AS catogory ON catogory.id=ticket.cid
    LEFT JOIN `".DB_PREFIX."user` AS user ON user.id=ticket.uid
    WHERE ticket.id='".$id."'")->fetch();
    if(!$data){
      return null;
    }
    
    if($data->uid == $user->id()){
      return $data;
    }
    
    //we know this is not a user owen ticket. Let finde out if the user has access to it...
    $access = new CatAccess($data->cid, $user);
    
    return $access->has("TICKET_OTHER") ? $data : null;
  }
}
