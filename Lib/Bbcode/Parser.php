<?php
namespace Lib\Bbcode;

class Parser{
  private $dom;
  private $source = "";
  
  public function __construct(string $code){
    $this->dom = Lexer::toDom($code);
  }
  
  public function getHtml(){
    return $this->dom->toHtml();
  }
  
  public static function getJavascript(){
   ?>
   <script>
     function spoiler_click(obj){
       var context = obj.parentNode.getElementsByClassName("spoiler_context")[0];
       context.style.display = CowDom.isVisible(context) ? "none" : "block";
     }
   </script>
   <?php
  }
}