<?php
    /**
     * vi:set sw=4 ts=4 expandtab enc=utf8:
     * @class  mobilemessageView
     * @author diver(diver@coolsms.co.kr)
     * @brief  mobilemessageView
     */
    class mobilemessageView extends mobilemessage {
        var $use_point;
        var $sms_point;
        var $lms_point;
        var $alert_message="";

        function init() {
            // 템플릿 설정
            $this->setTemplatePath($this->module_path.'tpl');

            $oModel = &getModel('mobilemessage');
            $this->config = $oModel->getModuleConfig();
            Context::set('base_url', $this->config->callback_url);
        }


        /**
         * @brief 핸드폰인증 루틴
         **/
        function dispMobilemessageValidation() {
            // 핸드폰인증 애드온의 모듈 의존성을 낮추기 위해 애드온으로 코드 이동. dispMobilemessageValidation 함수는 존재해야함. - 2009/08/13
        }

        /**
         * @brief 그룹목록 - Content-Type: JSON
         **/
        function dispMobilemessageGroupList() {
            $site_srl = Context::get('site_srl');
            if (!$site_srl) $site_srl = 0;
		    $oMemberModel = &getModel('member');
		    $group_list = $oMemberModel->getGroups($site_srl);
            Context::set('group_list', $group_list);
	    }

        /**
         * @brief 그룹에 속하는 멤버정보 가져오기(페이징) - Content-Type: JSON
         **/
        function dispMobilemessageMembersInGroupPaging() {
            $args = new Object();
            $this->makeArgs($args);

            if ($args->group_srl)
                $query_id = 'mobilemessage.getMembersInGroupPaging';
            else
                $query_id = 'mobilemessage.getMembersPaging';

            $output = executeQueryArray($query_id, $args);
            $data = $this->getResponseData($output->data);
            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            //Context::set('page_navigation', $output->page_navigation);
            Context::set('member_list', $data);
        }

        /**
         * @brief 그룹에 속하는 모든 멤버정보 가져오기 - Content-Type: JSON
         **/
        function dispMobilemessageMembersInGroup() {
            $query_id = 'mobilemessage.getMembersInGroup';
            $args->group_srl = Context::get('group_srl');
            $output = executeQueryArray($query_id, $args);
            $data = $this->getResponseData($output->data, Context::get('nonphone_skip'));
            Context::set('member_list', $data);
        }


        function makeArgs(&$args) {
            $args->is_admin = Context::get('is_admin');
            $args->is_denied = Context::get('is_denied');
            $args->group_srl = Context::get('group_srl');
            $args->page = Context::get('page');

            $search_target = trim(Context::get('search_target'));
            $search_keyword = trim(Context::get('search_keyword'));

            if($search_target && $search_keyword) {
                switch($search_target) {
                    case 'user_id' :
                            if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
                            $args->s_user_id = $search_keyword;
                        break;
                    case 'user_name' :
                            if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
                            $args->s_user_name = $search_keyword;
                        break;
                    case 'nick_name' :
                            if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
                            $args->s_nick_name = $search_keyword;
                        break;
                    case 'email_address' :
                            if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
                            $args->s_email_address = $search_keyword;
                        break;
                    case 'regdate' :
                            $args->s_regdate = ereg_replace("[^0-9]","",$search_keyword);
                        break;
                    case 'regdate_more' :
                            $args->s_regdate_more = substr(ereg_replace("[^0-9]","",$search_keyword) . '00000000000000',0,14);
                        break;
                    case 'regdate_less' :
                            $args->s_regdate_less = substr(ereg_replace("[^0-9]","",$search_keyword) . '00000000000000',0,14);
                        break;
                    case 'last_login' :
                            $args->s_last_login = $search_keyword;
                        break;
                    case 'last_login_more' :
                            $args->s_last_login_more = substr(ereg_replace("[^0-9]","",$search_keyword) . '00000000000000',0,14);
                        break;
                    case 'last_login_less' :
                            $args->s_last_login_less = substr(ereg_replace("[^0-9]","",$search_keyword) . '00000000000000',0,14);
                        break;
                    case 'extra_vars' :
                            $args->s_extra_vars = ereg_replace("[^0-9]","",$search_keyword);
                        break;
                }
            }

        }

        function getResponseData(&$resultset, $nonphone_skip=false) {
            $fields = array("user_id", "user_name", "cellphone");
            $data = array();
            if (is_array($resultset)) {
                foreach ($resultset as $no => $row) {
                    $obj = $this->getResponseObject($row, $fields);
                    if ( !($nonphone_skip && !$obj->cellphone) )
                        $data[] = $obj;
                }
            }
            return $data;
        }

        function getResponseObject(&$row, &$fields) {
            $extra_vars = unserialize($row->extra_vars);

            $obj = new StdClass();
            $obj->cellphone = '';
            if ($this->config->cellphone_fieldname) {
                if ($extra_vars->{$this->config->cellphone_fieldname})
                    $obj->cellphone = $extra_vars->{$this->config->cellphone_fieldname};
                else
                    $obj->cellphone = $row->{$this->config->cellphone_fieldname};
            }
            foreach ($fields as $f) {
                if (isset($row->{$f})) $obj->{$f} = $row->{$f};
                else if (isset($extra_vars->{$f})) {
                    $obj->{$f} = $extra_vars->{$f};
                    $obj->{$f} = str_replace('|@|', '', $obj->{$f});
                }
            }

            return $obj;
        }

        function findFields($keywords, $column_type=null) {
             $fields = array();

             if (is_array($keywords)) $keywords = join($keywords, '|');

            // 확장 필드에서 찾아보기
            $oMemberModel = &getModel('member');
            $extra_fields = $oMemberModel->getJoinFormList();
            if (!$extra_fields) {
                return $fields;
            }

            foreach ($extra_fields as $row) {
                if ($column_type && $row->column_type != $column_type) continue;
                if (preg_match('/' . $keywords . '/i', $row->column_title)) {
                    $obj = new Object();
                    $obj->field_name = $row->column_name;
                    $fields[] = $obj;
                }
            }

            return $fields;
        }

       /**
         * @brief 전화번호 형식 필드명 가져오기
         **/
        function dispMobilemessageDetectFieldName() {
            $keywords = Context::get('keywords');
            $column_type = Context::get('column_type');
            $fields = $this->findFields($keywords, $column_type);
            Context::set('field_names', $fields);
        }

        /**
         * @brief JSON데이터를 받아서 문자전송
         **/
        function dispMobilemessageJSON() {
            $oModel = &getModel('mobilemessage');

            // 로그인 되어있지 않으면 빠져나간다.
            if (!Context::get('is_logged')) {
                return new Object(-1, 'msg_not_logged');
            }

            $decoded = $this->getJSON('json');
            $this->use_point = Context::get('use_point');
            $this->sms_point = Context::get('sms_point');
            $this->lms_point = Context::get('lms_point');

            require_once($this->module_path.'coolsms.php');
            $this->sms = new coolsms();
            $sln_reg_key = $oModel->getSlnRegKey();
            if ($sln_reg_key) $sms->enable_resale();
            $this->sms->appversion("MXE/" . $this->version . " XE/" . __ZBXE_VERSION__);
            $this->sms->setuser($this->config->cs_userid, $this->config->cs_passwd);
            $this->sms->emptyall();

            $gid = coolsms::keygen();

            $error_count=0;
            if (is_array($decoded)) {
                foreach ($decoded as $row) {
                    $row->gid = $gid;
                    $error_count += $this->sendJSON($row);
                }
            } else {
                $decoded->gid = $gid;
                $error_count += $this->sendJSON($decoded);
            }

            if (!$this->sms->connect()) {
                // cannot connect
                return new Object(-1, 'cannot connect to server.');
            }

            $data = array();
            $succ = 0;
            $fail = 0;

            if ($this->sms->send()) {
                $result = $this->sms->getr();

                $err_nospare = FALSE;
                foreach ($result as $row) {
                    if ($row["RESULT-CODE"] == "00")
                        $succ++;
                    else
                        $fail++;
                    if ($row["RESULT-CODE"] == "30")
                        $err_nospare = TRUE;
                }
                if ($err_nospare)
                    $this->alert_message .= "\n사용가능 SMS건수가 없습니다.";

                $obj = new StdClass();
                $obj->result_code = $row["RESULT-CODE"];
                $obj->group_id = $row["GROUP-ID"];
                $obj->message_id = $row["MESSAGE-ID"];
                $obj->called_number = $row["CALLED-NUMBER"];
                $data[] = $obj;
            }
            $this->sms->disconnect();

            $fail += $error_count;

            Context::set('return_data', $data);
            Context::set('success_count', $succ);
            Context::set('failure_count', $fail);
            Context::set('alert_message', $this->alert_message);
        }

        function minusPoint($point) {
            global $lang;

            $logged_info = Context::get('logged_info');
            if (!$logged_info)
                return new Object(-1, 'msg_not_logged');

            $current_version = $this->getXEVerInt();
            $v115 = $this->getVerInt("1.1.5");

            $oPointModel = &getModel('point');
            $rest_point = $oPointModel->getPoint($logged_info->member_srl, true);
            if ($rest_point < $point) {
                $this->alert_message .= "\n{$lang->warning_not_enough_point}";
                return false;
            }

            $oPointController = &getController('point');
            if ($current_version > $v115) {
                $oPointController->setPoint($logged_info->member_srl, $point, 'minus');
            } else {
                $oPointController->setPoint($logged_info->member_srl, ($rest_point - $point));
            }

            return true;
        }

        function sendJSON($obj) {
            $error_count = 0;

            $refid = $obj->refid;

            $oMemberModel = &getModel('member');
            if ($refid) {
                $member_info = $oMemberModel->getMemberInfoByUserID($refid);
                if ($member_info)
                    $obj->text = $this->mergeKeywords($obj->text, $member_info);
            } else {
                $member_info = new StdClass();
                $member_info->user_name = $obj->refname;
                $obj->text = $this->mergeKeywords($obj->text, $member_info);
            }
            // name
            $member_info = new StdClass();
            $member_info->name = $obj->refname;
            $obj->text = $this->mergeKeywords($obj->text, $member_info);

            $msgtype = strtoupper($obj->msgtype);
            $recipient = $obj->recipient;
            $callback = $obj->callback;
            $subject=false;
            if ($obj->subject)
                $subject = $obj->subject;
            $text = iconv("utf-8", "euc-kr//TRANSLIT", $obj->text);
            $splitlimit = $obj->splitlimit;
            $refname = iconv("utf-8", "euc-kr//TRANSLIT", $obj->refname);
            $reservdate = $obj->reservdate;

            $textlen = strlen($text);

            // korea 80 bytes as default
            $bytes_per_each = 80;

            // international 160 bytes
            if (!$obj->country) $obj->country = $this->config->default_country;
            if ($obj->country != '82') $bytes_per_each = 160;

            $quantity = ceil($textlen / $bytes_per_each);

            $oMobilemessageController = &getController("mobilemessage");


            if ($msgtype == 'SMS') {
                for ($i = 0; $i < $quantity; $i++) {
                    if ($this->use_point == 'Y') {
                        if (!$this->minusPoint($this->sms_point))
                            break;
                    }

                    $content = coolsms::cutout($text, $bytes_per_each);
                    $mid = coolsms::keygen();

                    $args = new StdClass();
                    $args->rcvnum = $recipient;
                    $args->callback = $callback;
                    $args->msg = $content;
                    $args->callname = $refname;
                    $args->reservdate = $reservdate;
                    $args->msgid = $mid;
                    $args->groupid = $obj->gid;
                    $args->country = $obj->country;
                    if (!$this->sms->addsmsobj($args)) {
                        $error_count++;
                    }

                    $text = substr($text, strlen($content));

                    // DB INSERT
                    unset($args);
                    $logged_info = Context::get('logged_info');
                    if ($logged_info)
                        $args->userid = $logged_info->user_id;
                    $args->gid = $obj->gid;
                    $args->mid = $mid;
                    $args->mtype = $msgtype;
                    $args->callno = $recipient;
                    $args->callback = $callback;
                    $args->content = iconv("euc-kr", "utf-8//TRANSLIT", $content);
                    $args->reservdate = $reservdate;
                    if ($reservdate)
                        $args->reservflag = "Y";
                    $args->ref_username = $obj->refname;
                    $args->country = $obj->country;
                    $oMobilemessageController->insertMobilemessage($args);
                }
            } else { // MMS
                if ($this->use_point == 'Y') {
                    if (!$this->minusPoint($this->lms_point))
                        break;
                }

                $mid = coolsms::keygen();
                if (!$subject)
                    $subject = coolsms::cutout($text, 20);

                $args = new StdClass();
                $args->rcvnum = $recipient;
                $args->callback = $callback;
                $args->subject = $subject;
                $args->msg = $text;
                $args->callname = $refname;
                $args->reservdate = $reservdate;
                $args->msgid = $mid;
                $args->groupid = $obj->gid;
                $args->country = $obj->country;
                if (!$this->sms->addlmsobj($args)) {
                    $error_count++;
                }

                // DB INSERT
                unset($args);
                $logged_info = Context::get('logged_info');
                if ($logged_info)
                    $args->userid = $logged_info->user_id;
                $args->gid = $obj->gid;
                $args->mid = $mid;
                $args->mtype = $msgtype;
                $args->callno = $recipient;
                $args->callback = $callback;
                $args->subject = $subject;
                $args->content = $obj->text;
                $args->reservdate = $reservdate;
                if ($reservdate)
                    $args->reservflag = "Y";
                $args->country = $obj->country;
                $oMobilemessageController->insertMobilemessage($args);
            }
            return $error_count;
        }

        /**
         * @brief CashInfo 가져오기 - Content-Type: JSON
         * 2010/01/10 moved to getMobilemessageCashInfo
         **/
        function dispMobilemessageGetCashInfo() {
            $oMobilemessageModel = &getModel('mobilemessage');

            require_once($this->module_path.'coolsms.php');
            $sms = new coolsms();
            $sln_reg_key = $oMobilemessageModel->getSlnRegKey();
            if ($sln_reg_key) $sms->enable_resale();
            $sms->appversion("MXE/" . $this->version . " XE/" . __ZBXE_VERSION__);
            $sms->setuser($this->config->cs_userid, $this->config->cs_passwd);

            // connect
            if (!$sms->connect()) {
                // cannot connect
                return new Object(-1, 'cannot connect to server.');
            }

            // get cash info
            $result = $sms->remain();

            // disconnect
            $sms->disconnect();

            Context::set('cash', $result["CASH"]);
            Context::set('point', $result["POINT"]);
            Context::set('mdrop', $result["DROP"]);
        }

        /**
         * @brief System Point 가져오기 - Content-Type: JSON
         * 2011/01/10 moved to getMobilemessagePointInfo
         **/
        function dispMobilemessageGetPointInfo() {
            global $lang;

            $logged_info = Context::get('logged_info');
            if (!$logged_info)
                return new Object(-1, 'msg_not_logged');

            $oPointModel = &getModel('point');
            $rest_point = $oPointModel->getPoint($logged_info->member_srl, true);

            Context::set('point', $rest_point);
            Context::set('msg_not_enough', $lang->warning_not_enough_point);
        }

        /**
         * @brief MemberMessageGroupList 가져오기 - Content-Type: JSON
         **/
        function dispMobilemessageMemberMessageGroupList() {
            // mobilemessage model 객체 생성후 목록을 구해옴
            $oMobilemessageModel = &getModel('mobilemessage');

            $logged_info = Context::get('logged_info');
            if (!$logged_info)
                return new Object(-1, 'msg_not_logged');

            $args->userid = $logged_info->user_id;
            $args->startdate = Context::get('startdate');
            $args->enddate = Context::get('enddate');
            $output = $oMobilemessageModel->getMessagesGrouping($args);
            if (!$output->toBool() || !$output->data) return new Object(-1, '조회 내용이 없습니다.');

            foreach ($output->data as $no => $row) {
                $output->data[$no]->content = str_replace("\r", "", $output->data[$no]->content);
                $output->data[$no]->content = str_replace("\n", "", $output->data[$no]->content);
            }

            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('message_list', $output->data);
        }

         /**
         * @brief MemberMessageList 가져오기 - Content-Type: JSON
         **/
        function dispMobilemessageMemberMessageList() {
            // mobilemessage model 객체 생성후 목록을 구해옴
            $oMobilemessageModel = &getModel('mobilemessage');

            $logged_info = Context::get('logged_info');
            if (!$logged_info)
                return new Object(-1, 'msg_not_logged');

            $args->gid = Context::get('gid');
            $output = $oMobilemessageModel->getMessagesInGroup($args);
            foreach ($output->data as $no => $row) {
                $output->data[$no]->content = str_replace("\r", "", $output->data[$no]->content);
                $output->data[$no]->content = str_replace("\n", "", $output->data[$no]->content);
            }

            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('data', $output->data);
        }

         /**
         * @brief MessageInfo 가져오기 - Content-Type: JSON
         **/
        function dispMobilemessageMessageInfo() {
            // mobilemessage model 객체 생성후 목록을 구해옴
            $oMobilemessageModel = &getModel('mobilemessage');

            $logged_info = Context::get('logged_info');
            if (!$logged_info)
                return new Object(-1, 'msg_not_logged');

            $args->msgid = Context::get('msgid');
            $output = $oMobilemessageModel->getMessageInfo($args);
            $output->data->content = str_replace("\r", "", $output->data->content);
            $output->data->content = str_replace("\n", "<br>", $output->data->content);

            Context::set('data', $output->data);
        }

        function dispMobilemessageExcelDownload() {
            $download_fields = Context::get('download_fields');
            if (!$download_fields) $download_fields = "user_id,user_name,cellphone";
            $download_fields_arr = explode(',', $download_fields);

            // check permission
            $allowed = false;
            $allow_group = Context::get('allow_group');
            $group_srls = explode(',', $allow_group);
            $logged_info = Context::get('logged_info');
            if (!$logged_info) return new Object(-1, 'msg_invalid_request');
            $oMemberModel = &getModel('member');
            foreach ($group_srls as $group_srl) {
                $group = $oMemberModel->getGroup($group_srl);
                if (in_array($group->title, $logged_info->group_list)) {
                    $allowed = true;
                }
            }
            if (!$allowed && $logged_info->is_admin != 'Y') return new Object(-1, 'msg_invalid_request');

            header("Content-Type: Application/octet-stream;");
            header("Content-Disposition: attachment; filename=\"members-" . date('Ymd') . ".xls\"");

            echo '<html>';
            echo '<head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /></head>';
            echo '<body>';
            echo '<table>';

            // header
            echo '<tr>';
            foreach ($download_fields_arr as $field) {
                echo "<th>{$field}</th>";
            }
            echo "</tr>\n";

            // arguments
            $args = new Object();
            $this->makeArgs($args);

            // include utility
            require_once('mobilemessage.utility.php');

            // only mysql
            $db_info = Context::getDBInfo();
            if ($args->group_srl) {
                $query = "SELECT * FROM {$db_info->db_table_prefix}_member member"
                    ." JOIN {$db_info->db_table_prefix}_member_group_member member_group"
                    ." ON  member_group.member_srl = member.member_srl"
                    ." WHERE member_group.group_srl = {$args->group_srl}";

                $oDB = &DB::getInstance();
                $result = $oDB->_query($query);
                require_once('zMigration.class.php');
                $dbtool = new zMigration();
                $dbtool->setDBInfo($db_info);

                while ($row = $dbtool->fetch($result)) {
                    $obj = $this->getResponseObject($row, $download_fields_arr);
                    $obj->cellphone = CSUtility::getDashTel(str_replace('|@|', '', $obj->cellphone));
                    // skip if no phone number.
                    if (Context::get('nonphone_skip') && !$obj->cellphone) continue;
                    echo '<tr>';
                    foreach ($download_fields_arr as $field) {
                        if (isset($obj->{$field})) echo '<td style="mso-number-format:\@\">' . $obj->{$field} . '</td>';
                    }
                    echo "</tr>\n";
                    unset($obj);
                    unset($row);
                }
            } else {
                // memory limit problem
                $query_id = 'mobilemessage.getMembers';
                $output = executeQueryArray($query_id, $args);

                foreach ($output->data as $no => $row) {
                    $obj = $this->getResponseObject($row, $download_fields_arr);
                    $obj->cellphone = CSUtility::getDashTel(str_replace('|@|', '', $obj->cellphone));
                    // skip if no phone number.
                    if (Context::get('nonphone_skip') && !$obj->cellphone) continue;
                    echo '<tr>';
                    foreach ($download_fields_arr as $field) {
                        if (isset($obj->{$field})) echo '<td style="mso-number-format:\@\">' . $obj->{$field} . '</td>';
                    }
                    echo "</tr>\n";
                    unset($obj);
                    unset($row);
                }
            }

            // tail
            echo '</table>';
            echo '</body>';
            echo '</html>';

            exit(0);
        }

        function dispMobilemessagePurplebookDownload() {
            $logged_info = Context::get('logged_info');
            if (!$logged_info) return new Object(-1, 'msg_not_logged');

            header("Content-Type: Application/octet-stream;");
            header("Content-Disposition: attachment; filename=\"phonelist-" . date('Ymd') . ".xls\"");

            $node_id = Context::get('node_id');
            if ($node_id && !in_array($node_id, array('f.','s.','t.'))) {
                $args->node_id = $node_id;
                $output = executeQuery('mobilemessage.getNodeInfoByNodeId', $args);
                if (!$output->toBool()) return $output;
                $node_route = $output->data->node_route . $node_id . '.';
            } else {
                if (in_array($node_id, array('f.','s.','t.'))) {
                    $node_route = $node_id;
                } else {
                    $node_route = 'f.';
                }
            }

            $args->user_id = $logged_info->user_id;
            $args->node_route = $node_route;
            $args->node_type = '2';

            $oMobilemessageModel = &getModel('mobilemessage');
            $output = executeQueryArray('mobilemessage.getPurplebookByNodeRoute', $args);

            require_once('mobilemessage.utility.php');
            $csutil = new CSUtility();
            Context::set('csutil', $csutil);
            Context::set('data', $output->data);

            $this->setLayoutFile('default_layout');
            $this->setTemplateFile('purplebook_download');
        }

        function dispMobilemessageLogGroupDownload() {
            $logged_info = Context::get('logged_info');
            if (!$logged_info)
                return new Object(-1, 'msg_not_logged');

            header("Content-Type: Application/octet-stream;");
            header("Content-Disposition: attachment; filename=\"loggroup-" . date('Ymd') . ".xls\"");

            $args->list_count = 99999;
            $args->userid = $logged_info->user_id;
            $args->startdate = Context::get('startdate');
            $args->enddate = Context::get('enddate');

            $oMobilemessageModel = &getModel('mobilemessage');
            $output = $oMobilemessageModel->getMessagesGrouping($args);

            Context::set('data', $output->data);

            $this->setTemplateFile('loggroup_download');
        }

        function dispMobilemessageLogListDownload() {
            $logged_info = Context::get('logged_info');
            if (!$logged_info)
                return new Object(-1, 'msg_not_logged');

            header("Content-Type: Application/octet-stream;");
            header("Content-Disposition: attachment; filename=\"loglist-" . date('Ymd') . ".xls\"");

            $args->list_count = 99999;
            $args->gid = Context::get('gid');

            $oMobilemessageModel = &getModel('mobilemessage');
            $output = $oMobilemessageModel->getMessagesInGroup($args);

            require_once('mobilemessage.utility.php');
            $csutil = new CSUtility();
            Context::set('csutil', $csutil);
            Context::set('data', $output->data);

            $this->setTemplateFile('loglist_download');
        }

        /**
         * @brief 선택된 로그 일괄 취소(그룹)
         **/
        function dispMobilemessageCancelGroupMessages() {
            $target_group_ids = Context::get('target_group_ids');
            if(!$target_group_ids) 
                return new Object(-1, 'msg_invalid_request');
            $group_ids = explode(',', $target_group_ids);
            $oMobilemessageController = &getController('mobilemessage');

            $output = $oMobilemessageController->cancelGroupMessages($group_ids);
            if(!$output->toBool()) {
                $this->setMessage('cancel_failed');
                return $output;
            }

            $this->setMessage('success_canceled');
        }
        /**
         * @brief 선택된 로그 일괄 취소
         **/
        function dispMobilemessageCancelMessages() {
            $target_msgids = Context::get('target_msgids');
            if(!$target_msgids) 
                return new Object(-1, 'msg_invalid_request');
            $msgids = explode(',', $target_msgids);
            $oMobilemessageController = &getController('mobilemessage');

            $output = $oMobilemessageController->cancelMessage($msgids);
            if(!$output->toBool()) {
                $this->setMessage('cancel_failed');
                return $output;
            }

            $this->setMessage('success_canceled');
        }

        /**
         * @brief 주소록 Node 추가
         **/
        function dispMobilemessagePurplebookAddNode() {

            $logged_info = Context::get('logged_info');
            if (!$logged_info)
                return new Object(-1, 'msg_not_logged');
            $args->node_id = getNextSequence();
            $args->member_srl = $logged_info->member_srl;
            $args->user_id = $logged_info->user_id;
            $args->parent_node = Context::get('parent_node');
            if ($args->parent_node)
                $args->node_route = Context::get('parent_route') . $args->parent_node . '.';
            else
                $args->node_route = '.';
            $args->node_name = Context::get('node_name');
            $args->node_type = Context::get('node_type');
            $args->phone_num = str_replace('-', '', Context::get('phone_num'));

            $oMobilemessageController = &getController('mobilemessage');
            $oMobilemessageController->insertPurplebook($args);

            Context::set('node_id', $args->node_id);
            Context::set('node_route', $args->node_route);
        }

        /**
         * @brief 주소록 Node 삭제
         **/
        function dispMobilemessagePurplebookDeleteNode() {
            $logged_info = Context::get('logged_info');
            if (!$logged_info)
                return new Object(-1, 'msg_not_logged');

            $args->user_id = $logged_info->user_id;
            $args->node_id = Context::get('node_id');
            $args->node_route = Context::get('node_route') . $args->node_id . '.';

            $oMobilemessageController = &getController('mobilemessage');
            $oMobilemessageController->deletePurplebook($args);
        }

        /**
         * @brief 주소록 Node 삭제
         **/
        function dispMobilemessagePurplebookDeleteList() {
            $logged_info = Context::get('logged_info');
            if (!$logged_info)
                return new Object(-1, 'msg_not_logged');

            $node_list = $this->getJSON('node_list');

            $oMobilemessageController = &getController('mobilemessage');

            foreach ($node_list as $node_id) {
                unset($args);
                $args->user_id = $logged_info->user_id;
                $args->node_id = $node_id;

                $oMobilemessageController->deletePurplebook($args);
            }
        }


        /**
         * @brief 주소록
         **/
        function dispMobilemessagePurplebookRenameNode() {
            $args->node_id = Context::get('node_id');
            $args->node_name = Context::get('node_name');

            $oMobilemessageController = &getController('mobilemessage');
            $oMobilemessageController->updatePurplebook($args);
        }

        /**
         * @brief 주소록
         **/
        function dispMobilemessagePurplebookUpdateRoute() {
            $data = $this->getJSON('json');

            $oMobilemessageController = &getController('mobilemessage');

            $args = new StdClass();
            $args->node_route = Context::get('node_route');
            foreach ($data as $node_id)
            {
                $args->node_id = $node_id;
                $oMobilemessageController->updatePurplebook($args);
            }
        }

        /**
         * @brief 주소록
         **/
        function dispMobilemessagePurplebookCopy() {
            $data = $this->getJSON('json');

            $logged_info = Context::get('logged_info');
            if (!$logged_info)
                return new Object(-1, 'msg_not_logged');

           $oMobilemessageController = &getController('mobilemessage');

            foreach ($data as $node_id) {
                unset($args);
                $args->user_id = $logged_info->user_id;
                $args->node_id = $node_id;
                
                $output = executeQuery('mobilemessage.getPurplebook', $args);

                if ($output->data) {
                    unset($args);
                    $args->node_id = getNextSequence();
                    $args->member_srl = $logged_info->member_srl;
                    $args->user_id = $logged_info->user_id;
                    $args->node_route = Context::get('node_route');
                    $args->node_name = $output->data->node_name;
                    $args->node_type = $output->data->node_type;
                    $args->phone_num = str_replace('-', '', $output->data->phone_num);

                    $oMobilemessageController->insertPurplebook($args);
                }
            }
        }

        /**
         * @brief 주소록
         **/
        function dispMobilemessagePurplebookRegister() {
            $data = $this->getJSON('data');

            $logged_info = Context::get('logged_info');
            if (!$logged_info)
                return new Object(-1, 'msg_not_logged');

            $oMobilemessageController = &getController('mobilemessage');

            $list = array();
            foreach ($data as $obj) {
                $args = new StdClass();
                $args->node_id = getNextSequence();
                $args->user_id = $logged_info->user_id;
                $args->member_srl = $logged_info->member_srl;
                $args->node_route = Context::get('node_route');
                $args->node_name = $obj->node_name;
                $args->node_type = '2';
                $args->phone_num = str_replace('-', '', $obj->phone_num);

                $list[] = $args;

                $oMobilemessageController->insertPurplebook($args);
            }
            Context::set('return_data', $list);
        }

        /**
         * @brief 주소록
         **/
        function dispMobilemessagePurplebookList() {
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
            Context::set('total_count', $output->total_count);
            Context::set('data', $data);
        }
 
        /**
         * @brief 주소록
         **/
        function dispMobilemessagePurplebookListPaging() {
            $logged_info = Context::get('logged_info');
            if (!$logged_info)
                return new Object(-1, 'msg_not_logged');

            $args->user_id = $logged_info->user_id;
            $args->node_route = Context::get('node_route');
            $args->node_type = Context::get('node_type');
            $args->page = Context::get('page');

            $oMobilemessageModel = &getModel('mobilemessage');
            $output = $oMobilemessageModel->getPurplebookListPaging($args);

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
            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);

            Context::set('data', $data);
        }

        /**
         * @brief 인증번호 발송
         **/
        function dispMobilemessageSendAuthCode() {
            $userid = Context::get('userid');
            $phonenum = Context::get('phonenum');

            // 아이디 & 폰번호 검사
            $oModel = &getModel('mobilemessage');
            $extra_userid = $oModel->getUserIDsByPhoneNumber($phonenum); 
            if (!$extra_userid || !in_array($userid, $extra_userid))
                return new Object(-1, '해당 계정의 아이디와 폰번호가 일치하지 않습니다.');

            $oController = &getController('mobilemessage');

            // valcode
            $output = $oController->insertValCode($phonenum);
            if (!$output->toBool()) return $output;

            // send
            unset($args);
            $args = new StdClass();
            $args->recipient = $phonenum;
            $args->callback = $phonenum;
            $args->message = $output->valcode . ' ☜ 인증번호를 정확히 입력해 주세요.';
            $output = $oController->sendMessage($args);
            if (!$output->toBool()) return $output;
        }

        /**
         * @brief UserID 전송
         **/
        function dispMobilemessageSendUserID() {
            $phonenum = Context::get('phonenum');
            $oController = &getController('mobilemessage');
            $output = $oController->sendUserID($phonenum);
            if (!$output->toBool()) return $output;
        }

        /**
         * @brief 비밀번호 변경
         **/
        function dispMobilemessageChangePassword() {
            $userid = Context::get('userid');
            $phonenum = Context::get('phonenum');
            $authcode = Context::get('authcode');
            $password = Context::get('password');

            $oModel = &getModel('mobilemessage');
            // phonenum, userid match check
            $user_ids = $oModel->getUserIDsByPhoneNumber($phonenum);
            if (!$user_ids || count($user_ids) == 0 || !in_array($userid, $user_ids))
                return new Object(-1, '입력하신 정보와 일치하는 회원이 없습니다.');
         
            // phonenum, authcode check
            $output = $oModel->getValCode($phonenum);
            if (!$output->valcode || $authcode != $output->valcode) 
                return new Object(-1, '인증번호가 일치하지 않습니다.');
            
            // change password
            $oMemberModel = &getModel('member');
            $member_info = $oMemberModel->getMemberInfoByUserID($userid);
            if (!$member_info || !$member_info->member_srl)
                return new Object(-1, '회원정보를 읽어올 수 없습니다.');

            $args = new StdClass();
            $args->member_srl = $member_info->member_srl;
            $args->user_id = $member_info->user_id;
            $args->password = $password;
            $oMemberController = &getController('member');
            $output = $oMemberController->updateMemberPassword($args);
            if (!$output->toBool()) return $output;

            // call trigger after password changing
            $trigger_args = new StdClass();
            $trigger_args->member_srl = $member_info->member_srl;
            $trigger_args->user_id = $member_info->user_id;
            $trigger_args->password = $password;
            $trigger_output = ModuleHandler::triggerCall('mobilemessage.changeMemberPassword', 'after', $trigger_args);
            if(!$trigger_output->toBool()) return $trigger_output;
        }

        function dispMobilemessageLatestNumbers() {
            $logged_info = Context::get('logged_info');
            if (!Context::get('is_logged') || !$logged_info) return new Object(-1, 'login_required');
            $args->user_id = $logged_info->user_id;
            $output = executeQueryArray('mobilemessage.getRecentReceivers', $args);
            if (!$output->toBool()) return $output;
            $latest_numbers = array();
            if ($output->data) {
                foreach ($output->data as $no => $row) {
                    $obj = new stdclass();
                    $obj->phone_num = $row->phone_num;
                    $obj->ref_name = $row->ref_name;
                    $latest_numbers[] = $obj;
                }
            }
            Context::set('latest_numbers', $latest_numbers);
        }
        function dispMobilemessageLatestMessages() {
            $logged_info = Context::get('logged_info');
            if (!Context::get('is_logged') || !$logged_info) return new Object(-1, 'login_required');
            $args->user_id = $logged_info->user_id;
            $output = executeQueryArray('mobilemessage.getKeepingInfo', $args);
            if (!$output->toBool()) return $output;
            $latest_messages = array();
            if ($output->data) {
                foreach ($output->data as $no => $row) {
                    $obj = new stdclass();
                    $obj->content = $row->content;
                    $obj->content = str_replace("\r", "", $obj->content);
                    $obj->content = str_replace("\n", "", $obj->content);

                    $latest_messages[] = $obj;
                }
            }
            Context::set('latest_messages', $latest_messages);
        }
        function dispMobilemessageKeepContent() {
            $logged_info = Context::get('logged_info');
            if (!Context::get('is_logged') || !$logged_info) return new Object(-1, 'login_required');
            $args->keeping_srl = getNextSequence();
            $args->user_id = $logged_info->user_id;
            $args->content = Context::get('content');

            $output = executeQuery('mobilemessage.insertKeeping', $args);
            if (!$output->toBool()) return $output;
        }

        function dispMobilemessageInsertRecentReceiver() {
            $logged_info = Context::get('logged_info');
            if (!Context::get('is_logged') || !$logged_info) return new Object(-1, 'login_required');
            $args->receiver_srl = getNextSequence();
            $args->user_id = $logged_info->user_id;
            $args->ref_name = Context::get('ref_name');
            $args->phone_num = Context::get('phone_num');

            $output = executeQuery('mobilemessage.deleteRecentReceiver', $args);
            $output = executeQuery('mobilemessage.insertRecentReceiver', $args);
            if (!$output->toBool()) return $output;
        }

        function dispMobilemessageFilePicker(){
            $logged_info = Context::get('logged_info');
            if(!$logged_info) {
                Context::set('message', Context::getLang('msg_login_required'));
                $this->setLayoutFile('default_layout');
                $this->setTemplateFile('filepicker_error');
                return;
            }

            $filter = Context::get('filter');
            if($filter) Context::set('arrfilter',explode(',',$filter));

            $this->setLayoutFile('default_layout');
            $this->setTemplateFile('filepicker');
        }
 
    }
?>
