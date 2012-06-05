<?php
    /**
     * @class  purplebookView
     * @author wiley(wiley@nurigo.net)
     * @brief  purplebookView
     */
    class purplebookView extends purplebook {
        var $use_point;
        var $sms_point;
        var $lms_point;
        var $alert_message="";

        function init() {
            // 템플릿 경로 설정
			if (!$this->module_info->skin) $this->module_info->skin = 'default';
            $this->setTemplatePath($this->module_path."skins/{$this->module_info->skin}");
        }

        function dispPurplebookIndex()
        {
            $this->setTemplateFile('address');
        }


        /**
         * @brief System Point 가져오기 - Content-Type: JSON
         * 2011/01/10 moved to getPurplebookPointInfo
         **/
        function dispPurplebookGetPointInfo() {
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
        function dispPurplebookMemberMessageGroupList() {
            // purplebook model 객체 생성후 목록을 구해옴
            $oPurplebookModel = &getModel('purplebook');

            $logged_info = Context::get('logged_info');
            if (!$logged_info)
                return new Object(-1, 'msg_not_logged');

            $args->userid = $logged_info->user_id;
            $args->startdate = Context::get('startdate');
            $args->enddate = Context::get('enddate');
            $output = $oPurplebookModel->getMessagesGrouping($args);
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
        function dispPurplebookMemberMessageList() {
            // purplebook model 객체 생성후 목록을 구해옴
            $oPurplebookModel = &getModel('purplebook');

            $logged_info = Context::get('logged_info');
            if (!$logged_info)
                return new Object(-1, 'msg_not_logged');

            $args->gid = Context::get('gid');
            $output = $oPurplebookModel->getMessagesInGroup($args);
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
        function dispPurplebookMessageInfo() {
            // purplebook model 객체 생성후 목록을 구해옴
            $oPurplebookModel = &getModel('purplebook');

            $logged_info = Context::get('logged_info');
            if (!$logged_info)
                return new Object(-1, 'msg_not_logged');

            $args->msgid = Context::get('msgid');
            $output = $oPurplebookModel->getMessageInfo($args);
            $output->data->content = str_replace("\r", "", $output->data->content);
            $output->data->content = str_replace("\n", "<br>", $output->data->content);

            Context::set('data', $output->data);
        }

        function dispPurplebookExcelDownload() {
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
            require_once('purplebook.utility.php');

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
                $query_id = 'purplebook.getMembers';
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

        function dispPurplebookPurplebookDownload() {
            $logged_info = Context::get('logged_info');
            if (!$logged_info) return new Object(-1, 'msg_not_logged');

            header("Content-Type: Application/octet-stream;");
            header("Content-Disposition: attachment; filename=\"phonelist-" . date('Ymd') . ".xls\"");

            $node_id = Context::get('node_id');
            if ($node_id && !in_array($node_id, array('f.','s.','t.'))) {
                $args->node_id = $node_id;
                $output = executeQuery('purplebook.getNodeInfoByNodeId', $args);
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

            $oPurplebookModel = &getModel('purplebook');
            $output = executeQueryArray('purplebook.getPurplebookByNodeRoute', $args);

            require_once('purplebook.utility.php');
            $csutil = new CSUtility();
            Context::set('csutil', $csutil);
            Context::set('data', $output->data);

            $this->setLayoutFile('default_layout');
            $this->setTemplateFile('purplebook_download');
        }

        function dispPurplebookLogGroupDownload() {
            $logged_info = Context::get('logged_info');
            if (!$logged_info)
                return new Object(-1, 'msg_not_logged');

            header("Content-Type: Application/octet-stream;");
            header("Content-Disposition: attachment; filename=\"loggroup-" . date('Ymd') . ".xls\"");

            $args->list_count = 99999;
            $args->userid = $logged_info->user_id;
            $args->startdate = Context::get('startdate');
            $args->enddate = Context::get('enddate');

            $oPurplebookModel = &getModel('purplebook');
            $output = $oPurplebookModel->getMessagesGrouping($args);

            Context::set('data', $output->data);

            $this->setTemplateFile('loggroup_download');
        }

        function dispPurplebookLogListDownload() {
            $logged_info = Context::get('logged_info');
            if (!$logged_info)
                return new Object(-1, 'msg_not_logged');

            header("Content-Type: Application/octet-stream;");
            header("Content-Disposition: attachment; filename=\"loglist-" . date('Ymd') . ".xls\"");

            $args->list_count = 99999;
            $args->gid = Context::get('gid');

            $oPurplebookModel = &getModel('purplebook');
            $output = $oPurplebookModel->getMessagesInGroup($args);

            require_once('purplebook.utility.php');
            $csutil = new CSUtility();
            Context::set('csutil', $csutil);
            Context::set('data', $output->data);

            $this->setTemplateFile('loglist_download');
        }

        /**
         * @brief 선택된 로그 일괄 취소(그룹)
         **/
        function dispPurplebookCancelGroupMessages() {
            $target_group_ids = Context::get('target_group_ids');
            if(!$target_group_ids) 
                return new Object(-1, 'msg_invalid_request');
            $group_ids = explode(',', $target_group_ids);
            $oPurplebookController = &getController('purplebook');

            $output = $oPurplebookController->cancelGroupMessages($group_ids);
            if(!$output->toBool()) {
                $this->setMessage('cancel_failed');
                return $output;
            }

            $this->setMessage('success_canceled');
        }
        /**
         * @brief 선택된 로그 일괄 취소
         **/
        function dispPurplebookCancelMessages() {
            $target_msgids = Context::get('target_msgids');
            if(!$target_msgids) 
                return new Object(-1, 'msg_invalid_request');
            $msgids = explode(',', $target_msgids);
            $oPurplebookController = &getController('purplebook');

            $output = $oPurplebookController->cancelMessage($msgids);
            if(!$output->toBool()) {
                $this->setMessage('cancel_failed');
                return $output;
            }

            $this->setMessage('success_canceled');
        }

        /**
         * @brief 주소록 Node 추가
         **/
        function dispPurplebookPurplebookAddNode() {

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

            $oPurplebookController = &getController('purplebook');
            $oPurplebookController->insertPurplebook($args);

            Context::set('node_id', $args->node_id);
            Context::set('node_route', $args->node_route);
        }

        /**
         * @brief 주소록 Node 삭제
         **/
        function dispPurplebookPurplebookDeleteNode() {
            $logged_info = Context::get('logged_info');
            if (!$logged_info)
                return new Object(-1, 'msg_not_logged');

            $args->user_id = $logged_info->user_id;
            $args->node_id = Context::get('node_id');
            $args->node_route = Context::get('node_route') . $args->node_id . '.';

            $oPurplebookController = &getController('purplebook');
            $oPurplebookController->deletePurplebook($args);
        }

        /**
         * @brief 주소록 Node 삭제
         **/
        function dispPurplebookPurplebookDeleteList() {
            $logged_info = Context::get('logged_info');
            if (!$logged_info)
                return new Object(-1, 'msg_not_logged');

            $node_list = $this->getJSON('node_list');

            $oPurplebookController = &getController('purplebook');

            foreach ($node_list as $node_id) {
                unset($args);
                $args->user_id = $logged_info->user_id;
                $args->node_id = $node_id;

                $oPurplebookController->deletePurplebook($args);
            }
        }


        /**
         * @brief 주소록
         **/
        function dispPurplebookPurplebookRenameNode() {
            $args->node_id = Context::get('node_id');
            $args->node_name = Context::get('node_name');

            $oPurplebookController = &getController('purplebook');
            $oPurplebookController->updatePurplebook($args);
        }

        /**
         * @brief 주소록
         **/
        function dispPurplebookPurplebookUpdateRoute() {
            $data = $this->getJSON('json');

            $oPurplebookController = &getController('purplebook');

            $args = new StdClass();
            $args->node_route = Context::get('node_route');
            foreach ($data as $node_id)
            {
                $args->node_id = $node_id;
                $oPurplebookController->updatePurplebook($args);
            }
        }

        /**
         * @brief 주소록
         **/
        function dispPurplebookPurplebookCopy() {
            $data = $this->getJSON('json');

            $logged_info = Context::get('logged_info');
            if (!$logged_info)
                return new Object(-1, 'msg_not_logged');

           $oPurplebookController = &getController('purplebook');

            foreach ($data as $node_id) {
                unset($args);
                $args->user_id = $logged_info->user_id;
                $args->node_id = $node_id;
                
                $output = executeQuery('purplebook.getPurplebook', $args);

                if ($output->data) {
                    unset($args);
                    $args->node_id = getNextSequence();
                    $args->member_srl = $logged_info->member_srl;
                    $args->user_id = $logged_info->user_id;
                    $args->node_route = Context::get('node_route');
                    $args->node_name = $output->data->node_name;
                    $args->node_type = $output->data->node_type;
                    $args->phone_num = str_replace('-', '', $output->data->phone_num);

                    $oPurplebookController->insertPurplebook($args);
                }
            }
        }

        /**
         * @brief 주소록
         **/
        function dispPurplebookPurplebookRegister() {
            $data = $this->getJSON('data');

            $logged_info = Context::get('logged_info');
            if (!$logged_info)
                return new Object(-1, 'msg_not_logged');

            $oPurplebookController = &getController('purplebook');

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

                $oPurplebookController->insertPurplebook($args);
            }
            Context::set('return_data', $list);
        }

        /**
         * @brief 주소록
         **/
        function dispPurplebookPurplebookList() {
            $logged_info = Context::get('logged_info');
            if (!$logged_info)
                return new Object(-1, 'msg_not_logged');

            $args->user_id = $logged_info->user_id;
            $args->node_route = Context::get('node_route');
            $args->node_type = Context::get('node_type');

            $oPurplebookModel = &getModel('purplebook');
            $output = $oPurplebookModel->getPurplebookList($args);

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
        function dispPurplebookPurplebookListPaging() {
            $logged_info = Context::get('logged_info');
            if (!$logged_info)
                return new Object(-1, 'msg_not_logged');

            $args->user_id = $logged_info->user_id;
            $args->node_route = Context::get('node_route');
            $args->node_type = Context::get('node_type');
            $args->page = Context::get('page');

            $oPurplebookModel = &getModel('purplebook');
            $output = $oPurplebookModel->getPurplebookListPaging($args);

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
        function dispPurplebookSendAuthCode() {
            $userid = Context::get('userid');
            $phonenum = Context::get('phonenum');

            // 아이디 & 폰번호 검사
            $oModel = &getModel('purplebook');
            $extra_userid = $oModel->getUserIDsByPhoneNumber($phonenum); 
            if (!$extra_userid || !in_array($userid, $extra_userid))
                return new Object(-1, '해당 계정의 아이디와 폰번호가 일치하지 않습니다.');

            $oController = &getController('purplebook');

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
        function dispPurplebookSendUserID() {
            $phonenum = Context::get('phonenum');
            $oController = &getController('purplebook');
            $output = $oController->sendUserID($phonenum);
            if (!$output->toBool()) return $output;
        }

        /**
         * @brief 비밀번호 변경
         **/
        function dispPurplebookChangePassword() {
            $userid = Context::get('userid');
            $phonenum = Context::get('phonenum');
            $authcode = Context::get('authcode');
            $password = Context::get('password');

            $oModel = &getModel('purplebook');
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
            $trigger_output = ModuleHandler::triggerCall('purplebook.changeMemberPassword', 'after', $trigger_args);
            if(!$trigger_output->toBool()) return $trigger_output;
        }

        function dispPurplebookLatestNumbers() {
            $logged_info = Context::get('logged_info');
            if (!Context::get('is_logged') || !$logged_info) return new Object(-1, 'login_required');
            $args->user_id = $logged_info->user_id;
            $output = executeQueryArray('purplebook.getRecentReceivers', $args);
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
        function dispPurplebookLatestMessages() {
            $logged_info = Context::get('logged_info');
            if (!Context::get('is_logged') || !$logged_info) return new Object(-1, 'login_required');
            $args->user_id = $logged_info->user_id;
            $output = executeQueryArray('purplebook.getKeepingInfo', $args);
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

        function dispPurplebookFilePicker(){
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
