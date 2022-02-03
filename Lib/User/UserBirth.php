<?php
namespace Lib\User;

class UserBirth{
	private $day;
	private $month;
	private $year;
	
	public function __construct(int $day, int $month, int $year){
		$this->day   = $day;
		$this->month = $month;
		$this->year  = $year;
	}
	
	public function age() : int{
		$year = date("Y") - $this->year;
        if((date("d") - $this->day) < 0 || (date("m") - $this->month) < 0)
			$year--;
		return $year;
	}
}
