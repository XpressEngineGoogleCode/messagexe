<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
 * @class  authentication
 * @author wiley(wiley@nurigo.net)
 * @brief  authentication
 */
class authentication extends ModuleObject 
{
	/**
	 * @brief Object를 텍스트의 %...% 와 치환.
	 */
	function mergeKeywords($text, &$obj) 
	{
		if (!is_object($obj)) return $text;

		foreach ($obj as $key => $val)
		{
			if (is_array($val)) $val = join($val);
			if (is_string($key) && is_string($val)) {
				if (substr($key,0,10)=='extra_vars') $val = str_replace('|@|', '-', $val);
				$text = preg_replace("/%" . preg_quote($key) . "%/", $val, $text);
			}
		}
		return $text;
	}

	/**
	 * @brief 모듈 설치 실행
	 */
	function moduleInstall() 
	{
		$oModuleController = &getController('module');
		$oModuleModel = &getModel('module');

		$oModuleController->insertTrigger('moduleHandler.proc', 'authentication', 'controller', 'triggerModuleHandlerProc', 'after');
	}

	/**
	 * @brief 설치가 이상없는지 체크
	 */
	function checkUpdate() 
	{
		$oDB = &DB::getInstance();
		$oModuleModel = &getModel('module');
		$oModuleController = &getController('module');

		if(!$oModuleModel->getTrigger('moduleHandler.proc', 'authentication', 'controller', 'triggerModuleHandlerProc', 'after'))
		{
			return true;
		}

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

		if(!$oModuleModel->getTrigger('moduleHandler.proc', 'authentication', 'controller', 'triggerModuleHandlerProc', 'after'))
		{
			$oModuleController->insertTrigger('moduleHandler.proc', 'authentication', 'controller', 'triggerModuleHandlerProc', 'after');
		}
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
/* End of file authentication.class.php */
/* Location: ./modules/authentication/authentication.class.php */