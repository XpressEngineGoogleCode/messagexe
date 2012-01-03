<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
 * @class  newpostsView
 * @author wiley(wiley@nurigo.net)
 * @brief  newpostsView
 */
class newpostsView extends newposts 
{
	function init() 
	{
		// 템플릿 설정
		$this->setTemplatePath($this->module_path.'tpl');
	}
}
?>
