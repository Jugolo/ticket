<?php
namespace Lib;

class Flood{
  public static function gc(){
    Database::get()->query("DELETE FROM `".DB_PREFIX."flood` WHERE `time`<'".strtotime("-5 MINUTS")."'");
  }
  
  public static function controle(string $type) : bool{
    $db = Database::get();
    $db->query("INSERT INTO `".DB_PREFIX."flood` (
                  `time`,
                  `type`,
                  `ip`
                ) VALUES (
                  '".time()."',
                  '{$db->escape($type)}',
                  '".IP."'
                )");
    
    //now wee see if the user is allowed to de the action
    return $db->query("SELECT COUNT(`id`) AS id FROM `".DB_PREFIX."flood` WHERE `type`='{$db->escape($type)}' AND `ip`='".IP."'")->fetch()->id < 6;
  }
}

Flood::gc();