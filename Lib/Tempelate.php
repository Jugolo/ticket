<?php
namespace Lib;

use Lib\Exception\TempelateException;
use Lib\Tempelate\DataContainer;
use Lib\Tempelate\Controler;
use Lib\Tempelate\Tokenizer;
use Lib\Tempelate\TempelateNode;
use Lib\Tempelate\ExpresionNode;
use Lib\Tempelate\CssNode;
use Lib\Tempelate\ScriptNode;
use Lib\Tempelate\ConfigNode;
use Lib\Tempelate\GetStyleNode;
use Lib\Tempelate\GetScriptNode;
use Lib\Tempelate\IfNode;
use Lib\Tempelate\AccessNode;
use Lib\Tempelate\EndBlockNode;
use Lib\Tempelate\LoggedInNode;
use Lib\Tempelate\ElseNode;
use Lib\Tempelate\BooleanCompareExpresion;
use Lib\Tempelate\NumberNode;
use Lib\Tempelate\IdentifyNode;
use Lib\Tempelate\ForeachNode;
use Lib\Tempelate\EchoNode;
use Lib\Tempelate\SetNode;
use Lib\Tempelate\ArrayGetNode;
use Lib\Tempelate\StringNode;
use Lib\Tempelate\BooleanBindNode;
use Lib\Tempelate\NotNode;
use Lib\Tempelate\PageAccessNode;

class Tempelate{
  private $dir;
  private $data;
  private $files = [];
  private $controler;
  
  public function __construct(string $dir){
    $this->dir = $this->getTempelateDirName($dir);
    $this->data = new DataContainer();
    if(!is_dir($this->dir)){
      throw new TempelateException("Tempelate path not found '{$this->dir}'");
    }
    //controle for controler file
    if(file_exists($this->dir."style.xml"))
      $this->controler = new Controler($this->dir."style.xml");
  }
  
  public function hasControler() : bool{
    return $this->controler !== null;
  }
  
  public function getControler(){
    return $this->controler;
  }
  
  public function put(string $name, $data){
    $this->data->put($name, $data);
  }
  
  public function render(string $name, Page $page){
    Report::toTempelate($this);
    if(Cache::exists($this->dir.$name)){
       $obj  = eval('?> '.Cache::get($this->dir.$name).' <?php ');
       if($obj->isValid()){
         $this->show($obj);
         return;
       }
       Cache::delete($this->dir.$name);
    }
    
    $code = $this->generateStyleClass($this->parse($this->getSourceCode($name, false)));
    Cache::create($this->dir.$name, $code);
    $this->show(eval('?> '.Cache::get($this->dir.$name).' <?php '));
  }
  
  private function generateStyleClass($code){
    return "<?php return new class(\$this->controler, \$page){
       private \$scriptFile = [];
       private \$css;
       private \$files = [{$this->convertFile()}];
       private \$controler;
       private \$page;
       
       public function __construct(\$controler, Lib\\Page \$page){
         \$this->css = new Lib\\Tempelate\\Css();
         \$this->controler = \$controler;
         \$this->page = \$page;
       }
       
       public function isValid(){
         for(\$i=0;\$i<count(\$this->files);\$i++){
           if(filemtime(\$this->files[\$i][0]) > \$this->files[\$i][1])
             return false;
         }
         return true;
       }
       
       public function show(Lib\Tempelate\DataContainer \$db){
         {$code}
       }
       
       public function getScriptSources(){
         echo '<script>';
         foreach(array_merge(\$this->controler !== null ? \$this->controler->getScripts() : [], \$this->scriptFile) as \$script){
           if(!file_exists(\$script)){
             throw new Lib\\Exception\\TempelateException('A requried script missing \"'.\$script.'\"');
           }
           echo file_get_contents(\$script);
         }
         echo '</script>';
       }
    };?>";
  }
  
  private function convertFile(){
    $data = [];
    foreach($this->files as $file){
      $data[] = "['{$file}', ".time()."]";
    }
    return implode(", ", $data);
  }
  
  private function parse(string $code){
    $currentPos = 0;
    $context = "";
    while(($pos = strpos($code, "!-", $currentPos)) !== false){
      if($currentPos < $pos){
        $context .= $this->html(substr($code, $currentPos, $pos-$currentPos));
        $currentPos = $pos+2;
      }else{
        $currentPos += 2;
      }
      
      $pos = strpos($code, "-!", $currentPos);
      if($pos === false){
        $context .= "!-";
        continue;
      }
      
      $context .= $this->code(substr($code, $currentPos, $pos-$currentPos));
      $currentPos = $pos+2;
    }
    
    //if wee not is done yet append the last in the code as html
    if(strlen($code)-1 > $currentPos){
      $context .= $this->html(substr($code, $currentPos));
    }
    
    return $context;
  }
  
  private function code(string $block){
     //now wee need to find out wich block it is. if it a single variabel it should be echo out. 
     $token = new Tokenizer($block);
     if($token->current()->isKeyword() && $token->current()->getContext() == "include"){
       return $this->handleInclude($token);
     }
     $lex = $this->lex($token, $block);
     if($lex instanceof ExpresionNode){
       return "echo htmlentities({$lex->toCode()});";
     }
    
     return $lex->toCode();
  }
  
  private function lex(Tokenizer $token, string $code) : TempelateNode{
    $node = $this->getStatment($token);
    if(!$token->end())
      throw new TempelateException("Unfinish code detected: ".$code);
    return $node;
  }
  
  private function getStatment(Tokenizer $token) : TempelateNode{
    $current = $token->current();
    if($current->isKeyword()){
      switch($current->getContext()){
        case "addCss":
          return $this->handleCss($token);
        case "getStyle":
          $token->next();
          return new GetStyleNode();
        case "addScript":
          return $this->handleScript($token);
        case "getScript":
          $token->next();
          return new GetScriptNode();
        case "if":
        case "elseif":
          return $this->handleIf($token);
        case "else":
          $token->next();
          return new ElseNode();
        case "endblock":
          $token->next();
          return new EndBlockNode();
        case "foreach":
          return $this->handleForeach($token);
        case "echo":
          $token->next();
          return new EchoNode($this->expresion($token));
        case "set":
          return $this->handleSet($token);
      }
    }
    return $this->expresion($token);
  }
  
  private function expresion(Tokenizer $token) : TempelateNode{
    return new ExpresionNode($this->booleanBind($token));
  }
  
  private function booleanBind(Tokenizer $token){
    $exp = $this->booleanExpresion($token);
    if($token->current()->isKeyword() && ($token->current()->getContext() == "or" || $token->current()->getContext() == "and")){
      $bind = $token->current()->getContext();
      $token->next();
      return new BooleanBindNode($exp, $bind, $this->booleanBind($token));
    }
    return $exp;
  }
  
  private function booleanExpresion(Tokenizer $token) : TempelateNode{
    $expresion = $this->prefixExpresion($token);
    if($token->current()->getType() == "PUNCTOR"){
      $current = $token->current()->getContext();
      if($current == "!=" || $current == "=="){
        $token->next();
        return new BooleanCompareExpresion($expresion, $current, $this->booleanExpresion($token));
      }
    }
    return $expresion;
  }
  
  private function prefixExpresion(Tokenizer $token){
    if($token->current()->isKeyword() && $token->current()->getContext() == "not"){
      $token->next();
      return new NotNode($this->primaryExpresion($token));
    }
    return $this->primaryExpresion($token);
  }
  
  private function primaryExpresion(Tokenizer $token){
    $current = $token->current();
    if($current->isKeyword()){
      switch($current->getContext()){
        case "config":
          return $this->handleConfig($token);
        case "pageAccess":
          return $this->handlePageAccess($token);
        case "access":
          return $this->handleAccess($token);
        case "loggedIn":
          $token->next();
          return new LoggedInNode($token);
      }
    }
    if($current->getType() == "NUMBER"){
      $token->next();
      return new NumberNode($current->getContext());
    }
    if($current->getType() == "IDENTIFY"){
      $token->next();
      return $this->handleIdentify($token, new IdentifyNode($current->getContext()));
    }
    if($current->getType() == "STRING"){
      $token->next();
      return new StringNode($current->getContext());
    }
    throw new TempelateException("Unexpected {$current->getType()}({$current->getContext()})");
  }
  
  private function handlePageAccess(Tokenizer $token){
    $token->next()->expect("IDENTIFY");
    $identify = $token->current()->getContext();
    $token->next();
    return new PageAccessNode($identify);
  }
  
  private function handleIdentify(Tokenizer $token, TempelateNode $node){
    while(!$token->end() && $token->current()->getType() == "PUNCTOR"){
      $current = $token->current();
      if($current->getContext() == "["){
        $token->next();
        $exp = $this->expresion($token);
        $token->current()->expect("PUNCTOR");
        if($token->current()->getContext() != "]")
          throw new TempelateException("Expected ] in end of array value get");
        $token->next();
        $node = new ArrayGetNode($node, $exp);
      }else{
        break;
      }
    }
    return $node;
  }
  
  private function handleAccess(Tokenizer $token){
    $token->next()->expect("IDENTIFY");
    $name = $token->current()->getContext();
    $token->next();
    return new AccessNode($name);
  }
  
  private function handleConfig(Tokenizer $token){
    $token->next()->expect("IDENTIFY");
    $name = $token->current()->getContext();
    $token->next();
    return new ConfigNode($name);
  }
  
  private function handleSet(Tokenizer $token) : TempelateNode{
    $token->next()->expect("IDENTIFY");
    $variabel = $token->current()->getContext();
    $token->next()->expect("PUNCTOR");
    if($token->current()->getContext() != "=")
      throw new TempelateException("Missing = after set variabel");
    $token->next();
    return new SetNode($variabel, $this->expresion($token));
  }
  
  private function handleEcho(string $value){
    if(!$this->isVariabel($value))
      throw new TempelateException("After echo block there must be a variabel");
    return "echo \$db->getString('{$value}');";
  }
  
  private function handleCss(Tokenizer $token){
    $token->next();
    return new CssNode($this->dir.$this->getUri($token));
  }
  
  private function handleForeach(Tokenizer $token){
    $token->next();
    $value = $this->expresion($token);
    $token->current()->expect("KEYWORD");
    if($token->current()->getContext() != "as")
      throw new TempelateException("Missing 'as' after identify in foreach");
    $token->next()->expect("IDENTIFY");
    $key = $token->current()->getContext();
    $val = null;
    if(!$token->end()){
      $token->next()->expect("PUNCTOR");
      if($token->current()->getContext() != ":")
        throw new TempelateException("Unknown punctor after key variabel in foreach");
      $token->next()->expect("IDENTIFY");
      $val = $token->current()->getContext();
    }
    return new ForeachNode($value, $key, $val);
  }
  
  private function handleScript(Tokenizer $token){
    $token->next();
    return new ScriptNode($this->dir.$this->getUri($token));
  }
  
  private function handleIf(Tokenizer $token){
    $isElseif = $token->current()->getContext() == "elseif";
    $token->next();
    return new IfNode($this->expresion($token), $isElseif);
  }
  
  private function handleInclude(Tokenizer $token){
    $token->next();
    return $this->parse($this->getSourceCode($this->getUri($token), true));
  }
  
  private function getUri(Tokenizer $token){
    $current = $token->current();
    $current->expect("IDENTIFY");
    $elements = [$current->getContext()];
    while(!$token->end() && $token->next()->getType() == "PUNCTOR" && $token->current()->getContext() == "."){
      $current = $token->next();
      $current->expect("IDENTIFY");
      $elements[] = $current->getContext();
    }
    
    return implode("/", $elements);
  }
  
  private function html(string $context) : string{
    return " echo '".str_replace("'", "\\'", $context)."'; \r\n";
  }
  
  private function getSourceCode(string $file, bool $isInclude) : string{
     $name = $this->dir.$file.".".($isInclude ? "include" : "style");
     if(!file_exists($name))
       throw new TempelateException("Unknown file: '".$name."'");
     $this->files[] = $name;
     return file_get_contents($name);
  }
  
  private function show($obj){
    $obj->show($this->data);
  }
  
  private function getTempelateDirName(string $dir){
    return "Lib/Tempelate/Style/".$dir.($dir ? "/" : "");
  }
}