<?php
    /**
     * vi:set sw=4 ts=4 expandtab enc=utf8:
     * @class  mobilemessageModel
     * @author diver(diver@coolsms.co.kr)
     * @brief  mobilemessageModel
     */
    class mobilemessageModel extends mobilemessage {

        function init() {
        }

        /**
         * 모듈 환경설정값 가져오기
         */
        function getModuleConfig() {
            if (!$GLOBALS['__mobilemessage_config__']) {
                $oModuleModel = &getModel('module');
                $config = $oModuleModel->getModuleConfig('mobilemessage');
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

                $GLOBALS['__mobilemessage_config__'] = $config;
            }
            return $GLOBALS['__mobilemessage_config__'];
        }

        /**
         * 환경값 읽어오기
         */
        function getConfig() {
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('mobilemessage');

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

            // 캐쉬, 포인트, 문자방울 잔량 가져오기
            $config->cs_cash=0;
            $config->cs_point=0;
            $config->cs_mdrop=0;

            require_once($this->module_path.'coolsms.php');
            $sms = new coolsms();
            $sln_reg_key = $this->getSlnRegKey();
            if ($sln_reg_key) $sms->setSRK($sln_reg_key);
            $sms->appversion("MXE/" . $this->version . " XE/" . __ZBXE_VERSION__);
            if ($config->cs_userid && $config->cs_passwd) {
                $sms->setuser($config->cs_userid, $config->cs_passwd);
                if ($sms->connect()) {
                    $remain = $sms->remain();
                    $config->cs_cash = $remain['CASH'];
                    $config->cs_point = $remain['POINT'];
                    $config->cs_mdrop = $remain['DROP'];
                    if ($remain['RESULT-CODE'] != '00')
                    {
                        Context::set('cs_is_logged', false);
                        switch ($remain['RESULT-CODE'])
                        {
                            case '20':
                                Context::set('cs_error_message', '<font color="red">존재하지 않는 아이디이거나 패스워드가 틀립니다.</font>');
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
                    Context::set('cs_error_message', '<font color="red">서비스 서버에 연결할 수 없습니다.<br />일부 웹호스팅에서 외부로 나가는 포트 접속을 허용하지 않고 있습니다.<br /><a href="http://message.xpressengine.net/18243690">사용불가 웹호스팅</a> 문서를 참고하시고 목록에 없다면 신고하여 주세요.</font>');
                }
            }
            Context::set('cs_cash', $config->cs_cash);
            Context::set('cs_point', $config->cs_point);
            Context::set('cs_mdrop', $config->cs_mdrop);

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

        function getMobilemessageList() {
            $query_id = 'mobilemessage.getMobilemessageList';

            $args->page = Context::get('page');
            $args->list_count = 40;
            $args->page_count = 10;

            return executeQuery($query_id, $args);
        }

        function getMessageInfo($args) {
            $query_id = 'mobilemessage.getMobilemessage';
            return executeQuery($query_id, $args);
        }

        function getMobilemessageMessageInfo() {
            $logged_info = Context::get('logged_info');
            if (!$logged_info) return new Object(-1, 'msg_login_required');

            $args->msgid = Context::get('msgid');
            $output = $this->getMessageInfo($args);
            $output->data->content = str_replace("\r", "", $output->data->content);
            $output->data->content = str_replace("\n", "<br>", $output->data->content);

            $this->add('data', $output->data);
        }

        function getMobilemessageListByMessageId() {
            $message_ids_arr = explode(',', Context::get('message_ids'));
            $args->message_ids = "'" . implode("','", $message_ids_arr) . "'";
            $output = executeQueryArray('mobilemessage.getStatusListByMessageId', $args);
            $this->add('data', $output->data);
        }


         /**
         * @brief MemberMessageList 가져오기
         **/
        function getMobilemessageMemberMessageList() {
            $logged_info = Context::get('logged_info');
            if (!$logged_info) return new Object(-1, 'msg_login_required');

            $args->gid = Context::get('gid');
            $output = $this->getMessagesInGroup($args);
            foreach ($output->data as $no => $row) {
                $output->data[$no]->content = str_replace("\r", "", $output->data[$no]->content);
                $output->data[$no]->content = str_replace("\n", "", $output->data[$no]->content);
            }

            $this->add('total_count', $output->total_count);
            $this->add('total_page', $output->total_page);
            $this->add('page', $output->page);
            $this->add('data', $output->data);
            $this->add('gid', Context::get('gid'));
            $config = $this->getModuleConfig();
            $this->add('base_url', $config->callback_url);
        }

        /**
         * @brief MemberMessageGroupList
         **/
        function getMobilemessageMemberMessageGroupList() {
            $logged_info = Context::get('logged_info');
            if (!$logged_info) return new Object(-1, 'msg_login_required');

            $args->userid = $logged_info->user_id;
            $args->startdate = Context::get('startdate');
            $args->enddate = Context::get('enddate');
            $output = $this->getMessagesGrouping($args);
            if (!$output->toBool() || !$output->data) return new Object(-1, '조회 내용이 없습니다.');

            foreach ($output->data as $no => $row) {
                $output->data[$no]->content = str_replace("\r", "", $output->data[$no]->content);
                $output->data[$no]->content = str_replace("\n", "", $output->data[$no]->content);
            }

            $this->add('total_count', $output->total_count);
            $this->add('total_page', $output->total_page);
            $this->add('page', $output->page);
            $this->add('message_list', $output->data);
            $this->add('startdate', Context::get('startdate'));
            $this->add('enddate', Context::get('enddate'));
            $config = $this->getModuleConfig();
            $this->add('base_url', $config->callback_url);
        }

        function getMessagesInGroup($args) {
            $query_id = 'mobilemessage.getMobilemessagesInGroup';

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
                $query_id = 'mobilemessage.getMobilemessageGrouping_MySQL';
            else
                $query_id = 'mobilemessage.getMobilemessageGrouping';

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
                    $msginfo = executeQueryArray('mobilemessage.getMobilemessageGroupMsgInfo', $args);
                    $output->data[$no]->regdate = $msginfo->data[1]->regdate;
                    $output->data[$no]->userid = $msginfo->data[1]->userid;
                    $output->data[$no]->content = $msginfo->data[1]->content;
                    $output->data[$no]->reservdate = $msginfo->data[1]->reservdate;
                }
            }
            return $output;
        }

        function getPurplebookList($args) {
            $query_id = 'mobilemessage.getPurplebookList';
            return executeQueryArray($query_id, $args);
        }

        function getPurplebookListPaging($args) {
            $query_id = 'mobilemessage.getPurplebookListPaging';
            return executeQueryArray($query_id, $args);
        }

        function getUserIDsByPhoneNumber($phone_num, $country_code='82') {
            $args = new StdClass();
            $args->phone_num = $phone_num;
            $args->country = $country_code;
            $query_id = 'mobilemessage.getMapping';
            $output = executeQueryArray($query_id, $args);
            if (!$output->toBool()) return false; // 오류

            $userid_array = array();
            if (!$output->data) return $userid_array; // No Data

            foreach ($output->data as $row) {
                $userid_array[] = $row->user_id;
            }

            return $userid_array;
        }

        function getValCode($phonenum, $country='82')
        {
            $query_id = 'mobilemessage.getValCode';
            $args = new StdClass();
            $args->callno = $phonenum;
            $args->country = $country;
            $output = executeQuery($query_id, $args);
            if ($output->toBool() && $output->data) $output->valcode = $output->data->valcode;
            return $output;
        }

        /**
         * @brief 인증번호 인증
         *  $args->callno
         *  $args->valcode
         *  $args->country
         **/
        function validateValCode($args) {
            if (!$args->callno || !$args->valcode) return false;

            $output = $this->getValCode($args->callno, $args->country);
            if (!$output->toBool() || !$output->data) return false;
            // comparison
            if ($output->data->valcode == $args->valcode) return true;
            return false;
        }


        /**
         * @brief check whether the phone number is prohibited or not.
         * @return true if prohibited, otherwise return false
         **/
        function isProhibitedNumber($phonenum, $country='82') {
            $query_id = 'mobilemessage.getProhibit';
            $args = new StdClass();
            $args->phone_num = $phonenum;
            $args->country = $country;
            $output = executeQueryArray($query_id, $args);
            if (!$output->toBool() || !$output->data) return false;
            if (count($output->data) > 0) {
                $limit_date = $output->data[0]->limit_date;
                // check limit date.
                if (!$limit_date) return true;
                // compare the limit date to today.
                else if ($limit_date >= date("Ymd")) return true;
                // prohibition expired
                return false;
            }
            return false;
        }

        /**
         * @brief Group List
         **/
        function getMobilemessageGroupList() {
            $site_srl = Context::get('site_srl');
            if (!$site_srl) $site_srl = 0;
		    $oMemberModel = &getModel('member');
		    $group_list = $oMemberModel->getGroups($site_srl);
            $this->add('grouplist', $group_list);
	    }

        /**
         * @brief 주소록
         **/
        /*
        function getMobilemessagePurplebookList() {
            $logged_info = Context::get('logged_info');
            if (!$logged_info)
                return new Object(-1, 'msg_not_logged');

            $args->user_id = $logged_info->user_id;
            $args->node_route = Context::get('node_route');
            $args->node_type = Context::get('node_type');

            $oMobilemessageModel = &getModel('mobilemessage');
            $output = $oMobilemessageModel->getPurplebookList($args);

            if ((!is_array($output->data) || !count($output->data)) && $args->node_type == '1' && $args->node_route == '.') {
                return;
            }

            $data = array();

            if (is_array($output->data)) {
                foreach ($output->data as $no => $row) {
                    $obj = new StdClass();
                    $obj->attributes = new StdClass();
                    $obj->attributes->id = $row->node_id;
                    $obj->attributes->node_id = $row->node_id;
                    $obj->attributes->node_name = $row->node_name;
                    $obj->attributes->node_route = $row->node_route;
                    $obj->attributes->phone_num = $row->phone_num;
                    $obj->data = $row->node_name;
                    $obj->state = "closed";
                    $data[] = $obj;
                }
            }
            $this->add('total_count', $output->total_count);
            $this->add('data', $data);
            $this->add('base_url', Context::get('base_url'));
        }
         */

        function getMobilemessagePurplebookSearch() {
            //$searchkey = Context::get('searchkey');
            $logged_info = Context::get('logged_info');
            if (!$logged_info) return new Object(-1, 'msg_invalid_request');

            $search_word = Context::get('search_word');

            /*
            switch ($searchkey) {
                case "name":
                    $args->node_name = $searchword;
                    break;
                case "phone":
                    $args->phone_num = $searchword;
                    break;
            }
             */
            $args->user_id = $logged_info->user_id;
            $args->search_word = $search_word;
            $output = executeQueryArray('mobilemessage.getPurplebookSearch', $args);
            if (!$output->toBool()) return $output;
            $this->add('data', $output->data);
        }

        function getNotiDocInfos($module_srl) {
            if (!$module_srl) return array();
            $query_id = "mobilemessage.getNotiDocInfoByModuleSrl";
            $args->module_srl = $module_srl;
            $output = executeQueryArray($query_id, $args);
            if (!$output->toBool() || !$output->data) return array();

            foreach ($output->data as $no => $row) {
                $extra_vars = unserialize($output->data[$no]->extra_vars);
                if ($extra_vars) {
                    foreach ($extra_vars as $key => $val) {
                        $output->data[$no]->{$key} = $val;
                    }
                }
            }

            return $output->data;
        }

        function getNotiComInfo($module_srl) {
            if (!$module_srl) return false;
            $query_id = "mobilemessage.getNotiComInfoByModuleSrl";
            $args->module_srl = $module_srl;
            $output = executeQuery($query_id, $args);
            if (!$output->toBool() || !$output->data) return false;

            $extra_vars = unserialize($output->data->extra_vars);
            if ($extra_vars) {
                foreach ($extra_vars as $key => $val) {
                    $output->data->{$key} = $val;
                }
            }

            return $output->data;
        }

        function getMobilemessageFilePickerPath($mobilemessage_file_srl){
            return sprintf("./files/attach/mobilemessage/%s",getNumberingPath($mobilemessage_file_srl,3));
        }

        function getSlnRegKey() {
            if (!file_exists($this->module_path.'resale.info.php')) return false;
            require_once($this->module_path.'resale.info.php');
            return __SOLUTION_REGISTRATION_KEY__;
        }

        /**
         * @brief CashInfo
         **/
        function getCashInfo($args=false) {
            $config = $this->getModuleConfig($args);

            require_once($this->module_path.'coolsms.php');
            $sms = new coolsms();
            $sln_reg_key = $this->getSlnRegKey();
            if ($sln_reg_key) $sms->setSRK($sln_reg_key);
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

        /**
         * @brief CashInfo 가져오기 - Content-Type: JSON
         **/
        function getMobilemessageCashInfo($args=false) {
            $config = $this->getModuleConfig($args);

            require_once($this->module_path.'coolsms.php');
            $sms = new coolsms();
            $sln_reg_key = $this->getSlnRegKey();
            if ($sln_reg_key) $sms->setSRK($sln_reg_key);
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

            $this->add('cash', $result["CASH"]);
            $this->add('point', $result["POINT"]);
            $this->add('mdrop', $result["DROP"]);
            $this->add('sms_price', $result["SMS-PRICE"]);
            $this->add('lms_price', $result["LMS-PRICE"]);
            $this->add('mms_price', $result["MMS-PRICE"]);
        }
        /**
         * @brief System Point 가져오기
         **/
        function getMobilemessagePointInfo() {
            global $lang;

            $logged_info = Context::get('logged_info');
            if (!$logged_info) return new Object(-1, 'msg_login_required');

            $oPointModel = &getModel('point');
            $rest_point = $oPointModel->getPoint($logged_info->member_srl, true);

            $this->add('point', $rest_point);
            $this->add('msg_not_enough', $lang->warning_not_enough_point);
        }

        function getSharedNodes($member_srl) {
            $args->share_member = $member_srl;
            $output = executeQueryArray('mobilemessage.getSharedNodes', $args);
            return $output;
        }

        /**
         * @brief 주소록
         **/
        function getMobilemessagePurplebookList() {
            $node_id = Context::get('node_id');
            $node_type = Context::get('node_type');
            $rel = Context::get('rel');

            $logged_info = Context::get('logged_info');
            if (!$logged_info) return new Object(-1, 'msg_login_required');

            $data = array();

            if ($node_id=='root') {
                $obj = new StdClass();
                $obj->attr = new StdClass();
                $obj->attr->id = 'node_0';
                $obj->attr->node_id = 'f.';
                $obj->attr->node_name = '주소록 폴더';
                $obj->attr->node_route = '';
                $obj->attr->subfolder = '';
                $obj->attr->subnode = '';
                $obj->attr->rel = 'root';
                $obj->state = 'closed';
                $obj->data = '주소록 폴더';
                $data[] = $obj;
                $this->add('data', $data);
                return;
            }

            if ($node_id=='all') {
                $obj = new StdClass();
                $obj->attr = new StdClass();
                $obj->attr->id = 'node_0';
                $obj->attr->node_id = 'f.';
                $obj->attr->node_name = '주소록 폴더';
                $obj->attr->node_route = '';
                $obj->attr->subfolder = '';
                $obj->attr->subnode = '';
                $obj->attr->rel = 'root';
                $obj->state = 'closed';
                $obj->data = '주소록 폴더';
                $data[] = $obj;
                $shared = new StdClass();
                $shared->attr = new StdClass();
                $shared->attr->id = 'node_1';
                $shared->attr->node_id = 's.';
                $shared->attr->node_name = '공유받은 폴더';
                $shared->attr->node_route = '';
                $shared->attr->subfolder = '';
                $shared->attr->subnode = '';
                $shared->attr->rel = 'shared';
                $shared->state = 'closed';
                $shared->data = '공유받은 폴더';
                $data[] = $shared;
                $trashcan = new StdClass();
                $trashcan->attr = new StdClass();
                $trashcan->attr->id = 'node_2';
                $trashcan->attr->node_id = 't.';
                $trashcan->attr->node_name = '휴지통';
                $trashcan->attr->node_route = '';
                $trashcan->attr->subfolder = '';
                $trashcan->attr->subnode = '';
                $trashcan->attr->rel = 'trashcan';
                $trashcan->state = 'closed';
                $trashcan->icon = 'closed';
                //$trashcan->data = array('휴지통',array(href=>'http://www.naver.com/', title=>'<a onclick="clearTrash()">비우기</a>',icon=>Context::get('cleartrash_ico'),aaa=>'<a href="http://www.coolsms.co.kr" onclick="alert(\'aaaa\');" class="clearTrash"><b>비우기111</b></a>',data=>'<a onclick="alert(\'aaaa\');">비우기</a>'));
                $trashcan->data = '휴지통';
                $data[] = $trashcan;
                $this->add('data', $data);
                return;
            }

            if ($node_type=='1'&&$node_id=='s.') {
                $output = $this->getSharedNodes($logged_info->member_srl);
                if (!$output->toBool()) return $output;
                if ($output->data) {
                    foreach ($output->data as $no => $val) {
                        $args->node_id = $val->node_id;
                        $out2 = executeQuery('mobilemessage.getNodeInfoByNodeId', $args);
                        if (!$out2->toBool()) return $out2;
                        $row = $out2->data;
                        $obj = new StdClass();
                        $obj->attr = new StdClass();
                        $obj->attr->id = 'node_'.$row->node_id;
                        $obj->attr->rel = 'folder';
                        $obj->attr->node_id = $row->node_id;
                        $obj->attr->node_name = $row->node_name;
                        $obj->attr->node_route = $row->node_route;
                        $obj->attr->phone_num = $row->phone_num;
                        $obj->attr->subfolder = $row->subfolder;
                        $obj->attr->subnode = $row->subnode;
                        $obj->attr->shared = $row->shared;
                        $obj->data = $row->node_name;
                        if ($row->subfolder > 0) $obj->state = "closed";
                        $data[] = $obj;

                    }
                    $this->add('total_count', $output->total_count);
                    $this->add('data', $data);
                    $config = $this->getModuleConfig();
                    $this->add('base_url', $config->callback_url);
                    return;
                }
            }

            // get node_route
            switch ($node_id) {
                case "f.":
                case "t.":
                case "s.":
                    $node_route = $node_id;
                    break;
                default:
                    if ($node_id) {
                        //$args->user_id = $logged_info->user_id;
                        $args->node_id = $node_id;
                        $output = executeQuery('mobilemessage.getNodeInfoByNodeId', $args);
                        if (!$output->toBool()) return $output;
                        $node_route = $output->data->node_route . $node_id . '.';
                        $user_id = $output->data->user_id;
                    } else {
                        $node_route = 'f.';
                    }
                    break;
            }

            unset($args);
            if (!$user_id) $user_id = $logged_info->user_id;
            $args->user_id = $user_id;
            $args->node_route = $node_route;
            $args->node_type = $node_type;

            /*
            if ($node_type == '1') {
                $query_id = 'mobilemessage.getFolderList';
            } else {
                $query_id = 'mobilemessage.getPurplebookList';
            }
            $output = executeQueryArray($query_id, $args);
             */
            $output = $this->getPurplebookList($args);

            if ((!is_array($output->data) || !count($output->data)) && $args->node_type == '1' && $args->node_route == '.') {
                return;
            }

            if (is_array($output->data)) {
                foreach ($output->data as $no => $row) {
                    $obj = new StdClass();
                    $obj->attr = new StdClass();
                    $obj->attr->id = 'node_'.$row->node_id;
                    if ($row->shared) {
                        $obj->attr->rel = 'shared_folder';
                    } else {
                        $obj->attr->rel = 'folder';
                    }
                    $obj->attr->node_id = $row->node_id;
                    $obj->attr->node_name = $row->node_name;
                    $obj->attr->node_route = $row->node_route;
                    $obj->attr->phone_num = $row->phone_num;
                    $obj->attr->subfolder = $row->subfolder;
                    $obj->attr->subnode = $row->subnode;
                    $obj->attr->shared = $row->shared;
                    $obj->data = $row->node_name;
                    if ($row->subfolder > 0) $obj->state = "closed";
                    $data[] = $obj;
                }
            }
            $this->add('total_count', $output->total_count);
            $this->add('data', $data);
            $config = $this->getModuleConfig();
            $this->add('base_url', $config->callback_url);
        }

        function getMobilemessageCallbackNumbers() {
            $logged_info = Context::get('logged_info');
            if (!$logged_info) return new Object(-1, 'msg_login_required');
            $args->member_srl = $logged_info->member_srl;
            $output = executeQueryArray('mobilemessage.getCallbackNumbers', $args);
            if (!$output->toBool()) return $output;
            $this->add('data', $output->data);
        }

        function getDefaultCallbackNumber() {
            $logged_info = Context::get('logged_info');
            if (!$logged_info) return false;
            $args->member_srl = $logged_info->member_srl;
            $output = executeQueryArray('mobilemessage.getDefaultCallbackNumber', $args);
            if (!$output->toBool()) return false;
            if ($output->data && count($output->data) > 0) return $output->data[0]->phonenum;
            return false;
        }

        function getMobilemessagePurplebookSharedUsers() {
            $logged_info = Context::get('logged_info');
            if (!$logged_info) return new Object(-1, 'msg_invalid_request');

            $node_id = Context::get('node_id');
            if (in_array($node_id, array('f.','s.','t.'))) return new Object(-1, 'msg_cannot_share_root');

            $args->node_id = $node_id;
            $output = executeQuery('mobilemessage.getNodeInfoByNodeId', $args);
            $node_info = $output->data;
            if ($logged_info->member_srl != $node_info->member_srl) return new Object(-1, 'msg_no_permission_to_share');

            $args->node_id = $node_id;
            $output = executeQueryArray('mobilemessage.getSharedUsers', $args);
            if (!$output->toBool()) return $output;
            $this->add('data',$output->data);
        }

        /*
        function getPostNode($node_route) {
            $route_arr = preg_split('/\./', trim($node_route, '.'));
            $last = count($route_arr) - 1;
            if ($last < 0) return;
            return $route_arr[$last];
        }
         */

        function getRootFolderName($node_id) {
            switch($node_id) {
                case 'f.':
                    return "주소록 폴더";
                case 's.':
                    return "공유받은 폴더";
                    break;
                case 't.':
                    return "휴지통";
            }
        }
        function getMobilemessagePurplebookProperties() {
            $logged_info = Context::get('logged_info');
            if (!$logged_info) return new Object(-1, 'msg_invalid_request');

            $node_id = Context::get('node_id');
            $data = array();

            // address root folder
            if ($node_id == 'f.') {
                $info = new StdClass();
                $info->name = '폴더명';
                $info->value = '주소록 폴더';
                $data[] = $info;
                $this->add('data',$data);
                return;
            }

            // root of shared folder
            if ($node_id == 's.') {
                $info = new StdClass();
                $info->name = '폴더명';
                $info->value = '공유 폴더';
                $data[] = $info;

                $this->add('data',$data);
                return;
            }

            // trashcan
            if ($node_id == 't.') {
                $info = new StdClass();
                $info->name = '폴더명';
                $info->value = '휴지통';
                $data[] = $info;
                $this->add('data',$data);
                return;
            }

            $args->node_id = $node_id;
            $output = executeQuery('mobilemessage.getNodeInfoByNodeId', $args);
            if (!$output->toBool()) return $output;
            $node_info = $output->data;;

            /*
            $info = new StdClass();
            $info->name = '폴더명';
            $info->value = $node_info->node_name;
            $data[] = $info;
             */
            if ($node_info->node_type=='1') {
                $info = new StdClass();
                $info->name = '서브폴더';
                $info->value = $node_info->subfolder . ' 개';
                $data[] = $info;

                $info = new StdClass();
                $info->name = '주소록명단';
                $info->value = $node_info->subnode . ' 명';
                $data[] = $info;

                // share info
                $args->node_id = $node_id;
                $output = executeQueryArray('mobilemessage.getSharedUsers', $args);
                if (!$output->toBool()) return $output;
                $shared_count = count($output->data);
                if ($shared_count) {
                    if ($node_info->member_srl == $logged_info->member_srl) {
                        $info = new StdClass();
                        $info->name = '공유정보';
                        if ($shared_count > 1)
                            $info->value = sprintf("%s 외 %u 명", $output->data[0]->nick_name, $shared_count);
                        else
                            $info->value = sprintf("%s", $output->data[0]->nick_name);
                        $info->value = sprintf("<a href=\"#\" onclick=\"obj=document.getElementById('node_%u');pb_share_folder(obj);\">%s</a>", $node_id, $info->value);
                        $data[] = $info;
                    } else {
                        $oMemberModel = &getModel('member');
                        $member_info = $oMemberModel->getMemberInfoByMemberSrl($node_info->member_srl);
                        if ($member_info) {
                            $info = new StdClass();
                            $info->name = '소유자';
                            $info->value = sprintf('<a href="#popup_menu_area" class="member_%u" onclick="return false">%s</a>', $member_info->member_srl, $member_info->nick_name);
                            $data[] = $info;
                        }
                    }
                }
            } else {
                // name
                $info = new StdClass();
                $info->name = '이름';
                $info->value = $node_info->node_name;
                $data[] = $info;
                // phone number
                $info = new StdClass();
                $info->name = '전화번호';
                $info->value = $node_info->phone_num;
                $data[] = $info;
                // folder name
                $parent_node = $this->getPostNode($node_info->node_route);
                if (in_array($parent_node, array('f','s','t'))) {
                    $info = new StdClass();
                    $info->name = '폴더명';
                    $info->value = $this->getRootFolderName($parent_node.'.');
                    $data[] = $info;
                } else {
                    if ($parent_node) {
                        $args->node_id = $parent_node;
                        $output = executeQuery('mobilemessage.getNodeInfoByNodeId',$args);
                        if (!$output->toBool()) return $output;
                        $parent_node_info = $output->data;;
                        if ($parent_node_info) {
                            $info = new StdClass();
                            $info->name = '폴더명';
                            //$info->value = sprintf("<a href=\"#\" onclick=\"jQuery('#smsPurplebookTree').jstree('search','node_%s'); return false;\">%s</a>", $parent_node_info->node_id, $parent_node_info->node_name);
                            $info->value = $parent_node_info->node_name;
                            $data[] = $info;
                        }
                    }
                }
                if ($node_info->member_srl != $logged_info->member_srl) {
                    $oMemberModel = &getModel('member');
                    $member_info = $oMemberModel->getMemberInfoByMemberSrl($node_info->member_srl);
                    if ($member_info) {
                        $info = new StdClass();
                        $info->name = '소유자';
                        $info->value = sprintf('<a href="#popup_menu_area" class="member_%u" onclick="return false">%s</a>', $member_info->member_srl, $member_info->nick_name);
                        $data[] = $info;
                    }
                }

            }

            $this->add('data',$data);
        }

        function getMobilemessageLatestNumbers() {
            $logged_info = Context::get('logged_info');
            if (!Context::get('is_logged') || !$logged_info) return new Object(-1, 'login_required');

            $args->member_srl = $logged_info->member_srl;
            $output = executeQueryArray('mobilemessage.getRecentReceivers', $args);
            if (!$output->toBool()) return $output;
            $latest_numbers = array();
            if ($output->data) {
                foreach ($output->data as $no => $row) {
                    $obj = new stdclass();
                    $obj->receiver_srl = $row->receiver_srl;
                    $obj->phone_num = $row->phone_num;
                    $obj->ref_name = $row->ref_name;
                    $latest_numbers[] = $obj;
                }
            }
            $this->add('data', $latest_numbers);
        }

        function getMobilemessageSavedMessages() {
            $logged_info = Context::get('logged_info');
            if (!Context::get('is_logged') || !$logged_info) return new Object(-1, 'login_required');

            $args->member_srl = $logged_info->member_srl;
            $output = executeQueryArray('mobilemessage.getKeepingInfo', $args);
            if (!$output->toBool()) return $output;
            $latest_messages = array();
            if ($output->data) {
                foreach ($output->data as $no => $row) {
                    $obj = new stdclass();
                    $obj->keeping_srl = $row->keeping_srl;
                    $obj->content = $row->content;
                    /*
                    $obj->content = str_replace("\r", "", $obj->content);
                    $obj->content = str_replace("\n", "", $obj->content);
                     */
                    $latest_messages[] = $obj;
                }
            }
            $this->add('data', $latest_messages);
        }

        function getMobilemessagePurplebookSearchFolder() {
            $logged_info = Context::get('logged_info');
            if (!Context::get('is_logged') || !$logged_info) return new Object(-1, 'login_required');

            $search = Context::get('search');
            $args->member_srl = $logged_info->member_srl;
            if (substr($search,0,5)=='node_') {
                $args->node_id = substr($search,5);
            }
            $output = executeQueryArray('mobilemessage.getSearchFolder', $args);
            if (!$output->toBool()) return $output;

            $data = array();
            if ($output->data) {
                foreach ($output->data as $no => $val) {
                    $data[] = $val->node_id;
                }
            }
            $this->add('data', $data);
        }
    }
?>
