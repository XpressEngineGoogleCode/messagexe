<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  purplebookModel
 * @author diver(diver@coolsms.co.kr)
 * @brief  purplebookModel
 */
require_once(_XE_PATH_.'modules/textmessage/textmessage.model.php');

class textmessagexModel extends textmessageModel 
{

	function init()
	{
		$class_path = ModuleHandler::getModulePath('textmessage');
		$this->setModulePath($class_path);
		parent::init();
	}

	function textmessagexModel()
	{
		$this->init();
	}

	/**
	 * 모듈 환경설정값 가져오기
	 */

}
/* End of file purplebook.model.php */
/* Location: ./modules/purplebook/purplebook.model.php */
