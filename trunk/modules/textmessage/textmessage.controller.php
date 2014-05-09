<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
 * @class  textmessageController
 * @author diver(diver@coolsms.co.kr)
 * @brief  textmessageController
 */
class textmessageController extends textmessage 
{
	function init() 
	{
	}


/*
	function insertTextmessage($args) 
	{
		// DB INSERT
		return executeQuery('textmessage.insertTextmessage', $args);
	}

	function insertTextmessageGroup($args) 
	{
		$output = executeQuery('textmessage.getTextmessageGroup', $args);
		if(!$output->toBool()) return $output;
		if($output->data)
		{
			$group_info = $output->data;
			$group_info->total_count += $args->total_count;
			$output = executeQuery('textmessage.updateTextmessageGroup', $group_info);
		}
		else
		{
			// DB INSERT
			$output = executeQuery('textmessage.insertTextmessageGroup', $args);
		}
		return $output;
	}

	function minusWaitingCount($group_id) 
	{
		$args->group_id = $group_id;
		return executeQuery('textmessage.minusWaitingCount', $args);
	}

	function minusSendingCount($group_id) 
	{
		$args->group_id = $group_id;
		return executeQuery('textmessage.minusSendingCount', $args);
	}

	function minusFailureCount($group_id) 
	{
		$args->group_id = $group_id;
		return executeQuery('textmessage.minusFailureCount', $args);
	}

	function minusSuccessCount($group_id) 
	{
		$args->group_id = $group_id;
		return executeQuery('textmessage.minusSuccessCount', $args);
	}

	function plusWaitingCount($group_id) 
	{
		$args->group_id = $group_id;
		$output = executeQuery('textmessage.plusWaitingCount', $args);
		return $output;
	}

	function plusSendingCount($group_id) 
	{
		$args->group_id = $group_id;
		return executeQuery('textmessage.plusSendingCount', $args);
	}

	function plusFailureCount($group_id) 
	{
		$args->group_id = $group_id;
		$output = executeQuery('textmessage.plusFailureCount', $args);
		return $output;
	}

	function plusSuccessCount($group_id) {
		$args->group_id = $group_id;
		$output = executeQuery('textmessage.plusSuccessCount', $args);
		return $output;
	}

	function minusCount($group_id, $status, $resultcode) 
	{
		if (in_array($status, array('0','9')))
		{
			if (in_array($resultcode, array('00','99'))) $this->minusWaitingCount($group_id);
			else $this->minusFailureCount($group_id);
		}
		if ($status == '1' && $resultcode == '00')
			$this->minusSendingCount($group_id);

		if ($status == '1' && $resultcode != '00')
			$this->minusFailureCount($group_id);
		
		if ($status == '2' && $resultcode != '00')
			$this->minusFailureCount($group_id);
		
		if ($status == '2' && $resultcode == '00')
			$this->minusSuccessCount($group_id);
	}

	function plusCount($group_id, $status, $resultcode) 
	{
		if (in_array($status, array('0','9'))) 
		{
			if (in_array($resultcode, array('00','99'))) $this->plusWaitingCount($group_id);
			else $this->plusFailureCount($group_id);
		}
		if ($status == '1' && $resultcode == '00') 
			$this->plusSendingCount($group_id);

		if ($status == '1' && $resultcode != '00') 
			$this->plusFailureCount($group_id);

		if ($status == '2' && $resultcode != '00') 
			$this->plusFailureCount($group_id);

		if ($status == '2' && $resultcode == '00') 
			$this->plusSuccessCount($group_id);
	}

	function updateStatus($in_args,$skip_minus=FALSE) 
	{
		$oTextmessageModel = &getModel('textmessage');
		$message_info = $oTextmessageModel->getMessageInfo($in_args->message_id);

		// minus
		if (!$skip_minus) $this->minusCount($message_info->group_id, $message_info->mstat, $message_info->rcode);

		// plus
		$this->plusCount($message_info->group_id, $in_args->status, $in_args->resultcode);

		$args->message_id = $in_args->message_id;
		$args->mstat = $in_args->status;
		$args->rcode = $in_args->resultcode;
		$args->senddate = $in_args->senddate;
		$args->carrier = $in_args->carrier;
		$output = executeQuery('textmessage.updateStatus', $args);
		return $output;
	}

	function composeType(&$in_args)
	{
		if (!$in_args->type) $in_args->type = 'SMS';
		$in_args->type = strtoupper($in_args->type);
		if (!in_array($in_args->type, array('SMS', 'LMS', 'MMS'))) 
			$in_args->type = 'SMS';
		return $in_args->type;
	}

	function composeAttachment(&$in_args)
	{
		if (in_array($in_args->type, array('LMS','MMS')))
		{
			if (!$in_args->attachment) $in_args->attachment = array();
			if (!is_array($in_args->attachment)) $in_args->attachment = array($in_args->attachment);
			return $in_args->attachment;
		}
		else
		{
			unset($in_args->attachment);
		}
		return NULL;
	}

	function composeSubject(&$in_args)
	{
		if (in_array($in_args->type, array('LMS','MMS')))
		{
			if ($in_args->subject)
			{
				$in_args->subject = coolsms::strcut_utf8($in_args->subject, 20, TRUE);
			}
			else
			{
				$in_args->subject = coolsms::strcut_utf8($in_args->content, 20, TRUE);
			}
			return $in_args->subject;
		}
		else
		{
			$in_args->subject = coolsms::strcut_utf8($in_args->content, 20, TRUE);
			return $in_args->subject;
		}
		return NULL;
	}

	function composeSize(&$in_args, &$options)
	{
		// multi-bytes
		$options->checkmb = TRUE;

		if($in_args->type == 'SMS')
		{
			// international 160 bytes
			if($in_args->country_code != '82') {
				$options->bytes_per_each = 160;
				if ($in_args->encode_utf16=='Y') {
					$options->bytes_per_each = 70;
					// count unicode string length
					$options->checkmb = FALSE;
				}
			}
		}
		else
		{
			$options->bytes_per_each = 4000;
		}

		// text length
		$in_args->content_length = coolsms::strlen_utf8($in_args->content, $options->checkmb);
		$options->quantity = ceil($in_args->content_length / $options->bytes_per_each);
		if (($options->splitlimit+1) < $options->quantity) $options->quantity = $options->splitlimit+1;
	}

	function composeMessage(&$in_args, &$options)
	{
		$content_arr = array();
		if($in_args->type=='SMS')
		{
			$text = $in_args->content;
			for ($i = 0; $i < $options->quantity; $i++) {
				$content = coolsms::strcut_utf8($text, $options->bytes_per_each, $checkmb);
				$content_arr[] = $content;
				$text = substr($text, strlen($content));
			}
		}
		else
		{
			$content_arr[] = coolsms::strcut_utf8($in_args->content, $options->bytes_per_each, $options->checkmb);
		}
		$in_args->content = $content_arr;
	}
 */
	/**
	 * @brief 메시지 전송
	 * @param[in] $args
	 *  $args->type = 'SMS' or 'LMS' or 'MMS' // default = 'SMS'
	 *  $args->recipient_no = '수신번호'
	 *  $args->sender_no = '발신번호'
	 *  $args->content = '메시지 내용'
	 *  $args->reservdate = 'YYYYMMDDHHMISS'
	 *  $args->subject = 'LMS제목'
	 *  $args->country_code = '국가번호
	 *  $args->country_iso_code = '국가ISO코드'
	 *  $args->attachment = 첨부파일
	 *  $args->encode_utf16 = true or false
	 *  $args->group_id = '그룹ID'
	 * @param[in] $user_id true means auto, false means none, otherwise, use in userid
	 * @return Object(error, message)
	 **/
	/*
	function addMessage($in_args, $options=NULL) 
	{
		return json_encode($in_args);	
		//
		// validate parameters
		//
		if (!$in_args->recipient_no) 
			return new Object(-1, 'msg_invalid_request');

		if (!class_exists('coolsms')) 
			require_once(_XE_PATH_.'modules/textmessage/coolsms.php');
		$oTextmessageModel = &getModel('textmessage');
		$config = &$oTextmessageModel->getModuleConfig();
		// generate group id
		if ($in_args->group_id)
		{
			$group_id = $in_args->group_id;
		}
		else
		{
			$group_id = coolsms::keygen();
		}
		// options' default values
		//
		// 
		if(!$options) $options = new StdClass();
		if(!$options->member_srl) $options->member_srl = 0;
		if(!$options->splitlimit) $options->splitlimit = 0;
		if(!$options->bytes_per_each) $options->bytes_per_each = 90;
		if(!$options->checkmb) $options->checkmb = TRUE;
		//
		// compose values
		//
		$this->composeType($in_args);
		$this->composeAttachment($in_args);
		if (!is_array($in_args->recipient_no)) 
			$in_args->recipient_no = array($in_args->recipient_no);
		if (!$in_args->country_code) 
			$in_args->country_code = $config->default_country;
		$this->composeSubject($in_args);
		$this->composeSize($in_args, $options);
		$this->composeMessage($in_args, $options);
		// create sms object
		$sms = &$oTextmessageModel->getCoolSMS();
		if($in_args->encode_utf16)	$sms->encode_utf16();
		//
		// adding messages
		//
		$total_count=0;
		foreach($in_args->recipient_no as $recipient_no) 
		{
			//
			// sms arguments
			// 
			$sms_args->type = $in_args->type;
			$sms_args->rcvnum = $recipient_no;
			$sms_args->callback = $in_args->sender_no;
			//$sms_args->callname = $in_args->ref_username;
			$sms_args->reservdate = $in_args->reservdate;
			$sms_args->groupid = $group_id;
			$sms_args->country = $in_args->country_code;
			$sms_args->subject = $in_args->subject;
			//$sms_args->country_iso_code = $in_args->country_iso_code;
			if ($in_args->type=='MMS')
				$sms_args->attachment = $in_args->attachment;
			//
			// db query arguments
			//
			$query_args->message_id = $in_args->message_id;
			$query_args->mtype = $in_args->type;
			$query_args->group_id = $group_id;
			$query_args->member_srl = $in_args->member_srl;
			$query_args->user_id = $in_args->user_id;
			$query_args->country_code = $in_args->country_code;
			$query_args->recipient_no = $recipient_no;
			$query_args->sender_no = $in_args->sender_no;
			$query_args->subject = $in_args->subject;
			$query_args->reservflag = $in_args->reservflag;
			$query_args->reservdate = $in_args->reservdate;
			foreach($in_args->content as $content)
			{
				$message_id = coolsms::keygen();
				// add sms
				$sms_args->msgid = $message_id;
				$sms_args->msg = $content;
				if (!$sms->addobj($sms_args)) {
					$failure_count++;
					$alert = $sms->lasterror();
				}
				// insert db record
				$query_args->message_id = $message_id;
				$query_args->content = $content;
				if(!$options->disable_db)	
				{
					$output = $this->insertTextmessage($query_args);
					if(!$output->toBool())
						return $output;
				}
				$total_count++;
			}
		}
	}
	 */

	/**
	 * @brief 메시지 전송, $in_args에 값이 있을 경우 전송대기열에 넣고 전송처리
	 * @param[in] $args
	 *  $args->type = 'SMS' or 'LMS' or 'MMS' // default = 'SMS'
	 *  $args->recipient_no = '수신번호'
	 *  $args->sender_no = '발신번호'
	 *  $args->content = '메시지 내용'
	 *  $args->reservdate = 'YYYYMMDDHHMISS'
	 *  $args->subject = 'LMS제목'
	 *  $args->country_code = '국가번호'
	 *  $args->country_iso_code = '국가ISO코드'
	 *  $args->attachment = 첨부파일
	 *  $args->encode_utf16 = true or false
	 * @param[in] $user_id true means auto, false means none, otherwise, use in userid
	 * @return Object(error, message)
	 **/
	function sendMessage($in_args) 
	{
		$oTextmessageModel = &getModel('textmessage');
		$sms = &$oTextmessageModel->getCoolSMS();
		// $in_args에 값이 있을 경우 전송대기열에 넣고 전송처리
		$options = new stdClass();

		// Purplebook has different Key names. 
		if($in_args->recipient_no)
		{
			if(is_array($in_args->recipient_no))
				$options->to = implode(',' , $in_args->recipient_no);
			else
				$options->to = $in_args->recipient_no;
		}
		elseif($in_args->to) 		$options->to = $in_args->to;

		if($in_args->sender_no) 	$options->from = $in_args->sender_no;
		elseif($in_args->from)		$options->from = $in_args->from;

		if($in_args->type)			$options->type = $in_args->type;
		if($in_args->attachment) 	$options->image = $in_args->attachment;
		if($in_args->image)			$options->image = $in_args->image;
		if($in_args->content)		$options->text = $in_args->content;
		if($in_args->refname)		$options->refname = $in_args->refname;
		if($in_args->country)		$options->country = $in_args->country;
		if($in_args->subject)		$options->subject = $in_args->subject;
		if($in_args->srk)			$options->srk = $in_args->srk;
		if($in_args->extension) 	$options->extension = $in_args->extension;

		$opt = new stdClass();
		$send_result = new stdClass();
		$send_result = $sms->send($options);
		$opt->gid = $send_result->group_id;

		$output = new Object();
		$output->add('success_count', $send_result->success_count);
		$output->add('failure_count', $send_result->error_count);
		return $output;
	}

	function getResult($args=null)
	{
		$oTextmessageModel = &getModel('textmessage');
		$sms = &$oTextmessageModel->getCoolSMS();
		$result = $sms->sent($args);
		return $result;
	}

	/* 예약전송 취소하기
	 * 
	 */
	function cancelMessage($msgid, $opts=FALSE)
	{
		$oTextmessageModel = &getModel('textmessage');
		$sms = &$oTextmessageModel->getCoolSMS();
		$options = new stdClass();
		$options->mid = $msgid;
		$result = $sms->cancel($options);
		if($result->code)	
			return new Object(-1, $result->code);
		else
			return new Object();
	}

	/**
	 * @brief 문자취소(그룹)
	 **/
	function cancelGroupMessages($grpid, $opts=FALSE)
	{
		$oTextmessageModel = &getModel('textmessage');
		$sms = &$oTextmessageModel->getCoolSMS();
		$options = new stdClass();
		$options->gid = $grpid;
		$result = $sms->cancel($options);
		if($ressult->code)
			return new Object(-1, $result->code);
		return new Object();
	}


/*
	function deleteMessage($message_id)
	{
		   $args->message_id = $message_id;
		   $output = executeQuery('textmessage.deleteMessage', $args);
		   return $output;
	}

	function deleteGroupMessage($group_id)
	{
		   $args->group_id = $group_id;
		   $output = executeQuery('textmessage.deleteMessagesByGroupId', $args);
		   if (!$output->toBool())
			   return $output;
		   $output = executeQuery('textmessage.deleteGroupMessage', $args);
		   return $output;
	}
 */
}
?>
