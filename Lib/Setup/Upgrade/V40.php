<?php
namespace Lib\Setup\Upgrade;

use Lib\Database;
use Lib\Config;

class V40{
 public $version = "V4.0";
  
  public function upgrade(){
    Config::set("standart_language", "En");
    $db = Database::get();
    $db->query("ALTER TABLE `ticket` ADD `admin_comments` INT(11) NOT NULL AFTER `comments`;");
    $db->query("ALTER TABLE `user` ADD `lang` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL AFTER `birth_year`;");
    $db->query("UPDATE `user` SET `lang`='En';");
    $db->query("UPDATE `log` SET `message`='LOG_CAT_DELETED' WHERE `message`='{$db->escape("%s deletede the category '%s'")}';");
    $db->query("UPDATE `log` SET `message`='LOG_CAT_CREATED' WHERE `message`='{$db->escape("%s created a new category '%s'")}';");
    $db->query("UPDATE `log` SET `message`='LOG_TICKET_OPEN' WHERE `message`='{$db->escape("%s open the ticket")}';");
    $db->query("UPDATE `log` SET `message`='LOG_TICKET_CLOSE' WHERE `message`='{$db->escape("%s closed the ticket")}';");
    $db->query("UPDATE `log` SET `message`='LOG_SYSTEM_UPDATE' WHERE `message`='{$db->escape("System upgraded to %s")}';");
    $db->query("UPDATE `log` SET `message`='LOG_SYSTEM_INSTALL' WHERE `message`='{$db->escape("System is installed")}';");
    $db->query("UPDATE `log` SET `message`='LOG_TEBELATE_CHANGE' WHERE `message`='{$db->escape("%s updated tempelate to %s")}';");
    $db->query("UPDATE `log` SET `message`='LOG_COMMENT_DELETE' WHERE `message`='{$db->escape("%s deleted a comment writet by %s")}';");
    $db->query("UPDATE `log` SET `message`='LOG_NICK_CHANGE' WHERE `message`='{$db->escape("%s changed nick to %s")}';");
    $db->query("UPDATE `log` SET `message`='LOG_EMAIL_CHANGE' WHERE `message`='{$db->escape("%s changed the email from %s to %s")}';");
    $db->query("UPDATE `log` SET `message`='LOG_OTHER_ACTIVATE' WHERE `message`='{$db->escape("%s activated the user")}';");
    $db->query("UPDATE `log` SET `message`='LOG_CAT_CLOSE' WHERE `message`='{$db->escape("%s closed the category %s")}';");
    $db->query("UPDATE `log` SET `message`='LOG_CAT_OPEN' WHERE `message`='{$db->escape("%s opnede the category %s")}';");
    $db->query("UPDATE `log` SET `message`='LOG_NEW_CAT' WHERE `message`='{$db->escape("%s created a new category '%s'")}';");
    
    $db->query("ALTER TABLE `catogory` ADD `input_count` INT(11) NOT NULL AFTER `age`, ADD `ticket_count` INT(11) NOT NULL AFTER `input_count`;");
    $db->query("ALTER TABLE `catogory` ADD `sort_ordre` INT(11) NOT NULL AFTER `ticket_count`;");
    $count = 0;
    $db->query("SELECT `id` FROM `catogory`")->fetch(function($id) use($db, &$count){
      //let us finde all ticket in this category and input
      $ticket_count = $db->query("SELECT COUNT(`id`) AS id FROM `ticket` WHERE `cid`='{$id}'")->fetch()->id;
      $input_count  = $db->query("SELECT COUNT(`id`) AS id FROM `category_item` WHERE `cid`='{$id}'")->fetch()->id;
      //let us update the category width the right data
      $db->query("UPDATE `catogory` SET `input_count`='{$input_count}', `ticket_count`='{$ticket_count}', `sort_ordre`='{$count}' WHERE `id`='{$id}'");
      $count++;
    });
    $db->query("TRUNCATE `notify`");
    $db->query("ALTER TABLE `notify` ADD `arg` TEXT CHARACTER SET utf8 COLLATE utf8_bin NOT NULL AFTER `message`;");
   
    return true; 
  }
}