<?php
namespace Lib\Ext\Page\ShowTickets;

use Lib\Ticket\Track;

use Lib\Database;
use Lib\Database\DatabaseFetch;
use Lib\Ext\Notification\NewTicket;
use Lib\Ext\Notification\NewComment;
use Lib\Error;
use Lib\Okay;
use Lib\Html\Table;

class TicketView{
  public static function body(DatabaseFetch $data){
    Track::track($data->id, user["id"]);
    NewTicket::markRead($data->id);
    NewComment::markRead($data->id);
    $group = getUsergroup(user["groupid"]);
    if(!empty($_POST["create"])){
      self::createComments($data);
    }
    echo "<fieldset>";
    echo "<legend>Information</legend>";
    echo two_container("Category", $data->name);
    if($group["showProfile"] == 1){
      echo two_container("From", "<a href='?view=profile&user={$data->uid}'>{$data->username}</a>");
    }else{
      echo two_container("From", $data->username);
    }
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
    
    echo "<fieldset>";
     echo "<legend>Comments</legend>";
     self::getComments($data);
    echo "</fieldset>";
    
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
  
  private static function createComments(DatabaseFetch $data){
    if(empty($_POST["comments"])){
      Error::report("Missing message");
    }else{
      $public = $data->uid == user["id"] || !empty($_POST["public"]);
      $db = Database::get();
      $db->query("INSERT INTO `comment` VALUES (
                    NULL,
                    '{$data->id}', 
                    '".user["id"]."', 
                    '".($public ? "1" : "0")."', 
                    NOW(),
                    '".$db->escape($_POST["comments"])."'
                  );");
      $db->query("UPDATE `ticket` SET `admin_changed`=NOW(), `comments`=comments+1".($public ? ", `user_changed`=NOW()" : "")." WHERE `id`='{$data->id}'");
      NewComment::createNotify($data->id, $data->uid, $public);
      Okay::report("Comments saved");
      header("location: #");
      exit;
    }
  }
  
  private static function getComments(DatabaseFetch $data){
    $db = Database::get();
    $query = $db->query("SELECT comment.message, comment.public, comment.created, user.username
                         FROM `comment`
                         LEFT JOIN `user` ON user.id=comment.uid
                         WHERE `tid`='{$data->id}'".($data->uid == user["id"] ? " AND comment.public='1'" : ""));
    if($query->count() == 0){
      echo "<h3>No comments yet</h3>";
    }else{
      echo "<div class='comments'>";
        $query->render(function($row) use($data){
          TicketView::handleComments($row, $data);
        });
      echo "</div>";
    }
  }
  
  public static function handleComments(DatabaseFetch $data, DatabaseFetch $ticket){
    echo "<div class='comment_item'>";
      echo "<div class='information'>";
        echo "<ul>";
          echo "<li>From: {$data->username}</li>";
          if(user["id"] != $ticket->uid){
            echo "<li>Is public: ".($data->get("public") == 1 ? "yes" : "no")."</li>";
          }
          echo "<li>Created: {$data->created}</li>";
        echo "</ul>";
      echo "</div>";
      echo "<div class='message'>";
       echo nl2br(htmlentities($data->message));
      echo "</div>";
      echo "<div class='clear'></div>";
    echo "</div>";
  }
  
  private static function setItem(Table $table, DatabaseFetch $data){
    $table->newColummen();
    if($data->type != 2){
      $table->th($data->text)->style = "border:1px solid black;background-color:blue;color:white;";
      $table->td($data->value)->style = "border:1px solid black";
    }else{
      $item = $table->th($data->text);
      $item->colspan = "2";
      $item->style = "border:1px solid black;background-color:blue;color:white;";
      $table->newColummen();
      $item = $table->td($data->value);
      $item->colspan = "2";
      $item->style = "border: 1px solid black";
    }
  }
}