<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
 * @class  authenticationController
 * @author NURIGO(contact@nurigo.net)
 * @brief  authenticationController
 */
class authenticationController extends authentication 
{
	function procAuthenticationSendAuthCode()
	{

		$oAuthenticationModel = &getModel('authentication');
		$config = $oAuthenticationModel->getModuleConfig();

		// check variables
		$vars = Context::getRequestVars();
		if(!$vars->phone_1 || !$vars->phone_2 || !$vars->phone_3 || !$vars->country)
		{
			return new Object(-1, '국가 및 휴대폰 번호를 전부 입력해주세요.');
		}

		// generate auth-code
		$key = rand(1, 99999999999);
		$number_limit = intval($config->number_limit);
		if($config->number_limit)
		{
			$keystr = substr($key,0,$number_limit);
		}
		else 
		{
			$keystr = substr($key,0,5);
		}

		$today = date("Ymd", mktime(0,0,0,date("m"),date("d"),date("Y")));

		$args->clue = $vars->phone_1.$vars->phone_2.$vars->phone_3;
		$args->regdate = $today;
		$output = executeQuery('authentication.getAuthcodeClue', $args);

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
		}

		unset($args->regdate);

		$args->country = $vars->country;
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

		$phone = array("phone_1" => $vars->phone_1, "phone_2" => $vars->phone_2, "phone_3" => $vars->phone_3);

		if(!$_SESSION['phone'] || !$_SESSION['authentication_srl'] || !$_SESSION['country'])
		{
			$_SESSION['country'] = $vars->country;
			$_SESSION['phone'] = $phone;
			$_SESSION['authentication_srl'] = $args->authentication_srl;
			$_SESSION['authentication_mid'] = $vars->authcode_mid;
		}

		$this->add('authentication_srl', $args->authentication_srl);
		$this->add('authcode_mid', $vars->authcode_mid);

		$authentication_srl = $args->authentication_srl;

		Context::set('authentication_srl', $_SESSION['authentication_srl']);

		unset($args);

		$args->country = $vars->country;
		$args->recipient_no =  $vars->phone_1.$vars->phone_2.$vars->phone_3;
		$args->callback = $vars->phone_1.$vars->phone_2.$vars->phone_3;
		if($config->message_content)
		{
			$content = str_replace(array("%authcode%"),array($keystr),$config->message_content);
			$args->content = $content;
		}
		else
		{
			$args->content = $keystr;
		}
		$args->encode_utf16 = $encode_utf16; 
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

	function procAuthenticationCompare()
	{
		if(!$_SESSION['phone'] || !$_SESSION['authentication_srl'] || !$_SESSION['country'])
		{
			return new Object(-1, '인증번호를 전송 받으십시오.');
		}

		$all_args = Context::getRequestVars();

		$authentication_srl = Context::get('authentication_srl');

		$args->authentication_srl = $authentication_srl;
		$output = executeQuery('authentication.getAuthentication', $args);
		if(!$output->toBool())
		{
			return $output;
		}

		$authentication_1 = Context::get('authcode');
		$authentication_2 = $output->data->authcode;

		if($authentication_1 == $authentication_2)
		{
			$_SESSION['authentication_pass'] = 'Y';
			debugPrint('aaaaaaaaa');
			debugPrint($_SESSION['authentication_pass']);

			$args->authcode_pass = 'Y';
			$args->authentication_srl = $_SESSION['authentication_srl'];
			$output = executeQuery('authentication.updateAuthcodePass', $args);
			if(!$output->toBool())
			{
				return $output;
			}

			if($_SESSION['authentication_update'])
			{
				$_SESSION['authentication_update'] = 'Y';

				$returnUrl = getNotEncodedUrl('', 'mid', $_SESSION['authentication_mid'], 'act', 'dispMemberModifyInfo');
			}
			else
			{
				$returnUrl = getNotEncodedUrl('', 'mid', $_SESSION['authentication_mid'], 'act', 'dispMemberSignUpForm');
			}

			unset($_SESSION['country']);
			unset($_SESSION['authentication_mid']);

			$this->setRedirectUrl($returnUrl);
		}
		else
		{
			return new Object(-1,'인증코드가 올바르지 않습니다.');
			/*
			$this->setError(-1);
			$this->setMessage('인증코드가 올바르지 않습니다.');
			$returnUrl = getNotEncodedUrl('', 'mid', $_SESSION['authentication_mid'], 'act', 'dispMemberSignUpForm', 'authentication_srl', $authentication_srl);
			$this->setRedirectUrl($returnUrl);
			 */
		}
	}

	function procAuthenticationUpdateStatus() 
	{
		if(!$_SESSION['phone'] || !$_SESSION['authentication_srl'] || !$_SESSION['country'])
		{
			return new Object(-1, '인증번호를 전송 받으십시오.');
		}
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

	function authcodeStartSet(&$oModule)
	{
		$oAuthenticationModel = &getModel('authentication');
		$config = $oAuthenticationModel->getModuleConfig();
		$oModule->setTemplatePath(sprintf($this->module_path.'skins/%s/', $config->skin));
		$oModule->setTemplateFile('index');

		if($config->authcode_time_limit)
		{
			Context::set('time_limit', $config->authcode_time_limit);
		}
		if($_SESSION['phone'])
		{
			$phone = $_SESSION['phone'];
		}
		
		Context::set('number_limit', $config->number_limit);

		if(!$_SESSION['country'])
		{
			if($config->country_code)
			{
				Context::set('country', $config->country_code);
			}
			else
			{
				Context::set('country', '82');
			}
		}
		else
		{
			Context::set('country', $_SESSION['country']);
		}
		
		Context::set('authcode_mid', Context::get('mid'));
		Context::set('phone_1', $phone[phone_1]);
		Context::set('phone_2', $phone[phone_2]);
		Context::set('phone_3', $phone[phone_3]);

		if($_SESSION['time_before'])
		{
			Context::set('time_before', $_SESSION['time_before']);
		}

		$output = executeQueryArray('authentication.getAuthenticationSend_time');

		if($output ->data)
		{
			foreach($output->data as $k => $v)
			{
				$send_time = $v->send_time;
			}
		}
		Context::set('send_time', $send_time);

		unset($_SESSION['message_id']);

		if($_SESSION['message_id'])
		{
			Context::set('message_id', $_SESSION['message_id']);
		}
	}

	/**
	 * @brief 모듈핸들러 실행 후 트리거 (애드온의 after_module_proc에 대응)
	 **/
	function triggerModuleHandlerProc(&$oModule)
	{
		debugPrint('$_SESSION[authentication_pass]');
		debugPrint($_SESSION['authentication_pass']);
		$args->module = 'authentication';
		$output = executeQuery('authentication.getModulesrl', $args);
		if(!$output->data)
		{
			return;
		}
		$module_srl  = $output->data->module_srl;
		$oModuleModel = &getModel('module');
		$list_config = $oModuleModel->getModulePartConfig('authentication', $module_srl);

		if($list_config)
		{
			foreach($list_config as $k => $v)
			{
				// 회원정보수정시
				if(Context::get('act') == 'dispMemberModifyInfo' && $_SESSION['authentication_update'] != 'Y' && $v == 'dispMemberModifyInfo')
				{
					$this->authcodeStartSet(&$oModule);

					$logged_info = Context::get('logged_info');

					$args->member_srl = $logged_info->member_srl;

					$output = executeQuery('authentication.getAuthcodeMembersrl', $args);

					if(!$output->toBool())
					{
						return $output;
					}

					$_SESSION['authentication_update'] = 'N';

				}

				if(Context::get('act') == "dispMemberModifyInfo" && $_SESSION['authentication_update'] == 'Y' && $v == 'dispMemberModifyInfo')
				{
					unset($_SESSION['authentication_update']);
				}

				// 회원가입시
				if(Context::get('act') == "dispMemberSignUpForm" && !$_SESSION['authentication_pass'] == 'Y' && $v == 'dispMemberSignUpForm')
				{
					$this->authcodeStartSet(&$oModule);
				}
				else
				{
					if(!$_SESSION['XE_VALIDATOR_RETURN_URL'] && $_SESSION['authentication_pass'] == 'Y')
					{
						//unset($_SESSION['authentication_pass']);
						debugPrint('unset');
					}

				}
			}
		}
		return new Object();
	}
	function triggerMembersrlGet(&$args)
	{
		$_SESSION['authcode_member_srl'] = $args->member_srl;
	}

	/*
	 * 회원가입후 member_srl과 인증정보들을 authentication_history table에 넣는다.
	 */
	function triggerMemberInsertAfter(&$args)
	{
		if($_SESSION['phone'] && $_SESSION['authentication_srl'] && $_SESSION['authcode_member_srl'])
		{
			$args->clue = $vars->phone_1.$vars->phone_2.$vars->phone_3;
			$args->regdate = $today;
			$args->authentication_srl = $_SESSION['authentication_srl'];
			$output = executeQuery('authentication.getAuthentication', $args);
			if(!$output->toBool())
			{
				return $output;
			}

			foreach($output as $k => $v)
			{
				if($v->authcode_pass == 'Y')
				{
					$authcode = $v->authcode;	
				}
			}
			unset($args->regdate);

			$args->authcode = $authcode;
			$args->member_srl = $_SESSION['authcode_member_srl'];
			$args->clue = $_SESSION['phone']['phone_1'] . $_SESSION['phone']['phone_2'] . $_SESSION['phone']['phone_3'];
			$output = executeQuery('authentication.insertAuthcodeMembersrl', $args);

			if(!$output->toBool())
			{
				return $output;
			}
			else
			{
				unset($_SESSION['phone']);
				unset($_SESSION['authentication_srl']);
				unset($_SESSION['authcode_member_srl']);
			}

		}
	}


	/*
	 * 발송체크
	 */
	function triggerMemberUpdateAfter(&$args)
	{
		if(Context::get('act') == "dispMemberModifyInfo")
		{
			$config->skin = 'default';
			$addon_tpl_path = sprintf('./modules/authentication/skins/%s/', $config->skin);
			$addon_tpl_file = 'index.html';
					
			$oModule->setTemplatePath($addon_tpl_path);
			$oModule->setTemplateFile($addon_tpl_file);

		}
	}
}
/* End of file authentication.controller.php */
/* Location: ./modules/authentication/authentication.controller.php */
