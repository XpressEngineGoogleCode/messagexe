<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
 * @class  authenticationController
 * @author wiley@nurigo.net
 * @brief  authenticationController
 */
class authenticationController extends authentication 
{
	function procAuthenticationSendAuthCode()
	{
		session_start();

		if($_COOKIE['authcode_overlap'] == 5)
		{
			return new Object(-1, '인증번호 보낸 횟수 초과(1시간 뒤 사용 가능)');
		}

		if(!$_COOKIE['authcode_overlap'])
		{
			setcookie('authcode_overlap',1, time()+3600);
		}

		else if($_COOKIE['authcode_overlap'])
		{
			setcookie('authcode_overlap',$_COOKIE['authcode_overlap'] + 1, time()+3600);
		}

		if($_COOKIE['authcode_overlap'] > 5)
		{
			setcookie('authcode_overlap',5, time()+3600);
		}
/*
		$aa = Context::get('mid');

		Context::set('authentication_pass', 1);
		$returnUrl = getNotEncodedUrl('','mid', $aa,  'act', 'dispMemberSignUpForm' );
		$this->setRedirectUrl($returnUrl);

 */
		$oAuthenticationView = &getView('authentication');
		$vars = Context::getRequestVars();

		$key = rand(1, 99999);
		$keystr = sprintf("%05d", $key);

		$args->country = $vars->country;
		$args->authentication_srl = getNextSequence();
		$args->expire = date("j",time()).date("i",time());
		$args->authcode = $keystr;
		$args->clue = $vars->phone_1.$vars->phone_2.$vars->phone_3;

		$output = executeQuery('authentication,getAuthcodePass', $args);

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
		}

		$this->add('authentication_srl', $args->authentication_srl);
		$this->add('authcode_mid', $vars->authcode_mid);

		$authentication_srl = $args->authentication_srl;

		Context::set('authentication_srl', $_SESSION['authentication_srl']);

		unset($args);

		$args->country = $vars->country;
		$args->recipient_no =  $vars->phone_1.$vars->phone_2.$vars->phone_3;
		$args->callback = $vars->phone_1.$vars->phone_2.$vars->phone_3;
		$args->content = $keystr;
		$args->encode_utf16 = $encode_utf16; 
		$controller = &getController('textmessage');
		$output = $controller->sendMessage($args);


		debugPrint('opw_1');
		debugPrint($args);
		debugPRint($output);
		debugPrint($this);

		if (!$output->toBool())
		{
			return $output;
		}

		$msgid = $output->variables[data][0]->message_id;
		$args->message_id = $msgid;
		$this->add('message_id', $msgid);
		
/*
		$returnUrl = getNotEncodedUrl('', 'mid', Context::get('mid'), 'act', 'dispAuthenticationCompare', 'authentication_srl', $authentication_srl, 'phone', $phone);
		$this->setRedirectUrl($returnUrl);
		 */
	}

	function procAuthenticationCompare()
	{
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
			setcookie('authcode_overlap','', time()-90000);
			$_SESSION['authentication_pass'] = 'Y';

			$args->elision = 'Y';
			$args->authentication_srl = $_SESSION['authentication_srl'];
			$output = executeQuery('authentication.updateElision', $args);
			if(!$output->toBool())
			{
				return $output;
			}

			unset($_SESSION['country']);
			unset($_SESSION['phone']);
			unset($_SESSION['authentication_mid']);
			unset($_SESSION['authentication_srl']);

			$returnUrl = getNotEncodedUrl('', 'mid', $all_args->authcode_mid, 'act', 'dispMemberSignUpForm');
			$this->setRedirectUrl($returnUrl);
		}
		else
		{
			$this->setError(-1);
			$this->setMessage('인증코드가 올바르지 않습니다.');
			$returnUrl = getNotEncodedUrl('', 'mid', $all_args->authcode_mid, 'act', 'dispMemberSignUpForm', 'authentication_srl', $authentication_srl);
			$this->setRedirectUrl($returnUrl);
		}
	}

	function validateAuthCode()
	{
	}

	/**
	 * @brief 모듈핸들러 실행 후 트리거 (애드온의 after_module_proc에 대응)
	 **/
	function triggerModuleHandlerProc(&$oModule)
	{
		// 회원가입시
		if(Context::get('act') == "dispMemberSignUpForm" && !$_SESSION['authentication_pass'] == 'Y')
		{
			$config->skin = 'default';
			$addon_tpl_path = sprintf('./modules/authentication/skins/%s/', $config->skin);
			$addon_tpl_file = 'index.html';
					
			$oModule->setTemplatePath($addon_tpl_path);
			$oModule->setTemplateFile($addon_tpl_file);

			if($_SESSION['phone'])
			{
				$phone = $_SESSION['phone'];
			}

			Context::set('country', $_SESSION['country']);
			Context::set('authcode_mid', Context::get('mid'));
			Context::set('phone_1', $phone[phone_1]);
			Context::set('phone_2', $phone[phone_2]);
			Context::set('phone_3', $phone[phone_3]);
		}
		else
		{
			if(!$_SESSION['XE_VALIDATOR_RETURN_URL'] && $_SESSION['authentication_pass'] == 'Y')
			{
				unset($_SESSION['authentication_pass']);
			}

		}
	
		return new Object();
	}

	function procAuthenticationUpdateStatus() 
	{
			$oTextmessageModel = &getModel('textmessage');
			$oTextmessageController = &getController('textmessage');

			$message_id = Context::get('message_id');

			$sms = $oTextmessageModel->getCoolSMS();
			if (!$sms->connect()) return new Object(-2, 'warning_cannot_connect');
			debugPrint('lmp_1');
			debugPrint($message_id);
			$result = $sms->rcheck($message_id);
			debugPrint('lmp_2');
			debugPRint($result);
			$args->message_id = $message_id;
			$args->status = $result['STATUS'];
			$args->resultcode = $result['RESULT-CODE'];
			$args->carrier = $result['CARRIER'];
			$args->senddate = $result['SEND-DATE'];
			$oTextmessageController->updateStatus($args);
			$sms->disconnect();

			$this->add('result', $result);
	}

}
?>
