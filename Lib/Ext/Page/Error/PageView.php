<?php
namespace Lib\Ext\Page\Error;

use Lib\Controler\Page\PageView as P;
use Lib\Database;
use Lib\Html\Table;
use Lib\Database\DatabaseFetch;
use Lib\Report;

class PageView implements P{
  function body(){
    $info = new Info();
    if(!$info->menuVisible()){
      notfound();
    }
    
    $page = !empty($_GET["ep"]) && is_numeric($_GET["ep"]) ? intval($_GET["ep"]) : 0;
    $db = Database::get();
    
    $count = $db->query("SELECT COUNT(`id`) AS id FROM `error`")->fetch()->id;
    
    if($page*30 > $count){
      $page = round($count/30);
    }
    
    $link = "";
    for($i=max($page-10, 0);$i<min($page+10, $count/30);$i++){
      if($i == $page){
        $link .= " ".($i+1);
      }else{
        $link .= " <a href='?view=error&ep={$i}'>".($i+1)."</a>";
      }
    }
    
    $query = $db->query("SELECT * FROM `error` LIMIT {$page}, 30");
    
    if($query->count() === 0){
      echo "<h3>No error detected</h3>";
      return;
    }
    
    if(!empty($_POST["delete"]) && !empty($_POST["errorSelect"])){
      $this->deleteErrors();
      header("location: #");
      exit;
    }
    
    $table = new Table();
    $table->className("style");
    $table->newColummen();
    $this->setHeader($table);
    $self = $this;
    $query->render([$this, "setBody"], $table);
    $this->setScript();
    echo "<form action='#' method='post'>";
    echo "<div style='padding:5px;'>";
    echo "<a href='#' onclick='selectAll();'>Select all</a> / ";
    echo "<a href='#' onclick='unselectAll();'>Unselect all</a> ";
    echo "<span id='delete_selected' class='hide'><button name='delete' value='delete'>Delete selected</button></span>";
    echo "</div>";
    $table->output();
    echo $link;
    echo "</form>";
  }
  
  public function setBody(DatabaseFetch $item, Table $table){
    $table->newColummen();
    $table->td("<input type='checkbox' name='errorSelect[]' onclick='onErrorSelectChange();' class='es' value='{$item->id}'>", true);
    $table->td($item->errno);
    $table->td($item->errstr);
    $table->td($item->errfile);
    $table->td($item->errline);
    $table->td($item->errtime);
  }
  
  private function setHeader(Table $table){
    $table->th("Select");
    $table->th("Type");
    $table->th("Message");
    $table->th("File");
    $table->th("Line");
    $table->th("Reported");
  }
  
  private function setScript(){
    ?>
    <script>
      function selectAll(){
        var input =  document.getElementsByClassName("es");
        for(var i=0;i<input.length;i++){
          if(!input[i].checked){
            input[i].checked = true; 
          }
        }
        onErrorSelectChange();
      }
      
      function unselectAll(){
        var input = document.getElementsByClassName("es");
        for(var i=0;i<input.length;i++){
          input[i].checked = false; 
        }
        onErrorSelectChange();
      }
      
      function onErrorSelectChange(){
        document.getElementById("delete_selected").style.display = errorSelected() ? "inline-block" : "none";
      }
      
      function errorSelected(){
        var input = document.getElementsByClassName("es");
        for(var i=0;i<input.length;i++){
          if(input[i].checked){
            return true; 
          }
        }
        return false;
      }
    </script>
    <?php
  }
  
  private function deleteErrors(){
    $queryString = "DELETE FROM `error` WHERE ";
    $ids = [];
    $db = Database::get();
    foreach($_POST["errorSelect"] as $id){
      $ids[] = "`id`='{$id}'";
    }
    
    Database::get()->query($queryString.implode(" OR ", $ids));
    Report::okay("Errors message is now deleted");
  }
}