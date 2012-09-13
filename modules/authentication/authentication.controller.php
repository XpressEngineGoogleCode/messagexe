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

		// check variables
		$phonenum = Context::get('phonenum');
		$country = Context::get('country');
		if(!$phonenum || !$country)
		{
			return new Object(-1, '국가 및 휴대폰 번호를 전부 입력해주세요.');
		}

		// generate auth-code
		$keystr = $this->getRandNumber($config->digit_number);

		$today = date("Ymd", mktime(0,0,0,date("m"),date("d"),date("Y")));

		$args->clue = $phonenum;
		$args->regdate = $today;
		$output = executeQuery('authentication.getAuthenticationByClue', $args);

		// check tries limit
		if($config->authcode_ban_limit)
		{
			if(is_array($output->data))
			{
				foreach($output->data as $k => $v)
				{
					if($v->authcode_ban == 'Y')
					{
						return new Object(-1, '잦은 인증번호 요청으로 금지되셨습니다. 1일뒤에 다시 시도해주십시오.');
					}
				}
			}
			/*
			
			if(!$_SESSION['authcode_clue'] || !$_SESSION['authcode_ban'])
			{
				$_SESSION['authcode_clue'] = $args->clue;
				$_SESSION['authcode_ban'] = 1;
			}

			if($args->clue == $_SESSION['authcode_srl'])
			{
				$_SESSION['authcode_ban'] = $_SESSION['authcode_ban'] + 1;
			}

			if($_SESSION['authcode_ban']  == $config->authcode_ban_limit)
			{
				$args->authcode_ban = 'Y';
				$_SESSION['authcode_ban'] = 1;
			}
			 */
		}

		unset($args->regdate);

		$args->country = $country;
		$args->authentication_srl = getNextSequence();
		$args->authcode = $keystr;

		if(!$config->authcode_time_limit || $config->authcode_time_limit < 10)
		{
			$send_time = date("ymdHis", mktime(date("H"), date("i"), date("s") + 20, date("m"), date("d"), date("y")));
		}
		else
		{
			$send_time = date("ymdHis", mktime(date("H"), date("i"), date("s") + $config->authcode_time_limit, date("m"), date("d"), date("y")));
		}
		

		$args->send_times = $send_time;

		$output = executeQuery('authentication.insertAuthentication', $args);
		if (!$output->toBool())
		{
			return $output;
		}

		$_SESSION['authentication_srl'] = $args->authentication_srl;
		$this->add('authentication_srl', $args->authentication_srl);
		Context::set('authentication_srl', $_SESSION['authentication_srl']);

		unset($args);

		$args->country = $country;
		$args->recipient_no =  $phonenum;
		$args->callback = '';
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

		$this->setMessage('인증번호를 발송하였습니다.');
	}

	function procAuthenticationVerifyAuthcode()
	{
		$authentication_srl = Context::get('authentication_srl');
		$args->authentication_srl = $authentication_srl;
		$output = executeQuery('authentication.getAuthentication', $args);
		if(!$output->toBool()) return $output;

		$authentication_1 = Context::get('authcode');
		$authentication_2 = $output->data->authcode;

		if($authentication_1 == $authentication_2)
		{
			$_SESSION['authentication_pass'] = 'Y';
			$args->authcode_pass = 'Y';
			$args->authentication_srl = $_SESSION['authentication_srl'];
			$output = executeQuery('authentication.updateAuthcodePass', $args);
			if(!$output->toBool()) return $output;
			$this->setMessage('인증이 완료되었습니다. 다음페이지로 이동합니다.');
		}
		else
		{
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
		$oModule->setTemplatePath(sprintf($this->module_path.'skins/%s/', $config->skin));
		$oModule->setTemplateFile('index');

		if($config->authcode_time_limit)
		{
			Context::set('time_limit', $config->authcode_time_limit);
		}
		
		Context::set('number_limit', $config->number_limit);

		if(!$config->country_code) $config->country_code = '82';

		Context::set('config', $config);
	}

	/**
	 * @brief 모듈핸들러 실행 후 트리거 (애드온의 after_module_proc에 대응)
	 **/
	function triggerModuleHandlerProc(&$oModule)
	{
		$args->module = 'authentication';
		$output = executeQuery('authentication.getModulesrl', $args);
		if(!$output->data) return;
		$module_srl  = $output->data->module_srl;

		$oModuleModel = &getModel('module');
		$list_config = $oModuleModel->getModulePartConfig('authentication', $module_srl);
		if(count($list_config) && in_array(Context::get('act'), $list_config) && $_SESSION['authentication_pass'] != 'Y')
		{
				$this->startAuthentication(&$oModule);
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

			$output = executeQuery('authentication.deleteAuthenticationMember', $args);
			if(!$output->toBool()) return $output;

			$output = executeQuery('authentication.insertAuthenticationMember', $args);
			if(!$output->toBool()) return $output;
		}
	}
}
/* End of file authentication.controller.php */
/* Location: ./modules/authentication/authentication.controller.php */
