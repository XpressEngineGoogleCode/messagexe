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
}
/* End of file authentication.model.php */
/* Location: ./modules/authentication/authentication.model.php */
