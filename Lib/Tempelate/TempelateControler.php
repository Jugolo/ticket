<?php
namespace Lib\Tempelate;

use Lib\Plugin\Plugin;
use Lib\Tempelate\Builder\TempelateIncludeBuilder;
use Lib\Tempelate\Builder\TempelateAddCssBuilder;
use Lib\Tempelate\Builder\TempelateIfBuilder;
use Lib\Tempelate\Builder\TempelateLanguageBuilder;
use Lib\Tempelate\Builder\TempelateEchoBuilder;
use Lib\Tempelate\Builder\TempelatePluginBuilder;
use Lib\Tempelate\Builder\TempelateForeachBuilder;
use Lib\Tempelate\Builder\TempelateGetStyleBuilder;
use Lib\Tempelate\Builder\TempelateAddScriptBuilder;
use Lib\Tempelate\Builder\TempelateGetScriptBuilder;
use Lib\Tempelate\Builder\TempelateSetBuilder;
use Lib\Exception\TempelateException;
use Lib\Tempelate;

class TempelateControler{
  public static function createInstance(Tempelate $tempelate) : TempelateControler{
    $controler = new TempelateControler();
    
    $controler->put("addCss",    new TempelateAddCssBuilder());
    $controler->put("getStyle",  new TempelateGetStyleBuilder());
    $controler->put("include",   new TempelateIncludeBuilder($tempelate));
    $controler->put("if",        new TempelateIfBuilder());
    $controler->put("language",  new TempelateLanguageBuilder());
    $controler->put("echo",      new TempelateEchoBuilder());
    $controler->put("plugin",    new TempelatePluginBuilder());
    $controler->put("foreach",   new TempelateForeachBuilder());
    $controler->put("addScript", new TempelateAddScriptBuilder());
    $controler->put("getScript", new TempelateGetScriptBuilder());
    $controler->put("set",       new TempelateSetBuilder());
    
    Plugin::trigger_event("system.tempelate.controler.init", $controler);
    return $controler;
  }
  
  private $nodes = [];
  
  private function __construct(){
    
  }
  
  public function hasControler(string $name) : bool{
    return array_key_exists($name, $this->nodes);
  }
  
  public function getControler(string $name) : TempelateBuilder{
    if(!$this->hasControler($name))
      throw new TempelateException("Missing controler '{$name}'");
    return $this->nodes[$name];
  }
  
  public function put(string $name, TempelateBuilder $builder){
    $this->nodes[$name] = $builder;
  }
}