<?php
namespace Lib\Setup\Upgrade;

use Lib\Database;
use Lib\Config;
use Lib\Bbcode\Parser;

class V34{
 public $version = "V3.4";
  
  public function upgrade(){
    $db = Database::get();
    $db->query("CREATE TABLE IF NOT EXISTS `access` (
                  `gid` int(11) NOT NULL,
                  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
    $db->query("SELECT * FROM `group`")->render([$this, "updateAccess"]);
    $db->query("ALTER TABLE `group`
                DROP `showTicket`,
                DROP `changeGroup`,
                DROP `handleGroup`,
                DROP `handleTickets`,
                DROP `showError`,
                DROP `showProfile`,
                DROP `closeTicket`,
                DROP `changeFront`,
                DROP `changeSystemName`,
                DROP `showTicketLog`,
                DROP `deleteTicket`,
                DROP `deleteComment`,
                DROP `activateUser`,
                DROP `viewUserLog`,
                DROP `viewSystemLog`,
                DROP `handleTempelate`;");
    $db->query("CREATE TABLE `plugin` ( 
                          `id` INT(11) NOT NULL AUTO_INCREMENT,
                          `path` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
                          PRIMARY KEY(`id`)
                ) ENGINE = InnoDB DEFAULT CHARSET=utf8;");
    return true; 
  }
  
  public function updateAccess($row){
    $insert = [];
      
    if($row->showTicket == 1){
      $insert[] = "TICKET_OTHER";
    }
    
    if($row->changeGroup == 1){
      $insert[] = "USER_GROUP";
    }
                         
    if($row->handleGroup == 1){
      $insert[] = "GROUP_CREATE";
      $insert[] = "GROUP_DELETE";
      $insert[] = "GROUP_ACCESS";
      $insert[] = "GROUP_STANDART";
    }
    
    if($row->handleTickets == 1){
      $insert[] = "CATEGORY_CREATE";
      $insert[] = "CATEGORY_DELETE";
      $insert[] = "CATEGORY_CLOSE";
      $insert[] = "CATEGORY_APPEND";
      $insert[] = "CATEGORY_ITEM_DELETE";
      $insert[] = "CATEGORY_SETTING";
    }
                         
    if($row->showError == 1){
      $insert[] = "ERROR_SHOW";
      $insert[] = "ERROR_DELETE";
    }
                         
    if($row->showProfile == 1){
      $insert[] = "USER_PROFILE";
    }
                         
    if($row->closeTicket == 1){
      $insert[] = "TICKET_CLOSE";
    }
     
    if($row->showTicketLog == 1){
      $insert[] = "TICKET_LOG";
    }
                         
    if($row->deleteTicket == 1){
      $insert[] = "TICKET_DELETE";
    }
                         
    if($row->deleteComment == 1){
      $insert[] = "COMMENT_DELETE";
    }
     
    if($row->viewUserLog == 1){
      $insert[] = "USER_LOG";
    }
                         
    if($row->changeFront == 1){
      $insert[] = "SYSTEM_FRONT";
    }
                         
    if($row->changeSystemName == 1){
      $insert[] = "SYSTEM_NAME";
    }
                         
    if($row->activateUser == 1){
      $insert[] = "USER_DELETE";
      $insert[] = "USER_ACTIVATE";
    }
                         
    if($row->viewSystemLog == 1){
      $insert[] = "SYSTEMLOG_SHOW";
    }
                         
    if($row->handleTempelate == 1){
      $insert[] = "TEMPELATE_SELECT";
    }
    
    if(count($insert) == 0)
      return;
    
    $db = Database::get();
    $sql = [];
    foreach($insert as $data)
      $sql[] = " ('{$row->id}', '{$data}')";
    $db->query("INSERT INTO `access` VALUES (".implode(", ", $sql).");");
  }
}