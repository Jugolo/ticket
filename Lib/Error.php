<?php
namespace Lib;

use Lib\Exception\TempelateException;

class Error{
  public static function tempelateError(TempelateException $e){
    echo "<!DOCTYPE html>
    <html>
      <head>
        <title>Sorry but a tempelate error happen</title>
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
          {$e->getMessage()}
        </fieldset>
      </body>
    </html>";
  }
}