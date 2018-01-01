<?php
namespace Lib;

use Lib\Language\Language;

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
      Report::error(Language::get("TICKET_YOUNG", $to));
      return self::TO_YOUNG;
    }
    return self::NO_ERROR;
  }
  
  public static function get_age(string $to, Tempelate $tempelate, Page $page){
    if(!empty($_POST["save"])){
      $count = Report::count("ERROR");
      if(empty($_POST["bd"]) || !is_numeric($_POST["bd"]))
        Report::error(Language::get("MISSING_B_D"));
      if(empty($_POST["bm"]) || !is_numeric($_POST["bm"]))
        Report::error(Language::get("MISSING_B_M"));
      if(empty($_POST["by"]) || !is_numeric($_POST["by"]))
        Report::error(Language::get("MISSING_B_Y"));
      if($count == Report::count("ERROR")){
        //wee start on month becuse wee know there need betwen 1 and 12
        $month = (int)$_POST["bm"];
        if($month < 1 || $month > 12){
          Report::error(Language::get("WRONG_YEAR"));
        }else{
          //month okay. now wee try to controle year
          $pd = date("Y");
          $year = (int)$_POST["by"];
          if($year < $pd-100 || $year > $pd){
            Report::error(Language::get("WRONG_YEAR"));
          }else{
            $day = (int)$_POST["bd"];
            if($day < 1 || cal_days_in_month(CAL_GREGORIAN, $month, $year) < $day){
              Report::error(Language::get("WRONG_DAY"));
            }else{
              Database::get()->query("UPDATE `user` SET 
                                        `birth_day`='{$day}',
                                        `birth_month`='{$month}',
                                        `birth_year`='{$year}'
                                      WHERE `id`='".user["id"]."';");
              Report::okay("Birth data is saved");
              header("location: #");
              exit;
            }
          }
        }
      }
    }
    
    $tempelate->render("get_age", $page);
  }
}