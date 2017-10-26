<?php
namespace Lib\Setup\Upgrade;

use Lib\Database;
use Lib\Config;
use Lib\Bbcode\Parser;

class V31{
 public $version = "V3.1";
  
  public function upgrade(){
    $db = Database::get();
    $db->query("UPDATE `config` SET `value`='{$this->version}' WHERE `name`='version'");
    Config::set("front", "");
    Config::set("system_name", "You system name. Please change");
    $db->query("ALTER TABLE `group` ADD `changeFront` INT(1) NOT NULL AFTER `closeTicket`;");
    $db->query("ALTER TABLE `group` ADD `changeSystemName` INT(1) NOT NULL AFTER `changeFront`;");
    $db->query("ALTER TABLE `group` ADD `changeSystemName` INT(1) NOT NULL AFTER `changeFront`;");
    $db->query("ALTER TABLE `comment` ADD `parsed_message` TEXT CHARACTER SET utf8 COLLATE utf8_bin NOT NULL AFTER `message`;");
    $db->query("SELECT `id`, `message` FROM `comment`")->render(function($row) use($db){
      $bbcode = new Parser($row->message);
      $db->query("UPDATE `comment` SET `parsed_message`='{$db->escape($bbcode->getHtml())}' WHERE `id`='{$row->id}'");
    });
    return true; 
  }
}
