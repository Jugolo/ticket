<?php
namespace Lib\Ext\Page\Front;

use Lib\Controler\Page\PageView as P;
use Lib\Config;
use Lib\Bbcode\Parser;
use Lib\Report;

class PageView implements P{
  public function body(){
    if(defined("user") && !empty($_GET["logout"]) && $_GET["logout"] == session_id()){
      session_destroy();
      header("location: ?view=front");
      exit;
    }
    
    if(defined("user")){
      if(group["changeFront"] == 1 && !empty($_GET["change"])){
        $this->changeFront();
        return;
      }
      if(group["changeSystemName"] == 1 && !empty($_GET["changeSystemName"])){
         $this->changeSystemNameEditor();
         return;
      }
      
      if(group["changeFront"] == 1){
        echo "<div>";
          echo "<div id='changeFrontLink'>";
            echo "<a href='?view=front&change=yes'>Change front page</a>";
          echo "</div>";
          echo "<div class='clear'></div>";
        echo "</div>";
      }
    }
    $parser = new Parser(Config::get("front"));
    Parser::getJavascript();
    echo $parser->getHtml();
  }
  
  private function changeSystemNameEditor(){
    if(!empty($_POST["systemname"]) && trim($_POST["systemname"])){
      Config::set("system_name", $_POST["systemname"]);
      Report::okay("System name is now updated");
      header("location: #");
      exit;
    }
    echo "<form method='post' action='#'>";
    echo two_container("New system name", "<input type='text' name='systemname' value='".Config::get("system_name")."'>");
    echo "<input type='submit' value='Change system name'>";
    echo "</form>";
  }
  
  private function changeFront(){
    if(!empty($_POST["changeFront"])){
      Config::set("front", $_POST["front"]);
      Report::okay("The front page is updated");
      header("location: ?view=front");
      exit;
    }
    
    echo "<form method='post' action='#'>";
      echo "<textarea name='front' id='frontTextarea'>".Config::get("front")."</textarea>";
      echo "<input type='submit' name='changeFront' value='Change'>";
    echo "</form>";
  }
}