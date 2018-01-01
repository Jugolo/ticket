<?php
namespace Lib\Ext\Page\Plugin;

use Lib\Controler\Page\PageView as P;
use Lib\Tempelate;
use Lib\Page;
use Lib\Plugin\Plugin;
use Lib\Database;
use Lib\Access;
use Lib\Report;
use Lib\Language\Language;
use Lib\Exception\PluginInstallException;
use Lib\Log;

class PageView implements P{
  public function loginNeeded() : string{
    return "YES";
  }
  
  public function identify() : string{
    return "plugin";
  }
  
  public function access() : array{
    return [
      "PLUGIN_INSTALL",
      "PLUGIN_UNINSTALL"
      ];
  }
  
  public function body(Tempelate $tempelate, Page $page){
    Language::load("plugin");
    if(!empty($_GET["install"]) && Access::userHasAccess("PLUGIN_INSTALL"))
      $this->install($_GET["install"]);
    if(!empty($_GET["uninstall"]) && Access::userHasAccess("PLUGIN_UNINSTALL"))
      $this->uninstall($_GET["uninstall"]);
    
    $installed = $this->getInstalled();
    $dir = "Lib/Ext/Plugin/";
    $stream = opendir($dir);
    $data = [];
    while($item = readdir($stream)){
      if($item == "." || $item == ".." || !is_dir($dir.$item))
        continue;
      $data[] = [
        "path"      => $dir.$item,
        "name"      => $item,
        "installed" => !empty($installed[$dir.$item."/"]),
        ];
    }
    closedir($stream);
    $tempelate->put("plugins", $data);
    $tempelate->render("plugin_list");
  }
  
  private function getInstalled() : array{
    $result = [];
    $query = Database::get()->query("SELECT * FROM `plugin`");
    while($row = $query->fetch()){
      $result[$row->path] = $row->id;
    }
    return $result;
  }
  
  private function install(string $name){
    //let see if the path exists
    $path = "Lib/Ext/Plugin/{$name}/";
    if(!is_dir($path)){
      Report::error(Language::get("P_NOT_FOUND"));
    }else{
      $installed = $this->getInstalled();
      if(!empty($installed[$path])){
        Report::error(Language::get("P_INSTALLED"));
      }else{
        try{
          Plugin::install($path);
          Log::system("LOG_PLUGIN_I", user["username"], $name);
        }catch(PluginInstallException $e){
          Language::load("plugin_install");
          Report::error(Language::get($e->getMessage()));
        }
      }
    }
    header("location: ?view=plugin");
    exit;
  }
  
  private function uninstall(string $name){
    $path = "Lib/Ext/Plugin/{$name}/";
    if(file_exists($path)){
      $installed = $this->getInstalled();
      if(!empty($installed[$path])){
        Plugin::uninstall($path);
        Log::system("LOG_PLUGIN_U", user["username"], $name);
      }else{
        Report::error(Language::get("P_N_INSTALL"));
      }
    }else{
      Report::error(Language::get("P_NOT_FOUND"));
    }
    header("location: ?view=plugin");
    exit;
  }
}