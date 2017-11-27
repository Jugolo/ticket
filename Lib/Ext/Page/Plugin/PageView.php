<?php
namespace Lib\Ext\Page\Plugin;

use Lib\Controler\Page\PageView as P;
use Lib\Tempelate;
use Lib\Page;
use Lib\Plugin\Plugin;
use Lib\Database;
use Lib\Access;
use Lib\Report;

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
    $tempelate->render("plugin_list", $page);
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
      Report::error("Could not find the plugin");
    }else{
      $installed = $this->getInstalled();
      if(!empty($installed[$path])){
        Report::error("The plugin is allready installed");
      }else{
        Plugin::install($path);
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
      }else{
        Report::error("The plugin is not installed yet");
      }
    }else{
      Report::error("Could not find the plugin");
    }
    header("location: ?view=plugin");
    exit;
  }
}