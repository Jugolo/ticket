<?php
namespace Lib\Ext\Plugin\GithubBot;

class GithubUserInformation{
  private $data = [];
  
  public function __construct(array $data){
    $this->data = $data;
  }
  
  public function username() : string{
    return $this->data["login"];
  }
  
  public function id() : int{
    return $this->data["id"];
  }
}