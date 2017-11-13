<?php
namespace Lib\Tempelate;

class ForeachNode implements TempelateNode{
  private $value;
  private $key;
  private $variabel;
  
  public function __construct(TempelateNode $value, string $key,  $variabel){
    $this->value    = $value;
    $this->key      = $key;
    $this->variabel = $variabel;
  }
  
  public function toCode() : string{
    $str = "foreach(\$db->toArray({$this->value->toCode()}) as \${$this->key}";
    if($this->variabel){
      $str .= " => \${$this->variabel}){\$db->put('{$this->key}', \${$this->key}); \$db->put('{$this->variabel}', \${$this->variabel});";
    }else{
      $str .= "){\$db->put('{$this->key}', \${$this->key});";
    }
    return $str;
  }
}