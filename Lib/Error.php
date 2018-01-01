<?php
namespace Lib;

use Lib\Exception\TempelateException;

class Error{
  public static function collect(){
    set_error_handler(function($errno, $errstr, $errfile, $errline){
      if(defined("IN_SETUP")){
        return;
      }
      if(!defined("ERROR")){
        define("ERROR", true);
      }
  
      $db = Database::get();
      if(!$db){
        return;
      }
  
      $db->query("INSERT INTO `error` (
          `errno`,
          `errstr`,
          `errfile`,
          `errline`,
          `errtime`
        ) VALUES (
          '".$db->escape($errno)."',
          '".$db->escape($errstr)."',
          '".$db->escape($errfile)."',
          '".$db->escape($errline)."',
          NOW()
        );");
  
      if(Access::userHasAccess("ERROR_SHOW"))
        Report::error($errstr);
      });
  }
  
  public static function systemError(string $message){
    self::printError("system", $message);
  }
  
  public static function tempelateError(TempelateException $e){
    self::printError("tempelate", htmlentities($e->getMessage())."<br>In file: ".htmlentities($e->getFile())."({$e->getLine()})");
  }
  
  private static function printError(string $type, string $message){
    echo "<!DOCTYPE html>
    <html>
      <head>
        <title>Sorry but a {$type} error happen</title>
        <style>
          body{
            background-color: black;
          }
          #container{
            border: 1px solid #E4DB57;
            background-color:#DD4111;
          }
          
          #container legend{
            color: #E4DB57;
            font-weight: bolder;
          }
        </style>
      </head>
      <body>
        <fieldset id='container'>
          <legend>The error message</legend>
          {$message}
        </fieldset>
      </body>
    </html>";
    exit;
  }
}