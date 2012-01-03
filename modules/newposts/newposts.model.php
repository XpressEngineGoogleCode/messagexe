<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
 * @class  newpostsModel
 * @author wiley(wiley@nurigo.net)
 * @brief  newpostsModel
 */
class newpostsModel extends newposts 
{

	/**
	 * @brief constructor
	 */
	function init() 
	{
	}

	function getModuleConfig() {
		if (!$GLOBALS['__newposts_config__']) {
			$oModuleModel = &getModel('module');
			$config = $oModuleModel->getModuleConfig('newposts');
			$GLOBALS['__newposts_config__'] = $config;
		}
		return $GLOBALS['__newposts_config__'];
	}

	function getConfigListByModuleSrl($module_srl) 
	{
		if (!$module_srl) return false;
		$args->module_srl = $module_srl;
		$output = executeQuery("newposts.getConfigByModuleSrl", $args);
		if (!$output->toBool() || !$output->data) return false;
		$config_list = $output->data;
		if (!is_array($config_list)) $config_list = array($output->data);

		foreach($config_list as $key=>$val){
			$extra_vars = unserialize($val->extra_vars);
			if ($extra_vars) {
				foreach ($extra_vars as $key2 => $val2) {
					$config_list[$key]->{$key2} = $val2;
				}
			}
		}
		return $config_list;
	}
}
?>
