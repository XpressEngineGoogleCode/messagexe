<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
 * @class  reset_passwordAdminModel
 * @author diver(diver@coolsms.co.kr)
 * @brief  reset_passwordAdminModel
 */
class reset_passwordAdminModel extends reset_password 
{

	function getReset_passwordAdminDeleteModInst() {
		$oModuleModel = &getModel('module');

		$module_srl = Context::get('module_srl');
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
		Context::set('module_info', $module_info);

		$oTemplate = &TemplateHandler::getInstance();
		$tpl = $oTemplate->compile($this->module_path.'tpl', 'form_delete_modinst');
		$this->add('tpl', str_replace("\n"," ",$tpl));
	}
}
/* End of file reset_password.admin.model.php */
/* Location: ./modules/reset_password/reset_password.admin.model.php */
