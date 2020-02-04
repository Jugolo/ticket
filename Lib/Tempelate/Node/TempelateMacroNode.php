<?php
namespace Lib\Tempelate\Node;

use Lib\Tempelate\TempelateNode;

class TempelateMacroNode implements TempelateNode{
  private $name;
  private $args;
  private $block;
  
  public function __construct(string $name, array $args, $block){
    $this->name = $name;
    $this->args = $args;
    $this->block = $block;
  }
  
  public function toString() : string{
    return "\$db->put(['{$this->name}' => function(...\$args) use(&\$context, \$db, \$data){
    if(count(\$args) < ".count($this->args).")
      throw new Exception('To few args was given to the marco \"{$this->name}\"');
    \$db = new Lib\\Tempelate\\TempelateDatabase(\$db);
    {$this->getArgs()}
  {$this->block}
}]);";
  }
  
  private function getArgs(){
    $return = [];
    for($i=0;$i<count($this->args);$i++){
      $return[] = "'{$this->args[$i]}' => \$args[{$i}]";
    }
    return "\$db->put([".implode(",\n", $return)."]);";
  }
}