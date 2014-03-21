<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
 * @class  authentication_update_memberAdminView
 * @author NURIGO(contact@nurigo.net)
 * @brief  authentication_update_memberAdminView
 */ 
class authentication_update_memberAdminView extends authentication_update_member 
{
	function init() 
	{
		// 템플릿 설정
		$this->setTemplatePath($this->module_path.'tpl');
	}

	/**
	 * config
	 */
	function dispAuthentication_update_memberAdminIndex() 
	{
		$oMemberModel = &getModel('member');
		$member_config = $oMemberModel->getMemberConfig();
		Context::set("member_config", $member_config);

		// set template file
		$this->setTemplateFile('index');
	}
}
/* End of file authentication_update_member.admin.view.php */
/* Location: ./modules/authentication_update_member/authentication_update_member.admin.view.php */
