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

	function getListConfig($module_srl) 
	{
		$oModuleModel = &getModel('module');
		$oDocumentModel = &getModel('document');

		debugPrint('kor_33');
		debugPrint($module_srl);
		// 저장된 목록 설정값을 구하고 없으면 빈값을 줌.
		$list_config = $oModuleModel->getModulePartConfig('authentication', $module_srl);
		debugPrint($list_config);
		if(!$list_config || !count($list_config)) $list_config = array('');

		// 사용자 선언 확장변수 구해와서 배열 변환후 return
		$inserted_extra_vars = $oDocumentModel->getExtraKeys($module_srl);

		foreach($list_config as $key) {
			if(preg_match('/^([0-9]+)$/',$key)) $output['extra_vars'.$key] = $inserted_extra_vars[$key];
			else $output[$key] = new ExtraItem($module_srl, -1, Context::getLang($key), $key, 'N', 'N', 'N', null);
		}
		return $output;
	}

	function getDefaultListConfig($module_srl) 
	{
		// 체크박스, 이미지, 상품명, 수량, 금액, 주문 추가
		$virtual_vars = array('dispMemberSignUpForm', 'dispMemberModifyInfo');
		foreach($virtual_vars as $key) {
			$extra_vars[$key] = new ExtraItem($module_srl, -1, Context::getLang($key), $key, 'N', 'N', 'N', null);
		}

		// 사용자 선언 확장변수 정리
		$oDocumentModel = &getModel('document');
		$inserted_extra_vars = $oDocumentModel->getExtraKeys($module_srl);

		if(count($inserted_extra_vars)) foreach($inserted_extra_vars as $obj) $extra_vars['extra_vars'.$obj->idx] = $obj;

		return $extra_vars;

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
/* End of file authentication.model.php */
/* Location: ./modules/authentication/authentication.model.php */
