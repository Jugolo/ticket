<?php
namespace Lib\Tempelate;

class TempelateFileCreator{
  public static function convert(string $body, TempelateStackTrace $trace){
    return "return new class(){
      private \$created = ".time().";
      private \$files   = [".self::makeFileList($trace)."];
      
      public function isFresh() : bool{
        foreach(\$this->files as \$files){
          if(filemtime(\$files) > \$this->created)
            return false;
        }
        return true;
      }
      
      public function get(\\Lib\\Tempelate\\TempelateData \$data, \\Lib\\Tempelate\\TempelateDatabase \$db, \\Lib\\Tempelate \$tempelate, \\Lib\\User\\User \$user){
        \$context = '';
        {$body}
        return \$context;
      }
    };";
  }
  
  private static function makeFileList(TempelateStackTrace $stack){
    $item = $stack->toArray();
    return count($item) == 0 ? "" : "'".implode("', '", $item)."'";
  }
}
