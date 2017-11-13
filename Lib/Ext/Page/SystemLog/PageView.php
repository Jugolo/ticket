<?php
namespace Lib\Ext\Page\SystemLog;

use Lib\Controler\Page\PageView as P;
use Lib\Tempelate;
use Lib\Log;

class PageView implements P{
  public function body(Tempelate $tempelate){
    $logs = [];
    Log::getSystemLog()->render(function($time, $message) use(&$logs){
      $logs[] = [
        "time"    => $time,
        "message" => $message
        ];
    });
    $tempelate->put("logs", $logs);
    $tempelate->render("system_log");
  }
}