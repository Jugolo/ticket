<?php
namespace Lib\Bbcode;

class TextNode implements BBNode{
  private $str;
  private $smylie = [
    ":)" => "img/smylie/glad.gif",
    ":D" => "img/smylie/happy.png",
    ":(" => "img/smylie/sad.png"
  ];
  
  public function __construct(string $str){
    $this->str = $str;
  }
  
  public function appendNode(BBNode $node){
    throw new \Exception("Cant append node to string node");
  }
  
  public function toHtml() : string{
    $cache = $this->smylie;
    $this->str = preg_replace_callback("/(http:\/\/)?(www\.)?([a-zA-Z]*)\.([a-zA-Z]{2,6})/", function($reg){
      $url = $reg[0];
      if(strpos($url, "http://") !== 0){
        $url = "http://".$url;
      }
      return "<a href='{$url}' target='_blank'>{$reg[0]}</a>";
    }, nl2br(htmlentities($this->str)));
    return preg_replace_callback("/(".$this->getRexExpSmylie().")/", function($code) use ($cache){
       return "<img src='{$cache[$code[0]]}' alt='{$code[0]}' class='smylie'>";
    }, $this->str);
  }
  
  private function getRexExpSmylie(){
    $parts = [];
    foreach($this->smylie as $key => $data){
      $parts[] = preg_quote($key);
    }
    return implode("|", $parts);
  }
  
  public function tag() : string{
    return "";
  }
}