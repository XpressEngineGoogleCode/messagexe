<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
 * @class  newpostsController
 * @author wiley@nurigo.net
 * @brief  newpostsController
 */
class newpostsController extends newposts 
{

	function sendMessages($content, $mail_content, $obj, $sender, $config) 
	{

		// get Phone# & email address accoring to category admin from newposts_admins table
		$args->category_srl = $obj->category_srl;
		$output = executeQuery("newposts.getAdminInfo", $args);

		$oTextmessageController = &getController('textmessage');
		$oNewpostsModel = &getModel('newposts');

		if (in_array($config->sending_method,array('1','2'))&&$oTextmessageController) 
		{
			// $args 확인후 전송번호 없을시 문자 보내지말것 
			// $config->admin_phones 를 $output->cellphone 으로 변경 
			$args->recipient_no = explode(',',$output->data->cellphone);
			//$args->sender_no = $receiver->recipient_no;
			$args->content = $content;
			$args->sender_no = $config->sender_phone;
			if(!empty($args->recipient_no[0]))
			{	
				$output = $oTextmessageController->sendMessage($args);
				if (!$output->toBool()) return $output;
			}

			//전체관리자 모드 : 분류에 상관없이 보냄
			//$args->recipient_no = explode(',',$config->admin_phones);
			$args->recipient_no[0] = str_replace('|@|', '', $obj->extra_vars1);
			//$args->sender_no = $receiver->recipient_no;
			$args->content = $content;
			debugPrint($args);
			if(!empty($args->recipient_no[0]))
			{
			//	$output = $oTextmessageController->sendMessage($args);
			//	if (!$output->toBool()) return $output;
			}
			
		}
	
		if (in_array($config->sending_method,array('1','3'))) 
		{
			if ($config->sender_email)
			{
				$sender_email_address = $config->sender_email;
			}
			else
			{
				$sender_email_address = $sender->email_address;
			}
			if ($config->sender_name)
			{
				$sender_name = $config->sender_name;
			}
			else
			{
				$sender_name = $sender->nick_name;
			}
			$oMail = new Mail();
			$oMail->setTitle($obj->title);
			$oMail->setContent($mail_content);
			$oMail->setSender($sender_name, $sender_email_address);
			// $config->admin_emails 를 $output->email 로 변경
			//$target_email = explode(',',$output->email);
			$target_email[0] = $obj->email_address;
			debugPrint($target_email);
			foreach ($target_email as $email_address) 
			{
				$email_address = trim($email_address);
				if (!$email_address) continue;
				$oMail->setReceiptor($email_address, $email_address);
				$oMail->send();
			}
			//전체관리자 Send 
			$target_email = explode(',',$config->admin_emails);
			
			foreach ($target_email as $email_address) 
			{
				$email_address = trim($email_address);
				if (!$email_address) continue;
				$oMail->setReceiptor($email_address, $email_address);
				$oMail->send();
			}
		}
	}

	function processNewposts(&$config,&$obj,&$sender,&$module_info) 
	{
		$oMemberModel = &getModel('member');
		// message content
		$sms_message = $this->mergeKeywords($config->content, $obj);
		$sms_message = $this->mergeKeywords($sms_message, $module_info);
		$sms_message = str_replace("&nbsp;", "", strip_tags($sms_message));
		// mail content
		$mail_content = $this->mergeKeywords($config->mail_content, $obj);
		$mail_content = $this->mergeKeywords($mail_content, $module_info);
/*
		// get document info.
		$oDocumentModel = &getModel('document');
		$oDocument = $oDocumentModel->getDocument($obj->document_srl);
		debugPrint('oDocument : ' . serialize($oDocument));
*/
		$tmp_obj->article_url = getFullUrl('','document_srl', $obj->document_srl);
		$tmp_content = $this->mergeKeywords($mail_content, $tmp_obj);
		$tmp_message = $this->mergeKeywords($sms_message, $tmp_obj);

		// 기존 $obj->title 을 $obj 으로 변경 
		$this->sendMessages($tmp_message, $tmp_content, $obj, $sender, $config);
	}

	/**
	 * @brief trigger for document insertion.
	 * @param $obj : document object.
	 **/
	function triggerInsertDocument(&$obj) 
	{
	//	debugPrint('triggerInsertDocument obj : ' . serialize($obj));
		$oMemberModel = &getModel('member');

		// if module_srl not set, just return with success;
		if (!$obj->module_srl) 
		{
			return;
		}

		// if module_srl is wrong, just return with success
		$args->module_srl = $obj->module_srl;
		$output = executeQuery('module.getMidInfo', $args);
		if (!$output->toBool() || !$output->data) 
		{
			return;
		}
		$module_info = $output->data;
		unset($args);
		if (!$module_info) 
		{
			return;
		}

		// check login.
		$sender = new StdClass();
		$sender->nick_name = $obj->nick_name;
		$sender->email_address = $obj->email_address;
		$logged_info = Context::get('logged_info');
		if ($logged_info) 
		{
			$sender = $logged_info;
		}

		// get configuration info. no configuration? just return.
		$oModel = &getModel('newposts');
		$config_list = $oModel->getConfigListByModuleSrl($obj->module_srl);
		if (!$config_list) 
		{
			return;
		}

		foreach ($config_list as $key=>$val) 
		{
			$this->processNewposts($val,$obj,$sender,$module_info);
		}
	}
}
?>
