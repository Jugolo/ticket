<?php
namespace Lib\Setup\Upgrade;

use Lib\Database;
use Lib\Config;
use Lib\Bbcode\Parser;

class V32{
 public $version = "V3.2";
  
  public function upgrade(){
    $db = Database::get();
    $db->query("CREATE TABLE IF NOT EXISTS `log` ( 
                         `id` INT(11) NOT NULL AUTO_INCREMENT ,
                         `type` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL ,
                         `created` DATETIME NOT NULL , `uid` INT(11) NOT NULL,
                         `tid` INT(11) NOT NULL,
                         `message` TEXT CHARACTER SET utf8 COLLATE utf8_bin NOT NULL, 
                         `arg` TEXT CHARACTER SET utf8 COLLATE utf8_bin NULL, 
                         PRIMARY KEY (`id`)
                       ) ENGINE = InnoDB DEFAULT CHARSET=utf8;");
    $db->query("ALTER TABLE `group` ADD `showTicketLog` INT(1) NOT NULL AFTER `changeSystemName`;");
    $db->query("ALTER TABLE `group` ADD `deleteTicket` int(1) NOT NULL AFTER `showTicketLog`;");
    $db->query("ALTER TABLE `group` ADD `deleteComment` INT(1) NOT NULL AFTER `deleteTicket`;");
    $db->query("ALTER TABLE `group` ADD `activateUser` INT(1) NOT NULL AFTER `deleteComment`;");
    $db->query("ALTER TABLE `group` ADD `viewUserLog` INT(1) NOT NULL AFTER `activateUser`;");
    return true; 
  }
}