<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
 * @class  authenticationAdminView
 * @author wiley(wiley@nurigo.net)
 * @brief  authenticationAdminView
 */ 
class authenticationAdminView extends authentication 
{
	var $group_list;

	function init() 
	{
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
	function dispAuthenticationAdminConfig() {
		$oAuthenticationModel = &getModel('authentication');
		$oMemberModel = &getModel('member');

		$config = $oAuthenticationModel->getModuleConfig();

		// load member list
		$query_id = "mobilemessage.getNotificationMembers";
		$id_list = explode(',', $config->id_list);
		$id_list = "'" . join("','", $id_list) . "'";
		if ($id_list) {
			$args->user_ids = $id_list;
			$output = executeQueryArray($query_id, $args);
			Context::set('member_list', $output->data);
		} else {
			Context::set('member_list', array());
		}

		// callback_number_direct
		$config->callback_number_direct = explode('|@|', $config->callback_number_direct);

		Context::set('mobilemessage_config', $config);

		$group_list = $oMemberModel->getGroups(0);
		Context::set('group_list', $group_list);

		$group_srl_list = explode(',', $config->group_srl_list);
		Context::set('group_srl_list', $group_srl_list);

		$change_group_srl_list = explode(',', $config->change_group_srl_list);
		Context::set('change_group_srl_list', $change_group_srl_list);

		// 템플릿 파일 지정
		$this->setTemplateFile('config');
	}

	/**
	 * @brief authentication configuration list.
	 **/
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

	
}
/* End of file authentication.admin.view.php */
/* Location: ./modules/authentication/authentication.admin.view.php */
