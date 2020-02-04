<?php
namespace Lib\Ext\Plugin\GithubBot\Events;

use Lib\Ext\Plugin\GithubBot\GithubUserInformation;

class GithubWebhookIssues{
  private $data;
  
  public function __construct(){
    $this->data = json_decode($_POST["payload"], true);
  }
  
  public function action(){
    return $this->data["action"];
  }
  
  public function id(){
    return $this->data["issue"]["id"];
  }
  
  public function number(){
    return $this->data["issue"]["number"];
  }
  
  public function title(){
    return $this->data["issue"]["title"];
  }
  
  public function message(){
    return $this->data["issue"]["body"];
  }
  
  public function getUser(){
    return new GithubUserInformation($this->data["issue"]["user"]);
  }
}