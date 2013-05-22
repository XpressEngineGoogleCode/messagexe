<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
 * @class  reset_passwordAdminController
 * @author wiley(wiley@nurigo.net)
 * @brief  reset_passwordAdminController
 */
class reset_passwordAdminController extends reset_password 
{

	/**
	 * @brief 모듈 환경설정값 쓰기
	 **/
	function procReset_passwordAdminInsertModInst() 
	{
		// module 모듈의 model/controller 객체 생성
		$oModuleController = &getController('module');
		$oModuleModel = &getModel('module');

		// 게시판 모듈의 정보 설정
		$args = Context::getRequestVars();
		$args->module = 'reset_password';

		// module_srl이 넘어오면 원 모듈이 있는지 확인
		if($args->module_srl) 
		{
			$module_info = $oModuleModel->getModuleInfoByModuleSrl($args->module_srl);
			if($module_info->module_srl != $args->module_srl)
			{
				unset($args->module_srl);
			}
		}

		// module_srl의 값에 따라 insert/update
		if(!$args->module_srl) 
		{
			$output = $oModuleController->insertModule($args);
			$msg_code = 'success_registed';
		}
		else
		{
			$output = $oModuleController->updateModule($args);
			$msg_code = 'success_updated';
		}

		if(!$output->toBool())
		{
			return $output;
		}

		$this->add('module_srl',$output->get('module_srl'));
		$this->setMessage($msg_code);

		$returnUrl = getNotEncodedUrl('', 'module', 'admin', 'act', 'dispReset_passwordAdminInsertModInst','module_srl',$this->get('module_srl'));
		$this->setRedirectUrl($returnUrl);
	}

	function procReset_passwordAdminDeleteModInst() 
	{
		$module_srl = Context::get('module_srl');

		$oModuleController = &getController('module');
		$output = $oModuleController->deleteModule($module_srl);
		if(!$output->toBool())
		{
			return $output;
		}

		$this->add('module','reset_password');
		$this->add('page',Context::get('page'));
		$this->setMessage('success_deleted');

		$returnUrl = getNotEncodedUrl('', 'module', 'admin', 'act', 'dispReset_passwordAdminModInstList');
		$this->setRedirectUrl($returnUrl);
	}


}
/* End of file reset_password.admin.controller.php */
/* Location: ./modules/reset_password/reset_password.admin.controller.php */
