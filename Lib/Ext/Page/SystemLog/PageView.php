<?php
namespace Lib\Ext\Page\SystemLog;

use Lib\Controler\Page\PageView as P;
use Lib\Tempelate;
use Lib\Log;
use Lib\Page;
use Lib\Language\Language;
use Lib\User\User;

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
  
  public function body(Tempelate $tempelate, Page $page, User $user){
    Language::load("system_log");
    $logs = [];
    Log::getSystemLog()->render(function($time, $message) use(&$logs){
      $logs[] = [
        "time"    => date("H:i d/m/Y", $time),
        "message" => $message
        ];
    });
    $tempelate->put("logs", $logs);
    $tempelate->render("system_log");
  }
}
