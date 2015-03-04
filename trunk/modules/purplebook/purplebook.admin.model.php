<?php
/**
 * @class  purplebookAdminModel
 * @author NURIGO(contact@nurigo.net)
 * @brief  purplebookAdminModel
 */
class purplebookAdminModel extends purplebook
{
	/**
	 * module delete form 가져오기
	 */
	function getPurplebookAdminDeleteModInst() 
	{
		$oModuleModel = &getModel('module');

		$module_srl = Context::get('module_srl');
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
		Context::set('module_info', $module_info);

		$oTemplate = &TemplateHandler::getInstance();
		$tpl = $oTemplate->compile($this->module_path.'tpl', 'form_delete_modinst');
		$this->add('tpl', str_replace("\n"," ",$tpl));
	}
}
/* End of file purplebook.admin.model.php */
/* Location: ./modules/purplebook/purplebook.admin.model.php */

