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

		function makeStatistics() {
			$output = executeQueryArray('textmessage.getTextmessages');

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

			foreach ($output->data as $key=>$val) {
				$stats_year = substr($val->regdate, 0, 4);
				$stats_month = substr($val->regdate, 4, 2);
				$stats_day = substr($val->regdate, 6, 2);
				$stats_hour = substr($val->regdate, 8, 2);

				if ($val->mstat=='2' and $val->rcode=='00' and $val->mtype=='SMS' AND $val->carrier=='SKT') $sms_sk_count++;
				if ($val->mstat=='2' and $val->rcode=='00' and $val->mtype=='SMS' AND $val->carrier=='KTF') $sms_kt_count++;
				if ($val->mstat=='2' and $val->rcode=='00' and $val->mtype=='SMS' AND $val->carrier=='LGT') $sms_lg_count++;
				if ($val->mstat=='2' and $val->rcode=='00' and $val->mtype=='LMS' AND $val->carrier=='SKT') $lms_sk_count++;
				if ($val->mstat=='2' and $val->rcode=='00' and $val->mtype=='LMS' AND $val->carrier=='KTF') $lms_kt_count++;
				if ($val->mstat=='2' and $val->rcode=='00' and $val->mtype=='LMS' AND $val->carrier=='LGT') $lms_lg_count++;
				if ($val->mstat=='2' and $val->rcode=='00' and $val->mtype=='MMS' AND $val->carrier=='SKT') $mms_sk_count++;
				if ($val->mstat=='2' and $val->rcode=='00' and $val->mtype=='MMS' AND $val->carrier=='KTF') $mms_kt_count++;
				if ($val->mstat=='2' and $val->rcode=='00' and $val->mtype=='MMS' AND $val->carrier=='LGT') $mms_lg_count++;

				if ($val->country_code == '82' and $val->mtype =='SMS') $sms_total_count++;
				if ($val->country_code == '82' and $val->mtype =='LMS') $lms_total_count++;
				if ($val->country_code == '82' and $val->mtype =='MMS') $mms_total_count++;
				if ($val->mstat=='2' and $val->rcode=='00' and $val->country_code != '82') $oversea_count++;
				if ($val->country_code != '82') $oversea_total_count++;
			}

			/*
               query = "DELETE FROM bizxe_statistics WHERE user_id = '%s' AND stats_year = '%s' AND stats_month = '%s' AND stats_day = '%s' AND stats_hour = '%s'" % (userid, stats_year, stats_month, stats_day, stats_hour)
                db.query(query)
			 */

			$args->stats_year = $stats_year;
			$args->stats_month = $stats_month;
			$args->stats_day = $stats_day;
			$args->stats_hour = $stats_hour;
			$args->sms_sk_count = $sms_sk_count;
			$args->sms_kt_count = $sms_kt_count;
			$args->sms_lg_count = $sms_lg_count;
			$args->lms_sk_count = $lms_sk_count;
			$args->lms_kt_count = $lms_kt_count;
			$args->lms_lg_count = $lms_lg_count;
			$args->mms_sk_count = $mms_sk_count;
			$args->mms_kt_count = $mms_kt_count;
			$args->mms_lg_count = $mms_lg_count;
			$args->sms_total_count =  $sms_total_count;
			$args->lms_total_count = $lms_total_count;
			$args->mms_total_count = $mms_total_count;
			$args->oversea_count = $oversea_count;
			$args->oversea_total_count = $oversea_total_count;


			$output = executeQuery('textmessage.insertStatistics', $args);
			debugPrint('insertStatistics : ' . serialize($output));
			if (!$output->toBool()) return $output;
		}
	}
?>
