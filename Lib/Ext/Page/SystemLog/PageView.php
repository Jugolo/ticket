<?php
namespace Lib\Ext\Page\SystemLog;

use Lib\Controler\Page\PageView as P;
use Lib\Tempelate;
use Lib\Log;
use Lib\Page;

class PageView implements P{
  public function loginNeeded() : string{
    return "YES";
  }
  
  public function identify() : string{
    return "systemLog";
  }
  
  public function access() : array{
    return [
      "SYSTEMLOG_SHOW"
      ];
  }
  
  public function body(Tempelate $tempelate, Page $page){
    $logs = [];
    Log::getSystemLog()->render(function($time, $message) use(&$logs){
      $logs[] = [
        "time"    => $time,
        "message" => $message
        ];
    });
    $tempelate->put("logs", $logs);
    $tempelate->render("system_log", $page);
  }
}