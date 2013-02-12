<?php
	/**
	 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
	 * @class  notificationAdminController
	 * @author diver(diver@coolsms.co.kr)
	 * @brief  notificationAdminController
	 */
	class notificationAdminController extends notification {
		function init() {
		}

		/**
		 * @brief 모듈 환경설정값 쓰기
		 **/
		function procNotificationAdminConfig() {
			$args = Context::gets('cellphone_fieldname', 'use_authdata');

			// save module configuration.
			$oModuleControll = getController('module');
			$output = $oModuleControll->insertModuleConfig('notification', $args);
			if (!$output->toBool()) return $output;

			$this->setMessage('success_updated');

			if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
				$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispNotificationAdminConfig');
				$this->setRedirectUrl($returnUrl);
				return;
			}
		}

		/**
		 * @brief notification append
		 **/
		function procNotificationAdminInsert() {
			$params = Context::gets('content','mail_content','module_srls','msgtype','sending_method','cellphone_fieldname','use_authdata');
			$extra_vars = new StdClass();
			$extra_vars->admin_phones = Context::get('admin_phones');
			$extra_vars->admin_emails = Context::get('admin_emails');
			$extra_vars->cellphone_fieldname = Context::get('cellphone_fieldname');
			$extra_vars->use_authdata = Context::get('use_authdata');
			$extra_vars->reverse_notify = Context::get('reverse_notify');
			$params->notification_srl = Context::get('noti_srl');

			if ($params->notification_srl) {
				// delete existences
				$args->notification_srl = $params->notification_srl;
				$output = executeQuery('notification.deleteNotiCom', $args);
				if (!$output->toBool()) return $output;
				$output = executeQuery('notification.deleteNotificationModuleSrl', $args);
				if (!$output->toBool()) return $output;
			} else {
				// new sequence
				$params->notification_srl = getNextSequence();
			}

			// insert module srls
			$module_srls = explode(',', $params->module_srls);
			foreach ($module_srls as $srl) {
				unset($args);
				$args->notification_srl = $params->notification_srl;
				$args->module_srl = $srl;
				$output = executeQuery('notification.insertNotificationModuleSrl', $args);
				if (!$output->toBool()) return $output;
			}

			$params->extra_vars = serialize($extra_vars);

			// insert notification
			$output = executeQuery('notification.insertNotiCom', $params);
			if (!$output->toBool()) return $output;

			$redirectUrl = getNotEncodedUrl('', 'module', 'admin', 'act', 'dispNotificationAdminModify','notification_srl',$params->notification_srl);
			$this->setRedirectUrl($redirectUrl);
		}

		function procNotificationAdminDelete() {
			$notification_srl = Context::get('notification_srl');
			if (!$notification_srl) return new Object(-1, 'msg_invalid_request');

			if ($notification_srl) {
				// delete existences
				$args->notification_srl = $notification_srl;
				$query_id = "notification.deleteNotiCom";
				executeQuery($query_id, $args);
				$query_id = "notification.deleteNotificationModuleSrl";
				executeQuery($query_id, $args);
			}
			$redirectUrl = getNotEncodedUrl('', 'module', 'admin', 'act', 'dispNotificationAdminList');
			$this->setRedirectUrl($redirectUrl);
		}
	}
?>
