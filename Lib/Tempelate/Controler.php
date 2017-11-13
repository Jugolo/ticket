<?php
namespace Lib\Tempelate;

use Lib\Exception\TempelateException;

class Controler{
  private $xml;
  private $script = ["Lib/Tempelate/Script/CowTicket.js"];
  private $base;
  
  public function __construct(string $dir){
    $this->base = dirname($dir)."/";
    $this->xml = new \SimpleXMLElement(file_get_contents($dir));
    if($this->xml->script)
      $this->scriptRender();
  }
  
  public function getScripts() : array{
    return $this->script;
  }
  
  private function scriptRender(){
    foreach($this->xml->script->script as $node)
      $this->script[] = $this->base.str_replace(".", "/", (string)$node["src"]).".js";
  }
}