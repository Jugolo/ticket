<?php
namespace Lib\Setup\Upgrade;

use Lib\Database;

class V42{
  public $version = "V4.2";
  
  public function upgrade(){
   $db = Database::get();
    $db->query("ALTER TABLE `".DB_PREFIX."ticket_value` ADD `cid` INT(11) NOT NULL AFTER `hid`;");
    $db->query("SELECT `id`, `cid` FROM `".DB_PREFIX."ticket`")->fetch(function(int $id, int $cid) use($db){
      $db->query("UPDATE `".DB_PREFIX."ticket_track` SET `cid`='{$cid}' WHERE `tid`='{$id}'");
      $db->query("UPDATE `".DB_PREFIX."ticket_value` SET `cid`='{$cid}' WHERE `hid`='{$id}'");
      $db->query("UPDATE `".DB_PREFIX."comment` SET `cid`='{$cid}' WHERE `tid`='{$id}'");
    });
    
    $db->query("CREATE TABLE IF NOT EXISTS `".DB_PREFIX."grup_member` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `gid` int(11) NOT NULL,
                  `uid` int(11) NOT NULL,
                  PRIMARY KEY (`id`)
                ) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4;");
     $db->query("SELECT `id`, `groupid` FROM `".DB_PREFIX."user`")->fetch(function(int $id, int $gid) use($db){
		 $db->insert("grup_member", [
		   "uid" => $id,
		   "gid" => $gid
		 ]);
	 });
	 
	 $db->query("CREATE TABLE IF NOT EXISTS `".DB_PREFIX."category_access` (
                   `gid` int(11) NOT NULL,
                   `cid` int(11) NOT NULL,
                   `name` varchar(255) NOT NULL
               ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
     return true;
  }
}
