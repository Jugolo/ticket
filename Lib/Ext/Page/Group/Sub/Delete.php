<?php
namespace Lib\Ext\Page\Group\Sub;

use Lib\Access;
use Lib\Report;
use Lib\Config;
use Lib\Group;
use Lib\Language\Language;
use Lib\Tempelate;
use Lib\User\User;
use Lib\Page;

class Delete{
  public static function body(Tempelate $tempelate, Page $page, User $user){
    if(empty($_GET["gid"]) || !$user->access()->has("GROUP_DELETE"))
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
