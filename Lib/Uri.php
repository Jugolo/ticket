<?php
namespace Lib;

use Lib\Tempelate\Tokenizer;

class Uri{
  public static function getTempelateURIPath(string $root, Tokenizer $token, bool $next = true) : string{
    $current = $next ? $token->next() : $token->current();
    $current->expect("IDENTIFY");
    $elements = [$current->getContext()];
    while($token->next()->test("PUNCTOR", ".")){
      $current = $token->next();
      if(!$current->test("IDENTIFY") && !$current->test("STRING")){
        $current->expect("IDENTIFY");
      }
      $elements[] = $current->getContext();
    }
    return $root.implode("/", $elements);
  }
  
  /**
  *Convert a uri script format "one.two.tre" to a uro "one/two/tre"
  */
  public static function convertURI(string $uri) : string{
    $data = [];
    if(strlen($uri) == 0 || $uri[0] == ".")
      return "";
    $pointer = 0;
    while(($pos = strpos($uri, ".", $pointer)) !== false){
      $buff = substr($uri, $pointer, $pos);
      if(!self::identify_test($buff))
        return "";
      $data[] = $buff;
      $pointer = $pos+1;
    }
    if($pointer < strlen($uri)){
      $buff = substr($uri, $pointer);
      if(!self::identify_test($buff))
        return "";
      $data[] = $buff;
    }
    
    return implode("/", $data);
  }
  
  private static function identify_test(string $part) : bool{
    return preg_match("/^([A-Za-z_]*?)$/", $part);
  }
}