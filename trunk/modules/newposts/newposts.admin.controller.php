<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
 * @class  newpostsAdminController
 * @author wiley(wiley@nurigo.net)
 * @brief  newpostsAdminController
 */
class newpostsAdminController extends newposts 
{
	/**
	 * @brief constructor
	 */
	function init() 
	{
	}
	/**
	 * @brief saving config values.
	 **/
	function procNewpostsAdminInsert() 
	{
		$params = Context::gets('admin_phones','admin_emails', 'sender_phone', 'sender_name','sender_email','content','mail_content','module_srls','msgtype','sending_method');

		// 모듈 입력을 하지 않앗을 경우 에러메시지 & Redirect
		if(!$params->module_srls)
		{
			$this->setMessage('notify_empty_module');
			$this->setMessageType('error');
			$redirectUrl = getNotEncodedUrl('', 'module', 'admin', 'act', 'dispNewpostsAdminInsert','config_srl',$params->config_srl);
			$this->setRedirectUrl($redirectUrl);
			return;
		}
		/*
		//선택한 모듈이 다른 새글알림에 사용되엇는지 확인
		$module_srls = explode(',', $params->module_srls);
		$args->module_srls = $module_srls;
		$output = executeQuery('newposts.getModuleCountByModuleSrl', $args);
		if(!$output->toBool()) return $output;

		$count = $output->data->count;
		if($count != '0')
		{
			$this->setMessage('notify_exist_module');
			$this->setMessageType('error');
			$redirectUrl = getNotEncodedUrl('', 'module', 'admin', 'act', 'dispNewpostsAdminInsert','config_srl',$params->config_srl); 
			$this->setRedirectUrl($redirectUrl);
			return;
		}
		 */

		//프로세스
		$this->processNewpostsAdmin($params);

		$this->setMessage('notify_add_newposts');
		$redirectUrl = getNotEncodedUrl('', 'module', 'admin', 'act', 'dispNewpostsAdminModify','config_srl',$params->config_srl);
		$this->setRedirectUrl($redirectUrl);
	}

	function procNewpostsAdminModify()
	{
		$params = Context::gets('admin_phones','admin_emails','sender_phone','sender_name','sender_email','content','mail_content','module_srls','msgtype','sending_method');
		$params->config_srl = Context::get('config_srl');
		// Insert 와 다른점은 이건 Modify 로 Redirect 하고 Insert 는 Insert 로 Redirect
		// 모듈 입력을 하지 않앗을 경우 에러메시지 & Redirect
		if(!$params->module_srls)
		{
			$this->setMessage('notify_empty_module');
			$this->setMessageType('error');
			$redirectUrl = getNotEncodedUrl('', 'module', 'admin', 'act', 'dispNewpostsAdminModify','config_srl',$params->config_srl);
			$this->setRedirectUrl($redirectUrl);
			return;
		}
		/*
		//선택한 모듈이 다른 새글알림에 사용되엇는지 확인
		$module_srls = explode(',', $params->module_srls);
		$args->module_srls = $module_srls;
		$args->config_srl = $params->config_srl;
		$output = executeQuery('newposts.getModuleCountByModuleSrlNConfigSrl', $args);
		if(!$output->toBool()) return $output;
		$count = $output->data->count;
		if($count != '0')
		{
			$this->setMessage('notify_exist_module');
			$this->setMessageType('error');
			$redirectUrl = getNotEncodedUrl('', 'module', 'admin', 'act', 'dispNewpostsAdminModify','config_srl',$params->config_srl); 
			$this->setRedirectUrl($redirectUrl);
			return;
		}
		 */

		//프로세스
		$this->processNewpostsAdmin($params);
		$this->setMessage('새글알림이 수정되었습니다.');
		$redirectUrl = getNotEncodedUrl('', 'module', 'admin', 'act', 'dispNewpostsAdminModify','config_srl',$params->config_srl);
		$this->setRedirectUrl($redirectUrl);
	}


	function processNewpostsAdmin(&$parm)
	{
		// 파라미터에 config_srl 있으면 지우고 다시만들고 없으면 새로 받아오고
		if ($parm->config_srl) 
		{
			// delete existences
			$args->config_srl = $parm->config_srl;
			$output = executeQuery('newposts.deleteConfig', $args);
			if (!$output->toBool()) return $output;
			$output = executeQuery('newposts.deleteModule', $args);
			if (!$output->toBool()) return $output;
		}
		else
		{
			// new sequence
			$parm->config_srl = getNextSequence();
		}
		// 현재 config_srl 에 있는 모듈 시리얼들을 가져오기
		$module_srls = explode(',', $parm->module_srls);

		// newposts.modules 에 insert 하기
		foreach ($module_srls as $srl) 
		{
			unset($args);
			$args->config_srl = $parm->config_srl;
			$args->module_srl = $srl;
			$output = executeQuery('newposts.insertModuleSrl', $args);
			if (!$output->toBool()) return $output;
		}
		// newposts.config 에 insert 하기 
		$output = executeQuery('newposts.insertConfig', $parm);
		if (!$output->toBool())	return $output;
	}


	function procNewpostsAdminDelete() 
	{
		$config_srl = Context::get('config_srl');
		if (!$config_srl) return new Object(-1, 'msg_invalid_request');

		if ($config_srl) 
		{
			// delete existences
			$args->config_srl = $config_srl;
			$query_id = "newposts.deleteConfig";
			executeQuery($query_id, $args);
			$query_id = "newposts.deleteModule";
			executeQuery($query_id, $args);
		}
		$redirectUrl = getNotEncodedUrl('', 'module', 'admin', 'act', 'dispNewpostsAdminList');
		$this->setRedirectUrl($redirectUrl);
	}

	function procNewpostsAdminSet()
	{
		$category_srl = Context::get('category_srl');
		$cellphone = Context::get('cellphone');
		$email = Context::get('email');
		$config_srl = Context::get('config_srl');
		for($i=0; $i<sizeOf($category_srl); $i++)
		{
			$args->cellphone = $cellphone[$i];
			$args->email = $email[$i];
			$args->category_srl = $category_srl[$i];
			$output = executeQueryArray("newposts.updateAdminInfo", $args);
		}
		$this->setMessage('등록되었습니다.');
		$redirectUrl = getNotEncodedUrl('', 'module', 'admin', 'act', 'dispNewpostsAdminSet', 'config_srl', $config_srl);
		$this->setRedirectUrl($redirectUrl);
	}
}
?>
