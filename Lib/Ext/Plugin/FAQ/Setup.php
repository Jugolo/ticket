<?php
namespace Lib\Ext\Plugin\FAQ;

use Lib\Database;

class Setup{
  public static function install(){
    $db = Database::get();
    $db->query("CREATE TABLE `faq` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
      `dec` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE = InnoDB DEFAULT CHARSET=utf8;");
  }
  
  public static function uninstall(){
    $db = Database::get();
    $db->query("DELETE FROM `access` WHERE `name`='FAQ_CHANGE'");
    $db->query("DROP TABLE `faq`");
  }
}