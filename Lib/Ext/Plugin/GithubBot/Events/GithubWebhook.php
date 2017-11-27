<?php
namespace Lib\Ext\Plugin\GithubBot\Events;

use Lib\Ext\Plugin\GithubBot\Exception\GithubWebhookException;

class GithubWebhook{
  private $secretKey = null;
  private $isWebhook;
  private $controler;
  
  public function setSecretKey(string $key){
    $this->secretKey = $key;
    $this->isWebhook = strpos($_SERVER["HTTP_USER_AGENT"], "GitHub-Hookshot/") === 0 && ($this->secretKey && $this->controleSecretKey() || !$this->secretKey);
    if($this->isWebhook){
      $this->controler = $this->initControler();
    }
  }
  
  public function isWebhook() : bool{
    return $this->isWebhook;
  }
  
  public function type() : string{
    if(!empty($_SERVER["HTTP_X_GITHUB_EVENT"]))
      return $_SERVER["HTTP_X_GITHUB_EVENT"];
    return "";
  }
  
  public function getControler(){
    return $this->controler;
  }
  
  private function initControler(){
    switch($this->type()){
      case "issues":
        return new GithubWebhookIssues();
      case "issue_comment":
        return new GithubWebhookIssuesComment();
      default:
        throw new GithubWebhookException("Unknown type webhook request: ".$this->type());
    }
  }
  
  private function controleSecretKey() : bool{
    if(empty($_SERVER["HTTP_X_HUB_SIGNATURE"]))
      return false;
    
    list($hash, $secret) = explode("=", $_SERVER["HTTP_X_HUB_SIGNATURE"]);
    
    //do we support the algro hash?
    if(!in_array($hash, hash_algos()))
      return false;
    $body = file_get_contents('php://input');
    $check = hash_hmac($hash, $body, $this->secretKey);
    //if it pass this we must trust this is a valid github webhook
    return hash_equals($check, $secret);
  }
}