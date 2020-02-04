<?php
namespace Lib;

class Email{
  private $args = [];
  private $title = "";
  private $message;
  private $arg = [];
  
  public function __construct(string $temp){
    $file = "Lib/Tempelate/Email/{$temp}.temp";
    if(!file_exists($file)){
      return false; 
    }
    
    $f = fopen($file, "r");
    if(!$f){
      return false;
    }
    
    while($line = trim(fgets($f))){
      $this->title .= $line;
    }
    
    while($line = trim(fgets($f))){
      $this->arg[] = $line;
    }
    
    while(($line = fgets($f)) !== false){
      $this->message .= $line;
    }
    $this->arg[] = "from:".$this->convertEmailName(Config::get("system_name"))."@".$_SERVER["SERVER_NAME"];
  }
  
  public function send(string $to){
    //clean old floods control 
    if(Flood::controle("EMAIL"))
      mail($to, $this->title, $this->parseMessage($this->message), implode("\r\n", $this->arg));
    else
      Log::system("LOG_EMAIL_FLOOD", $to, IP);
  }
  
  public function pushArg(string $name, string $value){
    $this->args[$name] = $value;
  }
  
  private function convertEmailName(string $name){
    return str_replace([
      " "
      ], [
      "_"
      ], $name);
  }
  
  private function parseMessage(string $message){
    $arg = $this->args;
    return preg_replace_callback(
      "/\{\{([a-zA-Z_.]*)\}\}/",
      function($m) use($arg){
        if(strpos($m[1], "config.") === 0){
           return Config::get(substr($m[1], 7));
        }
        if(empty($arg[$m[1]])){
          return $m[0];
        }
      
        return $arg[$m[1]];
      },
      $message
      );
  }
}