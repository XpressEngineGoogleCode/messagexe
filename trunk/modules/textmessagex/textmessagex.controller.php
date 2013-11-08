<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  purplebookControllerex
 * @author wiley@nurigo.net
 * @brief  purplebookControllerex
 */

require_once(_XE_PATH_. 'modules/textmessage/textmessage.controller.php');

class textmessagexController extends textmessageController
{
	function init() 
	{
		$class_path = ModuleHandler::getModulePath('textmessage');
		$this->setModulePath($class_path);
		parent::init();
	}

	function textmessagexController()
	{
		$this->init();
	}

	function insertTextmessage($args)
	{
		return new object();
		//아래는 원래 정상인 return 임
		//return executeQuery('textmessage.insertTextmessage', $args);
	}

	function insertTextmessageGroup($args)
	{
		return new object();
		//아래는 원래 정상인 return 임
		//return parent::insertTextmessageGroup($args);
	}
	
}
