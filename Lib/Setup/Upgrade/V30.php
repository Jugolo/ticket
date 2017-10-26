<?php
namespace Lib\Setup\Upgrade;
use Lib\Database;
class V30{
 public $version = "V3.0";
  
  public function upgrade(){
    $db = Database::get();
    $db->query("UPDATE `config` SET `value`='{$this->version}' WHERE `name`='version'");
    $db->query("ALTER TABLE `config` ADD UNIQUE(`name`);");
    $db->query("INSERT INTO `config` (`name`, `value`) VALUES ('cat_open', '".$this->getOpenCat()."');");
    $db->query("ALTER TABLE `ticket` ADD `open` INT(1) NOT NULL AFTER `admin_changed`;");
    $db->query("ALTER TABLE `group` ADD `closeTicket` INT(1) NOT NULL AFTER `showProfile`;");
    $db->query("UPDATE `ticket` SET `open`='1';");
    return true; 
  }
  
  private function getOpenCat(){
    return Database::get()->query("SELECT COUNT(`id`) AS id FROM `catogory` WHERE `open`='1'")->fetch()->id;
  }
}