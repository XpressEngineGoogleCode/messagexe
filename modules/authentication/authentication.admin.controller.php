<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
 * @class  authenticationAdminController
 * @author wiley(wiley@nurigo.net)
 * @brief  authenticationAdminController
 */
class authenticationAdminController extends authentication 
{
	/**
	 * @brief constructor
	 */
	function init() 
	{
	}

	function isValidFieldName($fieldname) {
		$bfound = false;

		// 확장 필드에세 찾아보기
		$oMemberModel = &getModel('member');
		$list = $oMemberModel->getJoinFormList();
		if ($list) {
			foreach ($list as $row) {
				if ($row->column_name == $fieldname) {
					$bfound = true;
				}
			}
		}

		// 기본 필드에세 찾아보기
		$logged_info = $oMemberModel->getLoggedInfo();
		if (isset($logged_info->{$fieldname}))
			$bfound = true;

		return $bfound;
	}

	/**
	 * @brief 모듈 환경설정값 쓰기
	 **/
	function procAuthenticationAdminConfig() {
		$bsucc = true;
		$messages = "= 설정오류 내역 =\n";

		$args = Context::getRequestVars();

		// 폰번호 필드 설정을 했지만 올바르지 않는지 체크
		if ($args->cellphone_fieldname) {
			if (!$this->isValidFieldName($args->cellphone_fieldname)) {
				$messages .= "[핸드폰번호 필드명] 필드명이 올바르지 않습니다. 정확히 입력해 주세요.";
				$bsucc = false;
			}
		}

		// 인증번호 필드 설정을 했지만 올바른지 체크
		if ($args->validationcode_fieldname) {
			if (!$this->isValidFieldName($args->validationcode_fieldname)) {
				$messages .= "[인증번호 필드명] 필드명이 올바르지 않습니다. 정확히 입력해 주세요.";
				$bsucc = false;
			}
		}

		// check whether countrycode_fieldname is valid
		if ($args->countrycode_fieldname) {
			if (!$this->isValidFieldName($args->countrycode_fieldname)) {
				$messages .= "[국가번호 필드명] 필드명이 올바르지 않습니다. 정확히 입력해 주세요.";
				$bsucc = false;
			}
		}

		// save module configuration.
		$oModuleController = getController('module');
		$output = $oModuleController->insertModuleConfig('authentication', $args);

		if ($bsucc)
			$this->setMessage('success_updated');
		else
			$this->setMessage($messages);

		$redirectUrl = getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAuthenticationAdminConfig');
		$this->setRedirectUrl($redirectUrl);
	}

	/**
	 * @brief saving config values.
	 **/
	function procAuthenticationAdminInsert() 
	{
		$params = Context::gets('admin_phones','admin_emails','content','mail_content','module_srls','msgtype','sending_method');
		$params->config_srl = Context::get('config_srl');

		if ($params->config_srl) 
		{
			// delete existences
			$args->config_srl = $params->config_srl;
			$output = executeQuery('authentication.deleteConfig', $args);
			if (!$output->toBool()) return $output;
			$output = executeQuery('authentication.deleteModule', $args);
			if (!$output->toBool()) return $output;
		}
		else
		{
			// new sequence
			$params->config_srl = getNextSequence();
		}

		// insert module srls
		$module_srls = explode(',', $params->module_srls);
		foreach ($module_srls as $srl) 
		{
			unset($args);
			$args->config_srl = $params->config_srl;
			$args->module_srl = $srl;
			$output = executeQuery('authentication.insertModuleSrl', $args);
			if (!$output->toBool()) return $output;
		}

		//$params->extra_vars = serialize($extra_vars);

		// insert authentication
		$output = executeQuery('authentication.insertConfig', $params);
		debugPrint('insertConfig : ' . serialize($output));
		if (!$output->toBool()) 
		{
			return $output;
		}

		$redirectUrl = getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAuthenticationAdminModify','config_srl',$params->config_srl);
		$this->setRedirectUrl($redirectUrl);
	}

	function procAuthenticationAdminDelete() 
	{
		$config_srl = Context::get('config_srl');
		if (!$config_srl) return new Object(-1, 'msg_invalid_request');

		if ($config_srl) 
		{
			// delete existences
			$args->config_srl = $config_srl;
			$query_id = "authentication.deleteConfig";
			executeQuery($query_id, $args);
			$query_id = "authentication.deleteModule";
			executeQuery($query_id, $args);
		}
		$redirectUrl = getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAuthenticationAdminList');
		$this->setRedirectUrl($redirectUrl);
	}
}
?>
