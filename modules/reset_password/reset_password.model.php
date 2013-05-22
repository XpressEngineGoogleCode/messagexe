<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
 * @class  reset_passwordModel
 * @author NURIGO(contact@nurigo.net)
 * @brief  reset_passwordModel
 */
class reset_passwordModel extends reset_password 
{
	/**
	 * @brief constructor
	 */
	function init() 
	{
	}

	function getModuleConfig() {
		if (!$GLOBALS['__reset_password_config__'])
		{
			$oModuleModel = &getModel('module');
			$config = $oModuleModel->getModuleConfig('reset_password');
			if(!$config->skin) $config->skin = 'default';
			if(!$config->digit_number) $config->digit_number = 5;
			if(!$config->country_code) $config->country_code = '82';
			if(!$config->resend_interval) $config->resend_interval = 20;
			$GLOBALS['__reset_password_config__'] = $config;
		}
		return $GLOBALS['__reset_password_config__'];
	}

	function getListConfig($module_srl) 
	{
		$oModuleModel = &getModel('module');
		$oDocumentModel = &getModel('document');

		// 저장된 목록 설정값을 구하고 없으면 빈값을 줌.
		$list_config = $oModuleModel->getModulePartConfig('reset_password', $module_srl);
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
}
/* End of file reset_password.model.php */
/* Location: ./modules/reset_password/reset_password.model.php */
