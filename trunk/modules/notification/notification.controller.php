<?php
	/**
	 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
	 * @class  notificationController
	 * @author wiley@nurigo.net
	 * @brief  notificationController
	 */
	class notificationController extends notification {

		function sendMessages($receiver, $content, $mail_content, $title, $sender, $noticom_info) {
			$oTextmessageController = &getController('textmessage');
			$oNotificationModel = &getModel('notification');

			if ($oNotificationModel->isConfigFieldSetted('cellphone_fieldname')&&in_array($noticom_info->sending_method,array('1','2'))&&$oTextmessageController) {
				$args->recipient_no = $oNotificationModel->getConfigValue($receiver, 'cellphone_fieldname', 'tel');
				$args->sender_no = $receiver->recipient_no;
				$args->content = $content;
				$output = $oTextmessageController->sendMessage($args);
				if (!$output->toBool()) return $output;
			}

			if (in_array($noticom_info->sending_method,array('1','3'))) {
				$title = $title;
				$oMail = new Mail();
				$oMail->setTitle($title);
				$oMail->setContent($mail_content);
				$oMail->setSender($sender->nick_name, $sender->email_address);
				if($receiver->email_address) {
					$oMail->setReceiptor($receiver->nick_name, $receiver->email_address);
					$oMail->send();
				}
			}
		}

		function processNotification(&$noticom_info,&$obj,&$sender,&$module_info) {
			$oMemberModel = &getModel('member');

			// message content
			$sms_message = $this->mergeKeywords($noticom_info->content, $obj);
			$sms_message = $this->mergeKeywords($sms_message, $module_info);
			$sms_message = str_replace("&nbsp;", "", strip_tags($sms_message));

			// mail content
			$mail_content = $this->mergeKeywords($noticom_info->mail_content, $obj);
			$mail_content = $this->mergeKeywords($mail_content, $module_info);

			// to writer
				$flagSend = true;

				// get document info.
				$oDocumentModel = &getModel('document');
				$oDocument = $oDocumentModel->getDocument($obj->document_srl);
				// writer's member_srl
				$document_member_srl = $oDocument->getMemberSrl();
				// get cellphone info.
				$receiver = $oMemberModel->getMemberInfoByMemberSrl($document_member_srl);
				if (!$receiver) return;
				// title
				$title = $oDocument->getTitleText();

				// 쪽지알림 연동이면서 notify_message가 'Y'가 아니면 보내지 않음
				if ($oDocument->useNotify()) {
					$flagSend = true;
				} else {
					$flagSend = false;
				}

				// 게시자 본인이면 보내지 않음
				if ($logged_info && $document_member_srl == $logged_info->member_srl) $flagSend = false;

				$tmp_obj->article_url = getFullUrl('','document_srl', $obj->document_srl);
				$tmp_content = $this->mergeKeywords($mail_content, $tmp_obj);
				$tmp_message = $this->mergeKeywords($sms_message, $tmp_obj);
				if ($flagSend) $this->sendMessages($receiver, $tmp_message, $tmp_content, $title, $sender, $noticom_info);

			// 상위 댓글자에게 알림
				if($obj->parent_srl) {
					$flagSend = true;

					$oCommentModel = &getModel('comment');
					$oParent = $oCommentModel->getComment($obj->parent_srl);
					$comment_member_srl = $oParent->getMemberSrl();

					// get cellphone info.
					$receiver = $oMemberModel->getMemberInfoByMemberSrl($comment_member_srl);
					if (!$receiver) return;

					if ($oDocument->useNotify()) {
						$flagSend = true;
					} else {
						$flagSend = false;
					}

					// 상위댓글자가 본인이면 보내지 않음
					if ($comment_member_srl == $obj->member_srl) $flagSend = false;

					// 게시자와 상위댓글자가 같으면 보내지 않음.(중복으로 보내지 않음)
					if ($document_member_srl && $comment_member_srl == $document_member_srl) $flagSend = false;

					$tmp_obj->article_url = getFullUrl('','document_srl', $obj->document_srl).'#comment_'.$obj->parent_srl;
					$tmp_content = $this->mergeKeywords($mail_content, $tmp_obj);
					$tmp_message = $this->mergeKeywords($sms_message, $tmp_obj);
					if ($flagSend) $this->sendMessages($receiver, $tmp_message, $tmp_content, $title, $sender, $noticom_info);
				}
		}

		/**
		 * @brief comment registration trigger
		 * @param $obj : comment info object
		 **/
		function triggerInsertComment(&$obj) {
			$oMemberModel = &getModel('member');

			// if module_srl not set, just return with success;
			if (!$obj->module_srl) return;

			// if module_srl is wrong, just return with success
			$args->module_srl = $obj->module_srl;
			$output = executeQuery('module.getMidInfo', $args);
			if (!$output->toBool() || !$output->data) return;
			$module_info = $output->data;
			unset($args);
			if (!$module_info) return;

			// check login.
			$sender = new StdClass();
			$sender->nick_name = $obj->nick_name;
			$sender->email_address = $obj->email_address;
			$logged_info = Context::get('logged_info');
			if ($logged_info) {
				$sender = $logged_info;
			}

			// get configuration info. no configuration? just return.
			$oModel = &getModel('notification');
			$noticom_info = $oModel->getNotiComInfo($obj->module_srl);
			if (!$noticom_info) return;

			foreach ($noticom_info as $key=>$val) {
				$this->processNotification($val,$obj,$sender,$module_info);
			}


		}
	}
?>
