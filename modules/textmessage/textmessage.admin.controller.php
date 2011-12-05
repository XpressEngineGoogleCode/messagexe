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

			// send callback-url to server
			if ($args->callback_url) {
				$callback_url = $args->callback_url;
				// choose '?' or '&' whether the callback_url has '?' notation.
				if (strpos($callback_url, '?') === false) $callback_url = $callback_url . '?';
				else $callback_url = $callback_url . '&';
				// add query
				$callback_url = $callback_url . "module=textmessage&act=procTextmessageUpdateResult";

				$sms = $oTextmessageModel->getCoolSMS();
				if ($sms->connect()) {
					$sms->setcallbackurl($callback_url);        
					$sms->disconnect();
				}
			}

			// save module configuration.
			$oModuleControll = getController('module');
			$output = $oModuleControll->insertModuleConfig('textmessage', $args);

			$this->setMessage('success_saved');

			$redirectUrl = getNotEncodedUrl('', 'module', 'admin', 'act', 'dispTextmessageAdminConfig');
			$this->setRedirectUrl($redirectUrl);
		}

		function procTextmessageAdminStatistics() {
			$args->regdate = Context::get('stats_date');
			$args->regdate = substr($args->regdate, 0, 6);
			$output = executeQueryArray('textmessage.getTextmessages',$args);

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

			$stat_data = array();
			foreach ($output->data as $key=>$val) {
				/*
				$stats_year = substr($val->regdate, 0, 4);
				$stats_month = substr($val->regdate, 4, 2);
				$stats_day = substr($val->regdate, 6, 2);
				$stats_hour = substr($val->regdate, 8, 2);
				 */
				$datetime = substr($val->regdate, 0, 10);

				if (!isset($stat_data[$datetime])) {
					$obj = new StdClass();
					$obj->regdate = $datetime;
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
				} else {
					$obj = &$stat_data[$datetime];
				}

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

				if (!isset($stat_data[$datetime])) {
					$stat_data[$datetime] = $obj;
				}
			}

			/*
               query = "DELETE FROM bizxe_statistics WHERE user_id = '%s' AND stats_year = '%s' AND stats_month = '%s' AND stats_day = '%s' AND stats_hour = '%s'" % (userid, stats_year, stats_month, stats_day, stats_hour)
                db.query(query)
			 */
			debugPrint('stat_data : ' . serialize($stat_data));

			foreach ($stat_data as $key=>$val) {
				$stats_year = substr($val->regdate, 0, 4);
				$stats_month = substr($val->regdate, 4, 2);
				$stats_day = substr($val->regdate, 6, 2);
				$stats_hour = substr($val->regdate, 8, 2);
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
				debugPrint('insertStatistics : ' . serialize($output));
				if (!$output->toBool()) return $output;
			}
			$redirectUrl = getNotEncodedUrl('', 'module', 'admin', 'act', 'dispTextmessageAdminStatisticsDaily');
			$this->setRedirectUrl($redirectUrl);
		}
	}
?>
