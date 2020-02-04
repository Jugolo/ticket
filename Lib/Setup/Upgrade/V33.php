<?php
namespace Lib\Setup\Upgrade;

use Lib\Database;
use Lib\Config;
use Lib\Bbcode\Parser;

class V33{
 public $version = "V3.3";
  
  public function upgrade(){
    $db = Database::get();
    $db->query("ALTER TABLE `group` ADD `viewSystemLog` INT(1) NOT NULL AFTER `viewUserLog`;");
    $db->query("ALTER TABLE `group` ADD `handleTempelate` INT(1) NOT NULL AFTER `viewSystemLog`");
    Config::set("tempelate", "CowTicket");
    if(!file_exists("Lib/Temp"))
      mkdir("Lib/Temp");
    $db->query("SELECT `id`, `message` FROM `comment`")->fetch(function($id, $message) use($db){
      $parser = new Parser($message);
      $db->query("UPDATE `comment` SET `parsed_message`='{$db->escape($parser->getHtml())}' SET `id`='{$id}'");
    });
    return true; 
  }
}