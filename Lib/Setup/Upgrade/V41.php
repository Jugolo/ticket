<?php
namespace Lib\Setup\Upgrade;

use Lib\Database;

class V41{
  public $version = "V4.1";
  
  public function upgrade(){
    $db = Database::get();
    $db->query("ALTER TABLE `".DB_PREFIX."log` CHANGE `created` `created` INT(11) NOT NULL;");
    $db->query("ALTER TABLE `".DB_PREFIX."config` ADD UNIQUE( `name`);");
    $db->query("ALTER TABLE `".DB_PREFIX."comment` CHANGE `message` `message` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL;");
    $db->query("ALTER TABLE `".DB_PREFIX."comment` CHANGE `parsed_message` `parsed_message` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL;");
    $db->query("ALTER TABLE `".DB_PREFIX."comment` CHANGE `created` `created` INT(11) NOT NULL;");
    $db->query("ALTER TABLE `".DB_PREFIX."ticket` CHANGE `created` `created` INT(11) NOT NULL, CHANGE `user_changed` `user_changed` INT(11) NOT NULL, CHANGE `admin_changed` `admin_changed` INT(11) NOT NULL;");
    $db->query("ALTER TABLE `".DB_PREFIX."ticket_track` CHANGE `visit` `visit` INT(11) NOT NULL;");
    $db->query("CREATE TABLE `".DB_PREFIX."cronwork` ( `id` INT(11) NOT NULL AUTO_INCREMENT , `cronwork` VARCHAR(255) NOT NULL , `next` INT(11) NOT NULL , `interval` INT(11) NOT NULL , PRIMARY KEY (`id`)) ENGINE = InnoDB;");
    $db->query("INSERT INTO `".DB_PREFIX."cronwork` (`id`, `cronwork`, `next`, `interval`) VALUES (NULL, 'Lib\\\\Cronwork\\\\Image::gc', 0, 2052000)");
    $db->query("INSERT INTO `".DB_PREFIX."cronwork` (`id`, `cronwork`, `next`, `interval`) VALUES (NULL, 'Lib\\\\Ticket\\\\TicketDeleter::gc', '0', '10000');");
    $db->query("ALTER TABLE `".DB_PREFIX."ticket_track` ADD `cid` INT(11) NOT NULL AFTER `tid`;");
    $db->query("ALTER TABLE `".DB_PREFIX."ticket_value` ADD `cid` INT(11) NOT NULL AFTER `hid`;");
    $db->query("ALTER TABLE `".DB_PREFIX."comment` ADD `cid` INT(11) NOT NULL AFTER `tid`;");
    $db->query("SELECT `id`, `cid` FROM `".DB_PREFIX."ticket`")->fetch(function(int $id, int $cid) use($db){
      $db->query("UPDATE `".DB_PREFIX."ticket_track` SET `cid`='{$cid}' WHERE `tid`='{$id}'");
      $db->query("UPDATE `".DB_PREFIX."ticket_value` SET `cid`='{$cid}' WHERE `hid`='{$id}'");
      $db->query("UPDATE `".DB_PREFIX."comment` SET `cid`='{$cid}' WHERE `tid`='{$id}'");
    });
    $db->query("ALTER TABLE `".DB_PREFIX."notify` CHANGE `created` `created` INT(11) NOT NULL;");
    $db->query("CREATE TABLE IF NOT EXISTS `".DB_PREFIX."flood`(`id` int(11) NOT NULL AUTO_INCREMENT, `time` int(11) NOT NULL, `type` varchar(255) COLLATE utf8mb4_bin NOT NULL, `ip` varchar(255) COLLATE utf8mb4_bin NOT NULL, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;");
    $db->query("CREATE TABLE `".DB_PREFIX."files`(`id` int(11) NOT NULL AUTO_INCREMENT, `item_id` int(11) NOT NULL, `name` varchar(255) COLLATE utf8mb4_bin NOT NULL, `created` int(11) NOT NULL, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;");
    $this->extensionGroup($db);
    return true;
  }
  
  private function extensionGroup($db){
    $db->query("CREATE TABLE `".DB_PREFIX."file_group` ( `id` INT(11) NOT NULL AUTO_INCREMENT ,  `name` VARCHAR(255) NOT NULL ,    PRIMARY KEY  (`id`)) ENGINE = InnoDB;");
    $db->query("CREATE TABLE `".DB_PREFIX."file_extension` ( `id` INT(11) NOT NULL AUTO_INCREMENT ,  `gid` INT(11) NOT NULL ,  `name` VARCHAR(255) NOT NULL , `mimetype` VARCHAR(255) NOT NULL,   PRIMARY KEY  (`id`)) ENGINE = InnoDB;");
    $this->addImage($db, $db->query("INSERT INTO `".DB_PREFIX."file_group` (`id`, `name`) VALUES (NULL, '@language.IMAGE');"));
  }
  
  private function addImage($db, $id){
    $db->query("INSERT INTO `".DB_PREFIX."file_extension` (`gid`, `name`, `mimetype`) VALUES
                                                            ('{$id}', 'jpg', 'image/jpg'),
                                                            ('{$id}', 'jpeg', 'image/jpg'),
                                                            ('{$id}', 'png', 'image/png');");
  }
}