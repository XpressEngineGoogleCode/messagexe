<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
 * @class  authenticationModel
 * @author wiley(wiley@nurigo.net)
 * @brief  authenticationModel
 */
class authenticationModel extends authentication 
{

	/**
	 * @brief constructor
	 */
	function init() 
	{
	}

	function getModuleConfig() {
		if (!$GLOBALS['__authentication_config__']) {
			$oModuleModel = &getModel('module');
			$config = $oModuleModel->getModuleConfig('authentication');
			$GLOBALS['__authentication_config__'] = $config;
		}
		return $GLOBALS['__authentication_config__'];
	}

	function getValCode($phonenum, $country='82')
	{
		$query_id = 'mobilemessage.getValCode';
		$args = new StdClass();
		$args->callno = $phonenum;
		$args->country = $country;
		$output = executeQuery($query_id, $args);
		if ($output->toBool() && $output->data) $output->valcode = $output->data->valcode;
		return $output;
	}

	/**
	 * @brief check whether the phone number is prohibited or not.
	 * @return true if prohibited, otherwise return false
	 **/
	function isProhibitedNumber($phonenum, $country='82') {
		$query_id = 'mobilemessage.getProhibit';
		$args = new StdClass();
		$args->phone_num = $phonenum;
		$args->country = $country;
		$output = executeQueryArray($query_id, $args);
		if (!$output->toBool() || !$output->data) return false;
		if (count($output->data) > 0) {
			$limit_date = $output->data[0]->limit_date;
			// check limit date.
			if (!$limit_date) return true;
			// compare the limit date to today.
			else if ($limit_date >= date("Ymd")) return true;
			// prohibition expired
			return false;
		}
		return false;
	}

	function getUserIDsByPhoneNumber($phone_num, $country_code='82') {
		$args = new StdClass();
		$args->phone_num = $phone_num;
		$args->country = $country_code;
		$query_id = 'mobilemessage.getMapping';
		$output = executeQueryArray($query_id, $args);
		if (!$output->toBool()) return false; // 오류

		$userid_array = array();
		if (!$output->data) return $userid_array; // No Data

		foreach ($output->data as $row) {
			$userid_array[] = $row->user_id;
		}

		return $userid_array;
	}
}
?>
