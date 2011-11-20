<?php
	/**
	 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
	 * @class  textmessageModel
	 * @author wiley(wiley@nurigo.net)
	 * @brief  textmessageModel
	 */
	class textmessageModel extends textmessage {

		function init() {
		}

		/**
		 * 모듈 환경설정값 가져오기
		 */
		function getModuleConfig() {
			if (!$GLOBALS['__textmessage_config__']) {
				$oModuleModel = &getModel('module');
				$config = $oModuleModel->getModuleConfig('textmessage');
				// country code
				if (!$config->default_country) $config->default_country = '82';
				if ($config->default_country == '82') $config->limit_bytes = 80;
				else $config->limit_bytes = 160;

				// callback
				$callback = explode("|@|", $config->callback); // source
				$config->a_callback = $callback;        // array
				$config->s_callback = join($callback);  // string

				// admin_phone
				if (!is_array($config->admin_phones))
					$config->admin_phones = explode("|@|", $config->admin_phones);

				$config->crypt = 'MD5';

				$GLOBALS['__textmessage_config__'] = $config;
			}
			return $GLOBALS['__textmessage_config__'];
		}

		function getCoolSMS() {
			$config = $this->getModuleConfig();
			require_once($this->module_path.'coolsms.php');
			$sms = new coolsms();
			$sms->appversion("messageXe/" . $this->version . " XE/" . __ZBXE_VERSION__);
			if ($config->service_id && $config->password) {
				$sms->setuser($config->service_id, $config->password);
			}
			return $sms;
		}

		/**
		 * 환경값 읽어오기
		 */
		function getConfig() {
			$config = $this->getModuleConfig('textmessage');
			// get balance information.
			$config->cs_cash=0;
			$config->cs_point=0;
			$config->cs_mdrop=0;

			$sms = $this->getCoolSMS();

			if ($sms->connect()) {
				$remain = $sms->remain();
				$config->cs_cash = $remain['CASH'];
				$config->cs_point = $remain['POINT'];
				$config->cs_mdrop = $remain['DROP'];
				$config->sms_price = $remain['SMS-PRICE'];
				$config->lms_price = $remain['LMS-PRICE'];
				$config->mms_price = $remain['MMS-PRICE'];
				$config->sms_volume = ((int)$config->cs_cash / (int)$config->sms_price) + ((int)$config->cs_point / (int)$config->sms_price) + (int)$cs_mdrop;
				$config->lms_volume = ((int)$config->cs_cash / (int)$config->lms_price) + ((int)$config->cs_point / (int)$config->lms_price) + ((int)$cs_mdrop / 3);
				$config->mms_volume = ((int)$config->cs_cash / (int)$config->mms_price) + ((int)$config->cs_point / (int)$config->mms_price) + ((int)$cs_mdrop / 10);
				if ($remain['RESULT-CODE'] != '00')
				{
					Context::set('cs_is_logged', false);
					switch ($remain['RESULT-CODE'])
					{
						case '20':
							Context::set('cs_error_message', '<font color="red">존재하지 않는 아이디이거나 패스워드가 틀립니다.</font><br /><a href="' . getUrl('act','dispTextmessageAdminConfig') . '">설정변경</a>');
							break;
						case '30':
							Context::set('cs_error_message', '<font color="red">사용가능한 SMS 건수가 없습니다.</font>');
							break;
						default:
							Context::set('cs_error_message', '<font color="red">오류코드:'.$remain['RESULT-CODE'].'</font>');
					}
				}
				else
				{
					Context::set('cs_is_logged', true);
				}
				$sms->disconnect();
			} else {
				Context::set('cs_is_logged', false);
				Context::set('cs_error_message', '<font color="red">서비스 서버에 연결할 수 없습니다.<br />일부 웹호스팅에서 외부로 나가는 포트 접속을 허용하지 않고 있습니다.<br /></font>');
			}
			Context::set('cs_cash', $config->cs_cash);
			Context::set('cs_point', $config->cs_point);
			Context::set('cs_mdrop', $config->cs_mdrop);
			Context::set('sms_price', $config->sms_price);
			Context::set('lms_price', $config->lms_price);
			Context::set('mms_price', $config->mms_price);
			Context::set('sms_volume', $config->sms_volume);

			return $config;
		}

		function getConfigValue(&$obj, $key, $type=null) {
			$return_value = null;
			$config = $this->getModuleConfig();

			$fieldname = $config->{$key};
			if (!$fieldname) return null;

			// 기본필드에서 확인
			if ($obj->{$fieldname}) {
				$return_value = $obj->{$fieldname};
			}

			// 확장필드에서 확인
			if ($obj->extra_vars) {
				$extra_vars = unserialize($obj->extra_vars);
				if ($extra_vars->{$fieldname}) {
					$return_value = $extra_vars->{$fieldname};
				}
			}
			if ($type=='tel' && is_array($return_value)) {
				$return_value = implode($return_value);
			}

			return $return_value;
		}

		/**
		 * @brief CashInfo
		 **/
		function getCashInfo($args=false) {
			$config = $this->getModuleConfig($args);

			require_once($this->module_path.'coolsms.php');
			$sms = new coolsms();
			$sln_reg_key = $this->getSlnRegKey();
			if ($sln_reg_key) $sms->enable_resale();
			$sms->appversion("MXE/" . $this->version . " XE/" . __ZBXE_VERSION__);
			$sms->setuser($config->cs_userid, $config->cs_passwd, $config->crypt);

			// connect
			if (!$sms->connect()) {
				// cannot connect
				return new Object(-1, 'cannot connect to server.');
			}

			// get cash info
			$result = $sms->remain();

			// disconnect
			$sms->disconnect();

			$obj = new Object();
			$obj->add('cash', $result["CASH"]);
			$obj->add('point', $result["POINT"]);
			$obj->add('mdrop', $result["DROP"]);
			$obj->add('sms_price', $result["SMS-PRICE"]);
			$obj->add('lms_price', $result["LMS-PRICE"]);
			$obj->add('mms_price', $result["MMS-PRICE"]);

			return $obj;
		}

        function getMessagesInGroup($args) {
            $query_id = 'textmessage.getMobilemessagesInGroup';

            if (!$args->page)
                $args->page = Context::get('page');
            if (!$args->list_count)
                $args->list_count = 40;
            if (!$args->page_count)
                $args->page_count = 10;

            return executeQuery($query_id, $args);
        }

        function getMessagesGrouping($args) {
            $db_info = Context::getDBInfo();
            if (strtolower(substr($db_info->db_type, 0, 5)) == 'mysql')
                $query_id = 'textmessage.getMobilemessageGrouping_MySQL';
            else
                $query_id = 'textmessage.getMobilemessageGrouping';

            if (!$args->page)
                $args->page = Context::get('page');
            if (!$args->list_count)
                $args->list_count = 40;
            if (!$args->page_count)
                $args->page_count = 10;

            $output = executeQueryArray($query_id, $args);
            if (!$output->toBool() || !$output->data) return $output;

            if (strtolower(substr($db_info->db_type, 0, 5)) != 'mysql') {
                foreach ($output->data as $no => $row) {
                    unset($args);
                    $args->gid = $row->gid;
                    $msginfo = executeQueryArray('textmessage.getMobilemessageGroupMsgInfo', $args);
                    $output->data[$no]->regdate = $msginfo->data[1]->regdate;
                    $output->data[$no]->userid = $msginfo->data[1]->userid;
                    $output->data[$no]->content = $msginfo->data[1]->content;
                    $output->data[$no]->reservdate = $msginfo->data[1]->reservdate;
                }
            }
            return $output;
        }

}
?>
