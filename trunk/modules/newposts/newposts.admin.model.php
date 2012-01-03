<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
 * @class  newpostsAdminModel
 * @author diver(diver@coolsms.co.kr)
 * @brief  newpostsAdminModel
 */
class newpostsAdminModel extends newposts 
{
	function getNewpostsAdminDelete() 
	{
		// get configs.
		$args->config_srl = Context::get('config_srl');
		$output = executeQuery("newposts.getConfig", $args);
		$id_list = $output->data->id_list;
		$group_srl_list = $output->data->group_srl_list;
		$config = $output->data;

		$args->config_srls = Context::get('config_srls');
		$output = executeQueryArray("newposts.getModuleInfoByConfigSrl", $args);
		$mid_list = array();
		if ($output->data) 
		{
			foreach ($output->data as $no => $val) 
			{
				$mid_list[] = $val->mid;
			}
		}
		$config->mid_list = join(',', $mid_list);

		Context::set('config', $config);

		$oTemplate = &TemplateHandler::getInstance();
		$tpl = $oTemplate->compile($this->module_path.'tpl', 'delete');
		$this->add('tpl', str_replace("\n"," ",$tpl));
	}
}
?>
