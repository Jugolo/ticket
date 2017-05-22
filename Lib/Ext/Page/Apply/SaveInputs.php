<?php
namespace Lib\Ext\Page\Apply;

class SaveInputs{
  private $id;
  private $saved = [];
  
  public function __construct(int $id){
    $this->id = $id;
    if(!empty($_SESSION["saved_inputs"]) && !empty($_SESSION["saved_inputs"][$id])){
      $this->saved = $_SESSION["saved_inputs"][$id];
    }
  }
  
  public function put($id, string $data){
    $this->saved[$id] = $data;
  }
  
  public function get(int $id) : string{
    if(!empty($this->saved[$id])){
      return $this->saved[$id];
    }
    return "";
  }
  
  public function save(){
    if(count($this->saved) != 0){
      if(empty($_SESSION["saved_inputs"])){
        $_SESSION["saved_inputs"] = [];
      }
      $_SESSION["saved_inputs"][$this->id] = $this->saved;
    }
  }
  
  public function delete(){
    if(!empty($_SESSION["saved_inputs"]) && !empty($_SESSION["saved_inputs"][$this->id])){
      unset($_SESSION["saved_inputs"][$this->id]);
    }
  }
}