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
/*
		$aa = Context::get('mid');

		Context::set('authentication_pass', 1);
		$returnUrl = getNotEncodedUrl('','mid', $aa,  'act', 'dispMemberSignUpForm' );
		$this->setRedirectUrl($returnUrl);

 */
		$oAuthenticationView = &getView('authentication');
		$oAuthenticationModel = &getModel('authentication');
		
		$info = $oAuthenticationModel->getModuleConfig();

		$vars = Context::getRequestVars();

		if(!$vars->phone_1 || !$vars->phone_2 || !$vars->phone_3 || !$vars->country)
		{
			return new Object(-1, '국가 및 휴대폰 번호를 전부 입력해주세요.');
		}
		debugPrint('konm_1');
		debugPrint($vars);
		$key = rand(1, 99999999999);
		debugPrint($info->number_limit);

		$number_limit = intval($info->number_limit);
		if($info->number_limit)
		{
			$keystr = substr($key,0,$number_limit);
			debugPrint($keystr);
		}
		else 
		{
			$keystr = substr($key,0,5);
		}

		$today = date("Ymd", mktime(0,0,0,date("m"),date("d"),date("Y")));

		$args->clue = $vars->phone_1.$vars->phone_2.$vars->phone_3;
		$args->regdate = $today;
		$output = executeQuery('authentication.getAuthcodeClue', $args);

		if($info->authcode_ban_limit)
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

			if($_SESSION['authcode_ban']  == $info->authcode_ban_limit)
			{
				$args->authcode_ban = 'Y';
				$_SESSION['authcode_ban'] == 1;
			}
		}

		debugPrint('halp_2');
		debugPrint($output);

		unset($args->regdate);

		$args->country = $vars->country;
		$args->authentication_srl = getNextSequence();
		$args->authcode = $keystr;
		
		
		$output = executeQuery('authentication.insertAuthentication', $args);

		debugPrint($output);
		if (!$output->toBool())
		{
			debugPrint('chk_insert');
			return $output;
		}

		

		debugPrint('chk_1');
		$phone = array("phone_1" => $vars->phone_1, "phone_2" => $vars->phone_2, "phone_3" => $vars->phone_3);
		debugPrint('chk_2');

		if(!$_SESSION['phone'] || !$_SESSION['authentication_srl'] || !$_SESSION['country'])
		{
			debugPRint('chk_3');
			$_SESSION['country'] = $vars->country;
			$_SESSION['phone'] = $phone;
			$_SESSION['authentication_srl'] = $args->authentication_srl;
			$_SESSION['authentication_mid'] = $vars->authcode_mid;

			debugPRint('kon_6');
			debugPrint($vars);
		}
		debugPRint('chk_4');
		debugPRint($vars);


		$this->add('authentication_srl', $args->authentication_srl);
		$this->add('authcode_mid', $vars->authcode_mid);

		$authentication_srl = $args->authentication_srl;

		Context::set('authentication_srl', $_SESSION['authentication_srl']);

		unset($args);

		$args->country = $vars->country;
		$args->recipient_no =  $vars->phone_1.$vars->phone_2.$vars->phone_3;
		$args->callback = $vars->phone_1.$vars->phone_2.$vars->phone_3;
		if($info->message_content)
		{
			$content = str_replace(array("[authcode]"),array($keystr),$info->message_content);
			$args->content = $content;
		}
		else
		{
			$args->content = $keystr;
		}
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

		debugPrint('kon_99');
		debugPrint($_SESSION['country'] );
		debugPrint($_SESSION['phone']);
		debugPrint($_SESSION['authentication_mid']);
		debugPrint($_SESSION['authentication_srl']);

		if(!$_SESSION['phone'] || !$_SESSION['authentication_srl'] || !$_SESSION['country'])
		{
			return new Object(-1, '인증번호를 전송 받으십시오.');
		}


		debugPrint('kon_1');
		debugPrint($_SESSION['authentication_mid']);
		$all_args = Context::getRequestVars();

		$authentication_srl = Context::get('authentication_srl');

		$args->authentication_srl = $authentication_srl;
		$output = executeQuery('authentication.getAuthentication', $args);

		debugPrint('ko_33');
		debugPrint($authentication_srl);
		if(!$output->toBool())
		{
			return $output;
		}

		$authentication_1 = Context::get('authcode');
		$authentication_2 = $output->data->authcode;

		if($authentication_1 == $authentication_2)
		{
			$_SESSION['authentication_pass'] = 'Y';

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
			debugPrint('kon_4');
			debugPrint($_SESSION['authentication_mid']);
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

	function authcodeStartSet(&$oModule)
	{
		$config->skin = 'default';
		$addon_tpl_path = sprintf('./modules/authentication/skins/%s/', $config->skin);
		$addon_tpl_file = 'index.html';
				
		$oModule->setTemplatePath($addon_tpl_path);
		$oModule->setTemplateFile($addon_tpl_file);

		$oAuthenticationModel = &getModel('authentication');
		$oMemberModel = &getModel('member');

		$info = $oAuthenticationModel->getModuleConfig();

		if($_SESSION['phone'])
		{
			$phone = $_SESSION['phone'];
		}
		Context::set('number_limit', $info->number_limit);

		if(!$_SESSION['country'])
		{
			Context::set('country', $info->country_code);
		}
		else
		{
			Context::set('country', $_SESSION['country']);
		}
		Context::set('authcode_mid', Context::get('mid'));
		Context::set('phone_1', $phone[phone_1]);
		Context::set('phone_2', $phone[phone_2]);
		Context::set('phone_3', $phone[phone_3]);
	}

	function validateAuthCode()
	{
	}

	/**
	 * @brief 모듈핸들러 실행 후 트리거 (애드온의 after_module_proc에 대응)
	 **/
	function triggerModuleHandlerProc(&$oModule)
	{
		$args->module = 'authentication';
		$output = executeQuery('authentication.getModulesrl', $args);
		if(!$output->data)
		{
			return new Object(-1, 'module_srl이 없습니다');
		}
		$module_srl  = $output->data->module_srl;
		$oModuleModel = &getModel('module');
		$list_config = $oModuleModel->getModulePartConfig('authentication', $module_srl);

		debugPrint('komn_33');
		debugPrint($list_config);
		if($list_config)
		{
			foreach($list_config as $k => $v)
			{
				if(Context::get('act') == 'dispMemberModifyInfo' && $_SESSION['authentication_update'] != 'Y' && $v == 'dispMemberModifyInfo')
				{
					debugPrint('konm_34');
					debugPrint($v);
					$logged_info = Context::get('logged_info');

					$args->member_srl = $logged_info->member_srl;
					debugPrint('ko=4');
					debugPrint($args);
					debugPrint(&$oMoulde);
					debugPrint(Context::get('member_srl'));
					debugPrint($logged_info);

					$output = executeQuery('authentication.getAuthcodeMembersrl', $args);

					if(!$output->toBool())
					{
						return $output;
					}

					debugPrint($output);

					$this->authcodeStartSet(&$oModule);
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
						unset($_SESSION['authentication_pass']);
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
				debugPrint('mko_2');
				debugPrint($k);
				debugPrint($v);
				if($v->authcode_pass == 'Y')
				{
					debugPrint('kol_3');
					debugPrint($v->authcode);
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
		debugPrint('triggerAfter_1');
		debugPrint($_SESSION['phone']);
		debugPrint($_SESSION['authentication_srl']);
		debugPrint($_SESSION['authcode_member_srl']);
		
	}


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
		debugPrint('trigger_update_1');
		debugPrint($args);
	}


}
?>
