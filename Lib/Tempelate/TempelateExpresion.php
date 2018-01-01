<?php
namespace Lib\Tempelate;

use Lib\Exception\TempelateException;
use Lib\Tempelate\TempelateParser;
use Lib\Tempelate\Node\TempelateTestExpresion;
use Lib\Tempelate\Node\TempelateBoolbindExpresion;
use Lib\Tempelate\Node\TempelateBoolExpresion;
use Lib\Tempelate\Node\TempelateNotExpresion;
use Lib\Tempelate\Node\TempelateConfigExpresion;
use Lib\Tempelate\Node\TempelatePageAccessNode;
use Lib\Tempelate\Node\TempelateAccessNode;
use Lib\Tempelate\Node\TempelateLoggedInNode;
use Lib\Tempelate\Node\TempelateNumberNode;
use Lib\Tempelate\Node\TempelateIdentifyNode;
use Lib\Tempelate\Node\TempelateArrayGetNode;
use Lib\Tempelate\Node\TempelateStringNode;
use Lib\Tempelate\Node\TempelateFunctionCallNode;

class TempelateExpresion{
  public static function expresion(Tokenizer $token, TempelateParser $parser) : TempelateNode{
    $expresion = self::boolBind($token, $parser);
    if($token->current()->test("PUNCTOR", "?")){
      $token->next();
      $true = self::expresion($token, $parser);
      $token->current()->expect("PUNCTOR", ":");
      $token->next();
      $expresion = new TempelateTestExpresion($expresion, $true, self::expresion($token, $parser));
    }
    return $expresion;
  }
  
  private static function boolBind(Tokenizer $token, TempelateParser $parser) : TempelateNode{
    $expresion = self::boolExpresion($token, $parser);
    if($token->current()->test("KEYWORD")){
      $current = $token->current()->getContext();
      if($current == "or" || $current == "and"){
        $token->next();
        return new TempelateBoolbindExpresion($expresion, $current, self::boolBind($token, $parser));
      }
    }
    return $expresion;
  }
  
  private static function boolExpresion(Tokenizer $token, TempelateParser $parser) : TempelateNode{
    $expresion = self::prefixExpresion($token, $parser);
    if($token->current()->test("PUNCTOR")){
      $current = $token->current()->getContext();
      if($current == "==" || $current == "!="){
        $token->next();
        $expresion = new TempelateBoolExpresion($expresion, $current, self::boolExpresion($token, $parser));
      }
    }
    return $expresion;
  }
  
  private static function prefixExpresion(Tokenizer $token, TempelateParser $parser) : TempelateNode{
    if($token->current()->test("KEYWORD", "not")){
      $token->next();
      return new TempelateNotExpresion(self::primarayExpresion($token, $parser));
    }
    return self::primarayExpresion($token, $parser);
  }
  
  private static function primarayExpresion(Tokenizer $token, TempelateParser $parser) : TempelateNode{
    $current = $token->current();
    $token->next();
    if($current->test("KEYWORD")){
      switch($current->getContext()){
        case "config":
          return new TempelateConfigExpresion($token);
        case "pageAccess":
          return new TempelatePageAccessNode($token);
        case "access":
          return new TempelateAccessNode($token);
        case "loggedIn":
          return new TempelateLoggedInNode();
      }
    }
    
    if($current->test("NUMBER"))
      return new TempelateNumberNode($current->getContext());
    
    if($current->test("IDENTIFY"))
      return self::afterIdentify($token, new TempelateIdentifyNode($current->getContext()), $parser);
    
    if($current->test("STRING"))
      return new TempelateStringNode($current->getContext());
    
     throw new TempelateException("Unexpected {$current->getType()}({$current->getContext()})", $parser->getFile(), $parser->getLine());
  }
  
  private static function afterIdentify(Tokenizer $token, TempelateNode $node, TempelateParser $parser) : TempelateNode{
    while($token->current()->test("PUNCTOR")){
      $current = $token->current();
      if($current->getContext() == "["){
         $node = new TempelateArrayGetNode($parser, $node);
      }elseif($current->getContext() == "(")
        $node = new TempelateFunctionCallNode($parser, $node);
      else{
        break;
      }
    }
    return $node;
  }
}