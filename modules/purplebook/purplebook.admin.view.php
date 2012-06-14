<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  purplebookAdminView
 * @author wiley(wiley@nurigo.net)
 * @brief  purplebookAdminView
 */ 
class purplebookAdminView extends purplebook
{
	function init()
	{
		// module_srl이 있으면 미리 체크하여 존재하는 모듈이면 module_info 세팅
		$module_srl = Context::get('module_srl');
		if(!$module_srl && $this->module_srl) {
			$module_srl = $this->module_srl;
			Context::set('module_srl', $module_srl);
		}

		$oModuleModel = &getModel('module');

		// module_srl이 넘어오면 해당 모듈의 정보를 미리 구해 놓음
		if($module_srl) {
			$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
			if(!$module_info) {
				Context::set('module_srl','');
				$this->act = 'list';
			} else {
				ModuleModel::syncModuleToSite($module_info);
				$this->module_info = $module_info;
				Context::set('module_info',$module_info);
			}
		}
		if($module_info && $module_info->module != 'purplebook') return $this->stop("msg_invalid_request");

		// 템플릿 설정
		$this->setTemplatePath($this->module_path.'tpl');
	}

	function dispPurplebookAdminModInstList()
	{
		$output = executeQueryArray('purplebook.getModInstList');
		$list = $output->data;

		if (!is_array($list)) $list = array();

		Context::set('list', $list);
		$this->setTemplateFile('modinstlist');
	}

	function dispPurplebookAdminInsertModInst()
	{
		// 스킨 목록을 구해옴
		$oModuleModel = &getModel('module');
		$skin_list = $oModuleModel->getSkins($this->module_path);
		Context::set('skin_list',$skin_list);

		$mskin_list = $oModuleModel->getSkins($this->module_path, "m.skins");
		Context::set('mskin_list', $mskin_list);

		// 레이아웃 목록을 구해옴
		$oLayoutModel = &getModel('layout');
		$layout_list = $oLayoutModel->getLayoutList();
		Context::set('layout_list', $layout_list);

		$mobile_layout_list = $oLayoutModel->getLayoutList(0,"M");
		Context::set('mlayout_list', $mobile_layout_list);

		$this->setTemplateFile('insertmodinst');
	}
}
/* End of file purplebook.admin.view.php */
/* Location: ./modules/purplebook/purplebook.admin.view.php */
