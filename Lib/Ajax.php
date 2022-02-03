<?php
namespace Lib;

use Lib\Plugin\Plugin;

class Ajax{
  private static $data = [];
  
  public static function isAjaxRequest(){
    return !empty($_GET["_ajax"]);
  }
  
  public static function set(string $key, $data){
    self::$data[$key] = $data;
  }
  
  public static function evulate(){
    header('Content-Type: application/json');
    Plugin::trigger_event("ajax.".$_GET["_ajax"]);
    $reports = Report::toArray();
    Report::unset();
    exit(json_encode([
      "reports" => $reports,
      "data" => self::$data
    ]));
  }
}