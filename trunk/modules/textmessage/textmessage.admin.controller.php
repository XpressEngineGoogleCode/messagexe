<?php
	/**
	 * @class  textmessageAdminController
	 * @author wiley (wiley@xnurigo.net)
	 * @brief  textmessage controller class of textmessage module
	 **/
	class textmessageAdminController extends textmessage {
		/**
		 * @brief initialization
		 * @return none
		 **/
		function init() {
		}

		function procTextmessageAdminInsertConfig() {
			$oTextmessageModel = &getModel('textmessage');

			$args = Context::gets('service_id', 'password', 'callback_url', 'encode_utf16');

			// send callback-url to server
			if ($args->callback_url) {
				$callback_url = $args->callback_url;
				// choose '?' or '&' whether the callback_url has '?' notation.
				if (strpos($callback_url, '?') === false) $callback_url = $callback_url . '?';
				else $callback_url = $callback_url . '&';
				// add query
				$callback_url = $callback_url . "module=textmessage&act=procTextmessageUpdateResult";

				$sms = $oTextmessageModel->getCoolSMS();
				if ($sms->connect()) {
					$sms->setcallbackurl($callback_url);        
					$sms->disconnect();
				}
			}

			// save module configuration.
			$oModuleControll = getController('module');
			$output = $oModuleControll->insertModuleConfig('textmessage', $args);

			$this->setMessage('success_saved');

			$redirectUrl = getNotEncodedUrl('', 'module', 'admin', 'act', 'dispTextmessageAdminConfig');
			$this->setRedirectUrl($redirectUrl);
		}
	}
?>
