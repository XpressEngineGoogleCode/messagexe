<?php
	/**
	 * @class  textmessageAdminController
	 * @author wiley (wiley@xnurigo.net)
	 * @brief  textmessage controller class of textmessage module
	 **/
	class textmessageAdminController extends textmessage {
		/**
		 * @brief initialization
		 * @return none
		 **/
		function init() {
		}

		function procTextmessageAdminInsertConfig() {
			$oTextmessageModel = &getModel('textmessage');

			$args = Context::gets('service_id', 'password', 'callback_url', 'encode_utf16');

			// save module configuration.
			$oModuleControll = getController('module');
			$output = $oModuleControll->insertModuleConfig('textmessage', $args);

			$this->setMessage('success_saved');

			$redirectUrl = getNotEncodedUrl('', 'module', 'admin', 'act', 'dispTextmessageAdminConfig');
			$this->setRedirectUrl($redirectUrl);
		}

		function makeStatistics($year, $month='', $day='', $hour='') {
			$args->regdate = $year . $month . $day . $hour;
			$output = executeQueryArray('textmessage.getTextmessages',$args);
			if (!$output->toBool()) return $output;
			$textmessages = $output->data;
			if (!$textmessages) $textmessages = array();

			$stat_data = array();
			foreach ($textmessages as $key=>$val) {
				$datetime = substr($val->regdate, 0, 10);

				if (!array_key_exists($datetime, $stat_data)) {
					$obj = new StdClass();
					$obj->sms_sk_count = 0;
					$obj->sms_kt_count = 0;
					$obj->sms_lg_count = 0;
					$obj->lms_sk_count = 0;
					$obj->lms_kt_count = 0;
					$obj->lms_lg_count = 0;
					$obj->mms_sk_count = 0;
					$obj->mms_kt_count = 0;
					$obj->mms_lg_count = 0;
					$obj->sms_total_count = 0;
					$obj->lms_total_count = 0;
					$obj->mms_total_count = 0;
					$obj->oversea_count= 0;
					$obj->oversea_total_count= 0;
					$stat_data[$datetime] = $obj;
				}
				$obj = $stat_data[$datetime];

				if ($val->mstat=='2' and $val->rcode=='00' and $val->mtype=='SMS' AND $val->carrier=='SKT') $obj->sms_sk_count++;
				if ($val->mstat=='2' and $val->rcode=='00' and $val->mtype=='SMS' AND $val->carrier=='KTF') $obj->sms_kt_count++;
				if ($val->mstat=='2' and $val->rcode=='00' and $val->mtype=='SMS' AND $val->carrier=='LGT') $obj->sms_lg_count++;
				if ($val->mstat=='2' and $val->rcode=='00' and $val->mtype=='LMS' AND $val->carrier=='SKT') $obj->lms_sk_count++;
				if ($val->mstat=='2' and $val->rcode=='00' and $val->mtype=='LMS' AND $val->carrier=='KTF') $obj->lms_kt_count++;
				if ($val->mstat=='2' and $val->rcode=='00' and $val->mtype=='LMS' AND $val->carrier=='LGT') $obj->lms_lg_count++;
				if ($val->mstat=='2' and $val->rcode=='00' and $val->mtype=='MMS' AND $val->carrier=='SKT') $obj->mms_sk_count++;
				if ($val->mstat=='2' and $val->rcode=='00' and $val->mtype=='MMS' AND $val->carrier=='KTF') $obj->mms_kt_count++;
				if ($val->mstat=='2' and $val->rcode=='00' and $val->mtype=='MMS' AND $val->carrier=='LGT') $obj->mms_lg_count++;

				if ($val->country_code == '82' and $val->mtype =='SMS') $obj->sms_total_count++;
				if ($val->country_code == '82' and $val->mtype =='LMS') $obj->lms_total_count++;
				if ($val->country_code == '82' and $val->mtype =='MMS') $obj->mms_total_count++;
				if ($val->mstat=='2' and $val->rcode=='00' and $val->country_code != '82') $obj->oversea_count++;
				if ($val->country_code != '82') $obj->oversea_total_count++;

				$stat_data[$datetime] = $obj;
			}

			foreach ($stat_data as $key=>$val) {
				$stats_year = substr($key, 0, 4);
				$stats_month = substr($key, 4, 2);
				$stats_day = substr($key, 6, 2);
				$stats_hour = substr($key, 8, 2);
				$args->stats_year = $stats_year;
				$args->stats_month = $stats_month;
				$args->stats_day = $stats_day;
				$args->stats_hour = $stats_hour;
				$args->sms_sk_count = $val->sms_sk_count;
				$args->sms_kt_count = $val->sms_kt_count;
				$args->sms_lg_count = $val->sms_lg_count;
				$args->lms_sk_count = $val->lms_sk_count;
				$args->lms_kt_count = $val->lms_kt_count;
				$args->lms_lg_count = $val->lms_lg_count;
				$args->mms_sk_count = $val->mms_sk_count;
				$args->mms_kt_count = $val->mms_kt_count;
				$args->mms_lg_count = $val->mms_lg_count;
				$args->sms_total_count = $val->sms_total_count;
				$args->lms_total_count = $val->lms_total_count;
				$args->mms_total_count = $val->mms_total_count;
				$args->oversea_count = $val->oversea_count;
				$args->oversea_total_count = $val->oversea_total_count;
				$output = executeQuery('textmessage.deleteStatistics', $args);
				$output = executeQuery('textmessage.insertStatistics', $args);
				if (!$output->toBool()) return $output;
			}
		}

		function procTextmessageAdminDeleteStatistics() {
			$stats_date = Context::get('stats_date');
			$args->stats_year = substr($stats_date, 0, 4);
			$args->stats_month = substr($stats_date, 4, 2);
			$args->stats_day = sprintf("%02u", (int)Context::get('day'));
			$output = executeQuery('textmessage.deleteStatistics', $args);
			if (!$output->toBool()) return $output;
		}

		function procTextmessageAdminDeleteStatisticsMonth() {
			$stats_date = Context::get('stats_date');
			$args->stats_year = substr($stats_date, 0, 4);
			$args->stats_month = sprintf("%02u", (int)Context::get('month'));
			$output = executeQuery('textmessage.deleteStatistics', $args);
			if (!$output->toBool()) return $output;
		}
		function procTextmessageAdminStatistics() {
			$stats_date = Context::get('stats_date');
			$args->regdate = $stats_date;
			$args->regdate = substr($args->regdate, 0, 6);
			$output = executeQueryArray('textmessage.getTextmessages',$args);
			if (!$output->toBool()) return $output;
			$textmessages = $output->data;
			if (!$textmessages) $textmessages = array();

			/*
			$sms_sk_count = 0;
			$sms_kt_count = 0;
			$sms_lg_count = 0;
			$lms_sk_count = 0;
			$lms_kt_count = 0;
			$lms_lg_count = 0;
			$mms_sk_count = 0;
			$mms_kt_count = 0;
			$mms_lg_count = 0;
			$sms_total_count = 0;
			$lms_total_count = 0;
			$mms_total_count = 0;
			$oversea_count = 0;
			$oversea_total_count = 0;
			 */

			$stat_data = array();
			foreach ($textmessages as $key=>$val) {
				$datetime = substr($val->regdate, 0, 10);

				if (!array_key_exists($datetime, $stat_data)) {
					$obj = new StdClass();
					$obj->sms_sk_count = 0;
					$obj->sms_kt_count = 0;
					$obj->sms_lg_count = 0;
					$obj->lms_sk_count = 0;
					$obj->lms_kt_count = 0;
					$obj->lms_lg_count = 0;
					$obj->mms_sk_count = 0;
					$obj->mms_kt_count = 0;
					$obj->mms_lg_count = 0;
					$obj->sms_total_count = 0;
					$obj->lms_total_count = 0;
					$obj->mms_total_count = 0;
					$obj->oversea_count= 0;
					$obj->oversea_total_count= 0;
					$stat_data[$datetime] = $obj;
				}
				$obj = $stat_data[$datetime];

				if ($val->mstat=='2' and $val->rcode=='00' and $val->mtype=='SMS' AND $val->carrier=='SKT') $obj->sms_sk_count++;
				if ($val->mstat=='2' and $val->rcode=='00' and $val->mtype=='SMS' AND $val->carrier=='KTF') $obj->sms_kt_count++;
				if ($val->mstat=='2' and $val->rcode=='00' and $val->mtype=='SMS' AND $val->carrier=='LGT') $obj->sms_lg_count++;
				if ($val->mstat=='2' and $val->rcode=='00' and $val->mtype=='LMS' AND $val->carrier=='SKT') $obj->lms_sk_count++;
				if ($val->mstat=='2' and $val->rcode=='00' and $val->mtype=='LMS' AND $val->carrier=='KTF') $obj->lms_kt_count++;
				if ($val->mstat=='2' and $val->rcode=='00' and $val->mtype=='LMS' AND $val->carrier=='LGT') $obj->lms_lg_count++;
				if ($val->mstat=='2' and $val->rcode=='00' and $val->mtype=='MMS' AND $val->carrier=='SKT') $obj->mms_sk_count++;
				if ($val->mstat=='2' and $val->rcode=='00' and $val->mtype=='MMS' AND $val->carrier=='KTF') $obj->mms_kt_count++;
				if ($val->mstat=='2' and $val->rcode=='00' and $val->mtype=='MMS' AND $val->carrier=='LGT') $obj->mms_lg_count++;

				if ($val->country_code == '82' and $val->mtype =='SMS') $obj->sms_total_count++;
				if ($val->country_code == '82' and $val->mtype =='LMS') $obj->lms_total_count++;
				if ($val->country_code == '82' and $val->mtype =='MMS') $obj->mms_total_count++;
				if ($val->mstat=='2' and $val->rcode=='00' and $val->country_code != '82') $obj->oversea_count++;
				if ($val->country_code != '82') $obj->oversea_total_count++;

				$stat_data[$datetime] = $obj;
			}

			foreach ($stat_data as $key=>$val) {
				$stats_year = substr($key, 0, 4);
				$stats_month = substr($key, 4, 2);
				$stats_day = substr($key, 6, 2);
				$stats_hour = substr($key, 8, 2);
				$args->stats_year = $stats_year;
				$args->stats_month = $stats_month;
				$args->stats_day = $stats_day;
				$args->stats_hour = $stats_hour;
				$args->sms_sk_count = $val->sms_sk_count;
				$args->sms_kt_count = $val->sms_kt_count;
				$args->sms_lg_count = $val->sms_lg_count;
				$args->lms_sk_count = $val->lms_sk_count;
				$args->lms_kt_count = $val->lms_kt_count;
				$args->lms_lg_count = $val->lms_lg_count;
				$args->mms_sk_count = $val->mms_sk_count;
				$args->mms_kt_count = $val->mms_kt_count;
				$args->mms_lg_count = $val->mms_lg_count;
				$args->sms_total_count = $val->sms_total_count;
				$args->lms_total_count = $val->lms_total_count;
				$args->mms_total_count = $val->mms_total_count;
				$args->oversea_count = $val->oversea_count;
				$args->oversea_total_count = $val->oversea_total_count;
				$output = executeQuery('textmessage.deleteStatistics', $args);
				$output = executeQuery('textmessage.insertStatistics', $args);
				if (!$output->toBool()) return $output;
			}

			$this->setMessage(substr($stats_date, 0, 4) . ' 년 ' . substr($stats_date, 4, 2) . ' 월 ' . ' 통계 데이터 생성');

			$redirectUrl = getNotEncodedUrl('', 'module', 'admin', 'act', 'dispTextmessageAdminStatisticsDaily','stats_date',Context::get('stats_date'));
			$this->setRedirectUrl($redirectUrl);
		}

        function procTextmessageAdminCancelReserv() {
            $target_message_ids = Context::get('target_message_ids');
            if(!$target_message_ids) return new Object(-1, 'msg_invalid_request');
            $message_id_arr = explode(',', $target_message_ids);
            $oTextmessageController = &getController('textmessage');

            $output = $oTextmessageController->cancelMessage($message_id_arr);
            if(!$output->toBool()) return $output;

            $this->setMessage('success_requested');

			$redirectUrl = getNotEncodedUrl('', 'module', 'admin', 'act', 'dispTextmessageAdminUsageStatement','group_id',Context::get('group_id'),'stats_date',Context::get('stats_date'));
			$this->setRedirectUrl($redirectUrl);
        }

        function procTextmessageAdminCancelGroup() {
            $target_group_ids = Context::get('target_group_ids');
            if(!$target_group_ids) return new Object(-1, 'msg_invalid_request');
            $group_ids = explode(',', $target_group_ids);
            $oTextmessageController = &getController('textmessage');

            $output = $oTextmessageController->cancelGroupMessages($group_ids);
            if(!$output->toBool()) return $output;

            $this->setMessage('success_requested');

			$redirectUrl = getNotEncodedUrl('', 'module', 'admin', 'act', 'dispTextmessageAdminUsageStatement','stats_date',Context::get('stats_date'));
			$this->setRedirectUrl($redirectUrl);
        }

        function procTextmessageAdminDeleteMessages() {
            $target_message_ids = Context::get('target_message_ids');
            if(!$target_message_ids) return new Object(-1, 'msg_invalid_request');
            $message_ids = explode(',', $target_message_ids);
            $oTextmessageController = &getController('textmessage');

            foreach($message_ids as $id) {
                $output = $oTextmessageController->deleteMessage($id);
                if(!$output->toBool()) return $output;
            }
            $this->setMessage('success_deleted');
			$redirectUrl = getNotEncodedUrl('', 'module', 'admin', 'act', 'dispTextmessageAdminUsageStatement','group_id',Context::get('group_id'),'stats_date',Context::get('stats_date'));
			$this->setRedirectUrl($redirectUrl);
        }

        function procTextmessageAdminDeleteGroup() {
            $target_group_ids = Context::get('target_group_ids');
            if(!$target_group_ids) return new Object(-1, 'msg_invalid_request');
            $group_ids = explode(',', $target_group_ids);
            $oTextmessageController = &getController('textmessage');

            foreach($group_ids as $gid) {
                $output = $oTextmessageController->deleteGroupMessage($gid);
                if(!$output->toBool()) {
                    $this->setMessage('failed_deleted');
                    return $output;
                }
            }

            $this->setMessage('success_deleted');
			$redirectUrl = getNotEncodedUrl('', 'module', 'admin', 'act', 'dispTextmessageAdminUsageStatement','stats_date',Context::get('stats_date'));
			$this->setRedirectUrl($redirectUrl);
        }

		function procTextmessageAdminUpdateStatus() {
			$oTextmessageModel = &getModel('textmessage');
			$oTextmessageController = &getController('textmessage');

			$message_id = Context::get('message_id');

			$sms = $oTextmessageModel->getCoolSMS();
			if (!$sms->connect()) return new Object(-2, 'warning_cannot_connect');
			$result = $sms->rcheck($message_id);
			$args->message_id = $message_id;
			$args->status = $result['STATUS'];
			$args->resultcode = $result['RESULT-CODE'];
			$args->carrier = $result['CARRIER'];
			$args->senddate = $result['SEND-DATE'];
			$oTextmessageController->updateStatus($args);
			$sms->disconnect();
		}
	}
?>
