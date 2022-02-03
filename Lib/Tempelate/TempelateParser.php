<?php
namespace Lib\Tempelate;

use Lib\Tempelate\Node\TempelateHTMLNode;
use Lib\Tempelate\Node\TempelateNodeList;
use Lib\Tempelate\Node\TempelateEmptyNode;
use Lib\Tempelate\Node\TempelateExpresionNode;
use Lib\Tempelate\Tokenizer;
use Lib\Exception\TempelateException;

class TempelateParser{
  private $base;
  private $reader;
  private $controler;
  private $flag;
  private $token;
  private $trace;
  
  const INLINE_CODE = 1;
  const OUTLINE_CODE = 2;
  
  public function __construct(string $base, TempelateFileReader $reader, TempelateControler $controler, TempelateStackTrace $trace){
    $this->base      = $base;
    $this->reader    = $reader;
    $this->controler = $controler;
    $this->flag      = self::OUTLINE_CODE;
    $this->token     = new Tokenizer($reader, $controler);
    $this->trace     = $trace;
    $trace->addFile($reader->getFile());
  }
  
  public function getStackTrace() : TempelateStackTrace{
    return $this->trace;
  }
  
  public function setFlag(int $flag){
    if(self::INLINE_CODE != $flag && self::OUTLINE_CODE != $flag)
      throw new TempelateException("Unknown flag code ({$flag})");
    $this->flag = $flag;
  }
  
  public function getFile() : string{
    return $this->reader->getFile();
  }
  
  public function getLine() : int{
    return $this->reader->getLine();
  }
  
  public function getPath() : string{
     return $this->base;
  }
  
  public function getTokenizer() : Tokenizer{
    return $this->token;
  }
  
  public function getControler() : TempelateControler{
    return $this->controler;
  }
  
  public function getBody() : string{
    $node = new TempelateNodeList();
    while(!$this->reader->eof()){
      $node->append($this->render(true));
    }
    return $node->toString();
  }
  
  public function getBlock(array $stop = []) : string{
     $node = new TempelateNodeList();
     while(!$this->reader->eof()){
       if($this->flag == self::INLINE_CODE && ($this->token->next()->test("KEYWORD", "endblock") || $this->token->current()->test("KEYWORD") &&  in_array($this->token->current()->getContext(), $stop))){
         if($this->token->current()->test("KEYWORD", "endblock")){
           if(!$this->token->next()->test("ECB")){
             throw new TempelateException("Detected unfinish code block '{$this->token->current()->getContext()}({$this->token->current()->getType()})'");
           }
           $this->flag = self::OUTLINE_CODE;
         }
         return $node->toString();
       }
       
       $node->append($this->render(false));
     }
     throw new TempelateException("Expected code 'endblock' got end of line", $this->getFile(), $this->getLine());
  }
  
  public function expresion() : TempelateNode{
    return TempelateExpresion::expresion($this->token, $this);
  }
  
  private function render(bool $next) : TempelateNode{
    if($this->flag == self::INLINE_CODE)
      return $this->renderCode($next);
    return $this->renderHtml();
  }
  
  private function renderCode(bool $next){
    if($next)
      $this->token->next();
    
    if($this->token->current()->getType() === "KEYWORD" && $this->controler->hasControler($this->token->current()->getContext()))
      $node = $this->controler->getControler($this->token->current()->getContext())->build($this);
    else
      $node = new TempelateExpresionNode($this->expresion());
    
    if(!$this->token->current()->test("ECB"))
      throw new TempelateException("Detected unfinish code block '{$this->token->current()->getContext()}({$this->token->current()->getType()})'", $this->getFile(), $this->getLine());
    $this->flag = self::OUTLINE_CODE;
    return $node;
  }
  
  private function renderHtml() : TempelateNode{
    $result = "";
    while(!$this->reader->eof()){
      $c = $this->reader->read();
      if($c == "!" && $this->reader->peek() == "-"){
        $this->reader->read();
        $this->flag = self::INLINE_CODE;
        break;
      }
      $result .= $c;
    }
    return trim($result) ? new TempelateHTMLNode($result) : new TempelateEmptyNode();
  }
}