<?php
namespace Lib\Ext\Page\ShowTickets;

use Lib\Controler\Page\PageView as P;

use Lib\Database;

class PageView implements P{
  public function body(){
    if(!empty($_GET["ticket_id"]) && ($data = $this->getTicketData(intval($_GET["ticket_id"])))){
      TicketView::body($data);
    }else{
      TicketOverView::body();
    }
  }
  
  private function getTicketData(int $id){
    $data = Database::get()->query("SELECT ticket.id, ticket.open, ticket.uid, catogory.name, catogory.age, user.username, user.birth_day, user.birth_month, user.birth_year
    FROM `ticket`
    LEFT JOIN `catogory` ON catogory.id=ticket.cid
    LEFT JOIN `user` ON user.id=ticket.uid
    WHERE ticket.id='".$id."'")->fetch();
    if(!$data){
      return null;
    }
    
    if($data->uid == user["id"]){
      return $data;
    }
    
    return getUsergroup(user["groupid"])["showTicket"] == 1 ? $data : null;
  }
}