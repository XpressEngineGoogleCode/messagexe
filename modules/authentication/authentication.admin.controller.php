<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
 * @class  authenticationAdminController
 * @author wiley(wiley@nurigo.net)
 * @brief  authenticationAdminController
 */
class authenticationAdminController extends authentication 
{
	/**
	 * @brief constructor
	 */
	function init() 
	{
	}

	function procAuthenticationAdminInsertModInst() 
	{
		
	}


	function isValidFieldName($fieldname)
	{
		$bfound = false;

		// 확장 필드에세 찾아보기
		$oMemberModel = &getModel('member');
		$list = $oMemberModel->getJoinFormList();
		if ($list) {
			foreach ($list as $row) {
				if ($row->column_name == $fieldname) {
					$bfound = true;
				}
			}
		}

		// 기본 필드에세 찾아보기
		$logged_info = $oMemberModel->getLoggedInfo();
		if (isset($logged_info->{$fieldname}))
			$bfound = true;

		return $bfound;
	}

	function procAuthenticationModuleInsert()
	{
		// module 모듈의 model/controller 객체 생성
		$oModuleController = &getController('module');
		$oModuleModel = &getModel('module');

		// 게시판 모듈의 정보 설정
		$args->module = 'authentication';
		$args->mid = 'authentication';
		$args->browser_title = 'authentication';

		// module_srl이 넘어오면 원 모듈이 있는지 확인
		if($args->module_srl) 
		{
			$module_info = $oModuleModel->getModuleInfoByModuleSrl($args->module_srl);
			if($module_info->module_srl != $args->module_srl)
			{
				unset($args->module_srl);
			}
		}

		// module_srl의 값에 따라 insert/update
		if(!$args->module_srl) 
		{
			$output = $oModuleController->insertModule($args);
			$msg_code = 'success_registed';
		}
		else
		{
			$output = $oModuleController->updateModule($args);
			$msg_code = 'success_updated';
		}

		if(!$output->toBool())
		{
			return $output;
		}

		$this->add('module_srl',$output->get('module_srl'));
		$this->setMessage($msg_code);
	}

	/**
	 * @brief 모듈 환경설정값 쓰기
	 **/
	function procAuthenticationAdminConfig()
	{
		$module_srl = Context::get('module_srl');
		$list = explode(',',Context::get('list'));
		if(!count($list)) return new Object(-1, 'msg_invalid_request');

		$list_arr = array();
		foreach($list as $val) 
		{
			$val = trim($val);
			if(!$val) continue;
			$list_arr[] = $val;
		}

		$oModuleController = &getController('module');
		$oModuleController->insertModulePartConfig('authentication', $module_srl, $list_arr);

		$bsucc = true;
		$messages = "= 설정오류 내역 =\n";

		$args = Context::getRequestVars();

		// check whether countrycode_fieldname is valid
		if ($args->countrycode_fieldname) {
			if (!$this->isValidFieldName($args->country_code)) {
				$messages .= "[국가번호 필드명] 필드명이 올바르지 않습니다. 정확히 입력해 주세요.";
				$bsucc = false;
			}
		}
		if ($args->number_limit > 10)
		{
			$args->number_limit = '';
			$messages .= "[인증번호 숫자 오류] 정확히 입력해 주세요.";
			$bsucc = false;
		}
		// save module configuration.
		$oModuleController = getController('module');
		$output = $oModuleController->insertModuleConfig('authentication', $args);

		if ($bsucc)
		{
			$this->setMessage('success_updated');
		}
		else
		{
			$this->setMessage($messages);
		}

		$redirectUrl = getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAuthenticationAdminConfig');
		$this->setRedirectUrl($redirectUrl);
	}

	/**
	 * @brief saving config values.
	 **/
	function procAuthenticationAdminInsert() 
	{
		$params = Context::gets('admin_phones','admin_emails','content','mail_content','module_srls','msgtype','sending_method');
		$params->config_srl = Context::get('config_srl');

		if ($params->config_srl) 
		{
			// delete existences
			$args->config_srl = $params->config_srl;
			$output = executeQuery('authentication.deleteConfig', $args);
			if (!$output->toBool()) return $output;
			$output = executeQuery('authentication.deleteModule', $args);
			if (!$output->toBool()) return $output;
		}
		else
		{
			// new sequence
			$params->config_srl = getNextSequence();
		}

		// insert module srls
		$module_srls = explode(',', $params->module_srls);
		foreach ($module_srls as $srl) 
		{
			unset($args);
			$args->config_srl = $params->config_srl;
			$args->module_srl = $srl;
			$output = executeQuery('authentication.insertModuleSrl', $args);
			if (!$output->toBool()) return $output;
		}

		//$params->extra_vars = serialize($extra_vars);

		// insert authentication
		$output = executeQuery('authentication.insertConfig', $params);
		debugPrint('insertConfig : ' . serialize($output));
		if (!$output->toBool()) 
		{
			return $output;
		}

		$redirectUrl = getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAuthenticationAdminModify','config_srl',$params->config_srl);
		$this->setRedirectUrl($redirectUrl);
	}

	function procAuthenticationAdminDelete() 
	{
		$config_srl = Context::get('config_srl');
		if (!$config_srl) return new Object(-1, 'msg_invalid_request');

		if ($config_srl) 
		{
			// delete existences
			$args->config_srl = $config_srl;
			$query_id = "authentication.deleteConfig";
			executeQuery($query_id, $args);
			$query_id = "authentication.deleteModule";
			executeQuery($query_id, $args);
		}
		$redirectUrl = getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAuthenticationAdminList');
		$this->setRedirectUrl($redirectUrl);
	}
}
/* End of file authentication.admin.controller.php */
/* Location: ./modules/authentication/authentication.admin.controller.php */
