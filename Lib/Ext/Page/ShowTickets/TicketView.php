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
use Lib\File\FileExtension;
use Lib\Category;
use Lib\Request;
use Lib\User\User;
use Lib\CatAccess;

class TicketView{
  public static function body(DatabaseFetch $data, Tempelate $tempelate, Page $page, User $user){
    Language::load("ticket_view");
    Track::track($data->id, $data->cid, $user->id());
    NewTicket::markRead($data->id);
    NewComment::markRead($data->id);
    $db = Database::get();
    $access = new CatAccess($data->cid, $user);
    
    if(!empty($_POST["create"])){
      self::createComments($data, $tempelate, $user);
    }
    if($access->has("TICKET_CLOSE") && !empty($_GET["close"]) && $data->uid != $user->id()){
      self::changeOpningState($data->open != 1, $data->id, $user);
    }
    if($access->has("TICKET_DELETE") && !empty($_GET["delete"]) && $data->uid != $user->id()){
      self::deleteTicket($data, $user);
    }
    if($access->has("COMMENT_DELETE") && !empty($_GET["deleteComment"]) && $data->uid != $user->id()){
      self::deleteComment(intval($_GET["deleteComment"]), $data->id, $user);
    }
    if(!empty($_GET["dawnload"]) && is_numeric($_GET["dawnload"]))
      self::dawnload($data->id, (int)$_GET["dawnload"]);
    
    $tempelate->put("TICKET_CLOSE",    $access->has("TICKET_CLOSE"));
    $tempelate->put("TICKET_DELETE",   $access->has("TICKET_DELETE"));
    $tempelate->put("COMMENT_DELETE",  $access->has("COMMENT_DELETE"));
    $tempelate->put("ticket_id",       $data->id);
    $tempelate->put("ticket_url",      "?view=tickets&ticket_id=".$data->id);
    $tempelate->put("ticket_username", $data->username);
    $tempelate->put("ticket_open",     $data->open);
    $tempelate->put("ticket_uid",      $data->uid);
    $tempelate->put("owen",            $data->uid == $user->id());
    
    if($data->age){
      $tempelate->put("age", \Lib\Age::calculate($data->birth_day ? : 0, $data->birth_month ? : 0, $data->birth_year ? : 0));
    }
    
    $query = $db->query("SELECT `id`, `text`, `type`, `value` FROM `".DB_PREFIX."ticket_value` WHERE `hid`='".$data->id."'");
    $ticket_data = [];
    while($row = $query->fetch())
      $ticket_data[] = $row->toArray();
    $tempelate->put("ticket_data", $ticket_data);
    
    if($user->id() != $data->uid){
      if($access->has("TICKET_LOG")){
        $log = Log::getTicketLog($data->id);
        $l = [];
        $log->render(function($time, $message) use(&$l){
          $l[] = [
            "time"    => date("H:i d/m/y", $time),
            "message" => $message
            ];
        });
        $tempelate->put("log", $l);
      }
      if($access->has("TICKET_SEEN")){
        $seen = [];
        $query = $db->query("SELECT user.username, ticket_track.visit
                             FROM `".DB_PREFIX."user` AS user
                             LEFT JOIN `".DB_PREFIX."ticket_track` AS ticket_track ON user.id=ticket_track.uid
                             WHERE ticket_track.tid='{$data->id}'
                             ORDER BY ticket_track.visit DESC;");
        while($row = $query->fetch()){
          $d = $row->toArray();
          $d["visit"] = date("H:i d/m/y", $d["visit"]);
          $seen[] = $d;
        }
        $tempelate->put("seen", $seen);
      }
    }
    
    self::getComments($data, $tempelate, $user);
    
    $tempelate->render("show_ticket");
  }
  
  private static function dawnload(int $ticket_id, int $file_id){
    //get the item to be sure its a file
    $db = Database::get();
    $query = $db->query("SELECT `type`, `value`, `text` FROM `".DB_PREFIX."ticket_value` WHERE `id`='{$file_id}' AND `hid`='{$ticket_id}'");
    $item = $query->fetch();
    if(!$item || $item->type != 4){
      Report::error(Language::get("UNKNOWN_FILE"));
      header("location: ?view=tickets&ticket_id=".$ticket_id);
      exit;
    }
    
    //let us dawnload the file
    $file = new FileExtension();
    if(!$file->download($ticket_id, (int)$item->value, $item->text)){
      Report::error(Language::get("UNKNOWN_FILE"));
      header("location: ?view=tickets&ticket_id=".$ticket_id);
      exit;
    }
  }
  
  private static function deleteComment(int $id, int $tid, User $user){
    $result = Database::get()->query("SELECT comment.id, comment.tid, user.username 
                                      FROM `".DB_PREFIX."comment` AS comment
                                      LEFT JOIN `".DB_PREFIX."user` AS user ON user.id=comment.uid
                                      WHERE comment.tid='{$tid}' AND comment.id='{$id}'")->fetch();
    if(!$result){
      Report::error("Unknown comment");
      return;
    }
    Log::ticket($result->tid, "LOG_COMMENT_DELETE", $user->username(), $result->username);
    Plugin::trigger_event("system.comment.delete", $result->id);
    Report::okay(Language::get("COMMENT_DELETED"));
  }
  
  private static function deleteTicket($data, User $user){
    $db = Database::get();
    $db->query("DELETE FROM `".DB_PREFIX."comment` WHERE `tid`='{$data->id}'");
    $db->query("DELETE FROM `".DB_PREFIX."ticket` WHERE `id`='{$data->id}'");
    $db->query("DELETE FROM `".DB_PREFIX."ticket_track` WHERE `tid`='{$data->id}'");
    $db->query("DELETE FROM `".DB_PREFIX."ticket_value` WHERE `hid`='{$data->id}'");
    $db->query("UPDATE `".DB_PREFIX."catogory` SET `ticket_count`=ticket_count-1 WHERE `id`='{$data->cid}'");
    Log::system("LOG_TICKET_DELETE", $user->username(), Category::getNameFromId($data->cid), $data->username);
    Report::okay(Language::get("TICKET_DELETED"));
    Plugin::trigger_event("system.ticket.delete", $data->id, $data->cid);
    header("location: ?view=tickets");
    exit;
  }
  
  private static function changeOpningState(bool $open, $id, User $user){
    if($open){
      Ticket::open($id, $user->id());
      Report::okay(Language::get("TICKET_OPEN"));
    }else{
      Ticket::close($id, $user->id());
      Report::okay(Language::get("TICKET_CLOSED"));
    }
    header("location: ?view=tickets&ticket_id=".$id);
    exit;
  }
  
  private static function createComments(DatabaseFetch $data, Tempelate $tempelate, User $user){
    if($data->open != 1){
      Report::error(Language::get("COMMENT_CLOSED"));
    }elseif(empty($_POST["comments"])){
      Report::error(Language::get("MISSING_MESSAGE"));
    }else{
      Ticket::createComment($data->id, $data->cid, $user->id(), $_POST["comments"], $data->uid == $user->id() || !empty($_POST["public"]), $user);
      Report::okay(Language::get("COMMENT_SAVED"));
      header("location: #");
      exit;
    }
  }
  
  private static function getPage() : int{
    $page = Request::toInt(Request::GET, "page");
    if($page == -1)
      return 0;
    return $page;
  }
  
  private static function pageSelect(Tempelate $tempelate, string $sql, int $page){
    $tempelate->put("p_number", $page);
    $s = explode("\n", $sql);
    $s[0] = "SELECT COUNT(comment.id) AS id";
    $size = Database::get()->query(implode("\n", $s))->fetch()->id;
    $pages = ceil($size / 10);
    $tempelate->put("p_last", $pages);
    if($pages < 10){
      $tempelate->put("back", false);
      $tempelate->put("forward", false);
      if($pages == 1)
        return;
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
  
  private static function getComments(DatabaseFetch $data, Tempelate $tempelate, User $user){
    $db = Database::get();
    $sql = "SELECT comment.id, user.id AS uid, comment.parsed_message, comment.public, comment.created, user.username
                         FROM `".DB_PREFIX."comment` AS comment
                         LEFT JOIN `".DB_PREFIX."user` AS user ON user.id=comment.uid
                         WHERE `tid`='{$data->id}'".($data->uid == $user->id() ? " AND comment.public='1'" : "");
    $page = self::getPage();
    self::pageSelect($tempelate, $sql, $page);
    
    $query = $db->query($sql." LIMIT ".($page * 10).", 10");
    $comments = [];
    while($row = $query->fetch()){
      $data = $row->toArray();
      $data["created"] = date("H:i d/m/y", $data["created"]);
      $comments[] = $data;
    }
    $tempelate->put("comments", $comments);
  }
}
