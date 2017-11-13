<?php
namespace Lib\Ext\Page\ShowTickets;

use Lib\Ticket\Track;

use Lib\Database;
use Lib\Database\DatabaseFetch;
use Lib\Ext\Notification\NewTicket;
use Lib\Ext\Notification\NewComment;
use Lib\Report;
use Lib\Bbcode\Parser;
use Lib\Email;
use Lib\User\Info;
use Lib\Log;
use Lib\Plugin\Plugin;
use Lib\Tempelate;

class TicketView{
  public static function body(DatabaseFetch $data, Tempelate $tempelate){
    Track::track($data->id, user["id"]);
    NewTicket::markRead($data->id);
    NewComment::markRead($data->id);
    $group = getUsergroup(user["groupid"]);
    if(!empty($_POST["create"])){
      self::createComments($data, $tempelate);
    }
    if($group["closeTicket"] == 1 && !empty($_GET["close"]) && $data->uid != user["id"]){
      self::changeOpningState($data->open != 1, $data->id);
    }
    if(group["deleteTicket"] == 1 && !empty($_GET["delete"]) && $data->uid != user["id"]){
      self::deleteTicket($data->id);
    }
    if(group["deleteComment"] == 1 && !empty($_GET["deleteComment"]) && $data->uid != user["id"]){
      self::deleteComment(intval($_GET["deleteComment"]), $data->id);
    }
    
    $tempelate->put("ticket_id",       $data->id);
    $tempelate->put("ticket_username", $data->username);
    $tempelate->put("ticket_open",     $data->open);
    $tempelate->put("ticket_uid",      $data->uid);
    $tempelate->put("owen",            $data->uid == user["id"]);
    
    if($data->age){
      $tempelate->put("age", \Lib\Age::calculate($data->birth_day, $data->birth_month, $data->birth_year));
    }
    
    $query = Database::get()->query("SELECT `text`, `type`, `value` FROM `ticket_value` WHERE `hid`='".$data->id."'");
    $ticket_data = [];
    while($row = $query->fetch())
      $ticket_data[] = $row->toArray();
    $tempelate->put("ticket_data", $ticket_data);
    
    if(user["id"] != $data->uid && group["showTicketLog"] == 1){
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
    
    self::getComments($data, $tempelate);
    
    $tempelate->render("show_ticket");
    return;
    echo "<fieldset>";
    echo "<legend>Information</legend>";
    if($group["closeTicket"] == 1 && $data->uid != user["id"]){
      $item = 0;
      if(group["closeTicket"] == 1){
        $item++;
        echo two_container("Change opning state", "<a href='?view=tickets&ticket_id=".$data->id."&close=true'>".($data->open == 1 ? "Close" : "Open")."</a>");
      }
      if(group["deleteTicket"] == 1){
        $item++;
        echo two_container("Delete this ticket", "<a href='?view=tickets&ticket_id={$data->id}&delete=true'>Delete this ticket</a>");
      }
      if($item > 0)
        echo "<hr>";
    }
    echo two_container("Category", $data->name);
    echo two_container("From", Info::userLink($data->uid, $data->username));
    
    if($data->age){
      echo two_container("Age",    \Lib\Age::calculate($data->birth_day, $data->birth_month, $data->birth_year));
    }
    echo "</fieldset>";
    
    echo "<fieldset>";
    echo "<legend>Data</legend>";
    $db = Database::get();
    $query = $db->query("SELECT `text`, `type`, `value` FROM `ticket_value` WHERE `hid`='".$data->id."'");
    if($query->count() == 0){
      echo "<h3>No data avaribel</h3>";
    }else{
      $table = new Table();
      $table->style = "width:100%;border-collapse:collapse;";
      $query->render(function($row) use($table){
        self::setItem($table, $row);
      });
      $table->output();
    }
    echo "</fieldset>";
    
    if(group["showTicketLog"] == 1){
      $log = Log::getTicketLog($data->id);
      if($log->size() > 0){
        echo "<fieldset>";
          echo "<legend>Log</legend>";
          $log->render(function($time, $message){
            echo "<div><i>[{$time}]</i>{$message}</div>";
          });
        echo "</fieldset>";
      }
    }
    
    Parser::getJavascript();
    echo "<fieldset>";
     echo "<legend>Comments</legend>";
     self::getComments($data);
    echo "</fieldset>";
    
    if($data->open == 1){
      echo "<form method='post' action='#'>";
      echo "<fieldset>";
        echo "<legend>Create comment</legend>";
        echo "<div>";
          echo "<div>";
            echo "<textarea id='comment' name='comments'></textarea>";
          echo "</div>";
          echo "<div>";
            echo "<input type='submit' name='create' value='Create comments'>";
          echo "</div>";
          if($data->uid != user["id"]){
            echo "<div>";
             echo "Public <input type='checkbox' name='public' value='yes' class='leave'>";
            echo "</div>";
          }
        echo "</div>";
       echo "</fieldset>";
      echo "</form>";
    }
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
    Log::ticket($result->tid, "%s deleted a comment writet by %s", user["username"], $result->username);
    Plugin::trigger_event("system.comment.delete", $result->id);
    Report::okay("The ticket is deleted");
  }
  
  private static function deleteTicket($id){
    Plugin::trigger_event("system.ticket.delete", $id);
    Report::okay("You have deleted the ticket");
    header("location: ?view=tickets");
    exit;
  }
  
  private static function changeOpningState(bool $open, $id){
    Database::get()->query("UPDATE `ticket` SET `open`='".($open ? 1 : 0)."' WHERE `id`='{$id}'");
    if($open){
      Report::okay("Ticket is now open");
      Log::ticket($id, "%s open the ticket", user["username"]);
    }else{
      Log::ticket($id, "%s closed the ticket", user["username"]);
      Report::okay("Ticket is now closed");
    }
    header("location: ?view=tickets&ticket_id=".$id);
    exit;
  }
  
  private static function createComments(DatabaseFetch $data, Tempelate $tempelate){
    if($data->open != 1){
      Report::error("You can not comments on closed ticket");
    }elseif(empty($_POST["comments"])){
      Report::error("Missing message");
    }else{
      $public = $data->uid == user["id"] || !empty($_POST["public"]);
      $db = Database::get();
      $parser = new Parser($_POST["comments"]);
      $db->query("INSERT INTO `comment` VALUES (
                    NULL,
                    '{$data->id}', 
                    '".user["id"]."', 
                    '".($public ? "1" : "0")."', 
                    NOW(),
                    '".$db->escape($_POST["comments"])."',
                    '{$db->escape($parser->getHtml())}'
                  );");
      $db->query("UPDATE `ticket` SET `admin_changed`=NOW(), `comments`=comments+1".($public ? ", `user_changed`=NOW()" : "")." WHERE `id`='{$data->id}'");
      NewComment::createNotify($data->id, $data->uid, $public);
      Report::okay("Comments saved");
      self::sendEmailOnComment($data, $public);
      header("location: #");
      exit;
    }
  }
  
  private static function sendEmailOnComment(DatabaseFetch $data, bool $public){
    $email = new Email();
    $db = Database::get();
    $query = $db->query("SELECT user.username, user.email
                         FROM `user`
                         LEFT JOIN `group` ON user.groupid=group.id
                         LEFT JOIN `comment` ON comment.uid=user.id
                         WHERE group.showTicket='1'
                         AND comment.tid='{$data->id}'
                         ".($public ? "" : "AND user.id <> '{$data->uid}'")."
                         AND user.id<>'".user["id"]."'
                         GROUP BY user.id");
    while($row = $query->fetch()){
      $email->pushArg("creator", $row->username);
      $email->pushArg("category", $data->name);
      $email->pushArg("my_username", user["username"]);
      $email->send("new_comment", $row->email);
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