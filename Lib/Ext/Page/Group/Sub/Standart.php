<?php
namespace Lib\Ext\Page\Group\Sub;

use Lib\Database;
use Lib\Report;
use Lib\Config;
use Lib\Language\Language;

class Standart{
  public static function body(){
    if(empty($_GET["gid"])){
      notfound();
      return;
    }
    Config::set("standart_group", $_GET["gid"]);
    Report::okay(Language::get("GROUP_N_STANDART"));
    header("location: ?view=handleGroup");
    exit;
  }
}