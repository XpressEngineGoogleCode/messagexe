<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  purplebookControllerex
 * @author wiley@nurigo.net
 * @brief  purplebookControllerex
 */

require_once(_XE_PATH_. 'modules/purplebook/purplebook.controller.php');

class purplebookexController extends purplebookController
{
	function init() 
	{
		$class_path = ModuleHandler::getModulePath('purplebook');
		$this->setModulePath($class_path);
		parent::init();
	}

	function purplebookexController()
	{
		$this->init();
	}

	function procPurplebookSendMsg()
	{
		$args->basecamp = true;
		$output = parent::procPurplebookSendMsg($args);
		if($output && !$output->toBool()) return $output;
	}

	function procPurplebookCancelMessages()
	{
		$target_msgids = Context::get('target_msgids');
		if(!$target_msgids)
			return new $Object(-1, 'msg_invalid_request');
		$msgids = explode(',', $target_msgids);

		$opts->basecamp = true;
		$output = $this->cancelMessage($msgids, $opts);
		if(!$output->toBool())
		{
			$this->setMessage('cancel_failed');
			return $output;
		}

		$this->setMessage('success_cancel');
	}

	function procPurplebookCancelGroupMessages()
	{
		$target_group_ids = Context::get('target_group_ids');
		if(!$target_group_ids)
			return new Object(-1, 'msg_invalide_request');
		$group_ids = explode(',', $target_group_ids);

		$opts->basecamp=true;
		$output = $this->cancelGroupMessages($group_ids, $opts);
		if(!$output->toBool())
		{
			$this->setMessage('cancel_failed');
			return $output;
		}
		$this->setMessage('success_canceled');
	}
}
