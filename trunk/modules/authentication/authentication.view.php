<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
 * @class  authenticationView
 * @author wiley(wiley@nurigo.net)
 * @brief  authenticationView
 */
class authenticationView extends authentication 
{
	function init() 
	{
		// 템플릿 설정
		$this->setTemplatePath($this->module_path.'tpl');
	}

	function dispAuthenticationCompare()
	{
		$vars = Context::get('phone');
		Context::set('phone', Context::get('phone'));
		Context::set('authentication_srl', Context::get('authentication_srl'));
		$config->skin = 'default';
		$addon_tpl_path = sprintf('./modules/authentication/skins/%s/', $config->skin);
		$addon_tpl_file = 'index.html';
				
		$this->setTemplatePath($addon_tpl_path);
		$this->setTemplateFile($addon_tpl_file);
	}

	function dispAuthenticationCompare3()
	{
		$config->skin = 'default';
		$addon_tpl_path = sprintf('./modules/authentication/skins/%s/', $config->skin);
		$addon_tpl_file = 'form_complete.html';
				
		$this->setTemplatePath($addon_tpl_path);
		$this->setTemplateFile($addon_tpl_file);
	}
}
/* End of file authentication.view.php */
/* Location: ./modules/authentication/authentication.view.php */
