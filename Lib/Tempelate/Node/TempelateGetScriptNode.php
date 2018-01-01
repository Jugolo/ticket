<?php
namespace Lib\Tempelate\Node;

use Lib\Tempelate\TempelateNode;

class TempelateGetScriptNode implements TempelateNode{
  private $file;
  private $line;
  
  public function __construct(string $file, int $line){
    $this->file = $file;
    $this->line = $line;
  }
  
  public function toString() : string{
    return "\$context .= \$data->getScripts('{$this->file}', {$this->line});";
  }
}