<?php
/* vi:set ts=4 sw=4 expandtab enc=utf8: */

class Utility 
{
	function date_format($date)
	{
		return substr($date, 0, 4) . "-" . substr($date, 4, 2) . "-" . substr($date, 6, 2);
	}

	function time_format($time)
	{
		return substr($time, 0, 2) . ":" . substr($time, 2, 2);
	}

	function cutout($msg, $limit) 
	{
		$msg = substr($msg, 0, $limit);
    		if (strlen($msg) < $limit)
			$limit = strlen($msg);

		$countdown = 0;
		for ($i = $limit - 1; $i >= 0; $i--) 
		{	 
			if (ord(substr($msg,$i,1)) < 128) break;
			$countdown++;
		}

		$msg = substr($msg, 0, $limit - ($countdown % 2));

		return $msg;
	}

	function abbreviate($msg, $limit)
	{
		if ($limit >= strlen($msg))
			return $msg;
		else
			return Utility::cutout($msg, $limit) . "..";
	}

	function riddash($str)
	{
		return str_replace("-", "", $str);
	}

	function key($postfix="")
	{
		$usec = explode(" ", microtime());
		return date("YmdHis") . $usec[0] . $postfix;
	}


	function dashed_telnum($telnum)
	{
		$DDD = array("02", "031", "033", "032", "042", "043", "041", "053", "054", "055", "052", "051", "063", "061", "062", "064", "011", "012", "013", "014", "015", "016", "017", "018", "019", "010", "070");
		$telnum = str_replace("-", "", $telnum);

		if ($telnum == "" || strlen($telnum) < 4)
		{
			return $telnum;
		}

		foreach ($DDD as $prefix)
		{
			if (substr($telnum, 0, strlen($prefix)) == $prefix)
			{
				if (strlen($telnum < 9))
				{
					return substr($telnum, 0, strlen($prefix)) . "-" . substr($telnum, strlen($prefix), strlen($telnum));
				} else {
					return substr($telnum, 0, strlen($prefix)) . "-" . substr($telnum, strlen($prefix), strlen($telnum) - strlen($prefix) - 4) . "-" . substr($telnum, strlen($telnum) - 4, strlen($telnum));
				}
			}

		}
		return $telnum;
	}

	function dashed_bizno($bizno)
	{
		$str = str_replace("-", "", $bizno);
		$initial = substr($str, 0, 3);
		$medial = substr($str, 3, 2);
		$final = substr($str, 5);

		return $initial . "-" . $medial . "-" . $final;
	}
	function keygen()
	{
		$randval = rand(100000, 999999);
		$usec = explode(" ", microtime());
		$str_usec = str_replace(".", "", strval($usec[0]));
		$str_usec = substr($str_usec, 0, 6);
		return date("YmdHis") . $str_usec . $randval;
	}

	function calc_remain($cash, $point, $credit)
	{
		$by_cash = intval($cash / 22);
		$by_point = intval($point / 22);
		$total = $by_cash + $by_point + $credit;

		return $total;
	}

}
?>
