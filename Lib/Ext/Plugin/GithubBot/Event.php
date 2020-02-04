<?php
namespace Lib\Ext\Plugin\GithubBot;

use Lib\Plugin\PluginInterface;
use Lib\Plugin\Event as E;
use Lib\Access;
use Lib\Report;
use Lib\Database;
use Lib\Config;
use Lib\Ticket\Ticket;
use Lib\User\Info;
use Lib\Language\Language;
use Lib\Ajax;

class Event implements PluginInterface{
  private $github;
  
  public function getEvents() : array{
    $config_dir = "Lib/Ext/Plugin/GithubBot/config.json";
    if(!file_exists($config_dir)){
      if(!Ajax::isAjaxRequest() && Access::userHasAccess("PLUGIN_INSTALL"))
        Report::error(Language::get("MISSING_CONFIG", [$config_dir]));
      return [
      "system.category.delete" => [$this, "onCategoryDelete"]
      ];
    }
    return [
      "system.started"         => [$this, "started"],
      "system.comment.created" => [$this, "onComment"],
      "system.ticket.close"    => [$this, "onTicketClose"],
      "system.ticket.open"     => [$this, "onTicketOpen"],
      "system.ticket.delete"   => [$this, "onTicketDelete"],
      "system.category.delete" => [$this, "onCategoryDelete"]
      ];
  }
  
  public function onCategoryDelete(E $event, int $id){
    if($id == Config::get("github_cat")){
      Report::error(Language::get("CANT_DELETE_CAT"));
      $event->stop();
    }
  }
  
  public function onTicketDelete(E $event, int $id){
    Database::get()->query("DELETE FROM `".DB_PREFIX."githubbot` WHERE `ticket_id`='{$id}'");
  }
  
  public function onTicketOpen(E $event, int $tid, int $uid, string $username){
    if($uid == Config::get("github_user"))
       return;
    
    $db = Database::get();
    $data = $db->query("SELECT `number` FROM `".DB_PREFIX."githubbot` WHERE `ticket_id`='{$tid}';")->fetch();
    if(!$data)
      return;
    
    $github = new Github();
    $config = json_decode(file_get_contents("Lib/Ext/Plugin/GithubBot/config.json"));
    if(!$config){
      $this->reportJsonError();
      return;
    }
    
    if($config->user){
      $github->addUser(new GithubUser($config->user->username, $config->user->password));
    }
    
    $github->openIssues($config->owner, $config->repo, $data->number, "Closed by ".$username);
  }
  
  public function onTicketClose(E $event, int $tid, int $uid, string $username){
    if($uid == Config::get("github_user"))
       return;
    
    $db = Database::get();
    $data = $db->query("SELECT `number` FROM `".DB_PREFIX."githubbot` WHERE `ticket_id`='{$tid}';")->fetch();
    if(!$data)
      return;
    
    $github = new Github();
    $config = json_decode(file_get_contents("Lib/Ext/Plugin/GithubBot/config.json"));
    if(!$config){
      $this->reportJsonError();
      return;
    }
    
    if($config->user){
      $github->addUser(new GithubUser($config->user->username, $config->user->password));
    }
    
    $github->closeIssues($config->owner, $config->repo, $data->number, "Closed by ".$username);
  }
  
  public function onComment(E $event, int $tid, int $uid, string $message, bool $public){
    if(!$public || $uid == Config::get("github_user"))
      return;
    
    //wee se if this belongs to a github issues ticket
    $db = Database::get();
    $data = $db->query("SELECT `number` FROM `".DB_PREFIX."githubbot` WHERE `ticket_id`='{$tid}'")->fetch();
    if(!$data)
      return;
    
    $github = new Github();
    $config = json_decode(file_get_contents("Lib/Ext/Plugin/GithubBot/config.json"));
    if(!$config){
      $this->reportJsonError();
      return;
    }
    if($config->user){
      $github->addUser(new GithubUser($config->user->username, $config->user->password));
    }
    $username = Info::getUsername($uid);
    $github->commentIssues($config->owner, $config->repo, $data->number, "User {$username}\r\n".$message);
  }
  
  public function started(){
    $this->github = new Github();
    $config = json_decode(file_get_contents("Lib/Ext/Plugin/GithubBot/config.json"));
    if(!$config){
      $this->reportJsonError();
      return;
    }
    if($config->secret){
      $this->github->webhook()->setSecretKey($config->secret);
    }
    
    if($this->github->webhook()->isWebhook()){
      switch($this->github->webhook()->type()){
        case "ping":
          echo "This message is respons on Github webhook ping respons\r\nThis means this bot is installed and ready to use";
          break;
        case "issues":
          $this->handleIssues($this->github->webhook());
          break;
        case "issue_comment":
          $this->handleComments($this->github->webhook());
          break;
        default:
          echo "Unknown github respons type: ".$this->github->webhook()->type();
      }
      exit;
    }
  }
  
  private function handleComments($webhook){
    $controler = $webhook->getControler();
    switch($controler->action()){
      case "created":
        $this->createComment($controler);
        break;
      default:
        echo "Unknown comment action: ".$controler->action();
    }
  }
  
  private function createComment($controler){
    if($controler->getUser()->username() == "Github")
      return;
    //Get first the data from our owen database to finde link betwen the issues and our ticket
    $db = Database::get();
    $data = $db->query("SELECT `ticket_id` FROM `".DB_PREFIX."githubbot` WHERE `item_id`='{$controler->id()}';")->fetch(function(int $id) use($controler){
      Ticket::createComment(
        $id,
        Config::get("github_user"),
        $controler->message(),
        true
        );
    });
    echo "Thanks i has saved the comment";
  }
  
  private function handleIssues($webhook){
    $controler = $webhook->getControler();
    switch($controler->action()){
      case "opened":
        $this->createTicket($controler);
        break;
      case "closed":
        $this->closeTicket($controler);
        break;
      case "reopened":
        $this->openTicket($controler);
        break;
      default:
        echo "Unknown issues action: ".$controler->action();
    }
  }
  
  private function openTicket($issues){
    Database::get()->query("SELECT `ticket_id` FROM `".DB_PREFIX."githubbot` WHERE `item_id`='{$issues->id()}';")->fetch(function(int $id) use($controler){
      Ticket::open($id, Config::get("github_user"));
    });
  }
  
  private function closeTicket($issues){
    Database::get()->query("SELECT `ticket_id` FROM `".DB_PREFIX."githubbot` WHERE `item_id`='{$issues->id()}';")->fetch(function(int $id) use($controler){
      Ticket::close($id, Config::get("github_user"));
    });
  }
  
  private function createTicket($issues){
    $id = Ticket::createTicket(Config::get("github_user"), Config::get("github_cat"), [
        [
          "text"  => "Github user",
          "type"  => 1,
          "value" => $issues->getUser()->username()
        ],
        [
          "text"  => "Title",
          "type"  => 1,
          "value" => $issues->title()
        ],
        [
          "text"  => "Message",
          "type"  => 2,
          "value" => $issues->message()
        ]
      ]);
    $db = Database::get();
    $db->query("INSERT INTO `".DB_PREFIX."githubbot` VALUES ('ISSUES', '{$issues->id()}', '{$id}', '{$issues->number()}');");
    echo "Thanks the issues is now saved in our database";
  }
  
  private function reportJsonError(){
    $code = json_last_error();
    if($code === JSON_ERROR_NONE){
      Report::error("Config file for GithubBot plugin would not load becuse a unknown error");
      return;
    }
    
    Report::error("Get config json result in '".json_last_error_msg()."' error");
  }
}