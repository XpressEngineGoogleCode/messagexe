<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
 * @class  authenticationAdminView
 * @author NURIGO(contact@nurigo.net)
 * @brief  authenticationAdminView
 */ 
class authenticationAdminView extends authentication 
{
	function init() 
	{
		// 템플릿 설정
		$this->setTemplatePath($this->module_path.'tpl');
	}

	/**
	 * config
	 */
	function dispAuthenticationAdminConfig() 
	{
		$oAuthenticationModel = &getModel('authentication');
		$oMemberModel = &getModel('member');
		$oModuleController = &getController('module');
		$oModuleModel = &getModel('module');

		$module_srl = Context::get('module_srl');

		$config = $oAuthenticationModel->getModuleConfig();
		Context::set('config', $config);

		// Get a layout list
		$oLayoutModel = &getModel('layout');
		$layout_list = $oLayoutModel->getLayoutList();
		Context::set('layout_list', $layout_list);
		$mlayout_list = $oLayoutModel->getLayoutList(0, 'M');
		Context::set('mlayout_list', $mlayout_list);

		// get skin list
		$skin_list = $oModuleModel->getSkins($this->module_path);
		Context::set('skin_list',$skin_list);
		$mskin_list = $oModuleModel->getSkins($this->module_path, "m.skins");
		Context::set('mskin_list', $mskin_list);

		
		require_once($this->module_path.'authentication.actions.php');
		//$action_list = array('dispMemberSignUpForm', 'dispMemberModifyInfo', 'dispMemberModifyPassword', 'dispMemberLeave');
		foreach($__AUTHENTICATION_ACTIONS__ as $key=>$val)
		{
			Context::setLang($key,$val);
		}
		$action_list = array_keys($__AUTHENTICATION_ACTIONS__);
		Context::set('action_list', $action_list);

		// set template file
		$this->setTemplateFile('config');
	}

	function dispAuthenticationAdminAuthcodeList() 
	{
		$args->page = Context::get('page');
		

		$search_key = Context::get('search_key');
		if($search_key == 'Y')
		{
			$authcode_pass = Context::get('n_authcode_pass');
			$phone_number = Context::get('n_phone_number');

			$args->passed = trim($authcode_pass);
			$args->clue = $phone_number;

			Context::set('n_authcode_pass', $authcode_pass);
			Context::set('n_phone_number', $phone_number);
		}

		$output = executeQuery('authentication.getAuthenticationAll',$args);
		if(!$output->toBool()) return $output;
		Context::set('authcode_list', $output->data);
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('list', $output->data);
		Context::set('page_navigation', $output->page_navigation);
		$this->setTemplateFile('authcode_list');
	}

	function dispAuthenticationAdminMemberList() 
	{
		$args->page = Context::get('page');
		$search_key = Context::get('search_key');
		if($search_key)
		{
			switch($search_key)
			{
				case 'phone_number':
					$args->clue = Context::get('search_value');
					break;
				case 'user_id':
					$args->user_id = Context::get('search_value');
					break;
				case 'nick_name':
					$args->nick_name = Context::get('search_value');
					break;
			}
		}

		$output = executeQuery('authentication.getAuthenticationMemberList',$args);
		if(!$output->toBool()) return $output;
		Context::set('list', $output->data);
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('list', $output->data);
		Context::set('page_navigation', $output->page_navigation);
		$this->setTemplateFile('memberlist');
	}

	function dispAuthenticationAdminXLSDownload() 
	{
		$args->list_count = 999999;
		$output = executeQuery('authentication.getAuthenticationMemberList',$args);
		if(!$output->toBool()) return $output;
		Context::set('list', $output->data);
		$this->setLayoutPath('./common/tpl');
		$this->setLayoutFile('default_layout');
		$this->setTemplateFile('xlsdownload');
		header("Content-Type: Application/octet-stream;");
		header("Content-Disposition: attachment; filename=\"LIST-" . date('Ymd') . ".xls\"");
	}
}
/* End of file authentication.admin.view.php */
/* Location: ./modules/authentication/authentication.admin.view.php */
