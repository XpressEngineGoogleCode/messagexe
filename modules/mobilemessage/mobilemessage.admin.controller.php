<?php
    /**
     * vi:set sw=4 ts=4 expandtab enc=utf8:
     * @class  mobilemessageAdminController
     * @author diver(diver@coolsms.co.kr)
     * @brief  mobilemessageAdminController
     */
    class mobilemessageAdminController extends mobilemessage {
        function init() {
        }

        /**
         * @brief 모듈 환경설정값 쓰기
         **/
        function procMobilemessageAdminInsertConfig() {
            $oModel = &getModel('mobilemessage');
            $bsucc = true;
            $messages = "= 설정오류 내역 =\n";

            $args = Context::gets('cs_userid', 'cs_passwd', 'cellphone_fieldname',
                'validationcode_fieldname', 'countrycode_fieldname', 'callback', 'admin_phones'
                , 'flag_welcome_member', 'flag_welcome_admin'
                , 'allow_lms_member', 'allow_lms_admin'
                , 'welcome_member', 'welcome_admin'
                , 'point_for_sms', 'point_for_lms'
                , 'callback_url', 'keep_mapping', 'keep_mapping_days'
                , 'id_list', 'group_srl_list'
                , 'callback_number_type', 'callback_number_direct'
                , 'change_group_srl_list', 'inform_group_change', 'allow_lms_group_change', 'group_change_message'
                , 'force_strip', 'default_country', 'display_country', 'encode_utf16'
            );

            if (!$args->cs_userid) $args->cs_userid = "";
            if (!$args->cs_passwd) $args->cs_passwd = "";

            // 서비스ID 입력 확인
            if (!$args->cs_userid)
            {
                $messages .= "서비스ID는 반드시 입력하셔야 합니다.\n";
                $bsucc = false;
            }

            // 서비스ID 입력 확인
            if (!$args->cs_passwd)
            {
                $messages .= "패스워드는 반드시 입력하셔야 합니다.\n";
                $bsucc = false;
            }

            // 가입환영 메시지 설정 확인
            if ($args->flag_welcome_member)
            {
                if (!$args->cellphone_fieldname)
                {
                    $messages .= "[핸드폰번호 필드명] 가입환영 메시지 사용에 필수 입력사항입니다.\n";
                    $bsucc = false;
                }
            }

            // 관리자에게 회원가입 알림 설정 확인
            if ($args->flag_welcome_admin) {
                if (!$args->id_list && !$args->group_srl_list && !$args->admin_phones) {
                    $messages .= "[회원가입 알림 / 관리자 알림] 회원가입 알림받을 관리자가 설정되지 않았습니다. 사용하지 않으신다면 [발송] 항목의 체크를 없애주세요.\n";
                    $bsucc = false;
                }
            }

            // 폰번호 필드 설정을 했지만 올바르지 않는지 체크
            if ($args->cellphone_fieldname) {
                if (!$this->isValidFieldName($args->cellphone_fieldname)) {
                    $messages .= "[핸드폰번호 필드명] 필드명이 올바르지 않습니다. 정확히 입력해 주세요.";
                    $bsucc = false;
                }
            }

            // 인증번호 필드 설정을 했지만 올바른지 체크
            if ($args->validationcode_fieldname) {
                if (!$this->isValidFieldName($args->validationcode_fieldname)) {
                    $messages .= "[인증번호 필드명] 필드명이 올바르지 않습니다. 정확히 입력해 주세요.";
                    $bsucc = false;
                }
            }

            // check whether countrycode_fieldname is valid
            if ($args->countrycode_fieldname) {
                if (!$this->isValidFieldName($args->countrycode_fieldname)) {
                    $messages .= "[국가번호 필드명] 필드명이 올바르지 않습니다. 정확히 입력해 주세요.";
                    $bsucc = false;
                }
            }

            // send callback-url to server
            if ($args->callback_url) {
                $callback_url = $args->callback_url;
                // choose '?' or '&' whether the callback_url has '?' notation.
                if (strpos($callback_url, '?') === false)
                    $callback_url = $callback_url . '?';
                else
                    $callback_url = $callback_url . '&';
                // add query
                $callback_url = $callback_url . "module=mobilemessage&act=procMobilemessageUpdateResult";

                require_once($this->module_path.'coolsms.php');

                $sms = new coolsms();
                $sln_reg_key = $oModel->getSlnRegKey();
                if ($sln_reg_key) $sms->enable_resale();
                $sms->appversion("MXE/{$this->version}/" . __ZBXE_VERSION__);
                $sms->setuser($args->cs_userid, $args->cs_passwd);
                if ($sms->connect()) {
                    $sms->setcallbackurl($callback_url);        
                    $sms->disconnect();
                }
            }

            if (!$args->keep_mapping) $args->keep_mapping = 'N';

            // save module configuration.
            $oModuleControll = getController('module');
            $output = $oModuleControll->insertModuleConfig('mobilemessage', $args);

            if ($bsucc)
                $this->setMessage('success_updated');
            else
                $this->setMessage($messages);
        }

        function isValidFieldName($fieldname) {
            $bfound = false;

            // 확장 필드에세 찾아보기
            $oMemberModel = &getModel('member');
            $list = $oMemberModel->getJoinFormList();
            if ($list) {
                foreach ($list as $row) {
                    if ($row->column_name == $fieldname) {
                        $bfound = true;
                    }
                }
            }

            // 기본 필드에세 찾아보기
            $logged_info = $oMemberModel->getLoggedInfo();
            if (isset($logged_info->{$fieldname}))
                $bfound = true;

            return $bfound;
        }

        /**
         * @brief delete selected files.
         **/
        function procMobilemessageAdminDeleteFiles() {
            $mobilemessage_file_srls = Context::get('mobilemessage_file_srls');
            if(!$mobilemessage_file_srls) return new Object(-1, 'msg_invalid_request');

            $srls = explode(',' , $mobilemessage_file_srls);
            if (!count($srls)) return new Object(-1, 'msg_invalid_request');

            for ($i = 0; $i < count($srls); $i++) {
                $srl = (int)$srls[$i];
                if (!$srl) continue;

                $args->mobilemessage_file_srl = $srl;

                $output = executeQuery('mobilemessage.getMobilemessageFile', $args);
                if (!$output->toBool()) continue;

                $file_info = $output->data;
                if (!$file_info) continue;

                $uploaded_filename = $file_info->filename;

                $output = executeQuery('mobilemessage.deleteMobilemessageFile', $args);
                if (!$output->toBool()) continue;

                FileHandler::removeFile($uploaded_filename);
            }
        }

        /**
         * @brief delete recent messages
         **/
        function procMobilemessageAdminDeleteRecentMessages() {
            $target_srls = Context::get('keeping_srls');
            if(!$target_srls) return new Object(-1, 'msg_invalid_request');
            $oController = &getController('mobilemessage');

            $args->keeping_srls = $target_srls;
            executeQuery('mobilemessage.deleteRecentMessages', $args);

            $this->setMessage('success_deleted');
        }

        /**
         * @brief delete recent receivers
         **/
        function procMobilemessageAdminDeleteRecentReceivers() {
            $target_srls = Context::get('receiver_srls');
            if(!$target_srls) return new Object(-1, 'msg_invalid_request');
            $oController = &getController('mobilemessage');

            $args->receiver_srls = $target_srls;
            executeQuery('mobilemessage.deleteRecentReceivers', $args);

            $this->setMessage('success_deleted');
        }

        /**
         * @brief delete selected logs.
         **/
        function procMobilemessageAdminDeleteLog() {
            $target_mobilemessage_srls = Context::get('target_mobilemessage_srls');
            if(!$target_mobilemessage_srls) return new Object(-1, 'msg_invalid_request');
            $mobilemessage_srls = explode(',', $target_mobilemessage_srls);
            $oMobilemessageController = &getController('mobilemessage');

            foreach($mobilemessage_srls as $mobilemessage) {
                $output = $oMobilemessageController->deleteMessage($mobilemessage);
                if(!$output->toBool()) {
                    $this->setMessage('failed_deleted');
                    return $output;
                }
            }

            $this->setMessage('success_deleted');
        }

        /**
         * @brief delete selected groups.
         **/
        function procMobilemessageAdminDeleteGroup() {
            $target_group_ids = Context::get('target_group_ids');
            if(!$target_group_ids) return new Object(-1, 'msg_invalid_request');
            $group_ids = explode(',', $target_group_ids);
            $oMobilemessageController = &getController('mobilemessage');

            foreach($group_ids as $gid) {
                $output = $oMobilemessageController->deleteGroupMessage($gid);
                if(!$output->toBool()) {
                    $this->setMessage('failed_deleted');
                    return $output;
                }
            }

            $this->setMessage('success_deleted');
        }

        /**
         * @brief delete selected mapping-data.
         **/
        function procMobilemessageAdminDeleteMapping() {
            $target_user_ids = Context::get('target_user_ids');
            if(!$target_user_ids) return new Object(-1, 'msg_invalid_request');
            $user_ids = explode(',', $target_user_ids);
            $oController = &getController('mobilemessage');

            foreach($user_ids as $uid) {
                $output = $oController->deleteMapping($uid);
                if(!$output->toBool()) {
                    $this->setMessage('failed_deleted');
                    return $output;
                }
            }

            $this->setMessage('success_deleted');
        }


        /**
         * @brief cancel selected messages.
         **/
        function procMobilemessageAdminCancel() {
            $target_mobilemessage_srls = Context::get('target_mobilemessage_srls');
            if(!$target_mobilemessage_srls) return new Object(-1, 'msg_invalid_request');
            $mobilemessage_srls = explode(',', $target_mobilemessage_srls);
            $oMobilemessageController = &getController('mobilemessage');

            $query_id = 'mobilemessage.getMobilemessages';
            unset($args);
            $args->mobilemessage_srl = $target_mobilemessage_srls;
            $output = executeQueryArray($query_id, $args);
            if(!$output->toBool()) {
                $this->setMessage('cancel_failed');
                return $output;
            }

            $msgids = array();
            foreach($output->data as $key => $val) 
            {
                $msgids[] = $val->mid;
            }

            $output = $oMobilemessageController->cancelMessage($msgids);
            if(!$output->toBool()) {
                $this->setMessage('cancel_failed');
                return $output;
            }

            $this->setMessage('success_canceled');
        }

        /**
         * @brief cancel selected message groups.
         **/
        function procMobilemessageAdminCancelGroup() {
            $target_group_ids = Context::get('target_group_ids');
            if(!$target_group_ids) return new Object(-1, 'msg_invalid_request');
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
         * @brief getting member infos from mapping data.
         **/
        function procMobilemessageAdminSyncMapping() {
            $oController = &getController('mobilemessage');
            $oModel = &getModel('mobilemessage');
            $config = $oModel->getModuleConfig();

            /*
            $query_id = 'mobilemessage.getAllMembers';
            $output = executeQueryArray($query_id);
             */
            $oDB = &DB::getInstance();
            $db_info = Context::getDBInfo();
            if ($db_info->db_table_prefix) $db_table_prefix = $db_info->db_table_prefix;
            else $db_table_prefix = $db_info->master_db["db_table_prefix"];

            // delete mapping info
            $query = sprintf("DELETE FROM %smobilemessage_mapping", $db_table_prefix);
            $oDB->_query($query);

            // query for member info
            $query = sprintf("SELECT * FROM %smember", $db_table_prefix);
            $result = $oDB->_query($query);

            // fetcher
            if (version_compare(__ZBXE_VERSION__,'1.5.0')==-1) {
                require_once($this->module_path.'zMigration.class.php');
            } else {
                require_once($this->module_path.'zMigration15.class.php');
            }
            $dbtool = new zMigration();
            $dbtool->setDBInfo($db_info);

            $total_count=0;
            $empty_count=0;
            while ($row = $dbtool->fetch($result)) {
                $args = new stdClass();
                $extra_vars = unserialize($row->extra_vars);
                $args->user_id = $row->user_id;
                $args->phone_num = "";
                if ($row->{$config->cellphone_fieldname}) {
                    $args->phone_num = $row->{$config->cellphone_fieldname};
                } else if ($extra_vars->{$config->cellphone_fieldname}) {
                    $args->phone_num = $extra_vars->{$config->cellphone_fieldname};
                }
                if (is_array($args->phone_num)) $args->phone_num = implode($args->phone_num);
                $args->phone_num = str_replace('|@|', '', $args->phone_num);
                if ($args->phone_num == "") $empty_count++;
                $args->country = "";
                if (isset($row->{$config->countrycode_fieldname})) {
                    $args->country = $row->{$config->countrycode_fieldname};
                } else if (isset($extra_vars->{$config->countrycode_fieldname})) {
                    $args->country = $extra_vars->{$config->countrycode_fieldname};
                }
                $total_count++;
                if (!$args->country) $args->country = '82';
                //$oController->insertMapping($args);
                $regdate = date('YmdHis');
                $query = sprintf("INSERT INTO %smobilemessage_mapping (user_id, phone_num, country, regdate)"
                    ." VALUES ('{$args->user_id}', '{$args->phone_num}', '{$args->country}', '{$regdate}')"
                    , $db_table_prefix);
                $dbtool->query($query);
                unset($query);
                unset($args);
                unset($extra_vars);
                unset($row);
            }
            $this->setMessage($total_count . '개를 가져왔습니다. [빈 폰번호: ' . $empty_count . '개]');
        }

        /**
         * @brief insert prihibitted phone number.
         **/
        function procMobilemessageAdminProhibitInsert() {
            $oController = &getController('mobilemessage');
            $args = Context::gets('phone_num', 'limit_date', 'memo');
            return $oController->insertProhibit($args);
        }

        /**
         * @brief delete selected prohibition data.
         **/
        function procMobilemessageAdminProhibitDelete() {
            $target_phone_nums = Context::get('target_phone_nums');
            if(!$target_phone_nums) return new Object(-1, 'msg_invalid_request');
            $phone_nums = explode(',', $target_phone_nums);
            $oController = &getController('mobilemessage');

            foreach($phone_nums as $phonenum) {
                $output = $oController->deleteProhibit($phonenum);
                if(!$output->toBool()) {
                    $this->setMessage('failed_deleted');
                    return $output;
                }
            }

            $this->setMessage('success_deleted');
        }

        /**
         * @brief notification append
         **/
        function procMobilemessageAdminNotiDocAppend() {
            $params = Context::gets('content', 'msgtype', 'module_srls', 'manager', 'registrant', 'replier', 'admin_reply', 'id_list', 'except_id_list', 'group_srl_list', 'direct_numbers', 'callback_number_type', 'callback_number_direct', 'nonmember_index');
            $extra_vars = Context::gets('time_use', 'ft_hour', 'ft_min', 'tt_hour', 'tt_min', 'week_use', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun');

            $params->notification_srl = Context::get('notification_srl');

            if ($params->notification_srl) {
                // delete existences
                $args->notification_srl = $params->notification_srl;
                $query_id = "mobilemessage.deleteNotiDoc";
                executeQuery($query_id, $args);
                $query_id = "mobilemessage.deleteNotificationModuleSrl";
                executeQuery($query_id, $args);
            } else {
                // new sequence
                $params->notification_srl = getNextSequence();
            }

            // insert module srls
            $query_id = 'mobilemessage.insertNotificationModuleSrl';
            $module_srls = explode(',', $params->module_srls);
            foreach ($module_srls as $srl) {
                unset($args);
                $args->notification_srl = $params->notification_srl;
                $args->module_srl = $srl;
                executeQuery($query_id, $args);
            }

            $params->extra_vars = serialize($extra_vars);
            // insert notification
            $query_id = "mobilemessage.insertNotiDoc";
            return executeQuery($query_id, $params);
        }

        function procMobilemessageAdminNotiDocDelete() {
            $notification_srl = Context::get('notification_srl');
            if (!$notification_srl) return new Object(-1, 'msg_invalid_request');

            if ($notification_srl) {
                // delete existences
                $args->notification_srl = $notification_srl;
                $query_id = "mobilemessage.deleteNotiDoc";
                executeQuery($query_id, $args);
                $query_id = "mobilemessage.deleteNotificationModuleSrl";
                executeQuery($query_id, $args);
            }
        }

        /**
         * @brief notification append
         **/
        function procMobilemessageAdminNotiComAppend() {
            $params = Context::gets('content', 'module_srls', 'msgtype', 'registrant', 'replier', 'nonmember_index', 'id_list', 'except_id_list', 'group_srl_list', 'manager', 'direct_numbers', 'callback_number_type', 'callback_number_direct', 'message_link', 'reverse_notify');
            $extra_vars = Context::gets('time_use', 'ft_hour', 'ft_min', 'tt_hour', 'tt_min', 'week_use', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun', 'limit_hours');
            $params->notification_srl = Context::get('notification_srl');

            if ($params->notification_srl) {
                // delete existences
                $args->notification_srl = $params->notification_srl;
                $query_id = "mobilemessage.deleteNotiCom";
                executeQuery($query_id, $args);
                $query_id = "mobilemessage.deleteNotificationModuleSrl";
                executeQuery($query_id, $args);
            } else {
                // new sequence
                $params->notification_srl = getNextSequence();
            }

            // insert module srls
            $query_id = 'mobilemessage.insertNotificationModuleSrl';
            $module_srls = explode(',', $params->module_srls);
            foreach ($module_srls as $srl) {
                unset($args);
                $args->notification_srl = $params->notification_srl;
                $args->module_srl = $srl;
                executeQuery($query_id, $args);
            }

            $params->extra_vars = serialize($extra_vars);
            // insert notification
            $query_id = "mobilemessage.insertNotiCom";
            return executeQuery($query_id, $params);
        }

        function procMobilemessageAdminNotiComDelete() {
            $notification_srl = Context::get('notification_srl');
            if (!$notification_srl) return new Object(-1, 'msg_invalid_request');

            if ($notification_srl) {
                // delete existences
                $args->notification_srl = $notification_srl;
                $query_id = "mobilemessage.deleteNotiCom";
                executeQuery($query_id, $args);
                $query_id = "mobilemessage.deleteNotificationModuleSrl";
                executeQuery($query_id, $args);
            }
        }

        function updateSubnodeCount() {
            // extract all subfolder
            $args->node_type = '1';
            $output = executeQueryArray('mobilemessage.getPurplebookAllSubnode', $args);
            if (!$output->toBool()) return new Object(-1, 'msg_invalid_request');
            $count=0;
            foreach ($output->data as $no => $val) {
                // get subfolder count
                unset($args);
                $args->node_route = $val->node_route . $val->node_id . '.';
                $args->node_id = $val->node_id;
                $out1 = executeQuery('mobilemessage.getSubfolder', $args);
                if (!$out1->toBool()) return new Object(-1, 'msg_error_occured');
                $subfolder = $out1->data->subfolder;

                // get subnode count
                unset($args);
                $args->node_route = $val->node_route . $val->node_id . '.';
                $out2 = executeQuery('mobilemessage.getSubnode', $args);
                if (!$out2->toBool()) return new Object(-1, 'msg_error_occured');
                $subnode = $out2->data->subnode;

                // update subfolder count
                unset($args);
                $args->node_id = $val->node_id;
                $args->subfolder = $subfolder;
                $out = executeQuery('mobilemessage.updateSubfolder', $args);
                if (!$out->toBool()) return new Object(-1, 'msg_error_occured');

                // update subnode count
                unset($args);
                $args->node_id = $val->node_id;
                $args->subnode = $subnode;
                $out = executeQuery('mobilemessage.updateSubnode', $args);
                if (!$out->toBool()) return new Object(-1, 'msg_error_occured');

                $count++;
            }
            return $count;
        }

        function updateMemberSrl() {
            $oMemberModel = &getModel('member');

            $output = executeQueryArray('mobilemessage.getAllNodes');
            if (!$output->toBool()) return $output;

            $count=0;
            if ($output->data) {
                foreach ($output->data as $no => $val) {
                    $node_id = $val->node_id;
                    $user_id = $val->user_id;
                    $member_info = $oMemberModel->getMemberInfoByUserId($user_id);       
                    if ($member_info) {
                        $args->node_id = $node_id;
                        $args->member_srl = $member_info->member_srl;
                        executeQuery('mobilemessage.updateNodeMemberSrl', $args);
                        unset($member_info);
                    }
                    $count++;
                }
            }
            return $count;
        }

        function updateNodeRoute() {
            $oMemberModel = &getModel('member');

            $output = executeQueryArray('mobilemessage.getAllNodes');
            if (!$output->toBool()) return $output;

            $count=0;
            if ($output->data) {
                foreach ($output->data as $no => $val) {
                    $node_id = $val->node_id;
                    $node_route = $val->node_route;
                    if (!in_array(substr($node_route,0,2),array('f.','s.','t.')) && substr($node_route,0,1)=='.') {
                        $node_route = 'f' . $node_route;
                        $args->node_id = $node_id;
                        $args->node_route = $node_route;
                        executeQuery('mobilemessage.updateNodeRoute', $args);
                        $count++;
                    }
                }
            }
            return $count;
        }

        function procMobilemessageAdminUpdateSubnodeCount() {
            $count1 = $this->updateMemberSrl();
            $this->add('update1_count', $count1);
            $count2 = $this->updateSubnodeCount();
            $this->add('update2_count', $count2);
            $count3 = $this->updateNodeRoute();
            $this->add('update3_count', $count3);
        }
    }
?>
