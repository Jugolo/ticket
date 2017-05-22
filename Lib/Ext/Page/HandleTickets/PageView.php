<?php
namespace Lib\Ext\Page\HandleTickets;

use Lib\Controler\Page\PageView as P;
use Lib\Html\Table;
use Lib\Database;
use Lib\Database\DatabaseFetch;
use Lib\Error;
use Lib\Okay;

class PageView implements P{
  public function body(){
    if(!empty($_GET["catogory"])){
      $this->setting();
    }else{
      $this->overview();
    }
  }
  
  private function setting(){
    $data = $this->getData();
    if(!$data){
      Error::report("Unknown catagory");
      header("location: ?view=".$_GET["view"]);
      exit;
    }
    
    if(!empty($_POST["append"])){
      $this->appendInput($data->id);
    }
    
    if(!empty($_POST["setting"])){
      $this->updateSetting($data->id);
    }
    
    if(!empty($_GET["delete"])){
      $this->deleteInput($_GET["delete"]);
    }
    
    echo "<h3>Setting for {$data->name}</h3>";
    
    echo "<fieldset>";
    echo "<legend>Create input</legend>";
    echo "<form method='post' action='#'>";
      echo "<div>";
        echo "<input type='text' name='name' placeholder='The input text'>";
      echo "</div>";
      echo "<div>";
        echo "<select name='type' onchange='updatePlaceholder(this);'>";
         echo "<option value='1'>Input</option>";
         echo "<option value='2'>Textarea</option>";
         echo "<option value='3'>Select</option>";
        echo "</select>";
      echo "</div>";
      echo "<div>";
        echo "<input type='text' name='placeholder' id='placeholder' placeholder='Write the placeholder'>";
      echo "</div>";
      echo "<input type='submit' name='append' value='Append input'>";
    echo "</form>";
    echo "</fieldset>";
    
    echo "<script>";
    echo "function updatePlaceholder(obj){
      var dom = document.getElementById('placeholder');
      switch(obj.value){
        case '1':
        case '2':
          dom.placeholder = 'Write the placeholder';
        break;
        case '3':
          dom.placeholder = 'Write the option seprate by a ,';
        break;
        default:
          CowTicket.error('unknown value: '+obj.value);
        break;
      }
    }";
    echo "</script>";
    
    echo "<fieldset>";
    echo "<legend>Inputs</legend>";
    $db = Database::get();
    $query = $db->query("SELECT * FROM `category_item` WHERE `cid`='{$data->id}'");
    if($query->count() == 0){
      echo "<h3>No input yet</h3>";
    }else{
      $self = $this;
      $table = new Table();
      $table->style = "width:100%;border-collapse:collapse;";
      $table->newColummen();
      $table->th("Name")->style = "border:1px solid grey;background-color:blue;";
      $table->th("Type")->style = "border:1px solud grey;background-color:blue;";
      $table->th("Placeholder")->style = "border:1px solid grey;background-color:blue;";
      $table->th("Option")->style = "border:1px solid grey;background-color:blue;";
      $query->render(function(DatabaseFetch $row) use($self, $table){
        $self->renderInput($row, $table);
      });
      $table->output();
    }
    echo "</fieldset>";
    
    echo "<fieldset>";
    echo "<legend>Setting</legend>";
      echo "<form method='post' action='#'>";
        echo two_container("Min. age", "<input type='text' name='age' value='{$data->age}'>");
      echo "<input type='submit' name='setting' value='Update'>";
      echo "</form>";
    echo "</fieldset>";
  }
  
  public function updateSetting(int $id){
    $input = [];
    if(!empty($_POST["age"]) && is_numeric($_POST["age"])){
      $input["age"] = "'".(int)$_POST["age"]."'";
    }else{
      $input["age"] = "NULL";
    }
    
    $sql = "UPDATE `catogory` SET ";
    $buffer = [];
    foreach($input as $name => $value){
      $buffer[] = "`{$name}`=".$value;
    }
    Database::get()->query("UPDATE `catogory` SET ".implode(", ", $buffer)." WHERE `id`='{$id}'");
    Okay::report("Setting is updated");
    header("location: #");
    exit;
  }
  
  public function deleteInput(int $id){
    Database::get()->query("DELETE FROM `category_item` WHERE `id`='{$id}'");
    Okay::report("input is deleted");
    header("location: ?view={$_GET["view"]}&catogory=".$_GET["catogory"]);
    exit;
  }
  
  public function appendInput(int $id){
    $error_count = html_error_count();
    if(empty($_POST["name"]) || !trim($_POST["name"])){
      Error::report("Missing input name");
    }
    
    if(empty($_POST["type"]) || $_POST["type"] < 0 || $_POST["type"] > 3){
      Error::report("Missing input type");
    }
    
    if(empty($_POST["placeholder"]) || !trim($_POST["placeholder"])){
      Error::report("Missing placeholder");
    }
    
    if($error_count == html_error_count()){
      $db = Database::get();
      $db->query("INSERT INTO `category_item` VALUES (
                   NULL,
                   '{$id}',
                   '{$db->escape($_POST["type"])}',
                   '{$db->escape($_POST["name"])}',
                   '{$db->escape($_POST["placeholder"])}'
                 );");
      Okay::report("The input is saved");
    }
    header("location: #");
    exit;
  }
  
  public function renderInput(DatabaseFetch $row, Table $table){
    $table->newColummen();
    
    $table->th($row->text)->style = "border:1px solid grey";
    $table->td($this->getTypeName($row->type))->style = "border:1px solid grey";
    $table->td($row->placeholder)->style = "border:1px solid grey";
    $table->td("<a href='?view={$_GET["view"]}&catogory={$_GET["catogory"]}&delete={$row->id}'>Delete</a>", true)->style="border:1px solid grey";
  }
  
  private function getTypeName(int $id) : string{
    switch($id){
      case 1:
        return "Input";
      case 2:
        return "Textarea";
      case 3:
        return "Select";
    }
    return "Unknown";
  }
  
  private function getData(){
    $db = Database::get();
    return $db->query("SELECT * FROM `catogory` WHERE `id`='{$db->escape($_GET["catogory"])}'")->fetch();
  }
  
  private function overview(){
    if(!empty($_POST["name"])){
      $this->create($_POST["name"]);
    }
    if(!empty($_GET["open"])){
      $this->changeOpen(intval($_GET["open"]));
    }
    if(!empty($_GET["delete"])){
      $this->delete(intval($_GET["delete"]));
    }
    echo "<fieldset>";
      echo "<legend>Create new Category</legend>";
      echo "<form method='post' action='#'>";
        echo "<div><input type='text' name='name' placeholder='Name'></div>";
        echo "<div><input type='submit' value='Create new category'></div>";
      echo "</form>";
    echo "</fieldset>";
    
    echo "<fieldset>";
      echo "<legend>Category</legend>";
      Database::get()->query("SELECT * FROM `catogory`")->render([$this, "render"]);
    echo "</fieldset>";
  }
  
  public function render(DatabaseFetch $row){
    echo "<div class='item'>";
     $item = [];
     $item[] = "<a href='?view={$_GET["view"]}&catogory={$row->id}'>Setting</a>";
     $item[] = "<a href='?view={$_GET["view"]}&open={$row->id}'>".($row->open != 1 ? "Open" : "Close")."</a>";
     $item[] = "<a href='?view={$_GET["view"]}&delete={$row->id}'>Delete</a>";
     echo two_container($row->name, implode(" ", $item));
    echo "</div>";
  }
  
  private function changeOpen(int $id){
    $db = Database::get();
    $data = $db->query("SELECT `open` FROM `catogory` WHERE `id`='{$id}'")->fetch();
    if(!$data){
      return;
    }
    
    $db->query("UPDATE `catogory` SET `open`='".($data->open == 1 ? '0' : '1')."' WHERE `id`='{$id}'");
    if($data->open == 1){
      Okay::report("The category is now closed");
    }else{
      Okay::report("The category is now open");
    }
    
    header("location: ?view=".$_GET["view"]);
    exit;
  }
  
  private function delete(int $id){
    $db = Database::get();
    $db->query("DELETE FROM `category_item` WHERE `cid`='{$id}'");
    $query = $db->query("SELECT `id` FROM `ticket` WHERE `cid`='{$id}'");
    while($row = $query->fetch()){
      $db->query("DELETE FROM `ticket_track` WHERE `tid`='{$row->id}'");
      $db->query("DELETE FROM `ticket_value` WHERE `hid`='{$row->id}'");
      $db->query("DELETE FROM `comment` WHERE `tid`='{$row->id}'");
    }
    $db->query("DELETE FROM `ticket` WHERE `cid`='{$id}'");
    $db->query("DELETE FROM `catogory` WHERE `id`='{$id}'");
    Okay::report("The category is now deleted");
  }
  
  private function create(string $name){
    $db = Database::get();
    $db->query("INSERT INTO `catogory` VALUES (NULL, '{$db->escape($name)}', 0, NULL);");
    Okay::report("Category is created");
    header("location: #");
    exit;
  }
}