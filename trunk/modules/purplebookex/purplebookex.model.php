<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  purplebookModel
 * @author diver(diver@coolsms.co.kr)
 * @brief  purplebookModel
 */
require_once(_XE_PATH_.'modules/purplebook/purplebook.model.php');

class purplebookexModel extends purplebookModel
{

	function init()
	{
		$class_path = ModuleHandler::getModulePath('purplebook');
		$this->setModulePath($class_path);
		parent::init();
	}

	function purplebookexModel()
	{
		$this->init();
	}

	/**
	 * 모듈 환경설정값 가져오기
	 */
	function getModuleConfig($args=false)
	{
		$config = parent::getModuleConfig();
		if($args && $args->basecamp)
		{
			$logged_info = Context::get('logged_info');
			if($logged_info)
			{
				$config->cs_userid = $logged_info->user_id;
				$config->cs_passwd = $logged_info->password;
				$config->crypt = '';
			} else 
			{
				$config->cs_userid = '';
				$config->cs_passwd = '';
				$config->crypt = 'MD5';
			}
		}
			return $config;
	}

	function getPurplebookCashInfo($args=false)
	{
		$config = $this->getModuleConfig($args);
		$oTextmessageModel = &getModel('textmessage');
		$args->basecamp = true;
		$sms = &$oTextmessageModel->getCoolSMS($args);
		// connect
		if (!$sms->connect()) {
			// cannot connect
			return new Object(-1, 'cannot connect to server.');
		}
		// get cash info
		$result = $sms->remain();
		// disconnect
		$sms->disconnect();

		$this->add('cash', $result["CASH"]);
		$this->add('point', $result["POINT"]);
		$this->add('mdrop', $result["DROP"]);
		$this->add('sms_price', $result["SMS-PRICE"]);
		$this->add('lms_price', $result["LMS-PRICE"]);
		$this->add('mms_price', $result["MMS-PRICE"]);
	}
}
/* End of file purplebook.model.php */
/* Location: ./modules/purplebook/purplebook.model.php */
