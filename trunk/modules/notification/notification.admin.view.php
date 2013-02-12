<?php
	/**
	 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
	 * @class  notificationAdminView
	 * @author diver(diver@coolsms.co.kr)
	 * @brief  notificationAdminView
	 */ 
	class notificationAdminView extends notification {
		var $group_list;

		function init() {
			$oMemberModel = &getModel('member');

			// group 목록 가져오기
			$this->group_list = $oMemberModel->getGroups();
			Context::set('group_list', $this->group_list);

			// 템플릿 설정
			$this->setTemplatePath($this->module_path.'tpl');
		}

		/**
		 * basic settings.
		 **/
		function dispNotificationAdminConfig() {
			$oNotificationModel = &getModel('notification');
			$oMemberModel = &getModel('member');

			$config = $oNotificationModel->getModuleConfig();
			$member_config = $oMemberModel->getMemberConfig();
			
			Context::set('config', $config);
			Context::set('member_config', $member_config);
			$this->setTemplateFile('config');
		}

		/**
		 * @brief notification configuration list.
		 **/
		function dispNotificationAdminList() {
			$notification_list = array();
			$args->page = Context::get('page');
			$output = executeQueryArray('notification.getNotiComList', $args);
			if ($output->toBool() && $output->data) {
				foreach ($output->data as $no => $val) {
					$val->no = $no;
					$val->module_info = array();
					$notification_list[$val->notification_srl] = $val;
				}
			}
			Context::set('total_count', $output->total_count);
			Context::set('total_page', $output->total_page);
			Context::set('page', $output->page);
			Context::set('page_navigation', $output->page_navigation);


			// module infos
			if (count($notification_list) > 0) {
				$notification_srls = array_keys($notification_list);
				$notification_srls = join(',', $notification_srls);

				$query_id = "notification.getModuleInfoByNotificationSrl";
				$args->notification_srls = $notification_srls;
				$output = executeQueryArray($query_id, $args);
				if ($output->data) {
					foreach ($output->data as $no => $val) {
						$notification_list[$val->notification_srl]->module_info[] = $val;
					}
				}
			}
			Context::set('notification_list', $notification_list);


			$oNotificationModel = &getModel('notification');
			$config = $oNotificationModel->getModuleConfig();
			Context::set('config',$config);

			$this->setTemplateFile('list');
		}

		/**
		 * @brief insert notification configuration info.
		 **/
		function dispNotificationAdminInsert() {
			$oMemberModel = &getModel('member');
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

			$notification_info->content = Context::getLang('default_content');
			$notification_info->mail_content = Context::getLang('default_mail_content');
			Context::set('notification_info', $notification_info);

			$member_config = $oMemberModel->getMemberConfig();
			Context::set('member_config', $member_config);

			$this->setTemplateFile('insert');
		}

		/**
		 * @brief modify notification configuration.
		 **/
		function dispNotificationAdminModify() {
			$oMemberModel = &getModel('member');

			$notification_srl = Context::get('notification_srl');
			// load notification info
			$args->notification_srl = $notification_srl;
			$output = executeQuery("notification.getNotiComInfo", $args);
			$notification_info = $output->data;
			$extra_vars = unserialize($notification_info->extra_vars);
			if ($extra_vars) {
				foreach ($extra_vars as $key => $val) {
					$notification_info->{$key} = $val;
				}
			}

			// load module srls
			$args->notification_srl = $notification_srl;
			$output = executeQueryArray("notification.getNotificationModuleSrls", $args);
			$module_srls = array();
			if ($output->toBool() && $output->data) {
				foreach ($output->data as $no => $val) {
					$module_srls[] = $val->module_srl;
				}
			}
			$notification_info->module_srls = join(',', $module_srls);
			Context::set('notification_info', $notification_info);

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
			$option->primary_key_name = 'noti_srl';
			$option->content_key_name = 'mail_content';
			$editor = $oEditorModel->getEditor($notification_srl, $option);
			Context::set('editor', $editor);

			$member_config = $oMemberModel->getMemberConfig();
			Context::set('member_config', $member_config);

			$this->setTemplateFile('insert');
		}
	}
?>
