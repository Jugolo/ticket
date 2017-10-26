<?php
namespace Lib;

use Lib\Log\LogResult;

class Log{
  public static function onTicketDelete(int $id){
    Database::get()->query("DELETE FROM `log` WHERE `type`='TICKET' AND `tid`='{$id}'");
  }
  
  public static function user(int $uid, string $message, ...$arg){
    $db = Database::get();
    $db->query("INSERT INTO `log`(
      `type`,
      `created`,
      `uid`,
      `tid`,
      `message`,
      `arg`
    ) VALUES (
      'USER',
      NOW(),
      '".(defined("user") ? user["id"] : 0)."',
      '{$uid}',
      '{$db->escape($message)}',
      '{$db->escape(json_encode($arg))}'
    )");
  }
  
  public static function getUserLog(int $uid){
    $query = Database::get()->query("SELECT * FROM `log` WHERE `type`='USER' AND `tid`='{$uid}'");
    $result = [];
    while($row = $query->fetch()){
      $result[] = $row;
    }
    return new LogResult($result);
  }
  
  public static function ticket(int $id, string $message, ...$arg){
    $db = Database::get();
    $db->query("INSERT INTO `log` (
      `type`,
      `created`,
      `uid`,
      `tid`,
      `message`,
      `arg`
    ) VALUES (
      'TICKET',
      NOW(),
      '".(defined("user") ? user["id"] : "0")."',
      '{$id}',
      '{$db->escape($message)}',
      '{$db->escape(json_encode($arg))}'
    )");
  }
  
  public static function getTicketLog(int $id) : LogResult{
    $query = Database::get()->query("SELECT * FROM `log` WHERE `type`='TICKET' AND `tid`='{$id}'");
    $result = [];
    while($row = $query->fetch()){
      $result[] = $row;
    }
    return new LogResult($result);
  }
}