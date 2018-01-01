<?php
namespace Lib\Ext\Plugin\GithubBot;

class GithubUser{
  private $username;
  private $password;
  
  public function __construct(string $username, string $password){
    $this->username = $username;
    $this->password = $password;
  }
  
  public function username() : string{
    return $this->username;
  }
  
  public function password() : string{
    return $this->password;
  }
}