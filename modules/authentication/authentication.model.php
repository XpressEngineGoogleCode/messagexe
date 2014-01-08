<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
 * @class  authenticationModel
 * @author NURIGO(contact@nurigo.net)
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
		if (!$GLOBALS['__authentication_config__'])
		{
			$oModuleModel = &getModel('module');
			$config = $oModuleModel->getModuleConfig('authentication');
			if(!$config->skin) $config->skin = 'default';
			if(!$config->digit_number) $config->digit_number = 5;
			if(!$config->country_code) $config->country_code = '82';
			if(!$config->resend_interval) $config->resend_interval = 20;
			if(!$config->day_try_limit) $config->day_try_limit = 10;
			if(!$config->message_content) $config->message_content = '[핸드폰인증] %authcode% ☜  인증번호를 정확히 입력해 주세요';
			if(!$config->number_overlap) $config->number_overlap = 'Y';
			$GLOBALS['__authentication_config__'] = $config;
		}
		return $GLOBALS['__authentication_config__'];
	}

	function getAuthenticationInfo($authentication_srl)
	{
		$args->authentication_srl = $authentication_srl;
		$output = executeQuery('authentication.getAuthentication', $args);
		if(!$output->toBool()) return;
		return $output->data;
	}

	function getAuthenticationMember($member_srl)
	{
		$args->member_srl = $member_srl;
		$output = executeQuery('authentication.getAuthenticationMember', $args);
		if(!$output->toBool()) return;
		return $output->data;
	}

	function getAuthenticationMemberListByClue($clue)
	{
		$args->clue = $clue;
		$output = executeQueryArray('authentication.getAuthenticationMemberListByClue', $args);
		debugPrint($output);
		if(!$output->toBool()) return;
		return $output->data;
	}

	function _getAgreement()
	{
		$agreement_file = _XE_PATH_.'files/authentication/agreement_' . Context::get('lang_type') . '.txt';
		if(is_readable($agreement_file))
		{
			return FileHandler::readFile($agreement_file);
		}

		$db_info = Context::getDBInfo();
		$agreement_file = _XE_PATH_.'files/authentication/agreement_' . $db_info->lang_type . '.txt';
		if(is_readable($agreement_file))
		{
			return FileHandler::readFile($agreement_file);
		}

		$lang_selected = Context::loadLangSelected();
		foreach($lang_selected as $key => $val)
		{
			$agreement_file = _XE_PATH_.'files/authentication/agreement_' . $key . '.txt';
			if(is_readable($agreement_file))
			{
				return FileHandler::readFile($agreement_file);
			}
		}

		return null;
	}

	function triggerMemberMenu($in_args)
	{
		$url = getUrl('','module','authentication','act','dispAuthenticationSendMessage','member_srl',Context::get('target_srl'));
		$oMemberController = &getController('member');
		$oMemberController->addMemberPopupMenu($url, 'test', '', 'popup');
	}
}
/* End of file authentication.model.php */
/* Location: ./modules/authentication/authentication.model.php */
