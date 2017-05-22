<?php
namespace Lib;

use Lib\Okay;
use Lib\Error;

class Age{
  const TO_YOUNG = 1;
  const NOT_USER = 2;
  const GET_AGE = 3;
  const NO_ERROR = 4;
  
  public static function calculate(int $day, int $month, int $year) : int{
    $year = date("Y") - $year;
    if((date("d") - $day) < 0 || (date("m") - $month) < 0)
      $year--;
    return $year;
  }
  
  public static function controle(int $age, string $to) : int{
    if(!defined("user")){
      return self::NOT_USER;
    }
    
    if(!user["birth_day"] || !user["birth_month"] || !user["birth_year"]){
      return self::GET_AGE;
    }
    
    if($age > self::calculate(user["birth_day"], user["birth_month"], user["birth_year"])){
      Error::report("Sorry you are to young to create a ticket to ".$to);
      return self::TO_YOUNG;
    }
    return self::NO_ERROR;
  }
  
  public static function get_age(string $to){
    if(!empty($_POST["bd"]) && !empty($_POST["bm"]) && !empty($_POST["by"])){
      $db = Database::get();
      $db->query("UPDATE `user` SET 
                `birth_day`='".$db->escape(intval($_POST["bd"]))."',
                `birth_month`='".$db->escape(intval($_POST["bm"]))."',
                `birth_year`='".$db->escape(intval($_POST["by"]))."'
               WHERE `id`=".user["id"]);
      Okay::report("You birth day is now saved");
      header("location: #");
      exit;
    }
    
    echo "<form method='post' action='#'>";
    echo "<h3>Please type your bith day to create the ticket to {$to}</h3>";
    echo two_container("Birth day", "<input type='number' name='bd'>");
    echo two_container("Birth month", "<input type='number' name='bm'>");
    echo two_container("Birth year", "<input type='number' name='by'>");
    echo "<input type='submit' value='Set you birth day'>";
    echo "</form>"; 
  }
}