<?php
namespace Lib\Bbcode;

class Lexer{
  public static function toDom(string $str){
    $token = new Tokenizer($str);
    $current = new MainNode();
    return self::render($current, $token);
  }
  
  private static function render(BBNode $current, Tokenizer $token){
    while(!$token->eos()){
      $c = $token->next();
      if($c == "["){
        if(self::parseTag($current, $token)){
          break;
        }
      }else{
        $current->appendNode(new TextNode($c));
      }
    }
    return $current;
  }
  
  private static function parseTag(BBNode $node, Tokenizer $token) : bool{
    if($token->eos()){
      $node->appendNode(new TextNode("["));
      return false;
    }
    
    $s = $token->next();
    
    if($s[0] == "/"){
      $tag = substr($s, 1);
      if($node->tag() != strtolower($tag)){
        $node->appendNode(new TextNode("[/".$tag));
        return false;
      }
      if($token->next() != "]"){
        $node->appendNode(new TextNode("[/".$tag));
      }
      return true;
    }
    
    if($s != "]"){
      list($tag, $option) = self::getOptions($s);
      if(($e = $token->next()) != "]"){
        $node->appendNode(new TextNode("[".$s.$e));
        return false;
      }
    }else{
      $tag = $s;
      $option = "]";
    }
    
    switch($tag){
      case "color":
        $color = $option ? : "black";
        $node->appendNode(self::render(new ColorNode($color), $token));
       break;
      case "spoiler":
        $title = $option ? : "Spoiler";
        $node->appendNode(self::render(new SpoilerNode($title), $token));
        break;
      case "url":
        $url = $option ? : "#";
        $node->appendNode(self::render(new UrlNode($url), $token));
        break;
      case "title":
        $node->appendNode(self::render(new TitleNode(), $token));
        break;
      case "b":
        $node->appendNode(self::render(new BNode(), $token));
        break;
      case "i":
        $node->appendNode(self::render(new INode(), $token));
        break;
      case "u":
        $node->appendNode(self::render(new UNode(), $token));
        break;
      default:
        $node->appendNode(new TextNode("[".$s."]"));
    }
    
    return false;
  }
  
  private static function getOptions(string $context){
    $option = "";
    if(($pos = strpos($context, "=")) !== false){
      $tag = substr($context, 0, $pos);
      $option = substr($context, $pos+1);
    }else{
      $tag = $context;
    }
    return [strtolower($tag), $option];
  }
}