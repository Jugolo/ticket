<?php
namespace Lib;

class Age{
  public static function calculate(int $day, int $month, int $year) : int{
    $year = date("Y") - $year;
    if((date("d") - $day) < 0 || (date("m") - $month) < 0)
      $year--;
    return $year;
  }
}