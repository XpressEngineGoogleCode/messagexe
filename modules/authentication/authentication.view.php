<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
 * @class  authenticationView
 * @author wiley(wiley@nurigo.net)
 * @brief  authenticationView
 */
class authenticationView extends authentication 
{
	function init() 
	{
		// 템플릿 설정
		$this->setTemplatePath($this->module_path.'tpl');
	}
}
?>
