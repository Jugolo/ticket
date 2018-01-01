<?php
namespace Lib\Ext\Plugin\GithubBot;

use Lib\Ext\Plugin\GithubBot\Events\GithubWebhook;

class Github{
  private $user = null;
  private $events = [];
  
  public function __construct(){
    $this->events = [
      "webhook" => new GithubWebhook()
      ];
  }
  
  public function webhook() : GithubWebhook{
    return $this->events["webhook"];
  }
  
  public function addUser(GithubUser $user){
    $this->user = $user;
  }
  
  public function commentIssues(string $user, string $repo, int $issues_id, string $message){
    $data = $this->sendRequest("https://api.github.com/repos/{$user}/{$repo}/issues/{$issues_id}/comments", [
      "body" => $message
      ]);
  }
  
  public function closeIssues(string $user, string $repo, int $number, $message){
    $this->sendRequest("https://api.github.com/repos/{$user}/{$repo}/issues/{$number}", [
        "state" => "close",
        "body"  => $message
      ]);
  }
  
  public function openIssues(string $user, string $repo, int $number, $message){
    $this->sendRequest("https://api.github.com/repos/{$user}/{$repo}/issues/{$number}", [
        "state" => "open",
        "body"  => $message
      ]);
  }
  
  private function sendRequest($url, array $post){
    $curl = curl_init();
    if($this->user){
      curl_setopt($curl, CURLOPT_USERPWD, $this->user->username().":".$this->user->password());
    }
    curl_setopt_array($curl, [
      CURLOPT_USERAGENT      => "CowTicket-GithubBot",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_HTTPAUTH       => CURLAUTH_BASIC,
      CURLOPT_HTTPHEADER     => array('Content-Type: application/json'),
      CURLOPT_URL            => $url,
      CURLOPT_POST           => true,
      CURLOPT_POSTFIELDS     => json_encode($post)
      ]);
    $result = curl_exec($curl);
    curl_close($curl);
    return $result;
  }
}