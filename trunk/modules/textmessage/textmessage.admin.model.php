<?php
    /**
     * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
     * @class  textmessageAdminModel
     * @author wiley(wiley@nurigo.net)
     * @brief  textmessageAdminModel
     */ 
    class textmessageAdminModel extends textmessage
    {
        function getTextmessageAdminCancelReserv() {
            $args->message_id = trim(Context::get('message_id'));
            $output = executeQueryArray('textmessage.getTextmessages', $args);
            Context::set('mobilemessage_list', $output->data);

            require_once('textmessage.utility.php');
            $csutil = new CSUtility();
            Context::set('csutil', $csutil);

            $oTemplate = &TemplateHandler::getInstance();
            $tpl = $oTemplate->compile($this->module_path.'tpl', 'cancel');

            $this->add('tpl', str_replace("\n"," ",$tpl));
		}

        function getTextmessageAdminCancelGroup() {
            $args->group_ids = "'" . implode("','", explode(',', trim(Context::get('group_ids')))) . "'";
            $output = executeQueryArray('textmessage.getTextmessageGroups', $args);
            Context::set('group_list', $output->data);

            require_once('textmessage.utility.php');
            $csutil = new CSUtility();
            Context::set('csutil', $csutil);

            $oTemplate = &TemplateHandler::getInstance();
            $tpl = $oTemplate->compile($this->module_path.'tpl', 'cancel_group');
            $this->add('tpl', str_replace("\n"," ",$tpl));
        }

        function getTextmessageAdminDeleteMessages() {
            $args->message_id = trim(Context::get('message_ids'));
            $output = executeQueryArray('textmessage.getTextmessages', $args);
            Context::set('mobilemessage_list', $output->data);

            require_once('textmessage.utility.php');
            $csutil = new CSUtility();
            Context::set('csutil', $csutil);

            $oTemplate = &TemplateHandler::getInstance();
            $tpl = $oTemplate->compile($this->module_path.'tpl', 'delete_messages');

            $this->add('tpl', str_replace("\n"," ",$tpl));
		}

        function getTextmessageAdminDeleteGroup() {
            $args->group_ids = "'" . implode("','", explode(',', trim(Context::get('group_ids')))) . "'";
            $output = executeQueryArray('textmessage.getTextmessageGroups', $args);
            Context::set('group_list', $output->data);

            require_once('textmessage.utility.php');
            $csutil = new CSUtility();
            Context::set('csutil', $csutil);

            $oTemplate = &TemplateHandler::getInstance();
            $tpl = $oTemplate->compile($this->module_path.'tpl', 'delete_group');
            $this->add('tpl', str_replace("\n"," ",$tpl));
        }

		function getTextmessageAdminUnfinishedMessages() {
			$args->page = Context::get('page');
			$output = executeQueryArray('textmessage.getUnfinishedMessages',$args);
			$this->add('total_count', $output->total_count);
			$this->add('total_page', $output->total_page);
			$this->add('page', $output->page);
			$this->add('data',$output->data);
		}

		function getTextmessageAdminStatisticsMonthly() {
			$oTextmessageAdminController = &getAdminController('textmessage');

			$stats_date = Context::get('stats_date');
			$month = Context::get('month');

			$args->stats_year = substr($stats_date, 0, 4);
			$args->stats_month = $month;
			$output = executeQueryArray("textmessage.getStatisticsDaily", $args);
			if (!$output->toBool()) return $output;
			$data = $output->data;
			if (!$data) {
				$args->stats_year = substr($stats_date, 0, 4);
				$args->stats_month = $month;
				$oTextmessageAdminController->makeStatistics($args->stats_year, $args->stats_month);

				$output = executeQueryArray("textmessage.getStatisticsMonthly", $args);
				if (!$output->toBool()) return $output;
				$data = $output->data;
			}
			if (!is_array($data)) $data = array($data);

			$stats->sms_sk_count = 0;
			$stats->sms_kt_count = 0;
			$stats->sms_lg_count = 0;
			$stats->lms_sk_count = 0;
			$stats->lms_kt_count = 0;
			$stats->lms_lg_count = 0;
			$stats->mms_sk_count = 0;
			$stats->mms_kt_count = 0;
			$stats->mms_lg_count = 0;
			$stats->oversea_count = 0;

			foreach ($data as $key=>$val) {
				$stats->sms_sk_count += $val->sms_sk_count;
				$stats->sms_kt_count += $val->sms_kt_count;
				$stats->sms_lg_count += $val->sms_lg_count;
				$stats->lms_sk_count += $val->lms_sk_count;
				$stats->lms_kt_count += $val->lms_kt_count;
				$stats->lms_lg_count += $val->lms_lg_count;
				$stats->mms_sk_count += $val->mms_sk_count;
				$stats->mms_kt_count += $val->mms_kt_count;
				$stats->mms_lg_count += $val->mms_lg_count;
				$stats->oversea_count += $val->oversea_count;
			}
			$this->add('data', $stats);
		}
		function getTextmessageAdminStatistics() {
			$oTextmessageAdminController = &getAdminController('textmessage');

			$stats_date = Context::get('stats_date');
			$day = Context::get('day');

			$args->stats_year = substr($stats_date, 0, 4);
			$args->stats_month = substr($stats_date, 4, 2);
			$args->stats_day = $day;
			$output = executeQueryArray("textmessage.getStatisticsDaily", $args);
			if (!$output->toBool()) return $output;
			$data = $output->data;
			if (!$data) {
				$args->stats_year = substr($stats_date, 0, 4);
				$args->stats_month = substr($stats_date, 4, 2);
				$args->stats_day = $day;
				$oTextmessageAdminController->makeStatistics($args->stats_year, $args->stats_month, $args->stats_day);

				$output = executeQueryArray("textmessage.getStatisticsDaily", $args);
				if (!$output->toBool()) return $output;
				$data = $output->data;
			}
			if (!is_array($data)) $data = array($data);

			$stats->sms_sk_count = 0;
			$stats->sms_kt_count = 0;
			$stats->sms_lg_count = 0;
			$stats->lms_sk_count = 0;
			$stats->lms_kt_count = 0;
			$stats->lms_lg_count = 0;
			$stats->mms_sk_count = 0;
			$stats->mms_kt_count = 0;
			$stats->mms_lg_count = 0;
			$stats->oversea_count = 0;

			foreach ($data as $key=>$val) {
				$stats->sms_sk_count += $val->sms_sk_count;
				$stats->sms_kt_count += $val->sms_kt_count;
				$stats->sms_lg_count += $val->sms_lg_count;
				$stats->lms_sk_count += $val->lms_sk_count;
				$stats->lms_kt_count += $val->lms_kt_count;
				$stats->lms_lg_count += $val->lms_lg_count;
				$stats->mms_sk_count += $val->mms_sk_count;
				$stats->mms_kt_count += $val->mms_kt_count;
				$stats->mms_lg_count += $val->mms_lg_count;
				$stats->oversea_count += $val->oversea_count;
			}
			$this->add('data', $stats);
		}
	}
?>
