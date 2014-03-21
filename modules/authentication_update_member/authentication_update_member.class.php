<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
 * @class  authentication_update_member
 * @author NURIGO(contact@nurigo.net)
 * @brief  authentication_update_member
 */
class authentication_update_member extends ModuleObject 
{
	/**
	 * @brief 모듈 설치 실행
	 */
	function moduleInstall() 
	{
		$oModuleController = &getController('module');
		$oModuleModel = &getModel('module');
	}

	/**
	 * @brief 설치가 이상없는지 체크
	 */
	function checkUpdate() 
	{
		$oDB = &DB::getInstance();
		$oModuleModel = &getModel('module');
		$oModuleController = &getController('module');
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
/* End of file authentication_update_member.class.php */
/* Location: ./modules/authentication_update_member/authentication_update_member.class.php */
