<?php
	/**
	 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
	 * @class  textmessageController
	 * @author diver(diver@coolsms.co.kr)
	 * @brief  textmessageController
	 */
	class textmessageController extends textmessage {
		function init() {
		}

		function insertTextmessage($args) {
			// DB INSERT
			return executeQuery('textmessage.insertTextmessage', $args);
		}

		function insertTextmessageGroup($args) {
			if ($args->mtype=='SMS') $args->subject = $args->content;
			// DB INSERT
			return executeQuery('textmessage.insertTextmessageGroup', $args);
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
		function sendMessage($in_args, $member_srl=0) {
			require_once($this->module_path.'coolsms.php');
			$oTextmessageModel = &getModel('textmessage');
			$config = &$oTextmessageModel->getModuleConfig();

			// generalize values
			if (!$in_args->type) $in_args->type = 'SMS';
			$in_args->type = strtoupper($in_args->type);
			if (!in_array($in_args->type, array('SMS', 'LMS', 'MMS'))) $type = 'SMS';
			if (!$in_args->attachment) $in_args->attachment = array();
			if (!is_array($in_args->attachment)) $in_args->attachment = array($in_args->attachment);
			if (!is_array($in_args->recipient_no)) $in_args->recipient_no = array($in_args->recipient_no);
			if (!$in_args->country_code) $in_args->country_code = $config->default_country;
			// trim message
			if (!$subject) $subject = coolsms::strcut_utf8($content, 20, true);
			if ($in_args->type == "SMS") {
				$in_args->content = coolsms::strcut_utf8($in_args->content, 160, true);
			} else { // LMS, MMS
				$in_args->content = coolsms::strcut_utf8($in_args->content, 2000, true);
			}

			// validate
			if (!$in_args->recipient_no) return new Object(-1, 'msg_invalid_request');

			$sms = $oTextmessageModel->getCoolSMS();
			if ($in_args->encode_utf16) $sms->encode_utf16();


			$group_id = coolsms::keygen();

			$total_count=0;
			$success_count=0;
			$failure_count=0;
			foreach ($in_args->recipient_no as $recipient_no) {
				$message_id = coolsms::keygen();
				$sms_args->type = $in_args->type;
				$sms_args->rcvnum = $recipient_no;
				$sms_args->callback = $in_args->sender_no;
				$sms_args->msg = $in_args->content;
				//$sms_args->callname = $in_args->ref_username;
				$sms_args->reservdate = $in_args->reservdate;
				$sms_args->msgid = $message_id;
				$sms_args->groupid = $group_id;
				$sms_args->country = $in_args->country_code;
				//$sms_args->country_iso_code = $in_args->country_iso_code;
				if ($args->type=='MMS') $sms_args->attachment = $in_args->attachment;

				$in_args->message_id = $message_id;
				$in_args->group_id = $group_id;
				$in_args->mtype = $in_args->type;
				debugPrint('in_args : ' . serialize($in_args));
				$output = $this->insertTextmessage($in_args);
				debugPrint('insertTextmessage : ' . serialize($output));
				if (!$output->toBool()) return $output;

				if (!$sms->addobj($sms_args)) {
					$failure_count++;
					$alert = $sms->lasterror();
				}
				$total_count++;
			}

			$args->group_id = $group_id;
			$args->mtype = $in_args->mtype;
			$args->subject =  $in_args->subject;
			$args->content =  $in_args->content;
			$args->reservflag = $in_args->reservflag;
			$args->reservdate = $in_args->reservdate;
			$args->total_count = $total_count;
			$args->success_count = $success_count;
			$args->failure_count = $failure_count;

			$output = $this->insertTextmessageGroup($args);
			debugPrint('insertTextmessageGroup : ' . serialize($output));
			if (!$output->toBool()) return $output;


			if (!$sms->connect()) {
				return new Object(-1, 'error_cannot_connect');
			}

			if ($sms->send()) {
				$result = $sms->getr();

				foreach ($result as $row)
				{
					if ($row["RESULT-CODE"] == "00") $success_count++;
					else $fail++;

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
				}
			}
			$sms->disconnect();
			$sms->emptyall();

			// failure
			if ($fail > 0) return new Object(-1, $alert);

			// success
			return new Object(0, $alert);
		}

        function cancelMessage($msgids, $opts=false) {
			$oTextmessageModel = &getModel('textmessage');
			$sms = $oTextmessageModel->getCoolSMS();

            if (!$sms->connect()) return new Object(-1, 'warning_cannot_connect');

            foreach ($msgids as $id) {
                $sms->cancel($id);
            }

            $sms->disconnect();

            return new Object();
        }

        /**
         * @brief 문자취소(그룹)
         **/
        function cancelGroupMessages($group_ids, $opts=false) {
			$oTextmessageModel = &getModel('textmessage');
			$sms = $oTextmessageModel->getCoolSMS();

            if (!$sms->connect()) return new Object(-1, 'warning_cannot_connect');

            foreach ($group_ids as $gid) {
                $sms->groupcancel($gid);
            }

            $sms->disconnect();

            return new Object();
        }
	}
?>
