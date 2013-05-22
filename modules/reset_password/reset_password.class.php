<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
 * @class  reset_password
 * @author NURIGO(contact@nurigo.net)
 * @brief  reset_password
 */
class reset_password extends ModuleObject 
{
	function registerTriggers()
	{
		$oModuleController = &getController('module');
		$oModuleModel = &getModel('module');

		if(!$oModuleModel->getTrigger('authentication.procAuthenticationSendAuthCode', 'reset_password', 'controller', 'triggerAuthenticationSendAuthCode', 'before'))
		{
			$oModuleController->insertTrigger('authentication.procAuthenticationSendAuthCode', 'reset_password', 'controller', 'triggerAuthenticationSendAuthCode', 'before');
		}
		if(!$oModuleModel->getTrigger('authentication.procAuthenticationVerifyAuthCode', 'reset_password', 'controller', 'triggerAuthenticationVerifyAuthCode', 'after'))
		{
			$oModuleController->insertTrigger('authentication.procAuthenticationVerifyAuthCode', 'reset_password', 'controller', 'triggerAuthenticationVerifyAuthCode', 'after');
		}
	}

	/**
	 * @brief 모듈 설치 실행
	 */
	function moduleInstall() 
	{
		$oModuleController = &getController('module');
		$oModuleModel = &getModel('module');

		$this->registerTriggers();
	}

	/**
	 * @brief 설치가 이상없는지 체크
	 */
	function checkUpdate() 
	{
		$oDB = &DB::getInstance();
		$oModuleModel = &getModel('module');
		$oModuleController = &getController('module');

		if(!$oModuleModel->getTrigger('authentication.procAuthenticationSendAuthCode', 'reset_password', 'controller', 'triggerAuthenticationSendAuthCode', 'before')) return true;
		if(!$oModuleModel->getTrigger('authentication.procAuthenticationVerifyAuthCode', 'reset_password', 'controller', 'triggerAuthenticationVerifyAuthCode', 'after')) return true;

		return false;
	}

	/**
	 * @brief 업데이트(업그레이드)
	 */
	function moduleUpdate() 
	{
		$oDB = &DB::getInstance();
		$oModuleModel = &getModel('module');
		$oModuleController = &getController('module');

		$this->registerTriggers();
	}

	/**
	 * @brief Unintall
	 */
	function moduleUninstall()
	{
		return new Object();
	}

	/**
	 * @brief 캐시파일 재생성
	 */
	function recompileCache() 
	{
	}
}
/* End of file reset_password.class.php */
/* Location: ./modules/reset_password/reset_password.class.php */
