<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
 * @class  newpostsController
 * @author wiley@nurigo.net
 * @brief  newpostsController
 */
class newpostsController extends newposts 
{

	function sendMessages($content, $mail_content, $title, $sender, $config) 
	{
		$oTextmessageController = &getController('textmessage');
		$oNewpostsModel = &getModel('newposts');

		if (in_array($config->sending_method,array('1','2'))&&$oTextmessageController) 
		{
			$args->recipient_no = explode(',',$config->admin_phones);
			//$args->sender_no = $receiver->recipient_no;
			$args->content = $content;
			$output = $oTextmessageController->sendMessage($args);
			if (!$output->toBool()) return $output;
		}

		if (in_array($config->sending_method,array('1','3'))) 
		{
			$title = $title;
			$oMail = new Mail();
			$oMail->setTitle($title);
			$oMail->setContent($mail_content);
			$oMail->setSender($sender->nick_name, $sender->email_address);
			$target_email = explode(',',$config->admin_emails);
			foreach ($target_email as $email_address) 
			{
				debugPrint('email address : ' . $email_address);
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

		// get document info.
		$oDocumentModel = &getModel('document');
		$oDocument = $oDocumentModel->getDocument($obj->document_srl);

		// title
		$title = $oDocument->getTitleText();

		$tmp_obj->article_url = getFullUrl('','document_srl', $obj->document_srl);
		$tmp_content = $this->mergeKeywords($mail_content, $tmp_obj);
		$tmp_message = $this->mergeKeywords($sms_message, $tmp_obj);
		$this->sendMessages($tmp_message, $tmp_content, $title, $sender, $config);
	}

	/**
	 * @brief trigger for document insertion.
	 * @param $obj : document object.
	 **/
	function triggerInsertDocument(&$obj) 
	{
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
