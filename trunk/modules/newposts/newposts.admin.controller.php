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
		$params = Context::gets('admin_phones','admin_emails','sender_name','sender_email','content','mail_content','module_srls','msgtype','sending_method');
		$params->config_srl = Context::get('config_srl');

		$module_srls = explode(',', $params->module_srls);

		foreach ($module_srls as $srl)
		{
			unset($args);
			$args->module_srl = $srl;
			$output = executeQuery('newposts.getNewpostsModuleInfoByModuleSrl', $args);
			if(sizeOf($output->data)!=0)
			{
				$this->setMessage('같은 모듈이 이미 등록되어있습니다.');
				$redirectUrl = getNotEncodedUrl('', 'module', 'admin', 'act', 'dispNewpostsAdminInsert','config_srl',$params->config_srl);
				$this->setRedirectUrl($redirectUrl);
				$checker = "false";
			}else{
				$checker = "true";
				break;
			}
			debugPrint($checker);
		}


		if($checker=="false"){
			return;
		}

		if ($params->config_srl) 
		{
			// delete existences
			$args->config_srl = $params->config_srl;
			$output = executeQuery('newposts.deleteConfig', $args);
			if (!$output->toBool()) return $output;
			$output = executeQuery('newposts.deleteModule', $args);
			if (!$output->toBool()) return $output;
		}
		else
		{
			// new sequence
			$params->config_srl = getNextSequence();
		}

		// insert module srls
	

		foreach ($module_srls as $srl) 
		{
			unset($args);
			$args->config_srl = $params->config_srl;
			$args->module_srl = $srl;
			$output = executeQuery('newposts.insertModuleSrl', $args);
			debugPrint($output);
			if (!$output->toBool()) return $output;
		}

		//$params->extra_vars = serialize($extra_vars);

		debugPrint('params : ' . serialize($params));
		// insert newposts
		$output = executeQuery('newposts.insertConfig', $params);
		debugPrint('insertConfig : ' . serialize($output));
		if (!$output->toBool()) 
		{
			return $output;
		}
		$this->setMessage('새글알림이 등록되었습니다.');
		$redirectUrl = getNotEncodedUrl('', 'module', 'admin', 'act', 'dispNewpostsAdminModify','config_srl',$params->config_srl);
		$this->setRedirectUrl($redirectUrl);
	}

	function procNewpostsAdminModify()
	{
		$params = Context::gets('admin_phones','admin_emails','sender_name','sender_email','content','mail_content','module_srls','msgtype','sending_method');
		$params->config_srl = Context::get('config_srl');

		$module_srls = explode(',', $params->module_srls);

		$args->config_srl = $params->config_srl;

		$exist_module_srls = array();
		$output = executeQueryArray('newposts.getModuleInfoByConfigSrl', $args);

		foreach($output->data as $srls){
			$exist_module_srls[] = $srls->module_srl;
		}

		$out = array();
		// get sameModuleExist Title
		for($i=0; $i<sizeOf($module_srls); $i++)
		{
			$tmp = in_array($module_srls[$i], $exist_module_srls);
			if(!$tmp){
				unset($args);
				$args->module_srl = $module_srls[$i];
				$output = executeQueryArray('newposts.getNewpostsModuleInfoByModuleSrl', $args);

				if(count($output->data)!=0){
					$output = executeQuery('newposts.getModuleInfoByModuleSrl', $args);  
					$out[] = ucfirst($output->data->browser_title);
					$sameModuleExist = join(',' , $out);
				}
			}			
		}
		// check exist modules then redirect 
		for($i=0; $i<sizeOf($module_srls); $i++)
		{
			$check = in_array($module_srls[$i], $exist_module_srls);
			if(!$check){
				unset($args);
				$args->module_srl = $module_srls[$i];
				$output = executeQueryArray('newposts.getNewpostsModuleInfoByModuleSrl', $args);

				if(count($output->data)!=0){
					$this->setMessage($sameModuleExist. ' 모듈이 이미 다른 새글알림에 등록되어있습니다.');
					$redirectUrl = getNotEncodedUrl('', 'module', 'admin', 'act', 'dispNewpostsAdminModify','config_srl',$params->config_srl);
					$this->setRedirectUrl($redirectUrl);
					return;
				}
			}
		}

		if ($params->config_srl) 
		{
			// delete existences
			$args->config_srl = $params->config_srl;
			$output = executeQuery('newposts.deleteConfig', $args);
			if (!$output->toBool()) return $output;
			$output = executeQuery('newposts.deleteModule', $args);
			if (!$output->toBool()) return $output;
		}
		else
		{
			// new sequence
			$params->config_srl = getNextSequence();
		}
 
		// insert module srls
	

		foreach ($module_srls as $srl) 
		{
			unset($args);
			$args->config_srl = $params->config_srl;
			$args->module_srl = $srl;
			$output = executeQuery('newposts.insertModuleSrl', $args);
			if (!$output->toBool()) return $output;
		}

		//$params->extra_vars = serialize($extra_vars);

	//	debugPrint('params : ' . serialize($params));
		// insert newposts
		$output = executeQuery('newposts.insertConfig', $params);
	//	debugPrint('insertConfig : ' . serialize($output));
		if (!$output->toBool()) 
		{
			return $output;
		}
		$this->setMessage('새글알림이 수정되었습니다.');
		$redirectUrl = getNotEncodedUrl('', 'module', 'admin', 'act', 'dispNewpostsAdminModify','config_srl',$params->config_srl);
		$this->setRedirectUrl($redirectUrl);
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
		
		for($i=0; $i<sizeOf($category_srl); $i++)
		{
			$args->cellphone = $cellphone[$i];
			$args->email = $email[$i];
			$args->category_srl = $category_srl[$i];
			debugPrint($args);
			$output = executeQueryArray("newposts.updateAdminInfo", $args);
			debugPrint($output);
		}
		$this->setMessage('등록되었습니다.');
		$redirectUrl = getNotEncodedUrl('', 'module', 'admin', 'act', 'dispNewpostsAdminList');
		$this->setRedirectUrl($redirectUrl);
	}
}
?>
