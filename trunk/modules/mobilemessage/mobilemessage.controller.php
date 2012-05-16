<?php
    /**
     * vi:set sw=4 ts=4 expandtab enc=utf8:
     * @class  mobilemessageController
     * @author diver(diver@coolsms.co.kr)
     * @brief  mobilemessageController
     */
    class mobilemessageController extends mobilemessage {
        function init() {
            $oModel = &getModel('mobilemessage');
            $this->config = $oModel->getModuleConfig();
        }

        function insertMobilemessage(&$args) {
            $args->mobilemessage_srl = getNextSequence();

            $oDB = &DB::getInstance();
            $oDB->begin();

            $output = executeQuery('mobilemessage.insertMobilemessage', $args);
            if (!$output->toBool())
            {
                $oDB->rollback();
                return $output;
            }

            $oDB->commit(true);
            return $output;
        }

        function minusPoint($point) {
            global $lang;

            $logged_info = Context::get('logged_info');
            if (!$logged_info)
                return new Object(-1, 'msg_log_required');

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

        function sendJSON($obj, $db_insert_flag=true, $encode_utf16='N') {
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
            //$text = iconv("utf-8", "euc-kr//TRANSLIT", $obj->text);
            $text = $obj->text;
            $splitlimit = intval($obj->splitlimit);
            $delay_count = intval($obj->delay_count);
            //$refname = iconv("utf-8", "euc-kr//TRANSLIT", $obj->refname);
            $refname = $obj->refname;
            $reservdate = $obj->reservdate;


            // korea 80 bytes as default
            $bytes_per_each = 80;
            $checkmb = true;

            // international 160 bytes
            if (!$obj->country) $obj->country = $this->config->default_country;
            if ($obj->country != '82') {
                $bytes_per_each = 160;
                if ($encode_utf16=='Y') {
                    $bytes_per_each = 70;
                    $checkmb = false; // count unicode string length
                }
            }

            $textlen = $this->sms->strlen_utf8($text, $checkmb);
            $quantity = ceil($textlen / $bytes_per_each);
            // limit split
            if (($splitlimit+1) < $quantity) $quantity = $splitlimit+1;

            if ($msgtype == 'SMS') {
                for ($i = 0; $i < $quantity; $i++) {
                    if ($this->use_point == 'Y') {
                        if (!$this->minusPoint($this->sms_point)) break;
                    }

                    $content = coolsms::strcut_utf8($text, $bytes_per_each, $checkmb);
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
                    if ($delay_count) {
                        $args->delay_count = $delay_count;
                    } else {
                        $args->delay_count = $i * 2;
                    }
                    if (!$this->sms->addsmsobj($args)) {
                        $error_count++;
                    }

                    $text = substr($text, strlen($content));

                    if ($db_insert_flag) {
                        // DB INSERT
                        unset($args);
                        $logged_info = Context::get('logged_info');
                        if ($logged_info) $args->userid = $logged_info->user_id;
                        $args->gid = $obj->gid;
                        $args->mid = $mid;
                        $args->mtype = $msgtype;
                        $args->callno = $recipient;
                        $args->callback = $callback;
                        $args->content = $content;
                        $args->reservdate = $reservdate;
                        if ($reservdate) $args->reservflag = "Y";
                        $args->ref_username = $obj->refname;
                        $args->country = $obj->country;
                        $this->insertMobilemessage($args);
                    }
                }
            } else { // MMS
                if ($this->use_point == 'Y') {
                    if ($msgtype == 'LMS') {
                        if (!$this->minusPoint($this->lms_point)) break;
                    } if ($msgtype == 'MMS') {
                        if (!$this->minusPoint($this->mms_point)) break;
                    }
                }

                $mid = coolsms::keygen();
                if (!$subject) $subject = coolsms::strcut_utf8($text, 20, true);
                $text = coolsms::strcut_utf8($text, 2000, true);

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

                if ($msgtype == 'LMS') {
                    if (!$this->sms->addlmsobj($args)) {
                        $error_count++;
                    }
                } else {
                    $args->attachment = $obj->file_srl;
                    if (!$this->sms->addmmsobj($args)) {
                        $error_count++;
                    }
                }

                if ($db_insert_flag) {
                    // DB INSERT
                    unset($args);
                    $logged_info = Context::get('logged_info');
                    if ($logged_info) $args->userid = $logged_info->user_id;
                    $args->gid = $obj->gid;
                    $args->mid = $mid;
                    $args->mtype = $msgtype;
                    $args->callno = $recipient;
                    $args->callback = $callback;
                    $args->subject = $subject;
                    $args->content = $obj->text;
                    $args->reservdate = $reservdate;
                    if ($reservdate) $args->reservflag = "Y";
                    $args->country = $obj->country;
                    $this->insertMobilemessage($args);
                }
            }
            return $error_count;
        }


        /**
         * @brief procMobilemessageSendMsg
         **/
        function procMobilemessageSendMsg($args=false) {
            $oModel = &getModel('mobilemessage');
            $this->config = $oModel->getModuleConfig($args);
            $db_insert_flag=true;
            if ($args && $args->basecamp) {
                $db_insert_flag=false;
            }

            // check ticket
            $ticket = Context::get('ticket');
            if (!$ticket || !$this->validateTicket($ticket)) return new Object(-1, 'msg_invalid_ticket');

            $encode_utf16 = Context::get('encode_utf16');

            $decoded = $this->getJSON('data');
            $this->use_point = Context::get('use_point');
            $this->sms_point = Context::get('sms_point');
            $this->lms_point = Context::get('lms_point');
            $this->mms_point = Context::get('mms_point');

            require_once($this->module_path.'coolsms.php');
            $this->sms = new coolsms();
            $sln_reg_key = $oModel->getSlnRegKey();
            if ($sln_reg_key) $this->sms->set_sln_reg_key($sln_reg_key);
            $this->sms->appversion("MXE/" . $this->version . " XE/" . __ZBXE_VERSION__);
            $this->sms->setuser($this->config->cs_userid, $this->config->cs_passwd, $this->config->crypt);
            $this->sms->charset('utf8');
            $this->sms->emptyall();
            if ($encode_utf16=='Y') $this->sms->encode_utf16();

            // group id
            $groupid_seed = Context::get('groupid_seed');
            if ($groupid_seed) {
                if ($groupid_seed == $_SESSION['MOBILEMESSAGE_GROUPID_SEED']) {
                    $gid = $_SESSION['MOBILEMESSAGE_GROUPID'];
                } else {
                    $gid = coolsms::keygen();
                    $_SESSION['MOBILEMESSAGE_GROUPID_SEED'] = $groupid_seed;
                    $_SESSION['MOBILEMESSAGE_GROUPID'] = $gid;
                }
            } else {
                $gid = coolsms::keygen();
            }

            $error_count=0;
            if (is_array($decoded)) {
                foreach ($decoded as $row) {
                    $row->gid = $gid;
                    $error_count += $this->sendJSON($row, $db_insert_flag, $encode_utf16);
                }
            } else {
                $decoded->gid = $gid;
                $error_count += $this->sendJSON($decoded, $db_insert_flag, $encode_utf16);
            }

            if (!$this->sms->connect()) {
                // cannot connect
                return new Object(-1, 'cannot connect to server.');
            }

            $data = array();
            $succ = 0;
            $fail = 0;

            $alert_message = "";
            if ($this->sms->send()) {
                $result = $this->sms->getr();

                $error_cause = array();
                foreach ($result as $row) {
                    if ($row["RESULT-CODE"] == "00") {
                        $succ++;
                    } else {
                        $fail++;
                        $error_cause[$row["RESULT-CODE"]] = $row["RESULT-MESSAGE"];
                    }
                    $obj = new StdClass();
                    $obj->result_code = $row["RESULT-CODE"];
                    $obj->group_id = $row["GROUP-ID"];
                    $obj->message_id = $row["MESSAGE-ID"];
                    $obj->called_number = $row["CALLED-NUMBER"];
                    $data[] = $obj;
                }
                foreach ($error_cause as $key => $val) {
                    if ($alert_message) $alert_message .= "\n";
                    $alert_message .= "[{$key}] {$val}";
                }

            }
            $this->sms->disconnect();

            $fail += $error_count;

            $this->add('data', $data);
            $this->add('success_count', $succ);
            $this->add('failure_count', $fail);
            $this->add('alert_message', $alert_message);
        }


        /**
         * @brief sendMessage
         **/
        function procMobilemessageSendMessage() {
            // check ticket
            $ticket = Context::get('ticket');
            if (!$ticket || !$this->validateTicket($ticket)) return new Object(-1, 'msg_invalid_ticket');

            $args->type = Context::get('type');
            $args->recipient = Context::get('recipient');
            $args->callback = Context::get('callback');
            $args->message = Context::get('message');
            $args->reservdate = Context::get('reservdate');
            $args->subject = Context::get('subject');
            $args->country = Context::get('country');
            $args->attachment = Context::get('attachment');
            $args->encode_utf16 = Context::get('encode_utf16');
            return $this->sendMessage($args);
        }

        /**
         * @brief sendMessage 으로 대체
         **/
        function procMobilemessageSend($args) {
            return $this->sendMessage($args);
        }

        /**
         * @brief 메시지 전송
         * @param[in] $args
         *  $args->type = 'SMS' or 'LMS' // default = 'SMS'
         *  $args->recipient = '받는사람 번호'
         *  $args->callback = '회신번호'
         *  $args->message = '문자 메시지 내용'
         *  $args->reservdate = 'YYYYMMDDHHMISS'
         *  $args->subject = 'LMS제목'
         *  $args->ref_userid = '수신자아이디'
         *  $args->ref_username = '수신자명'
         *  $args->country = '국가번호'
         *  $args->attachment = 첨부파일
         *  $args->encode_utf16 = true or false
         * @param[in] $user_id true means auto, false means none, otherwise, use in userid
         * @return Object(error, message)
         **/
        function sendMessage($args, $user_id=true) {
            $oMobilemessageModel = &getModel('mobilemessage');
            $config = &$oMobilemessageModel->getModuleConfig();

            $oMobilemessageModel = &getModel('mobilemessage');

            require_once($this->module_path.'coolsms.php');
            $sms = new coolsms();
            $sln_reg_key = $oMobilemessageModel->getSlnRegKey();
            if ($sln_reg_key) $sms->set_sln_reg_key($sln_reg_key);
            $sms->appversion("MXE/{$this->version} XE/" . __ZBXE_VERSION__);
            $sms->charset("utf8");
            $sms->setuser($config->cs_userid, $config->cs_passwd, $config->crypt);
            if ($args->encode_utf16) $sms->encode_utf16();

            $mid = coolsms::keygen();
            if (!$args->type) $args->type = 'SMS';
            $args->type = strtoupper($args->type);
            if (!in_array($args->type, array('SMS', 'LMS', 'MMS'))) $type = 'SMS';
            $type = $args->type;
            $message = $args->message;
            $recipient = $args->recipient;
            $callback = $args->callback;
            $reservdate = $args->reservdate;
            $subject = $args->subject;
            // ref_userid
            if (isset($args->ref_userid))
                $ref_userid = $args->ref_userid;
            else
                $ref_userid = '';
            // ref_username
            if (isset($args->ref_username))
                $ref_username = $args->ref_username;
            else
                $ref_username = '';
            $country = $args->country;
            if (!$country) $country = $config->default_country;
            if (!$args->attachment) 
                $attachment = array();
            else 
                $attachment = $args->attachment;
            if (!is_array($attachment)) $attachment = array($attachment);

            // DB INSERT
            $oMobilemessageController = &getController("mobilemessage");
            unset($args);
            $args = new StdClass();
            if ($user_id === true) {
                $logged_info = Context::get('logged_info');
                if ($logged_info)
                    $args->userid = $logged_info->user_id;
            } else if ($user_id === false) {
                // do nothing
            } else {
                $args->userid = $user_id;
            }
            $args->gid = $mid;
            $args->mid = $mid;
            $args->mtype = $type;
            $args->country = $country;
            $args->callno = $recipient;
            $args->callback = $callback;
            $args->subject = $subject;
            $args->content = $message;
            $args->reservdate = $reservdate;
            if ($reservdate)
                $args->reservflag = "Y";
            if ($ref_userid)
                $args->ref_userid = $ref_userid;
            if ($ref_username)
                $args->ref_username = $ref_username;
            $oMobilemessageController->insertMobilemessage($args);

            $alert="";
            $succ = 0;
            $fail = 0;

            if ($type == "SMS") {
                $message = coolsms::strcut_utf8($message, 160, true);
            } else { // LMS, MMS
                $message = coolsms::strcut_utf8($message, 2000, true);
                if (!$subject) $subject = coolsms::strcut_utf8($message, 20, true);
            }

            unset($args);
            $args = new StdClass();
            $args->type = $type;
            $args->rcvnum = $recipient;
            $args->callback = $callback;
            $args->msg = $message;
            $args->callname = $ref_username;
            $args->reservdate = $reservdate;
            $args->msgid = $mid;
            if ($subject) $args->subject = $subject;
            $args->groupid = $mid;
            $args->country = $country;
            if (count($attachment)) $args->attachment = $attachment;
            if (!$sms->addobj($args)) {
                $fail++;
                $alert = $sms->lasterror();
            }

            if (!$sms->connect())
            {
                return new Object(-1, 'error_cannot_connect');
            }

            if ($sms->send())
            {
                $result = $sms->getr();

                foreach ($result as $row)
                {
                    if ($row["RESULT-CODE"] == "00")
                        $succ++;
                    else
                        $fail++;

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

        function getMessageSender() {
            require_once($this->module_path."messagesender.php");
            $obj = new MessageSender();
            return $obj;
        }

        function sendUserID($phone_num) {
            $oModel = &getModel('mobilemessage');
            $user_ids = $oModel->getUserIDsByPhoneNumber($phone_num);
            if ($user_ids === false)
                return new Object(-1, 'DB 시스템 오류.');

            if (!count($user_ids))
                return new Object(-1, '등록된 폰번호가 아닙니다.');

            $args = new StdClass();
            $args->recipient = $phone_num;
            $args->callback = $phone_num;
            $args->message = "찾은 아이디(" . count($user_ids) . "개)\n" . join($user_ids, "\n");
            $output = $this->sendMessage($args);
            if (!$output->toBool()) return $output;

            return new Object();
        }
        function procMobilemessageSendUserID() {
            $phone_num = Context::get('cellphone');
            $phone_num = str_replace('|@|', '', $phone_num);
            $output = $this->sendUserID($phone_num);
            if (!$output->toBool()) return $output;
        }

        /**
         * @brief 문자 전송, 웹폰위젯 v0.5 이하 지원, 삭제 예정
         **/
        function procMobilemessageCellphoneSend() {
            $oMobilemessageModel = &getModel('mobilemessage');
            $config = &$oMobilemessageModel->getModuleConfig();

            // 핸드폰 생성
            require_once('mobilemessage.cellphone.php');
            $args = new StdClass();
            $args->userid = $config->cs_userid;
            $args->passwd = $config->cs_passwd;
            $args->version = $this->version;
            $args->module_path = $this->module_path;
            $cellphone = new Cellphone($args);
            $cellphone->OnSend();

            $this->setMessage('success_sent');
        }

        /**
         * @brief 포인트사용 문자전송, 웹폰위젯 v0.5 이하 지원, 삭제 예정
         **/
        function procMobilemessageSendUsingPoint() {
            $oMobilemessageModel = &getModel('mobilemessage');
            $config = &$oMobilemessageModel->getModuleConfig();

            // 핸드폰 생성
            require_once('mobilemessage.cellphone.php');
            $args = new StdClass();
            $args->userid = $config->cs_userid;
            $args->passwd = $config->cs_passwd;
            $args->using_point = true;
            $args->point_for_sms = $config->point_for_sms;
            $args->point_for_lms = $config->point_for_lms;
            $oMemberModel = &getModel('member');
            $logged_info = &$oMemberModel->getLoggedInfo();
            if (!$logged_info) {
                $this->setMessage('login_required');
                return;
            }
            $args->member_srl = $logged_info->member_srl;
            $cellphone = new Cellphone($args);
            $cellphone->OnSend();

            $this->setMessage('success_sent');
        }

        /**
         * @brief 전송결과 동기화
         **/
        function procMobilemessageSyncResult() {
            // Config 가져오기
            $oMobilemessageModel = &getModel('mobilemessage');
            $config = &$oMobilemessageModel->getModuleConfig();

            require_once($this->module_path.'coolsms.php');
            $sms = new coolsms();
            $sln_reg_key = $oMobilemessageModel->getSlnRegKey();
            if ($sln_reg_key) $sms->enable_resale();
            $sms->appversion("MXE/" . $this->version . " XE/" . __ZBXE_VERSION__);
            if (!$config->cs_userid || !$config->cs_passwd) {
                $this->setMessage('warning_check_setuser');
                return;
            }
            $sms->setuser($config->cs_userid, $config->cs_passwd, $config->crypt);

            if (!$sms->connect()) {
                $this->setMessage('warning_cannot_connect');
                return;
            }

            $args = new StdClass();
            $args->mobilemessage_srl = trim(Context::get('mobilemessage_srls'));
            $query_id = 'mobilemessage.getMobilemessages';
            $output = executeQueryArray($query_id, $args);

            if (!$output->data) {
                $this->setMessage('warning_no_record_to_sync');
                return;
            }

            $sync_count=0;
            $query_id = 'mobilemessage.updateMobilemessage';
            foreach ($output->data as $no => $val) {
                if (!$val->mid)
                    continue;
                $res = $sms->rcheck($val->mid);
                unset($args);
                $args = new StdClass();
                $args->mid = $val->mid;
                $args->mstat = $res['STATUS'];
                $args->rcode = $res['RESULT-CODE'];
                if (!in_array($res['RESULT-CODE'], array('00', '99')))
                    $args->mstat = '2';
                if (isset($res['SEND-DATE']))
                    $args->senddate = $res['SEND-DATE'];
                if (isset($res['CARRIER']))
                    $args->carrier = $res['CARRIER'];
                executeQuery($query_id, $args);
                $sync_count++;
            }
            $sms->disconnect();

            $success_syncresult = sprintf('%u 건 동기화 완료', $sync_count);

            $this->setMessage($success_syncresult);
        }

        /**
         * @brief Mapping정보 삭제
         **/
        function deleteMapping($user_id) {
            $args = new StdClass();
            $args->user_id = $user_id;
            $query_id = "mobilemessage.deleteMapping";
            return executeQuery($query_id, $args);
        }

        /**
         * @brief Mapping정보 Insert
         **/
        function insertMapping(&$args) {
            // delete
            $query_id = "mobilemessage.deleteMapping";
            $output = executeQuery($query_id, $args);
            if (!$output->toBool()) return $output;

            // insert
            $query_id = "mobilemessage.insertMapping";
            $output = executeQuery($query_id, $args);
            if (!$output->toBool()) return $output;

            return new Object();
        }


        function addMemberToGroup(&$obj, $callno) {
            if (!$callno) return false;

            $oMobilemessageModel = &getModel('mobilemessage');
            $config = &$oMobilemessageModel->getModuleConfig();

            // change group
            // check authcode input.
            $authcode = $oMobilemessageModel->getConfigValue($obj, "validationcode_fieldname");
            if (!$authcode) $authcode = '';
            $country = $oMobilemessageModel->getConfigValue($obj, "countrycode_fieldname");
            if (!$country) $country = $config->default_country;

            unset($args);
            $args->callno = $callno;
            $args->country = $country;
            $args->valcode = $authcode;

            $oMemberModel = &getModel('member');
            $oMemberController = &getController('member');

            $group_list = $oMemberModel->getGroups(0);
            $group_names = array();
            if ($args->callno && $args->valcode && $oMobilemessageModel->validateValCode($args)) {
                $groups = $oMemberModel->getMemberGroups($obj->member_srl);
                $change_group_srl_list = explode(',', $config->change_group_srl_list);
                foreach ($change_group_srl_list as $group_srl) {
                    if (!in_array($group_srl, array_keys($groups))) {
                        // add group
                        if (array_key_exists($group_srl, $group_list)) {
                            $group_names[] = $group_list[$group_srl]->title;
                            $oMemberController->addMemberToGroup($obj->member_srl, $group_srl);
                        }
                    }
                }
            }
            if (count($group_names) > 0 && $config->inform_group_change == 'Y') {
                $obj->groups = implode(',', $group_names);
                $config->group_change_message = $this->mergeKeywords($config->group_change_message, $obj);
                $config->group_change_message = $this->mergeKeywords($config->group_change_message, $extra_vars);

                unset($args);
                $args = new StdClass();
                $args->type = 'SMS';
                if ($config->allow_lms_group_change == 'Y') {
                    require_once('mobilemessage.utility.php');
                    $csutil = new CSUtility();
                    $oModel = &getModel('mobilemessage');
                    $config = $oModel->getModuleConfig();
                    if ($csutil->strlen_utf8($config->group_change_message, true) > $config->limit_bytes)
                        $args->type = 'LMS';
                }

                if ($callno) {
                    $callback = $this->getCallbackNumber($config->callback_number_type, $config->callback_number_direct, 0, $callno);
                    if ($callback == 'self') $callback = $callno;
                    $args->recipient = $callno;
                    $args->callback = $callback;
                    $args->message = $config->group_change_message;
                    $this->sendMessage($args);
                }
            }
        }

        /**
         * @brief 회원가입시 SMS발송 트리거
         **/
        function triggerMemberJoin(&$obj) {

            $oMobilemessageModel = &getModel('mobilemessage');
            $config = &$oMobilemessageModel->getModuleConfig();

            $extra_vars = unserialize($obj->extra_vars);

            $callno = $oMobilemessageModel->getConfigValue($obj, "cellphone_fieldname", 'tel');
            if (!$callno) $callno = "";
            else $callno = str_replace("|@|", "", $callno);

            // user_id 저장
            $args = new StdClass();
            $args->user_id = $obj->user_id;
            $args->phone_num = $callno;
            $output = $this->insertMapping($args);
            if (!$output->toBool())
                return new Object(-1, '[MessageXE] insertMapping DB처리 실패');

            // 그룹추가
            $this->addMemberToGroup($obj, $callno);

            // 이 기능을 사용하지 않는다면 빠져나가자.
            if ($config->flag_welcome_member != 'Y' && $config->flag_welcome_admin != 'Y')
                return new Object();


            // 가입자에게
            if ($config->flag_welcome_member == 'Y') {
                $config->welcome_member = $this->mergeKeywords($config->welcome_member, $obj);
                $config->welcome_member = $this->mergeKeywords($config->welcome_member, $extra_vars);

                unset($args);
                $args = new StdClass();
                $args->type = 'SMS';
                if ($config->allow_lms_member == 'Y') {
                    require_once('mobilemessage.utility.php');
                    $csutil = new CSUtility();
                    if ($csutil->strlen_utf8($config->welcome_member, true) > $config->limit_bytes)
                        $args->type = 'LMS';
                }

                if ($callno) {
                    $callback = $this->getCallbackNumber($config->callback_number_type, $config->callback_number_direct, 0, $callno);
                    if ($callback == 'self') $callback = $callno;
                    $args->recipient = $callno;
                    $args->callback = $callback;
                    $args->message = $config->welcome_member;
                    $this->sendMessage($args);
                }
            }
            // 관리자에게
            if ($config->flag_welcome_admin == 'Y') {
                $config->welcome_admin = $this->mergeKeywords($config->welcome_admin, $obj);
                $config->welcome_admin = $this->mergeKeywords($config->welcome_admin, $extra_vars);

                unset($args);
                $args = new StdClass();
                // msgtype
                $args->msgtype = 'SMS';
                if ($config->allow_lms_admin == 'Y') {
                    require_once('mobilemessage.utility.php');
                    $csutil = new CSUtility();
                    if ($csutil->strlen_utf8($config->welcome_admin, true) > $config->limit_bytes)
                        $args->msgtype = 'LMS';
                }
                // callback number
                $args->callback_number = $this->getCallbackNumber($config->callback_number_type, $config->callback_number_direct, 0, $callno);

                // content
                $args->content = $config->welcome_admin;

                // 대상 아이디
                $id_list = explode(',', $config->id_list);
                if (count($id_list) > 0) {
                    $this->sendToMembers($id_list, $args);
                }

                // 대상 그룹
                if ($config->group_srl_list) {
                    $this->sendToGroups($config->group_srl_list, $args);
                }

                // 직접 입력
                if (count($config->admin_phones)) {
                    $this->sendToDirectNumbers($config->admin_phones, $args);
                }
            }

            return new Object();
        }

        /**
         * @brief 회원탈퇴시 주소록, 인증번호 삭제
         **/
        function triggerMemberDelete(&$obj) {
            $oMobilemessageModel = &getModel('mobilemessage');
            $config = &$oMobilemessageModel->getModuleConfig();

            // 회원정보 획득
            $oMemberModel = &getModel('member');
            $member_info = $oMemberModel->getMemberInfoByMemberSrl($obj->member_srl);
            if (!$member_info) {
                return new Object(-1, "msg_invalid_request");
            }

            // 주소록 삭제
            $query_id = 'mobilemessage.deletePurplebookAll';
            $args = new StdClass();
            $args->user_id = $member_info->user_id;
            $output = executeQuery($query_id, $args);
            if (!$output->toBool()) return $output;

            // if keep_mapping is 'Y' then keep the data into the prohibithion table.
            if ($config->keep_mapping == 'Y' && $config->cellphone_fieldname && $member_info->{$config->cellphone_fieldname}) {
                $phone_num = $member_info->{$config->cellphone_fieldname};
                if (is_array($phone_num)) $phone_num = join($phone_num);
                unset($args);
                $args = new StdClass();
                $args->phone_num = $phone_num;
                $args->memo = "탈퇴회원/{$member_info->user_id}";
                if ($config->keep_mapping_days)
                    $args->limit_date = date('Ymd', time() + (60 * 60 * 24 * (int)$config->keep_mapping_days));
                $output = $this->insertProhibit($args);
                if (!$output->toBool()) return $output;
            }

            // mapping data deleting.
            $query_id = 'mobilemessage.deleteMapping';
            unset($args);
            $args = new StdClass();
            $args->user_id = $member_info->user_id;
            $output = executeQuery($query_id, $args);
            if (!$output->toBool()) return $output;

            return new Object();
        }

        /**
         * @brief trigger when user info modified.
         **/
        function triggerMemberUpdate(&$obj) {
            $oModel = &getModel('mobilemessage');
            $config = &$oModel->getModuleConfig();

            $extra_vars = unserialize($obj->extra_vars);

            // 핸드폰번호 필드명이 입력되어 있는지 검사.
            if ($config->cellphone_fieldname) {
                // 기본필드에서 확인
                if ($obj->{$config->cellphone_fieldname}) {
                    $obj->{$config->cellphone_fieldname} = str_replace("|@|", "", $obj->{$config->cellphone_fieldname});
                    $phonenum = $obj->{$config->cellphone_fieldname};
                }

                // 확장필드에서 확인
                if ($extra_vars->{$config->cellphone_fieldname}) {
                    $extra_vars->{$config->cellphone_fieldname} = str_replace("|@|", "", $extra_vars->{$config->cellphone_fieldname});
                    $phonenum = $extra_vars->{$config->cellphone_fieldname};
                }
            }

            if (is_array($phonenum)) $phonenum = implode($phonenum);

            // 매핑정보 기록
            unset($args);
            $args = new StdClass();
            $args->user_id = $obj->user_id;
            $args->phone_num = $phonenum;
            $oController = &getController("mobilemessage");
            $oController->insertMapping($args);

            $logged_info = Context::get('logged_info');
            if ($logged_info->member_srl == $obj->member_srl) {
                // 그룹추가
                $this->addMemberToGroup($obj, $phonenum);
            }
        }

        /**
         * @brief 문자삭제
         **/
        function deleteMessage($mobilemessage_srl) {
               $query_id = "mobilemessage.deleteMessage";
               $args = new StdClass();
               $args->mobilemessage_srl = $mobilemessage_srl;
               $output = executeQuery($query_id, $args);

               return $output;
        }

        /**
         * @brief 문자삭제(그룹)
         **/
        function deleteGroupMessage($gid) {
               $query_id = "mobilemessage.deleteGroupMessage";
               $args = new StdClass();
               $args->gid = $gid;
               $output = executeQuery($query_id, $args);

               return $output;
        }

        /**
         * @brief 문자취소
         **/
        function cancelMessage($msgids, $opts=false) {
            // Config 가져오기
            $oMobilemessageModel = &getModel('mobilemessage');
            $config = &$oMobilemessageModel->getModuleConfig($opts);

            require_once($this->module_path.'coolsms.php');
            $sms = new coolsms();
            $sln_reg_key = $oMobilemessageModel->getSlnRegKey();
            if ($sln_reg_key) $sms->enable_resale();
            $sms->appversion("MXE/" . $this->version . " XE/" . __ZBXE_VERSION__);
            if (!$config->cs_userid || !$config->cs_passwd) {
                return new Object(-1, 'warning_check_setuser');
            }

            $sms->setuser($config->cs_userid, $config->cs_passwd, $config->crypt);

            if (!$sms->connect()) {
                return new Object(-1, 'warning_cannot_connect');
            }

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
            // Config 가져오기
            $oMobilemessageModel = &getModel('mobilemessage');
            $config = &$oMobilemessageModel->getModuleConfig($opts);

            require_once($this->module_path.'coolsms.php');
            $sms = new coolsms();
            $sln_reg_key = $oMobilemessageModel->getSlnRegKey();
            if ($sln_reg_key) $sms->enable_resale();
            $sms->appversion("MXE/" . $this->version . " XE/" . __ZBXE_VERSION__);
            if (!$config->cs_userid || !$config->cs_passwd)
            {
                return new Object(-1, 'warning_check_setuser');
            }

            $sms->setuser($config->cs_userid, $config->cs_passwd, $config->crypt);

            if (!$sms->connect())
            {
                return new Object(-1, 'warning_cannot_connect');
            }

            foreach ($group_ids as $gid)
            {
                $sms->groupcancel($gid);
            }

            $sms->disconnect();

            return new Object();
        }

        /**
         * @brief 전송결과 갱신 (SMS센터에서 guest권한으로 접근)
         **/
        function procMobilemessageUpdateResult() {
            $update_values = Context::gets('msgid', 'code', 'senddate', 'net');

            $query_id = 'mobilemessage.updateMobilemessage';

            $args = new StdClass();
            $args->mid = $update_values->msgid;
            $args->mstat = '2'; // 전송완료
            $args->rcode = $update_values->code;
            $args->senddate = $update_values->senddate;
            $args->carrier = $update_values->net;
            return executeQuery($query_id, $args);
        }

        /**
         * @brief 인증번호 INSERT
         * @param[in] phonenum 폰번호
         **/
        function insertValCode($phonenum) {
            // key generate
            $key = rand(1, 99999);
            $keystr = sprintf("%05d", $key);

            // delete
            $query_id = 'mobilemessage.deleteValCode';
            unset($args);
            $args = new StdClass();
            $args->callno = $phonenum;
            $output = executeQuery($query_id, $args);
            if (!$output->toBool()) return $output;

            // insert
            $query_id = 'mobilemessage.insertValCode';
            unset($args);
            $args = new StdClass();
            $args->callno = $phonenum;
            $args->valcode = $keystr;
            executeQuery($query_id, $args);
            if (!$output->toBool()) return $output;

            $obj = new Object();
            $obj->valcode = $keystr;
            return $obj;
        }

        /**
         * @brief 주소록 등록
         * @param[in] node_id, user_id, node_route, node_name, node_type, phone_num
         **/
        function insertPurplebook(&$args) {
            $args->node_id = getNextSequence();
            $output = executeQuery('mobilemessage.insertPurplebook', $args);
            $output->node_id = $args->node_id;
            return $output;
        }

        /**
         * @brief node_id의 node_route를 구해서 node_route로 검색하여 하위 폴더 갯수를 구하여 업댓.
         * @param[in] node_id : 업댓할 node_id
         **/
        function updateSubfolder($user_id, $node_id) {
            $subfolder = 0;

            // check node_id
            if (!$node_id) return new Object(-1, 'msg_invalid_request');

            // get node_route
            $args->node_id = $node_id;
            $args->user_id = $user_id;
            $output = executeQuery('mobilemessage.getPurplebook', $args);
            if (!$output->toBool()) return $output;
            $node_route = $output->data->node_route . $node_id . '.';

            // get subfolder count
            unset($args);
            $args->node_id = $node_id;
            $args->node_route = $node_route;
            $output = executeQuery('mobilemessage.getSubfolder', $args);
            if (!$output->toBool()) return $output;
            if ($output->data) $subfolder = $output->data->subfolder;

            // update subfolder count
            unset($args);
            $args->subfolder = $subfolder;
            $args->node_id = $node_id;
            $output = executeQuery('mobilemessage.updateSubfolder', $args);
            return $output;
        }

        /**
         * @brief node_id의 node_route를 구해서 node_route로 검색하여 하위 명단 갯수를 구하여 업댓
         * @param[in] node_id : 업댓할 node_id
         **/
        function updateSubnode($user_id, $node_id) {
            $subnode = 0;

            $args->node_id = $node_id;
            $args->user_id = $user_id;
            $output = executeQuery('mobilemessage.getPurplebook', $args);
            if (!$output->toBool()) return $output;
            $node_route = $output->data->node_route . $node_id . '.';

            unset($args);
            $args->node_route = $node_route;
            $output = executeQuery('mobilemessage.getSubnode', $args);
            if (!$output->toBool()) return $output;
            if ($output->data) $subnode = $output->data->subnode;

            unset($args);
            $args->subnode = $subnode;
            $args->node_id = $node_id;
            $output = executeQuery('mobilemessage.updateSubnode', $args);
            return $output;
        }

        /**
         * @brief 주소록 수정
         * @param[in] 대상필드: node_id
         * @param[in] 수정필드: node_route, node_name, node_type, phone_num
         **/
        function updatePurplebook($args) {
            if (!$args->node_id) return new Object(-1, 'msg_invalid_request');
            $query_id = 'mobilemessage.updatePurplebook';
            return executeQuery($query_id, $args);
        }

        /**
         * @brief 주소록 명단 삭제
         * @param[in] user_id
         * @param[in] node_id
         **/
        function deletePurplebook($args) {
            $query_id = 'mobilemessage.deletePurplebook';
            return executeQuery($query_id, $args);
        }

        /**
         * @brief insert prohibition data.
         **/
        function insertProhibit($args) {
            $this->deleteProhibit($args->phone_num);
            $query_id = 'mobilemessage.insertProhibit';
            return executeQuery($query_id, $args);
        }

        /**
         * @brief delete prohibition data.
         **/
        function deleteProhibit($phonenum) {
            $query_id = 'mobilemessage.deleteProhibit';
            $args->phone_num = $phonenum;
            return executeQuery($query_id, $args);
        }


        /**
         * @brief 
         **/
        function checkTime($ft_hour, $ft_min, $tt_hour, $tt_min) {
            $cur_dt = getdate();
            $cur_dt_hour = $cur_dt["hours"];
            $cur_dt_min = $cur_dt["minutes"];
            $cur_dt_hourmin = $cur_dt_hour * 100 + $cur_dt_min;

            $ft_hourmin = intval($ft_hour) * 100 + intval($ft_min);
            $tt_hourmin = intval($tt_hour) * 100 + intval($tt_min);

            if ($ft_hourmin < $tt_hourmin) {
                if ($cur_dt_hourmin >= $ft_hourmin && $cur_dt_hourmin <= $tt_hourmin) return true;
            } else {
                if ($cur_dt_hourmin >= $ft_hourmin || $cur_dt_hourmin <= $tt_hourmin) return true;
            }

            return false;
        }

        /**
         * @brief
         **/
        function checkWeekday(&$obj)
        {
            $dt = getdate();
            $wday = $dt["wday"];

            switch($wday) {
                case 1:
                    if ($obj->mon == "Y") return true;
                case 2:
                    if ($obj->tue == "Y") return true;
                case 3:
                    if ($obj->wed == "Y") return true;
                case 4:
                    if ($obj->thu == "Y") return true;
                case 5:
                    if ($obj->fri == "Y") return true;
                case 6:
                    if ($obj->sat == "Y") return true;
                case 7:
                    if ($obj->sun == "Y") return true;
            }

            return false;
        }

        /**
         * @brief document registration trigger
         **/
        function triggerInsertDocument(&$obj) {
            if (!$obj->module_srl) return new Object();

            $args->module_srl = $obj->module_srl;
            $output = executeQuery('module.getMidInfo', $args);
            // if module_srl is wrong, just return with success
            if (!$output->toBool() || !$output->data) return;
            $module_info = $output->data;

            $oModel = &getModel('mobilemessage');
            $notidoc_infos = $oModel->getNotiDocInfos($obj->module_srl);

            // 회원정보 획득
            $oMemberModel = &getModel('member');
            $member_info = $oMemberModel->getMemberInfoByMemberSrl($obj->member_srl);

            foreach($notidoc_infos as $no => $notidoc_info) {

                // check exception IDs
                $except_id_list = explode(',', $notidoc_info->except_id_list);
                if (count($except_id_list) > 0) {
                    // exit if exception ID
                    if ($member_info && in_array($member_info->user_id, $except_id_list)) continue;
                }

                // check time limit
                if ($notidoc_info->time_use=='Y') {
                    if (!$this->checkTime($notidoc_info->ft_hour, $notidoc_info->ft_min, $notidoc_info->tt_hour, $notidoc_info->tt_min)) continue;
                }
                // check weekday limit
                if ($notidoc_info->week_use=='Y') {
                    if (!$this->checkWeekday($notidoc_info)) continue;
                }



                // message content
                $msg->content = $this->mergeKeywords($notidoc_info->content, $obj);
                $msg->content = $this->mergeKeywords($msg->content, $module_info);
                $msg->content = str_replace("&nbsp;", "", strip_tags($msg->content));

                if ($notidoc_info->msgtype == "MMS") {
                    $msg->attachment = array();
                    // attachment
                    if ($obj->uploaded_count > 0) {
                        $oFileModel = &getModel('file');
                        $file_list = $oFileModel->getFiles($obj->document_srl);
                        if(count($file_list)) {
                            foreach($file_list as $file) {
                                if(!preg_match("/\.(jpg|jpeg)$/i",$file->source_filename)) continue;
                                if ($file->file_size > 204800) continue;
                                $msg->attachment[] = $file->uploaded_filename;
                                break;
                            }
                        }
                    }
                }

                // message type
                $msg->msgtype = $this->getMsgType($notidoc_info->msgtype, $msg);
                // callback number
                $nonmember_phonenum = false;
                $fname = "extra_vars{$notidoc_info->nonmember_index}";
                if ($notidoc_info->nonmember_index && isset($obj->{$fname})) $nonmember_phonenum = $obj->{$fname};
                $msg->callback_number = $this->getCallbackNumber($notidoc_info->callback_number_type, $notidoc_info->callback_number_direct, $obj->member_srl, $nonmember_phonenum);

                // 대상 아이디 - 새글, 댓글
                $id_list = explode(',', $notidoc_info->id_list);
                if (count($id_list) > 0) {
                    $this->sendToMembers($id_list, $msg);
                }

                // 대상 그룹 - 새글, 댓글
                if ($notidoc_info->group_srl_list) {
                    $this->sendToGroups($notidoc_info->group_srl_list, $msg);
                }

                // 게시판관리자 - 새글, 댓글
                if ($notidoc_info->manager == 'Y') {
                    $this->sendToBoardManager($obj->module_srl, $msg);
                }
            
                // 직접 입력
                $notidoc_info->direct_numbers = explode('|@|', $notidoc_info->direct_numbers);
                if (count($notidoc_info->direct_numbers)) {
                    $this->sendToDirectNumbers($notidoc_info->direct_numbers, $msg);
                }
            }

            return new Object();
        }

        /**
         * @brief comment registration trigger
         **/
        function triggerInsertComment(&$obj) {
            if (!$obj->module_srl) return new Object();
            // get module info
            $args->module_srl = $obj->module_srl;
            $output = executeQuery('module.getMidInfo', $args);
            if (!$output->toBool() || !$output->data) return new Object(-1, 'msg_invalid_request');
            $module_info = $output->data;

            // get noticom info
            $oModel = &getModel('mobilemessage');
            $noticom_info = $oModel->getNotiComInfo($obj->module_srl);
            if (!$noticom_info) return new Object();

            // 회원정보 획득
            $oMemberModel = &getModel('member');


            // check time limit
            if ($noticom_info->time_use=='Y') {
                if (!$this->checkTime($noticom_info->ft_hour, $noticom_info->ft_min, $noticom_info->tt_hour, $noticom_info->tt_min)) return new Object();
            }
            // check weekday limit
            if ($noticom_info->week_use=='Y') {
                if (!$this->checkWeekday($noticom_info)) return new Object();
            }

            // check limit hours
            if ($noticom_info->limit_hours) {
                $oDocumentModel = &getModel('document');
                $oDocument = $oDocumentModel->getDocument($obj->document_srl);
                if (time() - ztime($oDocument->get('regdate')) > 60 * 60 * intval($noticom_info->limit_hours)) return new Object();
            }

            // message content
            $msg->content = $this->mergeKeywords($noticom_info->content, $obj);
            $msg->content = $this->mergeKeywords($msg->content, $module_info);
            $msg->content = str_replace("&nbsp;", "", strip_tags($msg->content));

            if ($noticom_info->msgtype == "MMS") {
                $msg->attachment = array();
                // attachment
                if ($obj->uploaded_count > 0) {
                    $oFileModel = &getModel('file');
                    $file_list = $oFileModel->getFiles($obj->comment_srl);
                    if(count($file_list)) {
                        foreach($file_list as $file) {
                            if(!preg_match("/\.(jpg|jpeg)$/i",$file->source_filename)) continue;
                            $msg->attachment[] = $file->uploaded_filename;
                            break;
                        }
                    }
                }
            }

            // message type
            $msg->msgtype = $this->getMsgType($noticom_info->msgtype, $msg);
            // callback number
            $msg->callback_number = $this->getCallbackNumber($noticom_info->callback_number_type, $noticom_info->callback_number_direct, $obj->member_srl);


            /**
             * 회원 알림
             **/
            // 게시자에게 알림
            $oModel = &getModel('mobilemessage');
            $config = $oModel->getModuleConfig();

            if ($noticom_info->registrant == 'Y') {
                $flagSend = true;

                $oDocumentModel = &getModel('document');
                $oDocument = $oDocumentModel->getDocument($obj->document_srl);
                $document_member_srl = $oDocument->getMemberSrl();


                // 쪽지알림 연동이면서 notify_message가 'Y'가 아니면 보내지 않음
                if ($noticom_info->message_link == 'Y') {
                    if ($oDocument->useNotify())
                        $flagSend = true;
                    else
                        $flagSend = false;
                }

                // 역알림 사용이면서 현재 notify_message가 'Y'이면 발송 
                if ($noticom_info->reverse_notify == 'Y') {
                    if (Context::get('notify_message') == 'Y') 
                        $flagSend = true;
                    else
                        $flagSend = false;
                }

                // 게시자 본인이면 보내지 않음
                if ($document_member_srl == $obj->member_srl) $flagSend = false;

                if ($flagSend) {
                    $phonenum = false;

                    // if member wrote
                    if ($document_member_srl) {
                        $member_info = $oMemberModel->getMemberInfoByMemberSrl($document_member_srl);
                        if ($config->cellphone_fieldname && $member_info->{$config->cellphone_fieldname}) {
                            $phonenum = $member_info->{$config->cellphone_fieldname};
                            if (is_array($phonenum)) $phonenum = join($phonenum);
                        }
                    }
                    // if nonmember wrote
                    if ($noticom_info->nonmember_index) {
                        $phonenum = $oDocument->getExtraValue($noticom_info->nonmember_index);
                    }

                    if ($phonenum) {
                        $phonenum = str_replace('|@|', '', $phonenum);
                        $args->type = $msg->msgtype;
                        $args->recipient = $phonenum;
                        if ($msg->callback_number == 'self') 
                            $args->callback = $phonenum;
                        else
                            $args->callback = $msg->callback_number;
                        $args->message = $msg->content;
                        $output = $this->sendMessage($args);
                        if (!$output->toBool()) {
                            debugPrint('[mobilemessage] sendMessage failure: ' . $output->getMessage());
                        }
                    }
                }
            }

            // 상위 댓글자에게 알림
            if ($noticom_info->replier == 'Y') {
                if($obj->parent_srl) {
                    $flagSend = true;

                    $oCommentModel = &getModel('comment');
                    $oParent = $oCommentModel->getComment($obj->parent_srl);
                    $comment_member_srl = $oParent->getMemberSrl();

                    // 쪽지알림 연동이면서 notify_message가 'Y'가 아니면 발송하지 않음
                    if ($noticom_info->message_link == 'Y') {
                        if ($oDocument->useNotify())
                            $flagSend = true;
                        else
                            $flagSend = false;
                    }

                    // 역알림 사용이면서 현재 notify_message가 'Y'이면 발송 
                    if ($noticom_info->reverse_notify == 'Y') {
                        if (Context::get('notify_message') == 'Y') 
                            $flagSend = true;
                        else
                            $flagSend = false;
                    }

                    // 상위댓글자가 본인이면 보내지 않음
                    if ($comment_member_srl == $obj->member_srl) $flagSend = false;

                    // 게시자와 상위댓글자가 같으면 보내지 않음.(중복으로 보내지 않음)
                    if ($document_member_srl && $comment_member_srl == $document_member_srl) $flagSend = false;
                   
                    if ($flagSend) {
                        $member_info = $oMemberModel->getMemberInfoByMemberSrl($comment_member_srl);
                        if ($config->cellphone_fieldname && $member_info->{$config->cellphone_fieldname}) {
                            $phonenum = $member_info->{$config->cellphone_fieldname};
                            if (is_array($phonenum)) $phonenum = join($phonenum);
                        }

                        if ($phonenum) {
                            $phonenum = str_replace('|@|', '', $phonenum);
                            $args->type = $msg->msgtype;
                            $args->recipient = $phonenum;
                            if ($msg->callback_number == 'self') 
                                $args->callback = $phonenum;
                            else
                                $args->callback = $msg->callback_number;
                            $args->message = $msg->content;
                            $output = $this->sendMessage($args);
                            if (!$output->toBool()) {
                                debugPrint('[mobilemessage] sendMessage failure: ' . $output->getMessage());
                            }
                        }
                    }
                }
            }

            /**
             * 관리자 알림
             **/
            // check exception IDs
            $except_id_list = explode(',', $noticom_info->except_id_list);
            if (count($except_id_list) > 0) {
                $member_info = $oMemberModel->getMemberInfoByMemberSrl($obj->member_srl);

                // exit if exception ID
                if ($member_info && in_array($member_info->user_id, $except_id_list)) return new Object();
            }

            // 대상 아이디 - 새글, 댓글
            $id_list = explode(',', $noticom_info->id_list);
            if (count($id_list) > 0) {
                $this->sendToMembers($id_list, $msg);
            }

            // 대상 그룹 - 새글, 댓글
            if ($noticom_info->group_srl_list) {
                $this->sendToGroups($noticom_info->group_srl_list, $msg);
            }

            // 게시판관리자 - 새글, 댓글
            if ($noticom_info->manager == 'Y') {
                $this->sendToBoardManager($obj->module_srl, $msg);
            }
        
            // 직접 입력
            $noticom_info->direct_numbers = explode('|@|', $noticom_info->direct_numbers);
            if (count($noticom_info->direct_numbers)) {
                $this->sendToDirectNumbers($noticom_info->direct_numbers, $msg);
            }

            return new Object();
        }

        function getMsgType($msgtype, &$msg) {
            $oModel = &getModel('mobilemessage');
            $config = $oModel->getModuleConfig();
            switch (strtoupper($msgtype)) {
                case 'SMS': 
                default:
                    $msgtype = 'SMS';
                    break;
                case 'LMS':
                case 'AUTO':
                    require_once('mobilemessage.utility.php');
                    $csutil = new CSUtility();
                    if ($csutil->strlen_utf8($msg->content, true) > $config->limit_bytes)
                        $msgtype = 'LMS';
                    else
                        $msgtype = 'SMS';
                    break;
                case 'MMS':
                    if (count($msg->attachment) > 0) {
                        $msgtype = 'MMS';
                    } else {
                        require_once('mobilemessage.utility.php');
                        $csutil = new CSUtility();
                        if ($csutil->strlen_utf8($msg->content, true) > $config->limit_bytes)
                            $msgtype = 'LMS';
                        else
                            $msgtype = 'SMS';
                    }
                    break;
            }
            return $msgtype;
        }

        function getCallbackNumber($callback_number_type, $callback_number_direct, $member_srl, $member_phonenum=false) {
            $oMemberModel = &getModel('member');
            $oModel = &getModel('mobilemessage');
            $config = $oModel->getModuleConfig();

            $callback_number = "";
            switch ($callback_number_type) {
                case 'self':
                    return 'self';
                case 'writer':
                    if ($member_srl) {
                        $member_info = $oMemberModel->getMemberInfoByMemberSrl($member_srl);
                        if (!isset($config->cellphone_fieldname)) break;
                        if ($member_info->{$config->cellphone_fieldname}) {
                            $phonenum = $member_info->{$config->cellphone_fieldname};
                            if (is_array($phonenum)) $phonenum = join($phonenum);
                        }
                        if ($phonenum) {
                            $callback_number = $phonenum;
                        }
                    }
                    if ($member_phonenum) {
                        $callback_number = $member_phonenum;
                    }
                    break;
                case 'basic':
                    $callback_number = $config->callback;
                    break;
                case 'direct':
                    $callback_number = $callback_number_direct;
                    break;
            }
            $callback_number = str_replace('|@|', '', $callback_number);

            return $callback_number;
        }


        function sendToMembers(&$id_list, &$msg) {
            $logged_info = Context::get('logged_info');
            $oModel = &getModel('mobilemessage');
            $config = $oModel->getModuleConfig();
            $oMemberModel = &getModel('member');
            foreach ($id_list as $id) {
                if ($logged_info && $logged_info->user_id == $id) continue;  // skip if writer

                $member_info = $oMemberModel->getMemberInfoByUserID($id);
                if ($config->cellphone_fieldname && $member_info->{$config->cellphone_fieldname}) {
                    $phonenum = $member_info->{$config->cellphone_fieldname};
                    if (is_array($phonenum)) $phonenum = join($phonenum);
                    $phonenum = str_replace('|@|', '', $phonenum);
                }

                if ($phonenum) {
                    $args->type = $msg->msgtype;
                    $args->recipient = $phonenum;
                    if ($msg->callback_number == 'self') 
                        $args->callback = $phonenum;
                    else
                        $args->callback = $msg->callback_number;
                    $args->message = $msg->content;
                    if ($msg->attachment) $args->attachment = $msg->attachment;
                    $output = $this->sendMessage($args);
                    if (!$output->toBool()) {
                        debugPrint('[mobilemessage] sendMessage failure: ' . $output->getMessage());
                    }
                }
            }
        }

        function sendToGroups(&$group_srl_list, &$msg) {
            $logged_info = Context::get('logged_info');
            $oModel = &getModel('mobilemessage');
            $config = $oModel->getModuleConfig();
            $oMemberModel = &getModel('member');

            // get message sender object
            $oMessageSender = &$this->getMessageSender();

            $args->selected_group_srl = $group_srl_list;
            $args->list_count = 100;
            $args->sort_order = 'asc';
            $output = executeQueryArray('member.getMemberListWithinGroup', $args);
            if (!$output->toBool() || !$output->data) return;

            // add message
            foreach ($output->data as $no => $member_info) {
                if ($logged_info && $logged_info->user_id == $member_info->user_id) continue;  // skip if writer
                unset($phonenum);
                $member_info = $oMemberModel->arrangeMemberInfo($member_info);
                if ($config->cellphone_fieldname && $member_info->{$config->cellphone_fieldname}) {
                    $phonenum = $member_info->{$config->cellphone_fieldname};
                    if (is_array($phonenum)) $phonenum = join($phonenum);
                    $phonenum = str_replace('|@|', '', $phonenum);
                }
                if ($phonenum) {
                    unset($args);
                    $args->type = $msg->msgtype;
                    $args->recipient = $phonenum;
                    if ($msg->callback_number == 'self') 
                        $args->callback = $phonenum;
                    else
                        $args->callback = $msg->callback_number;
                    $args->message = $msg->content;
                    if ($msg->attachment) $args->attachment = $msg->attachment;
                    $oMessageSender->addMessage($args);
                }
            }

            // send
            $oMessageSender->sendMessages();
        }

        function sendToBoardManager($module_srl, &$msg) {
            $logged_info = Context::get('logged_info');
            $oModel = &getModel('mobilemessage');
            $config = $oModel->getModuleConfig();
            $oMemberModel = &getModel('member');
            $oModuleModel = &getModel('module');
            $admin_members = $oModuleModel->getAdminId($module_srl);
            if (count($admin_members)) {
                foreach ($admin_members as $member)
                {
                    if ($logged_info && $logged_info->user_id == $member->user_id) continue;  // skip if writer

                    $member_info = $oMemberModel->arrangeMemberInfo($member);
                    if ($config->cellphone_fieldname && $member_info->{$config->cellphone_fieldname}) {
                        $phonenum = $member_info->{$config->cellphone_fieldname};
                        if (is_array($phonenum)) $phonenum = join($phonenum);
                        $phonenum = str_replace('|@|', '', $phonenum);
                    }
                    if ($phonenum) {
                        $args->type = $msg->msgtype;
                        $args->recipient = $phonenum;
                        if ($msg->callback_number == 'self') 
                            $args->callback = $phonenum;
                        else
                            $args->callback = $msg->callback_number;
                        $args->message = $msg->content;
                        if ($msg->attachment) $args->attachment = $msg->attachment;
                        $result = $this->sendMessage($args);
                        if (!$result->toBool()) {
                            debugPrint('[mobilemessage] sendMessage failure: ' . $result->getMessage());
                        }
                    }
                }
            }
        }

        function sendToDirectNumbers(&$direct_numbers, &$msg) {
            foreach ($direct_numbers as $phonenum) {
                if ($phonenum) {
                    $phonenum = str_replace('-', '', $phonenum);
                    $args->type = $msg->msgtype;
                    $args->recipient = $phonenum;
                    if ($msg->callback_number == 'self') 
                        $args->callback = $phonenum;
                    else
                        $args->callback = $msg->callback_number;
                    $args->message = $msg->content;
                    if ($msg->attachment) $args->attachment = $msg->attachment;
                    $result = $this->sendMessage($args);
                    if (!$result->toBool()) {
                        debugPrint('[mobilemessage] sendMessage failure: ' . $result->getMessage());
                    }
                }
            }
        }

        function getTicket() {
            if (!isset($_SESSION['MOBILEMESSAGE_TICKET'])) {
                $ticket = md5(strval(rand()));
                $_SESSION['MOBILEMESSAGE_TICKET'] = $ticket;
            }
            return $_SESSION['MOBILEMESSAGE_TICKET'];
        }

        function validateTicket($ticket) {
            if (!isset($_SESSION['MOBILEMESSAGE_TICKET'])) return false;
            if ($ticket == $_SESSION['MOBILEMESSAGE_TICKET']) return true;
            return false;
        }

        function procMobilemessageFilePicker(){
            $oModel = &getModel('mobilemessage');
            $this->setTemplatePath($this->module_path.'tpl');
            $this->setLayoutFile('default_layout');
            $this->setTemplateFile('filepicker');

            $logged_info = Context::get('logged_info');
            if (!$logged_info) {
                Context::set('message', Context::getLang('msg_login_required'));
                return;
            }

            $vars = Context::gets('addfile','filter');

            $source_file = $vars->addfile['tmp_name'];
            if (!is_uploaded_file($source_file)) {
                Context::set('message', Context::getLang('msg_invalid_request'));
                return;
            }

            // check file format, size
            $ext = strtolower(substr(strrchr($vars->addfile['name'],'.'),1));
            if($vars->filter) $filter = explode(',',$vars->filter);
            else $filter = array('jpg','jpeg','gif','png');
            if(!in_array($ext,$filter)) {
                Context::set('message', Context::getLang('msg_invalid_file_format'));
                return;
            }


            // 파일 정보 구함
            list($width, $height, $type, $attrs) = @getimagesize($source_file);
            switch($type) {
                case '1' :
                        $type = 'gif';
                    break;
                case '2' :
                        $type = 'jpg';
                    break;
                case '3' :
                        $type = 'png';
                    break;
                case '6' :
                        $type = 'bmp';
                    break;
                default :
                        return;
                    break;
            }

            $max_width = "640";
            $max_height = "480";
            $target_ext = 'jpg';
            $file_srl = getNextSequence();
            $path = $oModel->getMobilemessageFilePickerPath($file_srl);
            $save_filename = sprintf('%s%s.%s',$path, $file_srl, $target_ext);

            if ($ext != 'jpg' || $width > $max_width || $height > $max_height) {
                FileHandler::createImageFile($source_file, $save_filename, $max_width, $max_height, $target_ext);
            } else {
                // create directory 
                $path = dirname($save_filename);
                if(!is_dir($path)) FileHandler::makeDir($path);
                // move file
                if(!@move_uploaded_file($source_file, $save_filename)) {
                    Context::set('message', Context::getLang('msg_error_occured'));
                    return;

                }
            }

            $output = $this->insertFile($save_filename, $file_srl);
            if (!$output->toBool()) {
                Context::set('message', $output->getMessage());
                return;
            }

            Context::set('filename', $save_filename);
            Context::set('mobilemessage_file_srl', $file_srl);

            $this->setLayoutFile('default_layout');
            $this->setTemplateFile('filepicker_selected');
        }

        function insertFile($save_filename, $file_srl){
            $logged_info = Context::get('logged_info');
            if (!$logged_info) return new Object(-1, 'login_required');

            //$vars->mobilemessage_file_srl = getNextSequence();

            /*
            // get file path
            $oModel = &getModel('mobilemessage');
            $path = $oModel->getMobilemessageFilePickerPath($vars->mobilemessage_file_srl);
            FileHandler::makeDir($path);
            $save_filename = sprintf('%s%s.%s',$path, $vars->mobilemessage_file_srl, $vars->ext);
            $tmp = $vars->addfile['tmp_name'];
             */

            /*
            // upload
            if(!@move_uploaded_file($tmp, $save_filename)) {
                return new Object(-1, 'msg_error_occured');
            }
             */
            // 파일 정보 구함
            list($width, $height, $type, $attrs) = @getimagesize($save_filename);
            if($type == 3) $ext = 'png';
            elseif($type == 2) $ext = 'jpg';
            else $ext = 'gif';


            // insert
            $args->mobilemessage_file_srl = $file_srl;
            $args->member_srl = $logged_info->member_srl;
            $args->filename = $save_filename;
            $args->fileextension = $ext;
            $args->filesize = filesize($save_filename);

            $output = executeQuery('mobilemessage.insertFilePicker', $args);
            $output->save_filename = $save_filename;
            $output->mobilemessage_file_srl = $vars->mobilemessage_file_srl;
            return $output;

            /*
            // insert
            $args->mobilemessage_file_srl = $vars->mobilemessage_file_srl;
            $args->member_srl = $vars->member_srl;
            $args->filename = $save_filename;
            $args->fileextension = strtolower(substr(strrchr($vars->addfile['name'],'.'),1));
            $args->filesize = $vars->addfile['size'];

            $output = executeQuery('mobilemessage.insertFilePicker', $args);
            $output->save_filename = $save_filename;
            $output->mobilemessage_file_srl = $vars->mobilemessage_file_srl;
            return $output;
             */
        }

        /**
         * @return true : has permission, false : no permission
         **/
        function checkPermission($node_id) {
            // login check
            $logged_info = Context::get('logged_info');
            if (!$logged_info) return false;

            // check permission for node_id
            $args->node_id = $node_id;
            $output = executeQuery('mobilemessage.getNodeInfoByNodeId',$args);
            if (!$output->toBool() || !$output->data) return false;
            if ($output->data->member_srl != $logged_info->member_srl) return false;
            return true;
        }

        function procMobilemessagePurplebookUpdateName() {
            // login check
            $logged_info = Context::get('logged_info');
            if (!$logged_info) return new Object(-1, 'msg_invalid_request');

            $node_id = Context::get('node_id');
            $node_name = Context::get('name');

            // check permission for node_id
            if (!$this->checkPermission($node_id)) return new Object(-1, 'msg_no_permission');

            $args->user_id = $logged_info->user_id;
            $args->node_id = $node_id;
            $args->node_name = $node_name;
            $output = executeQuery('mobilemessage.updatePurplebookName', $args);
            return $output;
        }

        function procMobilemessagePurplebookUpdatePhone() {
            // login check
            $logged_info = Context::get('logged_info');
            if (!$logged_info) return new Object(-1, 'msg_invalid_request');

            $node_id = Context::get('node_id');
            $phone_num = Context::get('phone_num');

            // check permission for node_id
            if (!$this->checkPermission($node_id)) return new Object(-1, 'msg_no_permission');

            $args->user_id = $logged_info->user_id;
            $args->node_id = $node_id;
            $args->phone_num = $phone_num;
            $output = executeQuery('mobilemessage.updatePurplebookPhone', $args);
            return $output;
        }

        /**
         * @brief copy nodes
         **/
        function procMobilemessagePurplebookCopy() {
            $logged_info = Context::get('logged_info');
            if (!$logged_info) return new Object(-1, 'msg_not_logged');

            $node_list = $this->getJSON('node_list');
            $node_id = Context::get('node_id');

            // get node_route
            if (in_array($node_id,array('f.','s.','t.'))) {
                $node_route = $node_id;
            } else {
                $args->node_id = $node_id;
                $output = executeQuery('mobilemessage.getNodeInfoByNodeId',$args);
                if (!$output->toBool() || !$output->data) return $output;
                $node_route = $output->data->node_route . $output->data->node_id . '.';
            }

            foreach ($node_list as $node_id) {
                unset($args);
                $args->node_id = $node_id;
                $output = executeQuery('mobilemessage.getNodeInfoByNodeId', $args);
                if ($output->data) {
                    unset($args);
                    $args->node_id = getNextSequence();
                    $args->member_srl = $logged_info->member_srl;
                    $args->user_id = $logged_info->user_id;
                    $args->node_route = $node_route;
                    $args->node_name = $output->data->node_name;
                    $args->node_type = $output->data->node_type;
                    $args->phone_num = str_replace('-', '', $output->data->phone_num);
                    $this->insertPurplebook($args);
                }
            }
        }

        function procMobilemessageKeepContent() {
            $logged_info = Context::get('logged_info');
            if (!Context::get('is_logged') || !$logged_info) return new Object(-1, 'login_required');
            $args->keeping_srl = getNextSequence();
            $args->member_srl = $logged_info->member_srl;
            $args->content = Context::get('content');

            $output = executeQuery('mobilemessage.insertKeeping', $args);
            if (!$output->toBool()) return $output;
        }

        /**
         * @brief 선택된 로그 일괄 취소(그룹)
         **/
        function procMobilemessageCancelGroupMessages() {
            $target_group_ids = Context::get('target_group_ids');
            if(!$target_group_ids) 
                return new Object(-1, 'msg_invalid_request');
            $group_ids = explode(',', $target_group_ids);

            $output = $this->cancelGroupMessages($group_ids);
            if(!$output->toBool()) {
                $this->setMessage('cancel_failed');
                return $output;
            }

            $this->setMessage('success_canceled');
        }
        /**
         * @brief 선택된 로그 일괄 취소
         **/
        function procMobilemessageCancelMessages() {
            $target_msgids = Context::get('target_msgids');
            if(!$target_msgids) 
                return new Object(-1, 'msg_invalid_request');
            $msgids = explode(',', $target_msgids);

            $output = $this->cancelMessage($msgids);
            if(!$output->toBool()) {
                $this->setMessage('cancel_failed');
                return $output;
            }

            $this->setMessage('success_canceled');
        }

        /**
         * @brief 주소록 Node 추가
         **/
        function procMobilemessagePurplebookAddNode() {
            $logged_info = Context::get('logged_info');
            if (!$logged_info) return new Object(-1, 'msg_login_required');

            $parent_node = Context::get('parent_node');

            // deny adding to trashcan and folder shared
            if (in_array($parent_node, array('t.','s.'))) {
                return new Object(-1, 'msg_cannot_create_folder');
            }

            // get node_route
            if (in_array($parent_node, array('f.','t.','s.'))) {
                $node_route = $parent_node;
            } else {
                // get parent node
                $args->node_id = $parent_node;
                $output = executeQuery('mobilemessage.getNodeInfoByNodeId', $args);
                if (!$output->toBool()) return $output;
                if (!$output->data) return new Object(-1, 'msg_invalid_request');

                // check for permission
                if ($output->data->member_srl != $logged_info->member_srl) return new Object(-1,'msg_no_permission');

                $node_route = $output->data->node_route . $parent_node . '.';
            }


            unset($args);
            $args->member_srl = $logged_info->member_srl;
            $args->user_id = $logged_info->user_id;
            $args->parent_node = $parent_node;
            $args->node_route = $node_route;
            $args->node_name = Context::get('node_name');
            $args->node_type = Context::get('node_type');
            $args->phone_num = str_replace('-', '', Context::get('phone_num'));

            $this->insertPurplebook($args);

            if (!in_array($parent_node, array('f.','t.','s.'))) {
                if ($args->node_type=='1') $this->updateSubfolder($logged_info->user_id, $parent_node);
                if ($args->node_type=='2') $this->updateSubnode($logged_info->user_id, $parent_node);
            }

            $this->add('id', $args->node_id);
            $this->add('node_id', $args->node_id);
            $this->add('node_route', $args->node_route);
            $this->add('node_name', $args->node_name);
            if ($args->node_type=='1') $this->add('rel','folder');
        }

        /**
         * @brief 주소록 Node 추가
         **/
        function procMobilemessagePurplebookAddList() {
            $logged_info = Context::get('logged_info');
            if (!$logged_info) return new Object(-1, 'msg_login_required');

            $data = $this->getJSON('data');
            $parent_node = Context::get('parent_node');

            // get node_route
            if (in_array($parent_node, array('f.','t.','s.'))) {
                $node_route = $parent_node;
            } else {
                // get parent node
                $args->node_id = $parent_node;
                $output = executeQuery('mobilemessage.getNodeInfoByNodeId', $args);
                if (!$output->toBool()) return $output;
                if (!$output->data) return new Object(-1, 'msg_invalid_request');

                // check for permission
                if ($output->data->member_srl != $logged_info->member_srl) return new Object(-1,'msg_no_permission');

                $node_route = $output->data->node_route . $parent_node . '.';
            }


            $list = array();
            foreach ($data as $obj) {
                $args = new StdClass();
                $args->member_srl = $logged_info->member_srl;
                $args->user_id = $logged_info->user_id;
                $args->parent_node = $parent_node;
                $args->node_route = $node_route;
                $args->node_name = $obj->node_name;
                $args->node_type = '2';
                $args->phone_num = str_replace('-', '', $obj->phone_num);

                $list[] = $args;
                $this->insertPurplebook($args);
            }

            if (!in_array($parent_node, array('f.','t.','s.'))) {
                $this->updateSubnode($logged_info->user_id, $parent_node);
            }

            $this->add('return_data',$list);
        }

        /**
         * @brief 주소록
         **/
        function procMobilemessagePurplebookRenameNode() {
            $logged_info = Context::get('logged_info');
            if (!$logged_info) return new Object(-1, 'msg_invalid_request');

            $node_id = Context::get('node_id');
            $node_name = Context::get('node_name');

            // check permission for node_id
            if (!$this->checkPermission($node_id)) return new Object(-1, 'msg_no_permission');

            $args->node_id = $node_id;
            $args->node_name = $node_name;
            if (!$args->node_name) return;
            $output = $this->updatePurplebook($args);
            return $output;
        }


        /*
        function getPostNode($node_route) {
            debugPrint('getParentNode node_route:' . $node_route);
            $route_arr = preg_split('/\./', trim($node_route, '.'));
            $last = count($route_arr) - 1;
            if ($last < 0) return;
            debugPrint('route_arr:' . serialize($route_arr));
            debugPrint('last idx:' . $last);
            debugPrint('last:' . $route_arr[$last]);
            return $route_arr[$last];
        }
         */

        function copyNode($user_id, $node_id, $parent_id) {
            // get destination
            $args->node_id = $parent_id;
            $args->user_id = $user_id;
            $output = executeQuery('mobilemessage.getPurplebook', $args);
            if (!$output->toBool()) return $output;
            $dest_node = $output->data;

            // new route
            $new_args->node_id = $node_id;
            $new_args->node_route = $dest_node->node_route . $dest_node->node_id . '.';

            // get current node
            unset($args);
            $args->node_id = $node_id;
            $args->user_id = $user_id;
            $output = executeQuery('mobilemessage.getPurplebook', $args);
            if (!$output->toBool()) return $output;
            $current = $output->data;

            // copy current node
            unset($args);
            $args = clone($current);
            $args->node_route = $new_args->node_route;
            $output = $this->insertPurplebook($args);
            if (!$output->toBool()) return $output;
            $new_node_id = $output->node_id;

            // copy children
            $search_args->user_id = $user_id;
            $search_args->node_route = $current->node_route . $current->node_id . '.';
            //$search_args->node_type = '2';
            $output = executeQueryArray('mobilemessage.getPurplebookChildrenByNodeRoute', $search_args);
            if (!$output->toBool()) return $output;
            $new_route = $new_args->node_route . $new_node_id . '.';
            if ($output->data) {
                foreach ($output->data as $no => $val) {
                    $val->node_route = $new_route;
                    $old_node_id = $val->node_id;
                    if ($val->node_type = '1' && $val->subfolder > 0) {
                        $new_node_id = $res->node_id;
                        $this->copyNode($user_id, $old_node_id, $new_node_id);
                    } else {
                        $res = $this->insertPurplebook($val);
                    }
                    
                    //executeQuery('mobilemessage.updatePurplebook', $val);
                }
            }

            /*
            // update current
            $output = executeQuery('mobilemessage.updatePurplebook', $new_args);
            if (!$output->toBool()) return $output;

            $this->updateSubfolder($user_id, $previous_node);
             */
            if ($parent_id) $this->updateSubfolder($user_id, $parent_id);
        }

        function moveNode($node_id, $parent_id) {
            $logged_info = Context::get('logged_info');
            if (!$logged_info) return;

            // get destination
            if (in_array($parent_id, array('f.','t.','s.'))) {
                $dest_route = $parent_id;
            } else {
                $args->node_id = $parent_id;
                $args->member_srl = $logged_info->member_srl;
                $args->user_id = $logged_info->user_id;
                $output = executeQuery('mobilemessage.getPurplebook', $args);
                if (!$output->toBool()) return $output;
                $dest_node = $output->data;
                $dest_route = $dest_node->node_route . $dest_node->node_id . '.';
            }

            // new route
            $new_args->node_id = $node_id;
            $new_args->node_route = $dest_route;

            // update children
            $args->node_id = $node_id;
            $args->member_srl = $logged_info->member_srl;
            $args->user_id = $logged_info->user_id;
            $output = executeQuery('mobilemessage.getPurplebook', $args);
            if (!$output->toBool()) return $output;
            $search_args->member_srl = $logged_info->member_srl;
            $search_args->user_id = $logged_info->user_id;
            $search_args->node_route = $output->data->node_route . $output->data->node_id . '.';
            $previous_node = $this->getPostNode($output->data->node_route);
            $output = executeQueryArray('mobilemessage.getPurplebookByNodeRoute', $search_args);
            if (!$output->toBool()) return $output;
            $old_route = $search_args->node_route;
            $new_route = $new_args->node_route . $node_id . '.';
            if ($output->data) {
                foreach ($output->data as $no => $val) {
                    $val->node_route = str_replace($old_route, $new_route, $val->node_route);
                    executeQuery('mobilemessage.updatePurplebook', $val);
                }
            }

            // update current
            $output = executeQuery('mobilemessage.updatePurplebook', $new_args);
            if (!$output->toBool()) return $output;

            // root folder has no node_id.
            if ($previous_node) $this->updateSubfolder($logged_info->user_id, $previous_node);
            if ($parent_id) $this->updateSubfolder($logged_info->user_id, $parent_id);
        }

        function procMobilemessagePurplebookMoveNode() {
            $logged_info = Context::get('logged_info');
            if (!$logged_info) return new Object(-1, 'msg_log_required');

            $parent_id = Context::get('parent_id');
            $node_id = Context::get('node_id');
            $copy = Context::get('copy');

            // check permission for parent_id
            if (!in_array($parent_id,array('f.','s.','t.'))) {
                if (!$this->checkPermission($parent_id)) return new Object(-1, 'msg_no_permission');
                /*
                $args->node_id = $parent_id;
                $output = executeQuery('mobilemessage.getNodeInfoByNodeId',$args);
                if (!$output->toBool() || !$output->data) return $output;
                if ($output->data->member_srl != $logged_info->member_srl) return new Object(-1, 'msg_no_permission');
                 */
            }

            // check permission for node_id
            if (!$this->checkPermission($node_id)) return new Object(-1, 'msg_no_permission');

            if ($copy) {
                //$this->copyNode($logged_info->user_id, $node_id, $parent_id);
            } else {
                // move
                $this->moveNode($node_id, $parent_id);
            }
        }

        function procMobilemessagePurplebookMoveList() {
            $logged_info = Context::get('logged_info');
            if (!$logged_info) return new Object(-1, 'msg_log_required');

            $parent_id = Context::get('parent_id');
            $node_list = $this->getJSON('node_list');

            // check permission for parent_id
            if (!in_array($parent_id,array('f.','s.','t.'))) {
                if (!$this->checkPermission($parent_id)) return new Object(-1, 'msg_no_permission');
            /*
                $args->node_id = $parent_id;
                $output = executeQuery('mobilemessage.getNodeInfoByNodeId',$args);
                if (!$output->toBool() || !$output->data) return $output;
                if ($output->data->member_srl != $logged_info->member_srl) return new Object(-1, 'msg_no_permission');
             */
            }

            foreach ($node_list as $node_id) {
                // check permission for node_id
                if (!$this->checkPermission($node_id)) return new Object(-1, 'msg_no_permission');
                /*
                $args->node_id = $node_id;
                $output = executeQuery('mobilemessage.getNodeInfoByNodeId',$args);
                if (!$output->toBool() || !$output->data) return $output;
                if ($output->data->member_srl != $logged_info->member_srl) return new Object(-1, 'msg_no_permission');
                 */
                $this->moveNode($node_id, $parent_id);
            }
        }

        /**
         * @brief 주소록 Node 삭제
         **/
        function procMobilemessagePurplebookDeleteNode() {
            $node_id = Context::get('node_id');
            $logged_info = Context::get('logged_info');
            if (!$logged_info) return new Object(-1, 'msg_login_required');

            // get destination
            if (in_array($node_id, array('f.','t.','s.'))) {
                $node_route = $node_id;
            } else {
                // get parent node
                $args->node_id = $node_id;
                $args->user_id = $logged_info->user_id;
                $output = executeQuery('mobilemessage.getPurplebook', $args);
                if (!$output->toBool()) return $output;
                $parent_node = $this->getPostNode($output->data->node_route);
                $node_route = $output->data->node_route . $node_id . '.';
            }
            unset($args);

            // delete share info.
            $args->user_id = $logged_info->user_id;
            $args->node_route = $node_route;
            $args->node_type = '1';
            $output = executeQueryArray('mobilemessage.getPurplebookByNodeRoute', $args);
            if (!$output->toBool()) return $output;
            unset($args);
            $shared_ids = array();
            if ($output->data) {
                foreach ($output->data as $no=>$val) {
                    $shared_ids[] = $val->node_id;
                }
            }
            if (count($shared_ids)) {
                $args->node_ids = implode(',', $shared_ids);
                $output = executeQuery('mobilemessage.deleteSharedFolders', $args);
                if (!$output->toBool()) return $output;
            }

            // delete subfolder
            $args->user_id = $logged_info->user_id;
            $args->node_route = $node_route;
            $output = executeQuery('mobilemessage.deletePurplebookByNodeRoute', $args);
            if (!$output->toBool()) return $output;
            unset($args);

            // delete self
            if (!in_array($node_id, array('f.','t.','s.'))) {
                $args->user_id = $logged_info->user_id;
                $args->node_id = $node_id;
                $output = executeQuery('mobilemessage.deletePurplebook', $args);
                if (!$output->toBool()) return $output;
            }
            unset($args);

            // update parent subfolder
            if ($parent_node) {
                $output = $this->updateSubfolder($logged_info->user_id, $parent_node);
                if (!$output->toBool()) return $output;
            }
        }

        function procMobilemessagePurplebookShareNode() {
            $logged_info = Context::get('logged_info');
            if (!$logged_info) return new Object(-1, 'msg_login_required');

            $node_id = Context::get('node_id');
            $user_id = Context::get('user_id');

            $oMemberModel = &getModel('member');
            $member_info = $oMemberModel->getMemberInfoByUserId($user_id);
            if (!$member_info) return new Object(-1, 'msg_not_exists_member');

            // check myself
            if ($member_info->member_srl==$logged_info->member_srl) return new Object(-1, 'msg_cannot_share_oneself');
            
            $args->share_member = $member_info->member_srl;
            $args->node_id = $node_id;
            $output = executeQueryArray('mobilemessage.getSharedFolder', $args);
            if (!$output->toBool()) return $output;
            if (count($output->data)) return new Object(-1, 'msg_exist_shared_folder');

            $output = executeQuery('mobilemessage.deleteSharedFolder', $args);
            if (!$output->toBool()) return $output;
            $output = executeQuery('mobilemessage.insertSharedFolder', $args);
            if (!$output->toBool()) return $output;

            // get shared count
            $args->node_id = $node_id;
            $output = executeQuery('mobilemessage.getSharedCount', $args);
            if (!$output->toBool()) return $output;
            $shared_count = 0;
            if ($output->data) $shared_count = $output->data->shared;

            // update shared count
            $args->node_id = $node_id;
            $args->shared = $shared_count;
            $output = executeQuery('mobilemessage.updateShared', $args);
            if (!$output->toBool()) return $output;

            $this->add('node_id', $node_id);
            $this->add('member_srl', $member_info->member_srl);
            $this->add('user_id', $member_info->user_id);
            $this->add('nick_name', $member_info->nick_name);
            $this->add('shared_count', $shared_count);

            $this->setMessage('msg_folder_shared');
        }

        function procMobilemessagePurplebookUnshareNode() {
            $logged_info = Context::get('logged_info');
            if (!$logged_info) return new Object(-1, 'msg_login_required');

            $node_id = Context::get('node_id');
            $member_srl = Context::get('member_srl');

            // delete shared folder
            $args->share_member = $member_srl;
            $args->node_id = $node_id;
            $output = executeQuery('mobilemessage.deleteSharedFolder', $args);
            if (!$output->toBool()) return $output;

            // count up exist shared folders
            $args->node_id = $node_id;
            $output = executeQuery('mobilemessage.getSharedCount', $args);
            if (!$output->toBool()) return $output;
            $shared_count = 0;
            if ($output->data) $shared_count = $output->data->shared;

            // update count
            $args->node_id = $node_id;
            $args->shared = $shared_count;
            $output = executeQuery('mobilemessage.updateShared', $args);
            if (!$output->toBool()) return $output;

            $this->add('member_srl', $member_srl);
            $this->add('shared_count', $shared_count);

            $this->setMessage('msg_folder_unshared');
        }

        function procMobilemessageCallbackNumberInsert() {
            $logged_info = Context::get('logged_info');
            if (!$logged_info) return new Object(-1, 'msg_login_required');

            $args->member_srl = $logged_info->member_srl;
            $output = executeQuery('mobilemessage.getCountCallbackNumber', $args);
            if (!$output->toBool()) return $output;
            if ($output->data->count >= 5) return new Object(-1, 'msg_callback_limit');

            $args->callback_srl = getNextSequence();
            $args->member_srl = $logged_info->member_srl;
            $args->user_id = $logged_info->user_id;
            $args->phonenum = preg_replace("/[^0-9]/", "", Context::get('phonenum'));
            if (!$args->phonenum) return new Object(-1, '번호를 올바르게 입력해 주세요.');
            return executeQuery('mobilemessage.insertCallbackNumber', $args);
        }

        function procMobilemessageCallbackNumberDelete() {
            $callback_srl = Context::get('callback_srl');
            if (!$callback_srl) return new Object(-1, 'msg_invalid_request');

            $args->callback_srl = $callback_srl;
            return executeQuery('mobilemessage.deleteCallbackNumber', $args);
        }

        function procMobilemessageSetDefaultCallbackNumber() {
            $logged_info = Context::get('logged_info');
            if (!$logged_info) return new Object(-1, 'msg_login_required');

            $phonenum = preg_replace("/[^0-9]/", "", Context::get('phonenum'));

            $args->member_srl = $logged_info->member_srl;
            $args->flag_default = 'N';
            $output = executeQuery('mobilemessage.updateCallbackNumber', $args);

            $args->member_srl = $logged_info->member_srl;
            $args->phonenum = $phonenum;
            $args->flag_default = 'Y';
            $output = executeQuery('mobilemessage.updateCallbackNumber', $args);
            return $output;
        }

        function procMobilemessageInsertRecentReceiver() {
            $logged_info = Context::get('logged_info');
            if (!Context::get('is_logged') || !$logged_info) return new Object(-1, 'login_required');

            $args->receiver_srl = getNextSequence();
            $args->member_srl = $logged_info->member_srl;
            $args->user_id = $logged_info->user_id;
            $args->ref_name = Context::get('ref_name');
            $args->phone_num = Context::get('phone_num');

            $output = executeQuery('mobilemessage.deleteRecentReceiver', $args);
            $output = executeQuery('mobilemessage.insertRecentReceiver', $args);
            if (!$output->toBool()) return $output;
        }

        function procMobilemessageDeleteRecentNumber() {
            $logged_info = Context::get('logged_info');
            if (!Context::get('is_logged') || !$logged_info) return new Object(-1, 'login_required');

            $args->member_srl = $logged_info->member_srl;
            $args->receiver_srl = Context::get('receiver_srl');

            $output = executeQuery('mobilemessage.deleteRecentNumber', $args);
            if (!$output->toBool()) return $output;

            $this->setMessage('success_deleted');
        }

        function procMobilemessageDeleteRecentContent() {
            $logged_info = Context::get('logged_info');
            if (!Context::get('is_logged') || !$logged_info) return new Object(-1, 'login_required');

            $args->member_srl = $logged_info->member_srl;
            $args->keeping_srl = Context::get('keeping_srl');

            $output = executeQuery('mobilemessage.deleteRecentMessage', $args);
            if (!$output->toBool()) return $output;

            $this->setMessage('success_deleted');
        }

        /**
         * @brief 인증번호 발송
         * @args->phonenum : 수신번호
         * @args->callback : 발신번호 - 생략가능
         * @args->country : 국가번호(국제) - 생략가능
         * @args->content : 내용(%authcode% 포함) - 생략시 "[핸드폰인증]\n%authcode% ☜ 인증번호를 정확히 입력해 주세요."
         * $args->encode_utf16 : UTF16 전송 true or false
         **/
        function sendAuthCode($args) {
            $oMobilemessageModel = &getModel('mobilemessage');
            $config = &$oMobilemessageModel->getModuleConfig();
            $phonenumber = $args->phonenum;
            $callback = $args->callback;
            $country = $args->country;
            $content = $args->content;

            // default country code
            $default_country = $config->default_country;

            $key = rand(1, 99999);
            $keystr = sprintf("%05d", $key);
            if (!$content) $content = "[핸드폰인증]\n%validation_code% ☜ 인증번호를 정확히 입력해 주세요.";
            $content = preg_replace("/%authcode%/", $keystr, $content);

            // delete
            unset($args);
            $args = new StdClass();
            $args->callno = $phonenumber;
            $args->country = $country;
            executeQuery('mobilemessage.deleteValCode', $args);

            // insert
            unset($args);
            $args = new StdClass();
            $args->callno = $phonenumber;
            $args->country = $country;
            $args->valcode = $keystr;
            executeQuery('mobilemessage.insertValCode', $args);

            unset($args);
            $args = new StdClass();
            $args->country = $country;
            $args->recipient = $phonenumber;
            if ($callback)
                $args->callback = $callback;
            else
                $args->callback = $config->s_callback;
            $args->message = $content;
            $args->encode_utf16 = $encode_utf16;

            $controller = &getController('mobilemessage');
            $output = $controller->sendMessage($args);
            if (!$output->toBool())
                return $output;

            return new Object();
        }
    }
?>
