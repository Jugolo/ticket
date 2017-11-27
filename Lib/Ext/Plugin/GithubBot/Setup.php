<?php
namespace Lib\Ext\Plugin\GithubBot;

use Lib\Category;
use Lib\Config;
use Lib\User\Auth;
use Lib\Database;

class Setup{
  public static function install(){
    Config::set("github_cat", Category::create("Github Issues"));
    Config::set("github_user", Auth::createUser("Github", (string)uniqid(), "github@localhost.local", true));
    $db = Database::get();
    $db->query("CREATE TABLE `githubbot`(
      `type` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
      `item_id` INT(11) NOT NULL,
      `ticket_id` INT(11) NOT NULL,
      `number` INT(11) NOT NULL
    ) ENGINE = InnoDB DEFAULT CHARSET=utf8;");
  }
  
  public static function uninstall(){
    Auth::deleteUser(Config::get("github_user"));
    Category::delete(Config::get("github_cat"));
    Config::delete("github_cat");
    Config::delete("github_user");
    $db = Database::get();
    $db->query("DROP TABLE `githubbot`");
  }
}