<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
 * @class  newposts
 * @author wiley(wiley@nurigo.net)
 * @brief  newposts
 */
class newposts extends ModuleObject 
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
				if (substr($key,0,10)=='extra_vars') 
					$val = str_replace('|@|', '-', $val);
				elseif(!$val)
					$val = "";
				$text = preg_replace("/%" . preg_quote($key) . "%/", $val, $text);
			}
		}
		$pattern = "/%[a-z]+_[a-z]+\d%/";
		$text = preg_split("/[\s,]+/", $text);
		foreach($text as $key)
		{
			if(!preg_match($pattern, $key))
				$output .= $key . "\n";
		}
		return $output;
	}

	/**
	 * @brief 모듈 설치 실행
	 **/
	function moduleInstall() 
	{
		$oModuleController = &getController('module');
		$oModuleModel = &getModel('module');

		// Document Registration Trigger
		$oModuleController->insertTrigger('document.insertDocument', 'newposts', 'controller', 'triggerInsertDocument', 'after');
	}

	/**
	 * @brief 설치가 이상없는지 체크
	 **/
	function checkUpdate() 
	{
		$oDB = &DB::getInstance();
		$oModuleModel = &getModel('module');
		$oModuleController = &getController('module');

		// Document Registration Trigger
		if (!$oModuleModel->getTrigger('document.insertDocument', 'newposts', 'controller', 'triggerInsertDocument', 'after'))
		{
			return true;
		}

		// 2012.03.06 add newposts_config.sender_name
		if(!$oDB->isColumnExists("newposts_config","sender_name")) return true;
		// 2012.03.06 add newposts_config.sender_email
		if(!$oDB->isColumnExists("newposts_config","sender_email")) return true;


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

		// Document Registration Trigger
		if (!$oModuleModel->getTrigger('document.insertDocument', 'newposts', 'controller', 'triggerInsertDocument', 'after'))
		{
			$oModuleController->insertTrigger('document.insertDocument', 'newposts', 'controller', 'triggerInsertDocument', 'after');
		}

		if(!$oDB->isColumnExists("newposts_config","sender_name")) {
			$oDB->addColumn("newposts_config","sender_name", "varchar","80");
		}
		if(!$oDB->isColumnExists("newposts_config","sender_email")) {
			$oDB->addColumn("newposts_config","sender_email", "varchar","250");
		}
		if(!$oDB->isColumnExists("newposts_config","sender_phone")) {
			$oDB->addColumn("newposts_config", "sender_phone", "varchar", "250");
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
