<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  purplebookView
 * @author wiley(wiley@nurigo.net)
 * @brief  purplebookView
 */
require_once(_XE_PATH_.'modules/purplebook/purplebook.view.php');

class purplebookexView extends purplebookView
{
	function init()
	{
		global $cs_config;
		$cs_config->db_index = 1;
		$class_path = ModuleHandler::getModulePath('purplebook');
		$this->setModulePath($class_path);
		parent::init();
	}

	function purplebookexView()
	{
		$this->init();
	}
}
