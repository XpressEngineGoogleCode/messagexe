<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
 * @class  reset_passwordController
 * @author NURIGO(contact@nurigo.net)
 * @brief  reset_passwordController
 */
class reset_passwordController extends reset_password 
{
	function genPassword ($length = 8)
	{
	  // given a string length, returns a random password of that length
	  $password = "";
	  // define possible characters
	  //$possible = "0123456789abcdfghjkmnpqrstvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
	  $possible = "0123456789abcdfghjkmnpqrstvwxyz";
	  $i = 0;
	  // add random characters to $password until $length is reached
	  while ($i < $length) {
		// pick a random character from the possible ones
		$char = substr($possible, mt_rand(0, strlen($possible)-1), 1);
		// we don't want this character if it's already in the password
		if (!strstr($password, $char)) {
		  $password .= $char;
		  $i++;
		}
	  }
	  return $password;
	}

	function procReset_passwordUpdatePassword()
	{
		if($_SESSION['authentication_pass'] == 'Y')
		{
            // Create a member model object
            $oMemberModel = &getModel('member');
            $oMemberController = &getController('member');

			// update password
			$current_password = Context::get('current_password');
			$password1 = Context::get('password1');
			$password2 = Context::get('password2');

            // Extract the necessary information in advance
            $current_password = trim(Context::get('current_password'));
            $password = trim(Context::get('password1'));
            // Get information of logged-in user
            $logged_info = $oMemberModel->getMemberInfoByUserId(Context::get('user_id'));
            $member_srl = $logged_info->member_srl;

            // Get information of member_srl
			$columnList = array('member_srl', 'password');
            $member_info = $oMemberModel->getMemberInfoByMemberSrl($member_srl, 0, $columnList);
            // Verify the cuttent password
            if(!$oMemberModel->isValidPassword($member_info->password, $current_password, $member_srl)) return new Object(-1, 'invalid_password');

            // Check if a new password is as same as the previous password
            if ($current_password == $password) return new Object(-1, 'invalid_new_password');

            // Execute insert or update depending on the value of member_srl
            $args->member_srl = $member_srl;
            $args->password = $password;
            $output = $oMemberController->updateMemberPassword($args);
            if(!$output->toBool()) return $output;

			$this->setMessage('비밀번호를 변경하였습니다.');
		}
	}

	function triggerAuthenticationSendAuthCode(&$in_args)
	{
		if($in_args->user_id)
		{
			$oMemberModel = &getModel('member');
			$member_info = $oMemberModel->getMemberInfoByUserID($in_args->user_id);
			if(!$member_info) return new Object(-1, '존재하지 않는 아이디입니다.');

			$oAuthenticationModel = &getModel('authentication');
			$authinfo = $oAuthenticationModel->getAuthenticationMember($member_info->member_srl);
			if($authinfo->clue != $in_args->phonenum) return new Object(-1, '아이디와 휴대폰번호가 일치하지 않습니다.');
		}
	}

	function triggerAuthenticationVerifyAuthCode(&$in_args)
	{
		$oMemberModel = &getModel('member');
		$oAuthenticationModel = &getModel('authentication');
		debugPrint('$in_args');
		debugPrint($in_args);

		if($in_args->user_id && $in_args->passed=='Y')
		{
			$member_info = $oMemberModel->getMemberInfoByUserID($in_args->user_id);
			if(!$member_info) return new Object(-1, '존재하지 않는 아이디입니다.');

			// get authentication info
			$authinfo = $oAuthenticationModel->getAuthenticationInfo($in_args->authentication_srl);

			// check a match of user_id and phone number
			$authmem = $oAuthenticationModel->getAuthenticationMember($member_info->member_srl);
			if($authmem->clue != $authinfo->clue) return new Object(-1, '아이디와 휴대폰번호가 일치하지 않습니다.');

			$tmp_password = $this->genPassword();
			$_SESSION['tmp_password'] = $tmp_password;

            // Execute insert or update depending on the value of member_srl
            $args->member_srl = $member_info->member_srl;
            $args->password = $tmp_password;
			$oMemberController = &getController('member');
            $output = $oMemberController->updateMemberPassword($args);
            if(!$output->toBool()) return $output;
		}
	}

}
/* End of file reset_password.controller.php */
/* Location: ./modules/reset_password/reset_password.controller.php */
