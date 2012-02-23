<?php
	/**
	 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
	 * @class  notificationView
	 * @author diver(diver@coolsms.co.kr)
	 * @brief  notificationView
	 */
	class notificationView extends notification {
		var $use_point;
		var $sms_point;
		var $lms_point;
		var $alert_message="";

		function init() {
			// 템플릿 설정
			$this->setTemplatePath($this->module_path.'tpl');

			$oModel = &getModel('notification');
			$this->config = $oModel->getModuleConfig();
			Context::set('base_url', $this->config->callback_url);
		}
	}
?>
