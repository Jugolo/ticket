<?php
namespace Lib\Bbcode;

class TextNode implements BBNode{
  private $str;
  private $smylie = [
    ":)" => "\u{1F60A}",
    ":D" => "\u{1F603}",
    ":(" => "\u{1F622}"
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
       return $cache[$code[0]];
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