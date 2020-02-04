<?php
namespace Lib\Ext\Page\Group\Sub;

use Lib\Access;
use Lib\Report;
use Lib\Config;
use Lib\Group;
use Lib\Language\Language;

class Delete{
  public static function body(){
    if(empty($_GET["gid"]) || !Access::userHasAccess("GROUP_DELETE"))
      return;
    
    if(Config::get("standart_group") == $_GET["gid"]){
      Report::error(Language::get("CANT_D_STANDART"));
      header("location: ?view=handleGroup");
      exit;
    }
    Group::delete(intval($_GET["gid"]));
    Report::okay(Language::get("GROUP_DELETED"));
    header("location: ?view=handleGroup");
    exit;
  }
}