<?php
	/**
	 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
	 * @class  notificationModel
	 * @author diver(diver@coolsms.co.kr)
	 * @brief  notificationModel
	 */
	class notificationModel extends notification {

		function init() {
		}

		/**
		 * 모듈 환경설정값 가져오기
		 */
		function getModuleConfig() {
			if (!$GLOBALS['__notification_config__']) {
				$oModuleModel = &getModel('module');
				$config = $oModuleModel->getModuleConfig('notification');
				$GLOBALS['__notification_config__'] = $config;
			}
			return $GLOBALS['__notification_config__'];
		}

		function isConfigFieldSetted($field_name) {
			$config = $this->getModuleConfig();
			if (isset($config->{$field_name}) && $config->{$field_name}) return true;
			return false;
		}

		/**
		 * 환경값 읽어오기
		 */
		function getConfig() {
			$oModuleModel = &getModel('module');
			$config = $oModuleModel->getModuleConfig('notification');

			// country code
			if (!$config->default_country) $config->default_country = '82';
			if ($config->default_country == '82') $config->limit_bytes = 80;
			else $config->limit_bytes = 160;

			// callback
			$callback = explode("|@|", $config->callback); // source
			$config->a_callback = $callback;        // array
			$config->s_callback = join($callback);  // string

			// admin_phone
			if (!is_array($config->admin_phones))
				$config->admin_phones = explode("|@|", $config->admin_phones);

			return $config;
		}

		/**
		 * $obj : member info object.
		 */
		function getConfigValue(&$obj, $key, $type=null) {
			$return_value = null;
			$config = $this->getModuleConfig();

			$fieldname = $config->{$key};
			if (!$fieldname) return null;

			// 기본필드에서 확인
			if ($obj->{$fieldname}) {
				$return_value = $obj->{$fieldname};
			}

			// 확장필드에서 확인
			if ($obj->extra_vars) {
				$extra_vars = unserialize($obj->extra_vars);
				if ($extra_vars->{$fieldname}) {
					$return_value = $extra_vars->{$fieldname};
				}
			}
			if ($type=='tel' && is_array($return_value)) {
				$return_value = implode($return_value);
			}

			return $return_value;
		}

		function getNotificationList() {
			$query_id = 'notification.getNotificationList';

			$args->page = Context::get('page');
			$args->list_count = 40;
			$args->page_count = 10;

			return executeQuery($query_id, $args);
		}

		function getMessageInfo($args) {
			$query_id = 'notification.getNotification';
			return executeQuery($query_id, $args);
		}

		function getNotificationMessageInfo() {
			$logged_info = Context::get('logged_info');
			if (!$logged_info) return new Object(-1, 'msg_login_required');

			$args->msgid = Context::get('msgid');
			$output = $this->getMessageInfo($args);
			$output->data->content = str_replace("\r", "", $output->data->content);
			$output->data->content = str_replace("\n", "<br>", $output->data->content);

			$this->add('data', $output->data);
		}

		function getNotificationListByMessageId() {
			$message_ids_arr = explode(',', Context::get('message_ids'));
			$args->message_ids = "'" . implode("','", $message_ids_arr) . "'";
			$output = executeQueryArray('notification.getStatusListByMessageId', $args);
			$this->add('data', $output->data);
		}


		 /**
		 * @brief MemberMessageList 가져오기
		 **/
		function getNotificationMemberMessageList() {
			$logged_info = Context::get('logged_info');
			if (!$logged_info) return new Object(-1, 'msg_login_required');

			$args->gid = Context::get('gid');
			$output = $this->getMessagesInGroup($args);
			foreach ($output->data as $no => $row) {
				$output->data[$no]->content = str_replace("\r", "", $output->data[$no]->content);
				$output->data[$no]->content = str_replace("\n", "", $output->data[$no]->content);
			}

			$this->add('total_count', $output->total_count);
			$this->add('total_page', $output->total_page);
			$this->add('page', $output->page);
			$this->add('data', $output->data);
			$this->add('gid', Context::get('gid'));
			$config = $this->getModuleConfig();
			$this->add('base_url', $config->callback_url);
		}

		function getNotiComInfo($module_srl) {
			if (!$module_srl) return false;
			$args->module_srl = $module_srl;
			$output = executeQuery("notification.getNotiComInfoByModuleSrl", $args);
			if (!$output->toBool() || !$output->data) return false;
			$noticom_infos = $output->data;
			if (!is_array($noticom_infos)) $noticom_infos = array($output->data);

			foreach($noticom_infos as $key=>$val){
				$extra_vars = unserialize($val->extra_vars);
				if ($extra_vars) {
					foreach ($extra_vars as $key2 => $val2) {
						$noticom_infos[$key]->{$key2} = $val2;
					}
				}
			}
			return $noticom_infos;
		}
	}
?>
