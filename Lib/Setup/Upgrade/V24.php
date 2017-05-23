<?php
namespace Lib\Setup\Upgrade;

use Lib\Database;
use Lib\Config;

class V24{
  public $version = "V2.4";
  
  public function upgrade() : bool{
    $db = Database::get();
    $db->query("UPDATE `config` SET `value`='{$this->version}' WHERE `name`='version'");
    $this->upgradeNotify();
    $db->query("TRUNCATE `ticket_track`");
    
    $row = $db->query("SELECT * FROM `group` WHERE `isStandart`='1'")->fetch();
    Config::set("standart_group", $row->id);
    $db->query("ALTER TABLE `group` DROP `isStandart`;");
  }
  
  private function upgradeNotify(){
    $buffer = [];
    $db = Database::get();
    $query = $db->query("SELECT * FROM `notify_setting`");
    while($row = $query->fetch()){
      $buffer = [
        $row->uid,
        (($pos = strrpos($row->name, ".")) !== false ? substr($row->name, 0, $pos) : $row->name)
        ];
    }
    
    $db->query("TRUNCATE `notify_setting`");
    foreach($buffer as $col){
      $db->query("INSERT INTO `notify_setting` VALUES ('{$db->escape($col[0])}', '{$db->escape($col[1])}');"); 
    }
  }
}