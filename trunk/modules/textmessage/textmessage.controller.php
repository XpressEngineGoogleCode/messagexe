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

	function insertTextmessage($args) 
	{
		// DB INSERT
		return executeQuery('textmessage.insertTextmessage', $args);
	}

	function insertTextmessageGroup($args) 
	{
		// DB INSERT
		return executeQuery('textmessage.insertTextmessageGroup', $args);
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
		{
			$this->minusSendingCount($group_id);
		}
		if ($status == '1' && $resultcode != '00')
		{
			$this->minusFailureCount($group_id);
		}
		if ($status == '2' && $resultcode != '00')
		{
			$this->minusFailureCount($group_id);
		}
		if ($status == '2' && $resultcode == '00')
		{
			$this->minusSuccessCount($group_id);
		}
	}

	function plusCount($group_id, $status, $resultcode) 
	{
		if (in_array($status, array('0','9'))) 
		{
			if (in_array($resultcode, array('00','99'))) $this->plusWaitingCount($group_id);
			else $this->plusFailureCount($group_id);
		}
		if ($status == '1' && $resultcode == '00') 
		{
			$this->plusSendingCount($group_id);
		}
		if ($status == '1' && $resultcode != '00') 
		{
			$this->plusFailureCount($group_id);
		}
		if ($status == '2' && $resultcode != '00') 
		{
			$this->plusFailureCount($group_id);
		}
		if ($status == '2' && $resultcode == '00') 
		{
			$this->plusSuccessCount($group_id);
		}
	}



	function updateStatus($in_args,$skip_minus=false) 
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
		if (!in_array($in_args->type, array('SMS', 'LMS', 'MMS'))) $in_args->type = 'SMS';
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
				$in_args->subject = coolsms::strcut_utf8($in_args->subject, 20, true);
			}
			else
			{
				$in_args->subject = coolsms::strcut_utf8($in_args->content, 20, true);
			}
			return $in_args->subject;
		}
		else
		{
			$in_args->subject = coolsms::strcut_utf8($in_args->content, 20, true);
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

	/**
	 * @brief 메시지 전송
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
	 *  $args->group_id = '그룹ID'
	 * @param[in] $user_id true means auto, false means none, otherwise, use in userid
	 * @return Object(error, message)
	 **/
	function addMessage($in_args, $options=NULL) 
	{
		//
		// validate parameters
		//
		if (!$in_args->recipient_no) return new Object(-1, 'msg_invalid_request');


		require_once($this->module_path.'coolsms.php');
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
		if(!$options)
		{
			$options = new StdClass();
			$options->member_srl = 0;
			$options->splitlimit = 5;
			$options->bytes_per_each = 80;
			$options->checkmb = TRUE;
		}

		//
		// compose values
		//
		$this->composeType($in_args);
		$this->composeAttachment($in_args);
		if (!is_array($in_args->recipient_no)) $in_args->recipient_no = array($in_args->recipient_no);
		if (!$in_args->country_code) $in_args->country_code = $config->default_country;
		$this->composeSubject($in_args);
		$this->composeSize($in_args, $options);
		$this->composeMessage($in_args, $options);

		// create sms object
		$sms = &$oTextmessageModel->getCoolSMS();
		if($in_args->encode_utf16)
		{
			$sms->encode_utf16();
		}

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
			//$sms_args->country_iso_code = $in_args->country_iso_code;
			if ($args->type=='MMS')
			{	
				$sms_args->attachment = $in_args->attachment;
			}

			//
			// db query arguments
			//
			$query_args = $in_args;
			$query_args->mtype = $in_args->type;
			$query_args->group_id = $group_id;
			$query_args->recipient_no = $recipient_no;

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
				$output = $this->insertTextmessage($query_args);
				if (!$output->toBool()) return $output;

				$total_count++;
			}
		}


	}

	/**
	 * @brief 메시지 전송
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
	function sendMessage($in_args=NULL, $options=NULL) 
	{
		$oTextmessageModel = &getModel('textmessage');
		$sms = &$oTextmessageModel->getCoolSMS();

		if($in_args)
		{
			//
			// add messages
			//
			if(!is_array($in_args))
			{
				$in_args = array($in_args);
			}
			foreach($in_args as $arg)
			{
				$output = $this->addMessage($arg, $options);
				if($output && !$output->toBool())
				{
					return $output;
				}
			}
		}

		if(!$sms->count())
		{
			return new Object();
		}

		$first_msg = $sms->msgl[0];
		$total_count = $sms->count();

		// connect to server
		if(!$sms->connect()) 
		{
			return new Object(-1, 'error_cannot_connect');
		}

		$sending_count=0;
		$failure_count=0;

		$data = array();
		if($sms->send())
		{
			$result = $sms->getr();
			foreach ($result as $row) {
				if ($row["RESULT-CODE"] == "00") {
					$sending_count++;
				} else {
					$failure_count++;
				}

				switch($row["RESULT-CODE"]) {
					case "20":
						$alert = "[MessageXE 설정오류] 존재하지 않는 아이디이거나 패스워드가 틀립니다.";
						break;
					case "30":
						$alert = "잔액이 모두 소진되어 전송실패 했습니다.";
						break;
					case "58":
						$alert = "해당 번호로 전송할 경로가 없습니다.";
				}
				$args->message_id = $row["MESSAGE-ID"];
				$args->status = '9';
				$args->resultcode = $row["RESULT-CODE"];
				$output = $this->updateStatus($args,true);
				if (!$output->toBool()) $alert = $output->getMessage();

                $obj = new StdClass();
                $obj->result_code = $row["RESULT-CODE"];
                $obj->group_id = $row["GROUP-ID"];
                $obj->message_id = $row["MESSAGE-ID"];
                $obj->called_number = $row["CALLED-NUMBER"];
                $data[] = $obj;
			}
		}
		$sms->disconnect();
		$sms->emptyall();

		$group_id = $first_msg['GROUP-ID'];
		$mtype = $first_msg['TYPE'];
		$subject = $first_msg['SUBJECT'];
		$message = $first_msg['MESSAGE'];
		if (isset($first_msg['RESERVDATE']))
		{
			$reservdate = $first_msg['RESERVDATE'];
			$reservflag = 'Y';
		}

		// insert group info.
		$args->group_id = $group_id;
		$args->mtype = $mtype;
		$args->subject =  $subject;
		$args->content =  $message;
		if (!$args->subject)
		{
			$args->subject = $message;
		}
		$args->reservflag = $reservflag;
		$args->reservdate = $reservdate;
		$args->total_count = $total_count;
		$output = $this->insertTextmessageGroup($args);
		if(!$output->toBool())
		{
			return $output;
		}


		if ($failure_count > 0)
		{
			$output = new Object(-1, $alert);
		}
		else
		{
			// success
			$output = new Object(0, $alert);
		}
		$output->add('data', $data);
		$output->add('success_count', $sending_count);
		$output->add('failure_count', $failure_count);
		return $output;
	}

	function cancelMessage($msgids, $opts=false)
	{
		$oTextmessageModel = &getModel('textmessage');
		$sms = &$oTextmessageModel->getCoolSMS();

		if (!$sms->connect())
		{
			return new Object(-1, 'warning_cannot_connect');
		}

		foreach ($msgids as $id)
		{
			$sms->cancel($id);
		}

		$sms->disconnect();

		return new Object();
	}

	/**
	 * @brief 문자취소(그룹)
	 **/
	function cancelGroupMessages($group_ids, $opts=false)
	{
		$oTextmessageModel = &getModel('textmessage');
		$sms = &$oTextmessageModel->getCoolSMS();

		if(!$sms->connect())
		{
			return new Object(-1, 'warning_cannot_connect');
		}

		foreach($group_ids as $gid)
		{
			$sms->groupcancel($gid);
		}

		$sms->disconnect();

		return new Object();
	}

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
		   {
			   return $output;
		   }
		   $output = executeQuery('textmessage.deleteGroupMessage', $args);
		   return $output;
	}
}
?>
