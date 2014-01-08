<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
 * @class  authenticationController
 * @author NURIGO(contact@nurigo.net)
 * @brief  authenticationController
 */
class authenticationController extends authentication 
{
	function getRandNumber($e)
	{
		for($i=0;$i<$e;$i++)
		{
			 $rand =  $rand .  rand(0, 9); 
		}
		return $rand;
	}

	function procAuthenticationSendAuthCode()
	{
		$oAuthenticationModel = &getModel('authentication');
		$config = $oAuthenticationModel->getModuleConfig();
		$target_action = Context::get('target_action');

		// check variables
		$phonenum = Context::get('phonenum');
		$country_code = Context::get('country_code');
		if(!$phonenum || !$country_code)
		{
			return new Object(-1, '국가 및 휴대폰 번호를 전부 입력해주세요.');
		}
		$reqvars = Context::getRequestVars();

		// check duplicated.
		if($config->number_overlap == 'N' && $target_action == 'dispMemberSignUpForm')
		{		 
			$args->clue = $phonenum;
			$output = executeQuery('authentication.getAuthenticationMemberCountByClue', $args);
			if(!$output->toBool()) return $output;
			if($output->data->count > 0) return new Object(-1, '가입하신 휴대폰 번호로 중복 가입이 불가능합니다.');
		}

		$trigger_output = ModuleHandler::triggerCall ('authentication.procAuthenticationSendAuthCode', 'before', $reqvars);
		if(!$trigger_output->toBool ()) return $trigger_output;

		// generate auth-code
		$keystr = $this->getRandNumber($config->digit_number);

		// check day try limit
		$today = date("Ymd", mktime(0,0,0,date("m"),date("d"),date("Y")));
		$args->clue = $phonenum;
		$args->regdate = $today;
		$output = executeQuery('authentication.getTryCountByClue', $args);
		if (!$output->toBool()) return $output;
		unset($args);
		if($output->data->count > $config->day_try_limit)
		{
			return new Object(-1, '잦은 인증번호 요청으로 금지되셨습니다. 1일뒤에 다시 시도해주십시오.');
		}

		// check day try limit
		$today = date("YmdHis", time()-$config->resend_interval);
		$args->clue = $phonenum;
		$args->regdate = $today;
		$output = executeQuery('authentication.getTryCountByClue', $args);
		if (!$output->toBool()) return $output;
		unset($args);
		if($output->data->count > 0)
		{
			return new Object(-1, $config->resend_interval . '초 동안 다시 받으실 수 없습니다. 전송확인 버튼을 눌러 수신받지 못하는 사유를 확인하세요.');
		}

		// save auth info
		$args->authentication_srl = getNextSequence();
		$args->country_code = $country_code;
		$args->clue = $phonenum;
		$args->authcode = $keystr;
		$args->ipaddress = $_SERVER['REMOTE_ADDR'];
		$output = executeQuery('authentication.insertAuthentication', $args);
		if (!$output->toBool()) return $output;

		$_SESSION['authentication_srl'] = $args->authentication_srl;
		$this->add('authentication_srl', $args->authentication_srl);
		Context::set('authentication_srl', $_SESSION['authentication_srl']);

		//unset($args);

		$args->country_code = $country_code;
		$args->recipient_no =  $phonenum;
		$args->sender_no = $config->sender_no;
		if($config->message_content)
		{
			$content = str_replace(array("%authcode%"),array($keystr),$config->message_content);
			$args->content = $content;
		}
		else
		{
			$args->content = $keystr;
		}
		//$args->encode_utf16 = $encode_utf16; 
		$controller = &getController('textmessage');
		$output = $controller->sendMessage($args);

		if (!$output->toBool())
		{
			return $output;
		}
		$data = $output->get('data');
		$obj = $data[0];
		$message_id = $obj->message_id;
		$this->add('message_id', $message_id);

		$trigger_output = ModuleHandler::triggerCall ('authentication.procAuthenticationSendAuthCode', 'after', $args);
		if(!$trigger_output->toBool ()) return $trigger_output;

		$this->setMessage('인증번호를 발송하였습니다.');
	}

	function procAuthenticationVerifyAuthCode()
	{
		$reqvars = Context::getRequestVars();

		$authentication_srl = Context::get('authentication_srl');
		$args->authentication_srl = $authentication_srl;
		$output = executeQuery('authentication.getAuthentication', $args);
		if(!$output->toBool()) return $output;

		$authentication_1 = Context::get('authcode');
		$authentication_2 = $output->data->authcode;

		if($authentication_1 == $authentication_2)
		{
			$_SESSION['authentication_pass'] = 'Y';
			$args->passed = 'Y';
			$args->authentication_srl = $_SESSION['authentication_srl'];
			$output = executeQuery('authentication.updateAuthentication', $args);
			if(!$output->toBool()) return $output;
			$this->setMessage('인증이 완료되었습니다. 다음페이지로 이동합니다.');

			$reqvars->passed = 'Y';
			$reqvars->authentication_srl = $args->authentication_srl;
			$trigger_output = ModuleHandler::triggerCall ('authentication.procAuthenticationVerifyAuthCode', 'after', $reqvars);
			if(!$trigger_output->toBool ()) return $trigger_output;
		}
		else
		{
			$reqvars->passed = 'N';
			$reqvars->authentication_srl = $args->authentication_srl;
			$trigger_output = ModuleHandler::triggerCall ('authentication.procAuthenticationVerifyAuthCode', 'after', $reqvars);
			if(!$trigger_output->toBool ()) return $trigger_output;
			return new Object(-1,'인증코드가 올바르지 않습니다.');
		}
	}

	function procAuthenticationUpdateStatus() 
	{
		$oTextmessageModel = &getModel('textmessage');
		$oTextmessageController = &getController('textmessage');

		$message_id = Context::get('message_id');

		$sms = $oTextmessageModel->getCoolSMS();
		if (!$sms->connect()) return new Object(-2, 'warning_cannot_connect');
		$result = $sms->rcheck($message_id);
		$args->message_id = $message_id;
		$args->status = $result['STATUS'];
		$args->resultcode = $result['RESULT-CODE'];
		$args->carrier = $result['CARRIER'];
		$args->senddate = $result['SEND-DATE'];
		$oTextmessageController->updateStatus($args);
		$sms->disconnect();

		$this->add('result', $result);
	}

	function startAuthentication(&$oModule)
	{
		$oAuthenticationModel = &getModel('authentication');
		$config = $oAuthenticationModel->getModuleConfig();
		$config->agreement = $oAuthenticationModel->_getAgreement();
		if(Mobile::isFromMobilePhone())
		{
			$oModule->setTemplatePath(sprintf($this->module_path.'m.skins/%s/', $config->mskin));
		}
		else
		{
			$oModule->setTemplatePath(sprintf($this->module_path.'skins/%s/', $config->skin));
		}
		Context::set('target_action', $oModule->act);
		$oModule->setTemplateFile('index');

		if($config->authcode_time_limit)
		{
			Context::set('time_limit', $config->authcode_time_limit);
		}
		Context::set('number_limit', $config->number_limit);
		Context::set('config', $config);
	}

	/**
	 * @brief 모듈핸들러 실행 후 트리거 (애드온의 after_module_proc에 대응)
	 **/
	function triggerModuleHandlerProc(&$oModule)
	{
		$oAuthenticationModel = &getModel('authentication');
		$config = $oAuthenticationModel->getModuleConfig();

		$action_list = array_filter(explode(',',$config->list));

		if(in_array(Context::get('act'), $action_list) && $_SESSION['authentication_pass'] != 'Y')
		{
			$this->startAuthentication($oModule);
		}
		return new Object();
	}

	/*
	 * 회원가입후 member_srl과 인증정보들을 authentication_member table에 넣는다.
	 */
	function triggerMemberInsert(&$in_args)
	{
		if($_SESSION['authentication_srl'])
		{
			$args->authentication_srl = $_SESSION['authentication_srl'];
			$output = executeQuery('authentication.getAuthentication', $args);
			if(!$output->toBool()) return $output;
			$authinfo = $output->data;

			$args->authcode = $authinfo->authcode;
			$args->member_srl = $in_args->member_srl;
			$args->clue = $authinfo->clue;
			$args->country_code = $authinfo->country_code;
			$output = executeQuery('authentication.insertAuthenticationMember', $args);
			if(!$output->toBool()) return $output;
		}
	}

	/**
	 * this function will be triggered by member module after module.updateMember called.
	 */
	function triggerMemberUpdate(&$in_args)
	{
		if($_SESSION['authentication_srl'])
		{
			$args->authentication_srl = $_SESSION['authentication_srl'];
			$output = executeQuery('authentication.getAuthentication', $args);
			if(!$output->toBool()) return $output;
			$authinfo = $output->data;

			$args->authcode = $authinfo->authcode;
			$args->member_srl = $in_args->member_srl;
			$args->clue = $authinfo->clue;
			$args->country_code = $authinfo->country_code;

			$output = executeQuery('authentication.deleteAuthenticationMember', $args);
			if(!$output->toBool()) return $output;

			$output = executeQuery('authentication.insertAuthenticationMember', $args);
			if(!$output->toBool()) return $output;
		}
	}
}
/* End of file authentication.controller.php */
/* Location: ./modules/authentication/authentication.controller.php */
