<?php
namespace Lib\Controler\Page;

class PageControler{
  public static function getPageInfo($callback){
    if(is_callable($callback)){
      $dir = "Lib/Ext/Page/";
      $stream = opendir($dir);
      while($i = readdir($stream)){
        if($i == "." || $i == ".."){
          continue;
        }
        
        if(is_dir($dir.$i) && file_exists($dir.$i."/Info.php")){
          $c = "Lib\\Ext\\Page\\".$i."\\Info";
          if(!class_exists($c)){
            include $dir.$i."/Info.php";
            if(!class_exists($c)){
              continue;
            }
          }
          
          $obj = new $c();
          if(!($obj instanceof PageInfo)){
            continue;
          }
          
          $data = call_user_func($callback, $obj);
          if($data){
            return $data;
          }
        }
      }
      closedir($stream);
    }else{
      trigger_error(E_USER_ERROR, "PageControler::getPageInfo() need a callable as agument");
    }
  }
  
  public static function getPage(){
    $page = empty($_GET["view"]) ? "front" : $_GET["view"];
    return self::getPageInfo(function(PageInfo $info) use($page){
      if($info->name() === $page){
        if(!$info->pageVisible()){
          notfound();
          return;
        }
        $class = get_class($info);
        $class = substr($class, 0, strrpos($class, "\\")+1)."PageView";
        //This lines is created becuse if there is page info file but no page view file we dont want to print it tp the screen
        $file = str_replace("\\", "/", $class).".php";
        if(!file_exists($file)){
          trigger_error("Missing page file location on '{$file}'", E_USER_ERROR);
          notfound();
          return;
        }
        $view = new $class();
        if($view instanceof PageView){
          return $view;
        }
      }
    });
  }
}