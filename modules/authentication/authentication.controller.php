<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
 * @class  authenticationController
 * @author wiley@nurigo.net
 * @brief  authenticationController
 */
class authenticationController extends authentication 
{
	function procAuthenticationSendAuthCode()
	{

		$this->setRedirectUrl(Context::get('return_url'));
	}

	function sendAuthCode($args)
	{
		$oMobilemessageModel = &getModel('mobilemessage');
		$config = &$oMobilemessageModel->getModuleConfig();
		$phonenumber = $args->phonenum;
		$callback = $args->callback;
		$country = $args->country;
		$content = $args->content;

		// default country code
		$default_country = $config->default_country;

		$key = rand(1, 99999);
		$keystr = sprintf("%05d", $key);
		if (!$content) $content = "[핸드폰인증]\n%validation_code% ☜ 인증번호를 정확히 입력해 주세요.";
		$content = preg_replace("/%authcode%/", $keystr, $content);

		// delete
		unset($args);
		$args = new StdClass();
		$args->callno = $phonenumber;
		$args->country = $country;
		executeQuery('mobilemessage.deleteValCode', $args);

		// insert
		unset($args);
		$args = new StdClass();
		$args->callno = $phonenumber;
		$args->country = $country;
		$args->valcode = $keystr;
		executeQuery('mobilemessage.insertValCode', $args);

		unset($args);
		$args = new StdClass();
		$args->country = $country;
		$args->recipient = $phonenumber;
		if ($callback)
			$args->callback = $callback;
		else
			$args->callback = $config->s_callback;
		$args->message = $content;
		$args->encode_utf16 = $encode_utf16;

		$controller = &getController('mobilemessage');
		$output = $controller->sendMessage($args);
		if (!$output->toBool())
			return $output;

		return new Object();
	}

	function validateAuthCode()
	{
	}

	/**
	 * @brief 모듈핸들러 실행 후 트리거 (애드온의 after_module_proc에 대응)
	 **/
	function triggerModuleHandlerProc(&$oModule)
	{
		// 회원가입시
		if(Context::get('act') == "dispMemberSignUpForm")
		{
			$config->skin = 'default';
			$addon_tpl_path = sprintf('./modules/authentication/skins/%s/', $config->skin);
			$addon_tpl_file = 'index.html';
					
			$oModule->setTemplatePath($addon_tpl_path);
			$oModule->setTemplateFile($addon_tpl_file);
		}
	
		return new Object();
	}
}
?>
