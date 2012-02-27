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
	 **/
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
	 **/
	function moduleInstall() 
	{
		$oModuleController = &getController('module');
		$oModuleModel = &getModel('module');

		$oModuleController->insertTrigger('member.insertMember', 'authentication', 'controller', 'triggerInsertMember', 'after');
		$oModuleController->insertTrigger('member.deleteMember', 'authentication', 'controller', 'triggerDeleteMember', 'before');
		$oModuleController->insertTrigger('member.updateMember', 'authentication', 'controller', 'triggerUpdateMember', 'after');
	}

	/**
	 * @brief 설치가 이상없는지 체크
	 **/
	function checkUpdate() 
	{
		$oDB = &DB::getInstance();
		$oModuleModel = &getModel('module');
		$oModuleController = &getController('module');

		if (!$oModuleModel->getTrigger('member.insertMember'
			, 'authentication'
			, 'controller'
			, 'triggerInsertMember'
			, 'after'))
		{
			return true;
		}

		if (!$oModuleModel->getTrigger('member.deleteMember'
			, 'authentication'
			, 'controller'
			, 'triggerDeleteMember'
			, 'before'))
		{
			return true;
		}

		if (!$oModuleModel->getTrigger('member.updateMember'
			, 'authentication'
			, 'controller'
			, 'triggerUpdateMember'
			, 'after'))
		{
			return true;
		}

		return false;
	}

	/**
	 * @brief 업데이트(업그레이드)
	 **/
	function moduleUpdate() 
	{
		$oDB = &DB::getInstance();
		$oModuleModel = &getModel('module');
		$oModuleController = &getController('module');

		if (!$oModuleModel->getTrigger('member.insertMember', 'authentication', 'controller', 'triggerInsertMember', 'after'))
		{
			$oModuleController->insertTrigger('member.insertMember', 'authentication', 'controller', 'triggerInsertMember', 'after');
		}
		if (!$oModuleModel->getTrigger('member.deleteMember', 'authentication', 'controller', 'triggerDeleteMember', 'before'))
		{
			$oModuleController->insertTrigger('member.deleteMember', 'authentication', 'controller', 'triggerDeleteMember', 'before');
		}
		if (!$oModuleModel->getTrigger('member.updateMember', 'authentication', 'controller', 'triggerUpdateMember', 'after'))
		{
			$oModuleController->insertTrigger('member.updateMember', 'authentication', 'controller', 'triggerUpdateMember', 'after');
		}
	}

	/**
	 * @brief 캐시파일 재생성
	 **/
	function recompileCache() 
	{
	}
}
?>
