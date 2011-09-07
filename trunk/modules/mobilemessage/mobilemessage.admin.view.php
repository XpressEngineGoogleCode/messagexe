<?php
    /**
     * vi:set sw=4 ts=4 expandtab enc=utf8:
     * @class  mobilemessageAdminView
     * @author diver(diver@coolsms.co.kr)
     * @brief  mobilemessageAdminView
     */ 
    class mobilemessageAdminView extends mobilemessage {
        var $group_list;

        function init() {
            // 멤버모델 객체
            $oMemberModel = &getModel('member');

            // group 목록 가져오기
            $this->group_list = $oMemberModel->getGroups();
            Context::set('group_list', $this->group_list);

            // 템플릿 설정
            $this->setTemplatePath($this->module_path.'tpl');

            // XE 버젼 비교를 위한 Integer값 얻어오기
            Context::set('current_version_int', $this->getXEVerInt());
            Context::set('version_115', $this->getVerInt("1.1.5"));
        }

        /**
         * 기본설정
         */
        function dispMobilemessageAdminConfig() {
            $oMobilemessageModel = &getModel('mobilemessage');
            $oMemberModel = &getModel('member');

            if ($this->getXEVerInt() < $this->getVerInt('1.4.0')) {
                $js_code = 'target_type_list["callback"] = "tel";';
                $js_code .= 'target_type_list["admin_phone"] = "tel";';
                $js_code .= 'target_type_list["callback_number_direct"] = "tel";';
                $js_code = "<script type=\"text/javascript\">//<![CDATA[\n".$js_code."\n//]]></script>";
                Context::addHtmlHeader($js_code);
            }

            $config = $oMobilemessageModel->getConfig();

            // load member list
            $query_id = "mobilemessage.getNotificationMembers";
            $id_list = explode(',', $config->id_list);
            $id_list = "'" . join("','", $id_list) . "'";
            if ($id_list) {
                $args->user_ids = $id_list;
                $output = executeQueryArray($query_id, $args);
                Context::set('member_list', $output->data);
            } else {
                Context::set('member_list', array());
            }

            if ($this->getXEVerInt() > $this->getVerInt("1.1.5"))
                $callback_url = Context::getDefaultUrl();
            else
                $callback_url = Context::getRequestUri();

            //$callback_url .= "?module=mobilemessage&act=procMobilemessageUpdateResult";

            $callback_url_style = "";
            if ($config->callback_url)
                $callback_url = $config->callback_url;
            else
                $callback_url_style = 'style="color:red;"';

            // callback_number_direct
            $config->callback_number_direct = explode('|@|', $config->callback_number_direct);

            Context::set('callback_url', $callback_url);
            Context::set('callback_url_style', $callback_url_style);
            Context::set('mobilemessage_config', $config);

            $group_list = $oMemberModel->getGroups(0);
            Context::set('group_list', $group_list);

            $group_srl_list = explode(',', $config->group_srl_list);
            Context::set('group_srl_list', $group_srl_list);

            $change_group_srl_list = explode(',', $config->change_group_srl_list);
            Context::set('change_group_srl_list', $change_group_srl_list);
            Context::set('sln_reg_key', $oMobilemessageModel->getSlnRegKey());

            // 템플릿 파일 지정
            $this->setTemplateFile('config');
        }

        /**
         * 회원목록 보기
         */
        function dispMobilemessageAdminList() {
            $oMobilemessageModel = &getModel('mobilemessage');
            $config = $oMobilemessageModel->getConfig();

            if (!$config->cs_userid || !$config->cs_passwd)
                Context::set('cs_notuserset', true);
            Context::set('callback', $config->s_callback);

            $oController = &getController('mobilemessage');
            $ticket = $oController->getTicket();
            Context::set('ticket', $ticket);
            Context::set('sln_reg_key', $oMobilemessageModel->getSlnRegKey());

            // 템플릿 파일 지정
            $this->setTemplateFile('sms');
        }

        /**
         * @brief 매핑정보 목록보기
         **/
        function dispMobilemessageAdminMappingList() {
            $search_target = trim(Context::get('search_target'));
            $search_keyword = trim(Context::get('search_keyword'));

            $args = new StdClass();
            if($search_target && $search_keyword) {
                switch($search_target) {
                    case 'user_id' :
                            if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
                            $args->user_id = $search_keyword;
                        break;
                    case 'phone_num' :
                            if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
                            $args->phone_num = $search_keyword;
                        break;
                }
            }


            $oAdminModel = &getAdminModel('mobilemessage');
            $output = $oAdminModel->getMappingList($args);

            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('mapping_list', $output->data);
            Context::set('page_navigation', $output->page_navigation);

            $oMobilemessageModel = &getModel('mobilemessage');
            $config = $oMobilemessageModel->getModuleConfig();

            require_once('mobilemessage.utility.php');
            $csutil = new CSUtility();
            Context::set('csutil', $csutil);
            Context::set('config', $config);

            $this->setTemplateFile('mapping_list');
        }

        /**
         * @brief list of prohibited phone numbers.
         **/
        function dispMobilemessageAdminProhibit() {
            $search_target = trim(Context::get('search_target'));
            $search_keyword = trim(Context::get('search_keyword'));

            $args = new StdClass();
            if($search_target && $search_keyword) {
                switch($search_target) {
                    case 'phone_num' :
                            if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
                            $args->phone_num = $search_keyword;
                        break;
                    case 'limit_date_more' :
                            $args->limit_date_more = substr(ereg_replace("[^0-9]","",$search_keyword) . '00000000000000',0,14);
                        break;
                    case 'limit_date_less' :
                            $args->limit_date_less = substr(ereg_replace("[^0-9]","",$search_keyword) . '00000000000000',0,14);
                        break;
                }
            }

            $oAdminModel = &getAdminModel('mobilemessage');
            $output = $oAdminModel->getProhibitList($args);

            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('prohibit_list', $output->data);
            Context::set('page_navigation', $output->page_navigation);

            $oMobilemessageModel = &getModel('mobilemessage');
            $config = $oMobilemessageModel->getModuleConfig();

            require_once('mobilemessage.utility.php');
            $csutil = new CSUtility();
            Context::set('csutil', $csutil);
            Context::set('config', $config);

            $this->setTemplateFile('prohibit_list');
        }

        /**
         * @brief display prohibition form.
         **/
        function dispMobilemessageAdminProhibitAppend() {
            $this->setTemplateFile('prohibit_append');
        }

        /**
         * @brief dispMobilemesageAdminNotiDoc
         **/
        function dispMobilemessageAdminNotiDocList() {
            $notification_list = array();

            // notification list
            $args->page = Context::get('page');
            $output = executeQueryArray('mobilemessage.getNotiDocList', $args);
            if ($output->toBool() && $output->data) {
                foreach ($output->data as $no => $val) {
                    $val->no = $no;
                    $val->module_info = array();
                    $notification_list[$val->notification_srl] = $val;
                }
            }
            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('page_navigation', $output->page_navigation);

            // module infos
            if (count($notification_list) > 0) {
                $notification_srls = array_keys($notification_list);
                $notification_srls = join(',', $notification_srls);

                $query_id = "mobilemessage.getModuleInfoByNotificationSrl";
                $args->notification_srls = $notification_srls;
                $output = executeQueryArray($query_id, $args);
                if ($output->data) {
                    foreach ($output->data as $no => $val) {
                        $notification_list[$val->notification_srl]->module_info[] = $val;
                    }
                }
            }

            // group infos
            $oMemberModel = &getModel('member');
            $group_list = $oMemberModel->getGroups(0);

            foreach ($notification_list as $key => $noti) {
                $group_srl_list = explode(',', $noti->group_srl_list);
                $selected_group_list = array();
                foreach ($group_list as $group) {
                    if (in_array($group->group_srl, $group_srl_list))
                        $selected_group_list[] = $group;
                }
                $notification_list[$key]->selected_group_list = $selected_group_list;
            }

            Context::set('notification_list', $notification_list);

            $this->setTemplateFile('notidoc_list');
        }

        /**
         * @brief dispMobilemesageAdminNotiDocAppend
         **/
        function dispMobilemessageAdminNotiDocAppend() {
            // 그룹 목록을 가져옴
            $oMemberModel = &getModel('member');
            $group_list = $oMemberModel->getGroups(0);
            Context::set('group_list', $group_list);
            Context::set('group_srl_list', array());

            if ($this->getXEVerInt() < $this->getVerInt('1.4.0')) {
                $js_code = 'target_type_list["callback_number_direct"] = "tel";';
                $js_code = "<script type=\"text/javascript\">//<![CDATA[\n".$js_code."\n//]]></script>";
                Context::addHtmlHeader($js_code);
            }

            $this->setTemplateFile('notidoc_append');
        }

        /**
         * @brief dispMobilemesageAdminNotiDocModify
         **/
        function dispMobilemessageAdminNotiDocModify() {
            // load notification info
            $query_id = "mobilemessage.getNotiDocInfo";
            $args->notification_srl = Context::get('notification_srl');
            $output = executeQuery($query_id, $args);
            $id_list = $output->data->id_list;
            $except_id_list = $output->data->except_id_list;
            $group_srl_list = $output->data->group_srl_list;
            $notification_info = $output->data;
            $extra_vars = unserialize($notification_info->extra_vars);
            if ($extra_vars) {
                foreach ($extra_vars as $key => $val) {
                    $notification_info->{$key} = $val;
                }
            }

            // load module srls
            $args->notification_srl = Context::get('notification_srl');
            $output = executeQueryArray('mobilemessage.getNotificationModuleSrls', $args);
            $module_srls = array();
            if ($output->toBool() && $output->data) {
                foreach ($output->data as $no => $val) {
                    $module_srls[] = $val->module_srl;
                }
            }
            $notification_info->module_srls = join(',', $module_srls);

            // id_list
            $id_list = explode(',', $id_list);
            $id_list = "'" . join("','", $id_list) . "'";
            if ($id_list) {
                $args->user_ids = $id_list;
                $output = executeQueryArray('mobilemessage.getNotificationMembers', $args);
                Context::set('member_list', $output->data);
            } else {
                Context::set('member_list', array());
            }

            // except_id_list
            $except_id_list = explode(',', $except_id_list);
            $except_id_list = "'" . join("','", $except_id_list) . "'";
            if ($except_id_list) {
                $args->user_ids = $except_id_list;
                $output = executeQueryArray('mobilemessage.getNotificationMembers', $args);
                Context::set('except_member_list', $output->data);
            } else {
                Context::set('except_member_list', array());
            }

            // group srl
            $group_srl_list = explode(',', $group_srl_list);
            Context::set('group_srl_list', $group_srl_list);

            if ($this->getXEVerInt() < $this->getVerInt('1.4.0')) {
                $js_code = 'target_type_list["callback_number_direct"] = "tel";';
                $js_code = "<script type=\"text/javascript\">//<![CDATA[\n".$js_code."\n//]]></script>";
                Context::addHtmlHeader($js_code);
            }

            $notification_info->callback_number_direct = explode('|@|', $notification_info->callback_number_direct);
            $notification_info->direct_numbers = explode('|@|', $notification_info->direct_numbers);
            Context::set('notification_info', $notification_info);
            $this->setTemplateFile('notidoc_append');
        }

        /**
         * @brief dispMobilemesageAdminNotiCom
         **/
        function dispMobilemessageAdminNotiComList() {
            $notification_list = array();
            $args->page = Context::get('page');
            $output = executeQueryArray('mobilemessage.getNotiComList', $args);
            if ($output->toBool() && $output->data) {
                foreach ($output->data as $no => $val) {
                    $val->no = $no;
                    $val->module_info = array();
                    $notification_list[$val->notification_srl] = $val;
                }
            }
            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('page_navigation', $output->page_navigation);


            // module infos
            if (count($notification_list) > 0) {
                $notification_srls = array_keys($notification_list);
                $notification_srls = join(',', $notification_srls);

                $query_id = "mobilemessage.getModuleInfoByNotificationSrl";
                $args->notification_srls = $notification_srls;
                $output = executeQueryArray($query_id, $args);
                if ($output->data) {
                    foreach ($output->data as $no => $val) {
                        $notification_list[$val->notification_srl]->module_info[] = $val;
                    }
                }
            }

            // group infos
            $oMemberModel = &getModel('member');
            $group_list = $oMemberModel->getGroups(0);

            foreach ($notification_list as $key => $noti) {
                $group_srl_list = explode(',', $noti->group_srl_list);
                $selected_group_list = array();
                foreach ($group_list as $group) {
                    if (in_array($group->group_srl, $group_srl_list))
                        $selected_group_list[] = $group;
                }
                $notification_list[$key]->selected_group_list = $selected_group_list;
            }

            Context::set('notification_list', $notification_list);

            $this->setTemplateFile('noticom_list');
        }

        /**
         * @brief dispMobilemesageAdminNotiComAppend
         **/
        function dispMobilemessageAdminNotiComAppend() {
            // 그룹 목록을 가져옴
            $oMemberModel = &getModel('member');
            $group_list = $oMemberModel->getGroups(0);
            Context::set('group_list', $group_list);
            Context::set('group_srl_list', array());

            $this->setTemplateFile('noticom_append');
        }

        /**
         * @brief dispMobilemesageAdminNotiComModify
         **/
        function dispMobilemessageAdminNotiComModify() {
            // load notification info
            $query_id = "mobilemessage.getNotiComInfo";
            $args->notification_srl = Context::get('notification_srl');
            $output = executeQuery($query_id, $args);
            $id_list = $output->data->id_list;
            $except_id_list = $output->data->except_id_list;
            $group_srl_list = $output->data->group_srl_list;
            $notification_info = $output->data;
            $extra_vars = unserialize($notification_info->extra_vars);
            if ($extra_vars) {
                foreach ($extra_vars as $key => $val) {
                    $notification_info->{$key} = $val;
                }
            }

            // load module srls
            $query_id = "mobilemessage.getNotificationModuleSrls";
            $args->notification_srl = Context::get('notification_srl');
            $output = executeQueryArray($query_id, $args);
            $module_srls = array();
            if ($output->toBool() && $output->data) {
                foreach ($output->data as $no => $val) {
                    $module_srls[] = $val->module_srl;
                }
            }
            $notification_info->module_srls = join(',', $module_srls);

            // id_list
            $id_list = explode(',', $id_list);
            $id_list = "'" . join("','", $id_list) . "'";
            if ($id_list) {
                unset($args);
                $args->user_ids = $id_list;
                $output = executeQueryArray('mobilemessage.getNotificationMembers', $args);
                Context::set('member_list', $output->data);
            } else {
                Context::set('member_list', array());
            }

            // except_id_list
            $except_id_list = explode(',', $except_id_list);
            $except_id_list = "'" . join("','", $except_id_list) . "'";
            if ($except_id_list) {
                unset($args);
                $args->user_ids = $except_id_list;
                $output = executeQueryArray('mobilemessage.getNotificationMembers', $args);
                Context::set('except_member_list', $output->data);
            } else {
                Context::set('except_member_list', array());
            }

            // group srl
            $group_srl_list = explode(',', $group_srl_list);
            Context::set('group_srl_list', $group_srl_list);

            if ($this->getXEVerInt() < $this->getVerInt('1.4.0')) {
                $js_code = 'target_type_list["callback_number_direct"] = "tel";';
                $js_code = "<script type=\"text/javascript\">//<![CDATA[\n".$js_code."\n//]]></script>";
                Context::addHtmlHeader($js_code);
            }

            $notification_info->callback_number_direct = explode('|@|', $notification_info->callback_number_direct);
            $notification_info->direct_numbers = explode('|@|', $notification_info->direct_numbers);
            Context::set('notification_info', $notification_info);
            $this->setTemplateFile('noticom_append');
        }


        /**
         * 발송내역 보기
         */
        function dispMobilemessageAdminLogView() {
            // mobilemessage model 객체 생성후 목록을 구해옴
            $oMobilemessageModel = &getModel('mobilemessage');
            $config = $oMobilemessageModel->getModuleConfig();

            if (Context::get('gid'))
            {
                $args->gid = Context::get('gid');
                $output = $oMobilemessageModel->getMessagesInGroup($args);
                $this->setTemplateFile('message_list');
            }
            else
            {
                $args = new StdClass();
                $output = $oMobilemessageModel->getMessagesGrouping($args);
                $this->setTemplateFile('message_grouping');
            }


            // 템플릿에 쓰기 위해서 context::set
            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('message_list', $output->data);
            Context::set('page_navigation', $output->page_navigation);

            require_once('mobilemessage.utility.php');
            $csutil = new CSUtility();
            Context::set('csutil', $csutil);
            Context::set('config', $config);

        }


        function dispMobilemessageAdminDeleteLog() {
            $args->mobilemessage_srl = trim(Context::get('mobilemessage_srls'));
            $output = executeQueryArray('mobilemessage.getMobilemessages', $args);
            Context::set('mobilemessage_list', $output->data);

            require_once('mobilemessage.utility.php');
            $csutil = new CSUtility();
            Context::set('csutil', $csutil);

            $this->setLayoutFile('popup_layout');
            $this->setTemplateFile('delete_log');
        }

        function dispMobilemessageAdminDeleteGroup() {
            $args->group_ids = "'" . implode("','", explode(',', trim(Context::get('group_ids')))) . "'";
            $output = executeQueryArray('mobilemessage.getGroups', $args);
            Context::set('group_list', $output->data);

            require_once('mobilemessage.utility.php');
            $csutil = new CSUtility();
            Context::set('csutil', $csutil);

            $this->setLayoutFile('popup_layout');
            $this->setTemplateFile('delete_group');
        }

        /**
         * @brief 맵핑목록 삭제
         **/
        function dispMobilemessageAdminDeleteMapping() {
            $args = new StdClass();
            $args->user_ids = "'" . implode("','", explode(',', trim(Context::get('user_ids')))) . "'";
            $output = executeQueryArray('mobilemessage.getMappingList', $args);
            Context::set('mapping_list', $output->data);

            $this->setLayoutFile('popup_layout');
            $this->setTemplateFile('delete_mapping');
        }

        /**
         * @brief deleting prohibition.
         **/
        function dispMobilemessageAdminProhibitDelete() {
            $args = new StdClass();
            $args->phone_nums = "'" . implode("','", explode(',', trim(Context::get('phonenums')))) . "'";
            $output = executeQueryArray('mobilemessage.getProhibits', $args);
            Context::set('prohibit_list', $output->data);

            $this->setLayoutFile('popup_layout');
            $this->setTemplateFile('delete_prohibits');
        }

        function dispMobilemessageAdminCancel() {
            $args->mobilemessage_srl = trim(Context::get('mobilemessage_srls'));
            $output = executeQueryArray('mobilemessage.getMobilemessages', $args);
            Context::set('mobilemessage_list', $output->data);

            require_once('mobilemessage.utility.php');
            $csutil = new CSUtility();
            Context::set('csutil', $csutil);

            $this->setLayoutFile('popup_layout');
            $this->setTemplateFile('cancel');
        }
        function dispMobilemessageAdminCancelGroup() {
            $args->group_ids = "'" . implode("','", explode(',', trim(Context::get('group_ids')))) . "'";
            $output = executeQueryArray('mobilemessage.getGroups', $args);
            Context::set('group_list', $output->data);

            require_once('mobilemessage.utility.php');
            $csutil = new CSUtility();
            Context::set('csutil', $csutil);

            $this->setLayoutFile('popup_layout');
            $this->setTemplateFile('cancel_group');
        }

        /**
         * @brief deleting notidoc.
         **/
        function dispMobilemessageAdminNotiDocDelete() {
            // load notification info
            $query_id = "mobilemessage.getNotiDocInfo";
            $args->notification_srl = Context::get('notification_srl');
            $output = executeQuery($query_id, $args);
            $id_list = $output->data->id_list;
            $group_srl_list = $output->data->group_srl_list;
            $notification_info = $output->data;

            $query_id = "mobilemessage.getModuleInfoByNotificationSrl";
            $args->notification_srls = Context::get('notification_srl');
            $output = executeQueryArray($query_id, $args);
            $mid_list = array();
            if ($output->data) {
                foreach ($output->data as $no => $val) {
                    $mid_list[] = $val->mid;
                }
            }
            $notification_info->mid_list = join(',', $mid_list);

            Context::set('notification_info', $notification_info);

            $this->setTemplateFile('notidoc_delete');
        }

        /**
         * @brief deleting notidoc.
         **/
        function dispMobilemessageAdminNotiComDelete() {
            // load notification info
            $query_id = "mobilemessage.getNotiComInfo";
            $args->notification_srl = Context::get('notification_srl');
            $output = executeQuery($query_id, $args);
            $id_list = $output->data->id_list;
            $group_srl_list = $output->data->group_srl_list;
            $notification_info = $output->data;

            $query_id = "mobilemessage.getModuleInfoByNotificationSrl";
            $args->notification_srls = Context::get('notification_srl');
            $output = executeQueryArray($query_id, $args);
            $mid_list = array();
            if ($output->data) {
                foreach ($output->data as $no => $val) {
                    $mid_list[] = $val->mid;
                }
            }
            $notification_info->mid_list = join(',', $mid_list);

            Context::set('notification_info', $notification_info);


            $this->setTemplateFile('noticom_delete');
        }

        /**
         * uploaded files management
         */
        function dispMobilemessageAdminFiles() {
            $args->page = Context::get('page');
            $output = executeQueryArray('mobilemessage.getMobilemessageFiles', $args);
            if (!$output->toBool()) return $output;

            // 템플릿에 쓰기 위해서 context::set
            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('file_list', $output->data);
            Context::set('page_navigation', $output->page_navigation);

/*
            require_once('mobilemessage.utility.php');
            $csutil = new CSUtility();
            Context::set('csutil', $csutil);
*/

            $this->setTemplateFile('files');
        }
        /**
         * recent messages management
         */
        function dispMobilemessageAdminRecentMessages() {
            $args->page = Context::get('page');
            $output = executeQueryArray('mobilemessage.getRecentMessages', $args);
            if (!$output->toBool()) return $output;

            // 템플릿에 쓰기 위해서 context::set
            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('file_list', $output->data);
            Context::set('page_navigation', $output->page_navigation);

            $this->setTemplateFile('recent_messages');
        }

        /**
         * recent receivers management
         */
        function dispMobilemessageAdminRecentReceivers() {
            $args->page = Context::get('page');
            $output = executeQueryArray('mobilemessage.getAdminRecentReceivers', $args);
            if (!$output->toBool()) return $output;

            // 템플릿에 쓰기 위해서 context::set
            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('file_list', $output->data);
            Context::set('page_navigation', $output->page_navigation);

            $this->setTemplateFile('recent_receivers');
        }

    }
?>
