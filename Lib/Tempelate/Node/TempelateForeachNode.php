<?php
namespace Lib\Tempelate\Node;

use Lib\Tempelate\TempelateNode;

class TempelateForeachNode implements TempelateNode{
  private $exp;
  private $key;
  private $value;
  private $block;
  
  public function __construct(TempelateNode $exp, string $key, string $value, string $block){
    $this->exp   = $exp;
    $this->key   = $key;
    $this->value = $value;
    $this->block = $block;
  }
  
  public function toString() : string{
    $str = "foreach(\$db->toArray({$this->exp->toString()}) as \$foreach_{$this->key}";
    if($this->value)
      $str .= " => \$foreach_{$this->value}){\$db->put(['{$this->key}' => \$foreach_{$this->key}, '{$this->value}' => \$foreach_{$this->value}]);";
    else
      $str .= "){\$db->put(['{$this->key}' => \$foreach_{$this->key}]);";
    $str .= $this->block."}";
    return $str;
  }
}