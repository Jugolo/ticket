<?php
namespace Lib;

use Lib\Log\LogResult;
use Lib\Plugin\Event;

class Log{
  public static function onTicketDelete(Event $event, int $id){
    Database::get()->query("DELETE FROM `log` WHERE `type`='TICKET' AND `tid`='{$id}'");
  }
  
  public static function system(string $message, ...$arg){
    self::save("SYSTEM", 0, $message, $arg);
  }
  
  public static function getSystemLog(){
    return self::getLog("SYSTEM");
  }
  
  public static function user(int $uid, string $message, ...$arg){
    self::save("USER", $uid, $message, $arg);
  }
  
  public static function getUserLog(int $uid){
    return self::getLog("USER", $uid);
  }
  
  public static function ticket(int $id, string $message, ...$arg){
    self::save("TICKET", $id, $message, $arg);
  }
  
  public static function getTicketLog(int $id) : LogResult{
    return self::getLog("TICKET", $id);
  }
  
  private static function getLog(string $type, int $hid = -1) : LogResult{
    $extra = "";
    if($hid > 1){
      $extra = " AND `tid`='{$hid}'";
    }
    return new LogResult(Database::get()->query("SELECT `created`, `message`, `arg` FROM `log` WHERE `type`='{$type}'{$extra}"));
  }
  
  private static function save(string $type, int $tid, string $msg, array $data){
     $db = Database::get();
     $db->query("INSERT INTO `log` (
       `type`,
       `created`,
       `uid`,
       `tid`,
       `message`,
       `arg`
     ) VALUES (
       '{$type}',
       NOW(),
       '".(defined("user") ? user["id"] : "0")."',
       '{$tid}',
       '{$db->escape($msg)}',
       '{$db->escape(json_encode($data))}'
     )");
  }
}