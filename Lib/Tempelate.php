<?php
namespace Lib;

use Lib\Tempelate\TempelateStack;
use Lib\Tempelate\TempelateDatabase;
use Lib\Tempelate\TempelateDirLoader;
use Lib\Tempelate\TempelateFileLoader;
use Lib\Tempelate\TempelateControler;
use Lib\Tempelate\TempelateParser;
use Lib\Exception\TempelateFileNotFound;
use Lib\Tempelate\TempelateFileCreator;
use Lib\Tempelate\TempelateData;
use Lib\Tempelate\TempelateInfo;
use Lib\Tempelate\TempelateStackTrace;
use Lib\Cache;
use Lib\Page;
use Lib\Language\Language;

class Tempelate{
  private $stack = [];
  private $name  = "<unknown>";
  private $db;
  private $main;
  private $controler;
  private $data;
  private $info;
  
  public function __construct(string $name, Page $page){
    $this->db        = new TempelateDatabase();
    $this->name      = $name;
    $this->main      = new TempelateDirLoader($this->getMainDir($name));
    $this->controler = TempelateControler::createInstance($this);
    if(!$this->main->exists())
      throw new TempelateFileNotFound($this->main->getDir()." was not found");
    $this->initTempelateSetting();
    $this->data      = new TempelateData($page, $this->info);
    
    $this->put("lang", function(string $key){
      return Language::get($key);
    });
  }
  
  public function getPathLoader() : TempelateDirLoader{
    return $this->main;
  }
  
  public function render_plugin(string $dir) : string{
    $result = "";
    foreach($this->stack as $stack){
      if($stack->containsFile($dir.".style")){
        $result .= $this->getSource($stack->getFile($dir.".style"));
      }
    }
    return $result;
  }
  
  public function put(string $key, $value){
    $this->db->put([$key => $value]);
  }
  
  public function putBlock(array $data){
    $this->db->put($data);
  }
  
  public function newStack(string $base){
    $dir = new TempelateDirLoader($base);
    if($dir->exists())
      $this->stack[] = $dir;
  }
  
  public function render(string $name, string $dir = ""){
    $loader = $dir ? new TempelateDirLoader($dir) : $this->main;
    if(!$loader->containsFile($name.".style"))
      throw new TempelateFileNotFound($loader->getDir().$name.".style was not found", $this->getMainDir().$name.".style", 0);
    $source = $this->getSource($loader->getFile($name.".style"));
    exit($source);
  }
  
  public function hasControler() : bool{
    return $this->info != null;
  }
  
  public function getControler() : TempelateInfo{
    return $this->info;
  }
  
  public function getMainName() : string{
    return $this->name;
  }
  
  private function getSource(TempelateFileLoader $loader) : string{
    if(!$loader->exists())
      throw new TempelateFileNotFound($loader->getPath()." was not found");
    
    //controle if wee has a cached version of this file
    if(Cache::exists($loader->getPath())){
      $controler = eval(Cache::get($loader->getPath()));
      if($controler->isFresh())
        return $this->handleTempelateObject($controler);
    }
    
    $trace = new TempelateStackTrace();
    
    $source = $this->getConvertedObject($this->parseSource($loader, $trace), $trace);
    Cache::create($loader->getPath(), $source);
    return $this->handleTempelateObject(eval($source));
  }
  
  private function handleTempelateObject($obj) : string{
    return $obj->get($this->data, $this->db, $this);
  }
  
  private function parseSource(TempelateFileLoader $loader, TempelateStackTrace $trace) : string{
    $parser = new TempelateParser($loader->base(), $loader->getReader(), $this->controler, $trace);
    return $parser->getBody();
  }
  
  private function getConvertedObject(string $body, TempelateStackTrace $trace){
    return TempelateFileCreator::convert($body, $trace);
  }
  
  private function getMainDir(){
    $dir = "Lib/Tempelate/Style/{$this->name}/";
    if(defined("IN_ADMIN"))
      $dir .= "Admin/";
    return $dir;
  }
  
  private function initTempelateSetting(){
    $info = "Lib/Tempelate/Style/{$this->name}/style.xml";
    if(file_exists($info))
      $this->info = new TempelateInfo(new \SimpleXMLElement(file_get_contents($info)), "Lib/Tempelate/Style/{$this->name}/");
  }
}