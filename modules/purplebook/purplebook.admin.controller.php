<?php
/**
 * @class  purplebookAdminController
 * @author NURIGO(contact@nurigo.net)
 * @brief  purplebookAdminController
 */
class purplebookAdminController extends purplebook
{
	/**
	 * insert/update module
	 */
	function procPurplebookAdminInsertModInst() 
	{
		// module 모듈의 model/controller 객체 생성
		$oModuleController = &getController('module');
		$oModuleModel = &getModel('module');

		$args = Context::getRequestVars();
		$args->module = 'purplebook';

		// module_srl이 넘어오면 원 모듈이 있는지 확인
		if($args->module_srl) {
			$module_info = $oModuleModel->getModuleInfoByModuleSrl($args->module_srl);
			if($module_info->module_srl != $args->module_srl) unset($args->module_srl);
		}

		// module_srl의 값에 따라 insert/update
		if(!$args->module_srl) {
			$output = $oModuleController->insertModule($args);
			$msg_code = 'success_registed';
		} else {
			$output = $oModuleController->updateModule($args);
			$msg_code = 'success_updated';
		}

		if(!$output->toBool()) return $output;

		$this->add('module_srl',$output->get('module_srl'));
		$this->setMessage($msg_code);

		$redirectUrl = getNotEncodedUrl('', 'module', 'admin', 'act', 'dispPurplebookAdminInsertModInst','module_srl',$output->get('module_srl'));
		$this->setRedirectUrl($redirectUrl);
	}

	/**
	 * delete module
	 */
	function procPurplebookAdminDeleteModInst() 
	{
		$module_srl = Context::get('module_srl');

		$oModuleController = &getController('module');
		$output = $oModuleController->deleteModule($module_srl);
		if(!$output->toBool()) return $output;

		$this->add('module','purplebook');
		$this->add('page',Context::get('page'));
		$this->setMessage('success_deleted');

		$returnUrl = getNotEncodedUrl('', 'module', 'admin', 'act', 'dispPurplebookAdminModInstList');
		$this->setRedirectUrl($returnUrl);
	}
}
/* End of file purplebook.admin.controller.php */
/* Location: ./modules/purplebook/purplebook.admin.controller.php */
