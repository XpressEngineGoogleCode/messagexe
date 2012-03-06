<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
 * @class  newpostsAdminController
 * @author wiley(wiley@nurigo.net)
 * @brief  newpostsAdminController
 */
class newpostsAdminController extends newposts 
{
	/**
	 * @brief constructor
	 */
	function init() 
	{
	}

	/**
	 * @brief saving config values.
	 **/
	function procNewpostsAdminInsert() 
	{
		$params = Context::gets('admin_phones','admin_emails','sender_name','sender_email','content','mail_content','module_srls','msgtype','sending_method');
		$params->config_srl = Context::get('config_srl');

		if ($params->config_srl) 
		{
			// delete existences
			$args->config_srl = $params->config_srl;
			$output = executeQuery('newposts.deleteConfig', $args);
			if (!$output->toBool()) return $output;
			$output = executeQuery('newposts.deleteModule', $args);
			if (!$output->toBool()) return $output;
		}
		else
		{
			// new sequence
			$params->config_srl = getNextSequence();
		}

		// insert module srls
		$module_srls = explode(',', $params->module_srls);
		foreach ($module_srls as $srl) 
		{
			unset($args);
			$args->config_srl = $params->config_srl;
			$args->module_srl = $srl;
			$output = executeQuery('newposts.insertModuleSrl', $args);
			if (!$output->toBool()) return $output;
		}

		//$params->extra_vars = serialize($extra_vars);

		debugPrint('params : ' . serialize($params));
		// insert newposts
		$output = executeQuery('newposts.insertConfig', $params);
		debugPrint('insertConfig : ' . serialize($output));
		if (!$output->toBool()) 
		{
			return $output;
		}

		$redirectUrl = getNotEncodedUrl('', 'module', 'admin', 'act', 'dispNewpostsAdminModify','config_srl',$params->config_srl);
		$this->setRedirectUrl($redirectUrl);
	}

	function procNewpostsAdminDelete() 
	{
		$config_srl = Context::get('config_srl');
		if (!$config_srl) return new Object(-1, 'msg_invalid_request');

		if ($config_srl) 
		{
			// delete existences
			$args->config_srl = $config_srl;
			$query_id = "newposts.deleteConfig";
			executeQuery($query_id, $args);
			$query_id = "newposts.deleteModule";
			executeQuery($query_id, $args);
		}
		$redirectUrl = getNotEncodedUrl('', 'module', 'admin', 'act', 'dispNewpostsAdminList');
		$this->setRedirectUrl($redirectUrl);
	}
}
?>
