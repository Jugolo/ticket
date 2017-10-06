<?php
namespace Lib;

class Email{
  private $arg = [];
  
  public function pushArg(string $name, string $value){
    $this->arg[$name] = $value;
  }
  
  public function send(string $temp, string $to){
    $file = "Lib/Tempelate/Email/{$temp}.temp";
    if(!file_exists($file)){
      exit("here");
      return false; 
    }
    
    $title = "";
    $f = fopen($file, "r");
    if(!$f){
      return false;
    }
    
    while($line = trim(fgets($f))){
      $title .= $line;
    }
    
    $arg = [];
    while($line = trim(fgets($f))){
      $arg[] = $line;
    }
          
    $message = "";
    while(($line = fgets($f)) !== false){
      $message .= $line;
    }
    $arg[] = "from:support@".$_SERVER["SERVER_NAME"];
    mail($to, $title, $this->parseMessage($message), implode("\r\n", $arg));
  }
  
  private function parseMessage(string $message){
    $arg = $this->arg;
    return preg_replace_callback(
      "/\{\{([a-zA-Z_]*)\}\}/",
      function($m) use($arg){
        if(empty($arg[$m[1]])){
          return $m[0];
        }
      
        return $arg[$m[1]];
      },
      $message
      );
  }
}