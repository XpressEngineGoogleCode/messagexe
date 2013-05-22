<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
 * @class  reset_passwordView
 * @author NURIGO(contact@nurigo.net)
 * @brief  reset_passwordView
 */
class reset_passwordView extends reset_password 
{
	function init() 
	{
		$config->skin = 'default';
		$this->setTemplatePath($this->module_path."skins/{$config->skin}");
	}

	function dispReset_passwordIndex()
	{
		$this->setTemplateFile('index');
	}
	function dispReset_passwordFindId()
	{
		$oAuthenticationModel = &getModel('authentication');
		$config = $oAuthenticationModel->getModuleConfig();
		Context::set('config', $config);

		$this->setLayoutFile('default_layout');

		if($_SESSION['authentication_pass'] == 'Y')
		{
			$authinfo = $oAuthenticationModel->getAuthenticationInfo($_SESSION['authentication_srl']);
			Context::set('authinfo', $authinfo);
			$member_list = $oAuthenticationModel->getAuthenticationMemberListByClue($authinfo->clue);
			Context::set('member_list', $member_list);
			$this->setTemplateFile('idfound');
			unset($_SESSION['authentication_pass']);
			return;
		}
		$this->setTemplateFile('findid');
	}
	function dispReset_passwordFindPassword()
	{
		$oAuthenticationModel = &getModel('authentication');
		$config = $oAuthenticationModel->getModuleConfig();
		Context::set('config', $config);

		if($_SESSION['authentication_pass'] == 'Y')
		{
			Context::set('tmp_password', $_SESSION['tmp_password']);
			$this->setTemplateFile('resetpassword');
			unset($_SESSION['authentication_pass']);
		}
		else
		{
			$this->setTemplateFile('findpassword');
		}
		$this->setLayoutFile('default_layout');
	}

}
/* End of file reset_password.view.php */
/* Location: ./modules/reset_password/reset_password.view.php */
