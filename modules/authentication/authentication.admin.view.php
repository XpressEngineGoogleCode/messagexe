<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
 * @class  authenticationAdminView
 * @author NURIGO(contact@nurigo.net)
 * @brief  authenticationAdminView
 */ 
class authenticationAdminView extends authentication 
{
	var $group_list;

	function init() 
	{
		$args->module = 'authentication';
		$output = executeQuery('authentication.getModulesrl', $args);
		if(!$output->data)
		{
			$authenticationAdminController = &getAdminController('authentication');
			$output = $authenticationAdminController->procAuthenticationModuleInsert();
			$output = executeQuery('authentication.getModulesrl', $args);
		}


		Context::set('module_srl', $output->data->module_srl);
		
		$oMemberModel = &getModel('member');

		// group 목록 가져오기
		$this->group_list = $oMemberModel->getGroups();
		Context::set('group_list', $this->group_list);

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
		Context::set('mobilemessage_config', $config);

		$country_code = explode(',',$config->country_code);
		Context::set('country_code', $country_code);


		// get skin list
		$skin_list = $oModuleModel->getSkins($this->module_path);
		Context::set('skin_list',$skin_list);
		$mskin_list = $oModuleModel->getSkins($this->module_path, "m.skins");
		Context::set('mskin_list', $mskin_list);

		
		// 설정 항목 추출 (설정항목이 없을 경우 기본 값을 세팅)
		$args->module_srl = $module_srl;
		$args->module = 'authentication';
		$output = executeQuery('module.getModulePartConfig', $args);
		if(!$output->data->config)
		{
			$oModuleController->insertModulePartConfig('authentication',$module_srl,$non_config);
		}

		if($oAuthenticationModel->getListConfig($module_srl))
		{
			Context::set('list_config', $oAuthenticationModel->getListConfig($module_srl));
		}
		Context::set('extra_vars', $oAuthenticationModel->getDefaultListConfig($module_srl));

		$security = new Security();
		$security->encodeHTML('list_config');


		// set template file
		$this->setTemplateFile('config');
	}

	/**
	 * @brief authentication configuration list.
	 **/
	/*
	function dispAuthenticationAdminList() 
	{
		$config_list = array();
		$args->page = Context::get('page');
		$output = executeQueryArray('authentication.getConfigList', $args);
		if ($output->toBool() && $output->data) 
		{
			foreach ($output->data as $no => $val) 
			{
				$val->no = $no;
				$val->module_info = array();
				$config_list[$val->config_srl] = $val;
			}
		}
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('page_navigation', $output->page_navigation);


		// module infos
		if (count($config_list) > 0) 
		{
			$config_srls = array_keys($config_list);
			$config_srls = join(',', $config_srls);

			$query_id = "authentication.getModuleInfoByConfigSrl";
			$args->config_srls = $config_srls;
			$output = executeQueryArray($query_id, $args);
			if ($output->data) 
			{
				foreach ($output->data as $no => $val) 
				{
					$config_list[$val->config_srl]->module_info[] = $val;
				}
			}
		}
		Context::set('list', $config_list);


		$oAuthenticationModel = &getModel('authentication');
		$config = $oAuthenticationModel->getModuleConfig();
		Context::set('config',$config);

		$this->setTemplateFile('list');
	}
	 */

	/**
	 * @brief insert authentication configuration info.
	 **/
	function dispAuthenticationAdminInsert() 
	{
		$oEditorModel = &getModel('editor');
		$config = $oEditorModel->getEditorConfig(0);
		// set editor options.
		$option->skin = $config->editor_skin;
		$option->content_style = $config->content_style;
		$option->content_font = $config->content_font;
		$option->content_font_size = $config->content_font_size;
		$option->colorset = $config->sel_editor_colorset;
		$option->allow_fileupload = true;
		$option->enable_default_component = true;
		$option->enable_component = true;
		$option->disable_html = false;
		$option->height = 200;
		$option->enable_autosave = false;
		$option->primary_key_name = 'noti_srl';
		$option->content_key_name = 'mail_content';

		$editor = $oEditorModel->getEditor(0, $option);
		Context::set('editor', $editor);

		$config->content = Context::getLang('default_content');
		$config->mail_content = Context::getLang('default_mail_content');
		Context::set('config', $config);

		$this->setTemplateFile('insert');
	}

	/**
	 * @brief modify authentication configuration.
	 **/
	/*
	function dispAuthenticationAdminModify() 
	{
		$config_srl = Context::get('config_srl');
		// load authentication info
		$args->config_srl = $config_srl;
		$output = executeQuery("authentication.getConfig", $args);
		$config = $output->data;
		$extra_vars = unserialize($config->extra_vars);
		if ($extra_vars) 
		{
			foreach ($extra_vars as $key => $val) 
			{
				$config->{$key} = $val;
			}
		}

		// load module srls
		$args->config_srl = $config_srl;
		$output = executeQueryArray("authentication.getModuleSrls", $args);
		if (!$output->toBool()) return $output;
		$module_srls = array();
		if ($output->toBool() && $output->data) 
		{
			foreach ($output->data as $no => $val) 
			{
				$module_srls[] = $val->module_srl;
			}
		}
		$config->module_srls = join(',', $module_srls);
		Context::set('config', $config);

		// editor
		$oEditorModel = &getModel('editor');
		$config = $oEditorModel->getEditorConfig(0);
		// set options.
		$option->skin = $config->editor_skin;
		$option->content_style = $config->content_style;
		$option->content_font = $config->content_font;
		$option->content_font_size = $config->content_font_size;
		$option->colorset = $config->sel_editor_colorset;
		$option->allow_fileupload = true;
		$option->enable_default_component = true;
		$option->enable_component = true;
		$option->disable_html = false;
		$option->height = 200;
		$option->enable_autosave = false;
		$option->primary_key_name = 'config_srl';
		$option->content_key_name = 'mail_content';
		$editor = $oEditorModel->getEditor($config_srl, $option);
		Context::set('editor', $editor);

		$this->setTemplateFile('insert');
	}
	 */


	function dispAuthenticationAdminAuthcodeList() 
	{
		$args->page = Context::get('page');
		

		$search_key = Context::get('search_key');
		if($search_key == 'Y')
		{
			$authcode_pass = Context::get('n_authcode_pass');
			$phone_number = Context::get('n_phone_number');

			$args->authcode_pass = trim($authcode_pass);
			$args->clue = $phone_number;

			Context::set('n_authcode_pass', $authcode_pass);
			Context::set('n_phone_number', $phone_number);
		}

		$output = executeQuery('authentication.getAuthenticationAll',$args);
		Context::set('authcode_list', $output->data);
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('list', $output->data);
		Context::set('page_navigation', $output->page_navigation);
		$this->setTemplateFile('authcode_list');
	}

	
}
/* End of file authentication.admin.view.php */
/* Location: ./modules/authentication/authentication.admin.view.php */
