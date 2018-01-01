<?php
namespace Lib\Ext\Page\ShowTickets;

use Lib\Ticket\Track;

use Lib\Database;
use Lib\Database\DatabaseFetch;
use Lib\Ext\Notification\NewTicket;
use Lib\Ext\Notification\NewComment;
use Lib\Report;
use Lib\User\Info;
use Lib\Log;
use Lib\Plugin\Plugin;
use Lib\Tempelate;
use Lib\Page;
use Lib\Access;
use Lib\Ticket\Ticket;
use Lib\Language\Language;

class TicketView{
  public static function body(DatabaseFetch $data, Tempelate $tempelate, Page $page){
    Language::load("ticket_view");
    Track::track($data->id, user["id"]);
    NewTicket::markRead($data->id);
    NewComment::markRead($data->id);
    $group = getUsergroup(user["groupid"]);
    $db = Database::get();
    if(!empty($_POST["create"])){
      self::createComments($data, $tempelate);
    }
    if(Access::userHasAccess("TICKET_CLOSE") && !empty($_GET["close"]) && $data->uid != user["id"]){
      self::changeOpningState($data->open != 1, $data->id);
    }
    if(Access::userHasAccess("TICKET_DELETE") && !empty($_GET["delete"]) && $data->uid != user["id"]){
      self::deleteTicket($data->id);
    }
    if(Access::userHasAccess("COMMENT_DELETE") && !empty($_GET["deleteComment"]) && $data->uid != user["id"]){
      self::deleteComment(intval($_GET["deleteComment"]), $data->id);
    }
    
    $tempelate->put("ticket_id",       $data->id);
    $tempelate->put("ticket_username", $data->username);
    $tempelate->put("ticket_open",     $data->open);
    $tempelate->put("ticket_uid",      $data->uid);
    $tempelate->put("owen",            $data->uid == user["id"]);
    
    if($data->age){
      $tempelate->put("age", \Lib\Age::calculate($data->birth_day ? : 0, $data->birth_month ? : 0, $data->birth_year ? : 0));
    }
    
    $query = $db->query("SELECT `text`, `type`, `value` FROM `ticket_value` WHERE `hid`='".$data->id."'");
    $ticket_data = [];
    while($row = $query->fetch())
      $ticket_data[] = $row->toArray();
    $tempelate->put("ticket_data", $ticket_data);
    
    if(user["id"] != $data->uid){
      if(Access::userHasAccess("TICKET_LOG")){
        $log = Log::getTicketLog($data->id);
        $l = [];
        $log->render(function($time, $message) use(&$l){
          $l[] = [
            "time"    => $time,
            "message" => $message
            ];
        });
        $tempelate->put("log", $l);
      }
      if(Access::userHasAccess("TICKET_SEEN")){
        $seen = [];
        $query = $db->query("SELECT user.username, ticket_track.visit
                             FROM `user`
                             LEFT JOIN `ticket_track` ON user.id=ticket_track.uid
                             WHERE ticket_track.tid='{$data->id}'
                             ORDER BY ticket_track.visit DESC;");
        while($row = $query->fetch())
          $seen[] = $row->toArray();
        $tempelate->put("seen", $seen);
      }
    }
    
    self::getComments($data, $tempelate);
    
    $tempelate->render("show_ticket");
  }
  
  private static function deleteComment(int $id, int $tid){
    $result = Database::get()->query("SELECT comment.id, comment.tid, user.username 
                                      FROM `comment` 
                                      LEFT JOIN `user` ON user.id=comment.uid
                                      WHERE comment.tid='{$tid}' AND comment.id='{$id}'")->fetch();
    if(!$result){
      Report::error("Unknown comment");
      return;
    }
    Log::ticket($result->tid, "LOG_COMMENT_DELETE", user["username"], $result->username);
    Plugin::trigger_event("system.comment.delete", $result->id);
    Report::okay(Language::get("COMMENT_DELETED"));
  }
  
  private static function deleteTicket($id){
    Plugin::trigger_event("system.ticket.delete", $id);
    Report::okay(Language::get("TICKET_DELETED"));
    header("location: ?view=tickets");
    exit;
  }
  
  private static function changeOpningState(bool $open, $id){
    if($open){
      Ticket::open($id, user["id"]);
      Report::okay(Language::get("TICKET_OPEN"));
    }else{
      Ticket::close($id, user["id"]);
      Report::okay(Language::get("TICKET_CLOSED"));
    }
    header("location: ?view=tickets&ticket_id=".$id);
    exit;
  }
  
  private static function createComments(DatabaseFetch $data, Tempelate $tempelate){
    if($data->open != 1){
      Report::error(Language::get("COMMENT_CLOSED"));
    }elseif(empty($_POST["comments"])){
      Report::error(Language::get("MISSING_MESSAGE"));
    }else{
      Ticket::createComment($data->id, user["id"], $_POST["comments"], $data->uid == user["id"] || !empty($_POST["public"]));
      Report::okay(Language::get("COMMENT_SAVED"));
      header("location: #");
      exit;
    }
  }
  
  private static function getComments(DatabaseFetch $data, Tempelate $tempelate){
    $db = Database::get();
    $query = $db->query("SELECT comment.id, user.id AS uid, comment.parsed_message, comment.public, comment.created, user.username
                         FROM `comment`
                         LEFT JOIN `user` ON user.id=comment.uid
                         WHERE `tid`='{$data->id}'".($data->uid == user["id"] ? " AND comment.public='1'" : ""));
    $comments = [];
    while($row = $query->fetch()){
      $comments[] = $row->toArray();
    }
    $tempelate->put("comments", $comments);
  }
}