<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
 * @class  notificationController
 * @author contact@nurigo.net
 * @brief  notificationController
 */
class notificationController extends notification
{
	/**
	 * send SMS
	 */
	function sendMobileMessage($recipientNumber, $senderNumber, $message)
	{
		$oTextmessageController = &getController('textmessage');
		if(!$oTextmessageController) return;

		$args->recipient_no = $recipientNumber;
		$args->sender_no = $senderNumber;
		$args->content = $message;
		$output = $oTextmessageController->sendMessage($args);
		if(!$output->toBool()) return $output;
	}

	/**
	 * send email
	 */
	function sendMailMessage($title, $message, $recipientName, $recipientEmailAddress, $senderName, $senderEmailAddress)
	{
		$oMail = new Mail();
		$oMail->setTitle($title);
		$oMail->setContent($message);
		$oMail->setSender($senderName, $senderEmailAddress);
		$oMail->setReceiptor($recipientName, $recipientEmailAddress);
		$oMail->send();
	}

	/**
	 * get phone number from authentication module's table.
	 */
	function getRecipientNumberFromAuthentication($member_srl)
	{
		$oAuthenticationModel = &getModel('authentication');
		if(!$oAuthenticationModel) return;
		$authinfo = $oAuthenticationModel->getAuthenticationMember($member_srl);
		if($authinfo) return $authinfo->clue;
	}

	/**
	 * get writer's phone number
	 */
	function getRecipientNumberForWriter(&$member_info, &$oDocument, &$config)
	{
		// get the references of modules' instances
		$oNotificationModel = &getModel('notification');

		// search from member data fields
		if(isset($config->cellphone_fieldname) && $member_info)
		{
			return $oNotificationModel->getConfigValue($member_info, $config->cellphone_fieldname, 'tel');
		}

		// search from authentication module table.
		if($config->use_authdata=='Y' && $member_info)
		{
			return $this->getRecipientNumberFromAuthentication($member_info->member_srl);
		}

		// search from document's extra vars
		if($config->use_extravar)
		{
			return $oDocument->getExtraValue($config->use_extravar);
		}

		return NULL;
	}

	/**
	 * get upper replier's phone number
	 */
	function getRecipientNumberForUpperReplier(&$member_info, &$config)
	{
		// get the references of modules' instances
		$oNotificationModel = &getModel('notification');

		// search from member data fields
		if(isset($config->cellphone_fieldname) && $member_info && $member_info->member_srl)
		{
			return $oNotificationModel->getConfigValue($member_info, $config->cellphone_fieldname, 'tel');
		}

		// search from authentication module table.
		if($config->use_authdata=='Y' && $member_info && $member_info->member_srl)
		{
			return $this->getRecipientNumberFromAuthentication($member_info->member_srl);
		}

		return NULL;
	}

	/**
	 * get writer's email address
	 */
	function getEmailAddressForWriter(&$member_info, &$oDocument, &$config)
	{
		$emailAddress = NULL;
		if($member_info) $emailAddress = $member_info->email_address;

		// search from document's extra vars
		if($config->use_extravar_email)
		{
			$emailAddress = $oDocument->getExtraValue($config->use_extravar_email);
		}

		return $emailAddress;
	}

	/**
	 * get upper replier's email address
	 */
	function getEmailAddressForUpperReplier(&$upperReplier)
	{
		$emailAddress = NULL;
		if($member_info) $emailAddress = $member_info->email_address;
		if(!$emailAddress) $emailAddress = $upperReplier->email_address;

		return $emailAddress;
	}

	/**
	 * get writer's nick name
	 */
	function getWriterNickName(&$member_info, &$oDocument)
	{
		$nickName = NULL;
		if($member_info) $nickName = $member_info->nick_name;
		if(!$nickName) $nickName = $oDocument->getNickName();

		return $nickName;
	}

	/**
	 * get writer's nick name
	 */
	function getNickNameForUpperReplier(&$upperReplier, &$oDocument)
	{
		$nickName = NULL;
		if($member_info) $nickName = $member_info->nick_name;
		if(!$nickName) $nickName = $oDocument->getNickName();

		return $nickName;
	}

	/**
	 * @param $receiver contains the member information. (like a logged_info)
	 */
	function sendMessages($recipientNumber, $senderNumber
							, $recipientEmailAddress, $recipientName, $senderEmailAddress, $senderName
							, $mobileContent, $mailContent, $title, &$config)
	{
		// SMS
		if(!is_array($recipientNumber)) $recipientNumber = array($recipientNumber);
		if(in_array($config->sending_method,array('1','2')))
		{
			foreach($recipientNumber as $phoneNumber)
			{
				if(!$phoneNumber) continue;
				$this->sendMobileMessage($phoneNumber, $senderNumber, $mobileContent);
			}
		}

		// MAIL
		if(!is_array($recipientEmailAddress)) $recipientEmailAddress = array($recipientEmailAddress);
		if(in_array($config->sending_method,array('1','3')))
		{
			foreach($recipientEmailAddress as $emailAddress)
			{
				$this->sendMailMessage($title, $mailContent, $recipientName, $emailAddress, $senderName, $senderEmailAddress);
			}
		}
	}

	/**
	 * @return TRUE(notification required) or FALSE(notification not required)
	 */
	function checkNotificationRequired(&$commentInfo, &$oDocument, &$config)
	{
		$notificationRequired = TRUE;

		// 1. 게시물의 알림이 체크되어 있으면 발송
		if($oDocument->useNotify())
		{
			$notificationRequired = TRUE;
		}
		else
		{
			$notificationRequired = FALSE;
		}

		// 2. 역알림 사용이면서 현재 comment의 notify_message가 'Y'이면 발송 
		if($config->reverse_notify == 'Y')
		{
			if($commentInfo->notify_message == 'Y') 
			{
				$notificationRequired = TRUE;
			}
			else
			{
				$notificationRequired = FALSE;
			}
		}

		// 3. force_notify설정이 Y이면 1, 2 무시하고 무조건 보냄
		if($config->force_notify == 'Y') $notificationRequired = TRUE;

		return $notificationRequired;
	}

	/**
	 * check for sending to writer
	 */
	function checkNotificationRequiredForWriter(&$commentInfo, &$oDocument, &$config)
	{
		// check basical things.
		$notificationRequired = $this->checkNotificationRequired($commentInfo, $oDocument, $config);

		// do not send messages if the writer and the replier is the same person.
		if($commentInfo->member_srl != 0 && $oDocument->get('member_srl') == $commentInfo->member_srl) $notificationRequired = FALSE;

		return $notificationRequired;
	}

	/**
	 * @return upper comment information.
	 */
	function getUpperComment(&$commentInfo)
	{
		if(!$commentInfo->parent_srl) return NULL;

		// get the references of module MVC instances.
		$oCommentModel = &getModel('comment');

		// get upper replier information using parent_srl.
		return $oCommentModel->getComment($commentInfo->parent_srl);
	}

	/**
	 * @return the member_info object of the upper replier.
	 */
	function getCommentMemberInfo(&$commentInfo)
	{
		$memberInfo = NULL;

		// get member information.
		$oMemberModel = &getModel('member');
		$member_srl = $commentInfo->getMemberSrl();
		if($member_srl) $memberInfo = $oMemberModel->getMemberInfoByMemberSrl($member_srl);

		// for comment from guest
		if(!$memberInfo)
		{
			$memberInfo = new stdClass();
			$memberInfo->nick_name = $commentInfo->nick_name;
			$memberInfo->email_address = $commentInfo->email_address;
		}

		return $memberInfo;
	}

	/**
	 * @return the member_info object of the upper replier.
	 */
	function getUpperReplier(&$commentInfo)
	{
		if(!$commentInfo->parent_srl) return NULL;

		// get the references of module MVC instances.
		$oMemberModel = &getModel('member');

		// get upper comment
		$upperComment = $this->getUpperComment($commentInfo);
		if(!$upperComment) return NULL;

		// get member information.
		$member_srl = $upperComment->getMemberSrl();
		$memberInfo = $oMemberModel->getMemberInfoByMemberSrl($member_srl);

		return $memberInfo;
	}

	/**
	 * check for sending to upper replier
	 */
	function checkNotificationRequiredForUpperReplier(&$commentInfo, &$upperComment, &$oDocument, &$config)
	{
		if(!$commentInfo->parent_srl) return FALSE;

		// check notification required.
		$notificationRequired = $this->checkNotificationRequired($commentInfo, $oDocument, $config);

		// 상위댓글자가 본인이면 보내지 않음
		if($upperComment->member_srl == $commentInfo->member_srl) $notificationRequired = FALSE;

		// 게시자와 상위댓글자가 같으면 보내지 않음.(중복으로 보내지 않음)
		if($oDocument->getMemberSrl() == $commentInfo->member_srl) $notificationRequired = FALSE;

		return $notificationRequired;
	}

	/**
	 * send to administrator
	 */
	function sendToAdministrator($mobileContent, $mailContent, $title, &$commentInfo, &$config)
	{
		if(!$config->admin_phones) return;
		$recipientNumber = explode(',', $config->admin_phones);
		$senderNumber = $config->sender_phone;
		$recipientEmailAddress = explode(',', $config->admin_emails);
		$senderEmailAddress = $config->email_sender_address;
		$senderName = $config->email_sender_name;
		if(!$senderEmailAddress) $senderEmailAddress = $commentInfo->email_address;
		if(!$senderName) $senderName = $commentInfo->nick_name;
		if(!$senderEmailAddress) $senderEmailAddress = $recipientEmailAddress;

		$tmpObj->article_url = getFullUrl('','document_srl', $commentInfo->document_srl);
		$tmpContent = $this->mergeKeywords($mailContent, $tmpObj);
		$tmpMessage = $this->mergeKeywords($mobileContent, $tmpObj);

		$this->sendMessages($recipientNumber, $senderNumber
							, $recipientEmailAddress, NULL, $senderEmailAddress, $senderName
							, $tmpMessage, $tmpContent, $title, $config, $commentInfo);
	}

	/**
	 * sending to writer
	 */
	function sendToWriter($mobileContent, $mailContent, $title, &$commentInfo, &$config)
	{
		// get the references of module MVC instances.
		$oMemberModel = &getModel('member');
		$oDocumentModel = &getModel('document');

		// get document info.
		$oDocument = $oDocumentModel->getDocument($commentInfo->document_srl);

		// writer's member_srl
		$writer_member_srl = $oDocument->getMemberSrl();
		// get member_info
		$member_info = $oMemberModel->getMemberInfoByMemberSrl($writer_member_srl);

		$recipientNumber = $this->getRecipientNumberForWriter($member_info, $oDocument, $config);
		$senderNumber = $config->sender_phone;
		$recipientEmailAddress = $this->getEmailAddressForWriter($member_info, $oDocument, $config);
		$recipientName = $this->getWriterNickName($member_info, $oDocument);
		$senderEmailAddress = $config->email_sender_address;
		$senderName = $config->email_sender_name;
		if(!$senderEmailAddress) $senderEmailAddress = $commentInfo->email_address;
		if(!$senderName) $senderName = $commentInfo->nick_name;
		if(!$senderEmailAddress) $senderEmailAddress = $recipientEmailAddress;
		if(!$senderName) $senderName = $recipientName;

		$tmpObj->article_url = getFullUrl('','document_srl', $commentInfo->document_srl);
		$tmpContent = $this->mergeKeywords($mailContent, $tmpObj);
		$tmpMessage = $this->mergeKeywords($mobileContent, $tmpObj);

		$this->sendMessages($recipientNumber, $senderNumber
							, $recipientEmailAddress, $recipientName, $senderEmailAddress, $senderName
							, $tmpMessage, $tmpContent, $title, $config, $commentInfo);
	}

	/**
	 * send to upper repliers
	 */
	function sendToUpperReplier($upperComment, $mobileContent, $mailContent, $title, &$commentInfo, &$config)
	{
		// get the references of module MVC instances.
		$oMemberModel = &getModel('member');

		// get member_info
		$member_info = $oMemberModel->getMemberInfoByMemberSrl($upperComment->member_srl);
		$upperReplier = $this->getCommentMemberInfo($upperComment);

		$recipientNumber = $this->getRecipientNumberForUpperReplier($member_info, $config);
		$senderNumber = $config->sender_phone;
		$recipientEmailAddress = $upperReplier->email_address;
		$recipientName = $upperReplier->nick_name;
		if(!$senderEmailAddress) $senderEmailAddress = $commentInfo->email_address;
		if(!$senderName) $senderName = $commentInfo->nick_name;
		$senderEmailAddress = $config->email_sender_address;
		$senderName = $config->email_sender_name;
		if(!$senderEmailAddress) $senderEmailAddress = $recipientEmailAddress;
		if(!$senderName) $senderName = $recipientName;

		$tmpObj->article_url = getFullUrl('','document_srl', $commentInfo->document_srl).'#comment_'.$commentInfo->parent_srl;
		$tmpContent = $this->mergeKeywords($mailContent, $tmpObj);
		$tmpMessage = $this->mergeKeywords($mobileContent, $tmpObj);

		$this->sendMessages($recipientNumber, $senderNumber
							, $recipientEmailAddress, $recipientName, $senderEmailAddress, $senderName
							, $tmpMessage, $tmpContent, $title, $config, $commentInfo);
	}

	/**
	 * @param $config is an object which consists of sending_method, sender_phone, admin_phones, admin_emails, use_authdata, reverse_notify, and etc.
	 * @param $commentInfo is an object which consists of comment_srl, content, parent_srl, user_id, nick_name, email_address, and etc.
	 * @param $sender is an object which includes nick_name, email_address. if the user logged in, $sender equals $logged_info.
	 * @param $module_info is a board module's instance information.
	 */
	function processNotification(&$config, &$commentInfo, &$module_info)
	{
		// get the reference of modules' instances.
		$oMemberModel = &getModel('member');

		// get document info.
		$oDocumentModel = &getModel('document');
		$oDocument = $oDocumentModel->getDocument($commentInfo->document_srl);
		$title = $oDocument->getTitleText();

		// message content
		$mobileContent = $this->mergeKeywords($config->content, $commentInfo);
		$mobileContent = $this->mergeKeywords($mobileContent, $module_info);
		$mobileContent = str_replace("&nbsp;", "", strip_tags($mobileContent));

		// mail content
		$mailContent = $this->mergeKeywords($config->mail_content, $commentInfo);
		$mailContent = $this->mergeKeywords($mailContent, $module_info);

		// send to administrator
		$this->sendToAdministrator($mobileContent, $mailContent, $title, $commentInfo, $config);

		// send to writer
		if($this->checkNotificationRequiredForWriter($commentInfo, $oDocument, $config))
		{
			$this->sendToWriter($mobileContent, $mailContent, $title, $commentInfo, $config);
		}

		// send to upper replier
		$upperComment = $this->getUpperComment($commentInfo);
		if($upperComment && $this->checkNotificationRequiredForUpperReplier($commentInfo, $upperComment, $oDocument, $config))
		{
			$this->sendToUpperReplier($upperComment, $mobileContent, $mailContent, $title, $commentInfo, $config);
		}
	}

	/**
	 * @brief comment registration trigger
	 * @param $obj : comment info object
	 **/
	function triggerInsertComment(&$commentInfo)
	{
		// get the references of modules' instances
		$oNotificationModel = &getModel('notification');
		$oModuleModel = &getModel('module');

		// if module_srl not set, just return with success;
		if(!$commentInfo->module_srl) return;

		// if module_srl is wrong, just return with success
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($commentInfo->module_srl);
		if(!$module_info) return;

		// get configuration info. no configuration? just return.
		$configList = $oNotificationModel->getNotiConfig($commentInfo->module_srl);
		if(!$configList) return;
		foreach($configList as $config)
		{
			$this->processNotification($config, $commentInfo, $module_info);
		}
	}
}
/* End of file notification.controller.php */
/* Location: ./modules/notification/notification.controller.php */
