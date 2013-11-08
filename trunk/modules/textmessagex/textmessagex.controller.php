<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  purplebookControllerex
 * @author wiley@nurigo.net
 * @brief  purplebookControllerex
 */

require_once(_XE_PATH_. 'modules/textmessage/textmessage.controller.php');

class textmessagexController extends textmessageController
{
	function init() 
	{
		$class_path = ModuleHandler::getModulePath('textmessage');
		$this->setModulePath($class_path);
		parent::init();
	}

	function textmessagexController()
	{
		$this->init();
	}

	function insertTextmessage($args)
	{
		if($args->base_camp)
			return new object();

		return executeQuery('textmessage.insertTextmessage', $args);
	}

	function insertTextmessageGroup($args)
	{
		if($args->base_camp)
			return new object();
		
		return parent::insertTextmessageGroup($args);
	}


	function addMessage($in_args, $options=NULL) 
	{
		//
		// validate parameters
		//
		if (!$in_args->recipient_no) return new Object(-1, 'msg_invalid_request');


		if (!class_exists('coolsms')) require_once($this->module_path.'coolsms.php');
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
			$options->splitlimit = 0;
			$options->bytes_per_each = 90;
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
			$sms_args->subject = $in_args->subject;
			//$sms_args->country_iso_code = $in_args->country_iso_code;
			if ($in_args->type=='MMS')
			{	
				$sms_args->attachment = $in_args->attachment;
			}

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
			//확장에 추가된 base_camp
			$query_args->base_camp = $in_args->base_camp;

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
				$output = $this->insertTextmessage($query_args);
				if (!$output->toBool())
				{
					return $output;
				}

				$total_count++;
			}
		}
	}

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

		//
		// insert group info.
		//
		$group_id = $first_msg['GROUP-ID'];
		$mtype = $first_msg['TYPE'];
		$subject = $first_msg['SUBJECT'];
		$message = $first_msg['MESSAGE'];
		if (isset($first_msg['RESERVDATE']))
		{
			$reservdate = $first_msg['RESERVDATE'];
			$reservflag = 'Y';
		}
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
		// 확장에 추가된 코드 base_camp 
		$args->base_camp = $arg->base_camp;
		$output = $this->insertTextmessageGroup($args);
		unset($args);

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

}


