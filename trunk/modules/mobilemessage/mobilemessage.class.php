<?php
    /**
     * vi:set sw=4 ts=4 expandtab enc=utf8:
     * @class  mobilemessage
     * @author diver(diver@coolsms.co.kr)
     * @brief  mobilemessage
     */
    class mobilemessage extends ModuleObject {
        var $version;

        /**
         * @brief contructor
         **/
        function mobilemessage() {
            $oModuleModel = &getModel('module');
            $this->module_info = $oModuleModel->getModuleInfoXml($this->module);
            $this->version = $this->module_info->version;

            // Module Config
            /*
            $this->config = $oModuleModel->getModuleConfig('mobilemessage');
            if (!$this->config->default_country) $this->config->default_country = '82';
            if ($this->config->default_country == '82') $this->config->limit_bytes = 80;
            else $this->config->limit_bytes = 160;
            Context::set('base_url', $this->config->callback_url);
             */
        }

        /**
         * @brief 버젼 비교를 위해 major.minor.build 문자열버젼을 Integer로 변환
         **/
        function getVerInt($version_str) {
            $version = preg_split("/\./", $version_str);
            $major = intval($version[0]) * 10000;
            $minor = intval($version[1]) * 100;
            $build = intval($version[2]);

            // 1.2.4 버젼의 integer값은 10204가 된다.
            $version_int = $major + $minor + $build;

            return $version_int;
        }

        /**
         * @brief 설치된 XE 버젼 Integer값 구하기
         **/
        function getXEVerInt() {
            return $this->getVerInt(__ZBXE_VERSION__);
        }

        /**
         * @brief Object를 텍스트의 %...% 와 치환.
         **/
        function mergeKeywords($text, &$obj) {
            if (!is_object($obj)) return $text;

            foreach ($obj as $key => $val)
            {
                if (is_array($val)) $val = join($val);
                if (is_string($key) && is_string($val)) {
                    if (substr($key,0,10)=='extra_vars') $val = str_replace('|@|', '-', $val);
                    $text = preg_replace("/%" . preg_quote($key) . "%/", $val, $text);
                }
            }
            return $text;
        }

        function getJSON($name) {
            // 1.1.2 이전 버젼은 무조건 stripslashes 되어 넘어온다.
            // 1.1.2 버젼부터는 get_magic_quotes_gpc에 따라서 On이면 addslashes된 상태이고 Off이면 raw상태로 넘어온다.
            $oModel = &getModel('mobilemessage');
            $config = $oModel->getModuleConfig();
            if ($config->force_strip=='Y') {
                $json_string = stripslashes(Context::get($name));
            } else {
                if ($this->getXEVerInt() >= $this->getVerInt('1.1.2')) {
                    if (get_magic_quotes_gpc()) {
                        $json_string = stripslashes(Context::get($name));
                    } else {
                        $json_string = Context::get($name);
                    }
                } else {
                    $json_string = Context::get($name);
                }
            }

            require_once('JSON.php');
            $json = new Services_JSON();
            $decoded = $json->decode($json_string);

            return $decoded;
        }

        /**
         * @return post node
         **/
        function getPostNode($node_route) {
            $route_arr = preg_split('/\./', trim($node_route, '.'));
            $last = count($route_arr) - 1;
            if ($last < 0) return;
            return $route_arr[$last];
        }


        /**
         * @brief 1.1.X 버젼에 필요
         **/
        function insertActionForward() {
            $oModuleController = &getController('module');

            $oModuleController->insertActionForward('mobilemessage', 'view', 'dispMobilemessageAdminList');
            $oModuleController->insertActionForward('mobilemessage', 'view', 'dispMobilemessageAdminConfig');
            $oModuleController->insertActionForward('mobilemessage', 'view', 'dispMobilemessageAdminLogView');
            $oModuleController->insertActionForward('mobilemessage', 'view', 'dispMobilemessageAdminDeleteLog');
            $oModuleController->insertActionForward('mobilemessage', 'view', 'dispMobilemessageAdminMappingList');
            $oModuleController->insertActionForward('mobilemessage', 'view', 'dispMobilemessageAdminProhibit');
            $oModuleController->insertActionForward('mobilemessage', 'view', 'dispMobilemessageAdminProhibitAppend');
        }

        /**
         * @brief 모듈 설치 실행
         **/
        function moduleInstall() {
            $oModuleController = &getController('module');
            $oModuleModel = &getModel('module');

            // action forward에 등록 (관리자 모드에서 사용하기 위함)
            if ($this->getXEVerInt() <= $this->getVerInt("1.1.5"))
            {
                $this->insertActionForward();
            }

            // 회원가입시 SMS발송 트리거 추가. - 2009/08/14
            $oModuleController->insertTrigger('member.insertMember', 'mobilemessage', 'controller', 'triggerMemberJoin', 'after');

            // 회원탈퇴시 주소록 삭제 트리거 - 2009/11/05
            $oModuleController->insertTrigger('member.deleteMember', 'mobilemessage', 'controller', 'triggerMemberDelete', 'before');

            // 회원정보 수정시 트리거 - 2010/02/07
            $oModuleController->insertTrigger('member.updateMember', 'mobilemessage', 'controller', 'triggerMemberUpdate', 'after');

            // Document Registration Trigger - 2010/02/03
            $oModuleController->insertTrigger('document.insertDocument', 'mobilemessage', 'controller', 'triggerInsertDocument', 'after');

            // Comment Registration Trigger - 2010/02/03
            $oModuleController->insertTrigger('comment.insertComment', 'mobilemessage', 'controller', 'triggerInsertComment', 'after');

            // 기본값 설정.
            $config = $oModuleModel->getModuleConfig('mobilemessage');
            if (!isset($config->welcome_member)) {
                $config->welcome_member = "[회원가입완료]\n%user_name%(%user_id%)님 가입을 축하드립니다.";
                $config->welcome_admin = "[회원가입알림]\n%user_name%(%user_id%)님이 가입했습니다.";
                $oModuleController->insertModuleConfig('mobilemessage', $config);
            }
            // 그룹변경알림 메시지 추가 - 2010/05/31
            if (!isset($config->group_change_message)) {
                $config->group_change_message = "[그룹변경알림]\n%user_name%(%user_id%)님 %groups% 그룹에 추가되었습니다.";
                $oModuleController->insertModuleConfig('mobilemessage', $config);
            }
        }

        /**
         * @brief 설치가 이상없는지 체크
         **/
        function checkUpdate() {
            $oDB = &DB::getInstance();
            $oModuleModel = &getModel('module');
            $oModuleController = &getController('module');

            // userid 필드 추가 - 2009/08/09
            if (!$oDB->isColumnExists('mobilemessage', 'userid'))
                return true;

            // reservflag 필드 추가 - 2009/10/16
            if (!$oDB->isColumnExists('mobilemessage', 'reservflag'))
                return true;

            // reservdate 필드 추가 - 2009/10/16
            if (!$oDB->isColumnExists('mobilemessage', 'reservdate'))
                return true;

            // gid 필드 추가 - 2009/10/19
            if (!$oDB->isColumnExists('mobilemessage', 'gid'))
                return true;

            // 회원가입시 SMS발송 트리거 추가. - 2009/08/14
            if (!$oModuleModel->getTrigger('member.insertMember', 'mobilemessage', 'controller', 'triggerMemberJoin', 'after'))
                return true;

            // 회원탈퇴시 주소록 삭제 트리거 - 2009/11/05
            if (!$oModuleModel->getTrigger('member.deleteMember', 'mobilemessage', 'controller', 'triggerMemberDelete', 'before'))
                return true;

            // 회원정보 수정시 트리거 - 2010/02/07
            if (!$oModuleModel->getTrigger('member.updateMember', 'mobilemessage', 'controller', 'triggerMemberUpdate', 'after'))
                return true;

            // Document Registration Trigger - 2010/02/03
            if (!$oModuleModel->getTrigger('document.insertDocument', 'mobilemessage', 'controller', 'triggerInsertDocument', 'after'))
                return true;

            // Comment Registration Trigger - 2010/02/03
            if (!$oModuleModel->getTrigger('comment.insertComment', 'mobilemessage', 'controller', 'triggerInsertComment', 'after'))
                return true;

            // 가입환영 메시지 추가. - 2009/08/14
            $config = $oModuleModel->getModuleConfig('mobilemessage');
            if (!isset($config->welcome_member)) return true;
            if (!isset($config->group_change_message)) return true;

            // country 필드 추가 - 2010/04/18
            if (!$oDB->isColumnExists('mobilemessage_mapping', 'country')) return true;

            // country 필드 추가 - 2010/04/18
            if (!$oDB->isColumnExists('mobilemessage_validation', 'country')) return true;

            // country 필드 추가 - 2010/04/18
            if (!$oDB->isColumnExists('mobilemessage_prohibit', 'country')) return true;

            // nonmember_index 필드 추가 - 2010/05/03
            if (!$oDB->isColumnExists('mobilemessage_noticom', 'nonmember_index')) return true;

            // nonmember_index 필드 추가 - 2010/05/03
            if (!$oDB->isColumnExists('mobilemessage_notidoc', 'nonmember_index')) return true;

            // extra_vasr 필드 추가 - 2010/06/03
            if (!$oDB->isColumnExists('mobilemessage_notidoc', 'extra_vars')) return true;

            // extra_vasr 필드 추가 - 2010/06/03
            if (!$oDB->isColumnExists('mobilemessage_noticom', 'extra_vars')) return true;

            // country 필드 추가 - 2010/07/02
            if (!$oDB->isColumnExists('mobilemessage', 'country')) return true;

            // except_id_list 필드 추가 - 2010/08/05
            if (!$oDB->isColumnExists('mobilemessage_notidoc', 'except_id_list')) return true;

            // except_id_list 필드 추가 - 2010/08/05
            if (!$oDB->isColumnExists('mobilemessage_noticom', 'except_id_list')) return true;

            // subfolder 필드 추가 - 2011/02/01
            if (!$oDB->isColumnExists('purplebook', 'subfolder')) return true;

            // subnode 필드 추가 - 2011/02/01
            if (!$oDB->isColumnExists('purplebook', 'subnode')) return true;

            // member_srl 필드 추가 - 2011/03/10
            if (!$oDB->isColumnExists('purplebook', 'member_srl')) return true;

            // shared 필드 추가 - 2011/03/10
            if (!$oDB->isColumnExists('purplebook', 'shared')) return true;

            // member_srl 필드 추가 - 2011/03/14
            if (!$oDB->isColumnExists('mobilemessage_recent_receiver', 'member_srl')) return true;
            // member_srl 필드 추가 - 2011/03/14
            if (!$oDB->isColumnExists('mobilemessage_keeping', 'member_srl')) return true;

           return false;
        }

        /**
         * @brief 업데이트(업그레이드)
         **/
        function moduleUpdate() {
            $oDB = &DB::getInstance();
            $oModuleModel = &getModel('module');
            $oModuleController = &getController('module');

            // action forward에 등록 (관리자 모드에서 사용하기 위함)
            if ($this->getXEVerInt() <= $this->getVerInt("1.1.5"))
            {
                $this->insertActionForward();
            }

            // userid 필드 추가 - 2009/08/09
            if (!$oDB->isColumnExists('mobilemessage', 'userid'))
            {
                $oDB->addColumn('mobilemessage', 'userid', 'varchar', 80, '', true);
                $oDB->addIndex('mobilemessage', 'idx_userid', 'userid', false);
            }

            // reservflag 필드 추가 - 2009/10/16
            if (!$oDB->isColumnExists('mobilemessage', 'reservflag'))
            {
                $oDB->addColumn('mobilemessage', 'reservflag', 'char', 1, 'N', true);
            }

            // reservdate 필드 추가 - 2009/10/16
            if (!$oDB->isColumnExists('mobilemessage', 'reservdate'))
            {
                $oDB->addColumn('mobilemessage', 'reservdate', 'varchar', 14, '', true);
            }

            // gid 필드 추가 - 2009/10/19
            if (!$oDB->isColumnExists('mobilemessage', 'gid'))
            {
                $oDB->addColumn('mobilemessage', 'gid', 'varchar', 30, '', true);
                $oDB->addIndex('mobilemessage', 'idx_gid', 'gid', false);

                $oDB->executeQuery('mobilemessage.updateGID');
            }

            // 회원가입시 SMS발송 트리거 추가. - 2009/08/14
            if (!$oModuleModel->getTrigger('member.insertMember', 'mobilemessage', 'controller', 'triggerMemberJoin', 'after'))
                $oModuleController->insertTrigger('member.insertMember', 'mobilemessage', 'controller', 'triggerMemberJoin', 'after');

            // 회원탈퇴시 주소록 삭제 트리거 - 2009/11/05
            if (!$oModuleModel->getTrigger('member.deleteMember', 'mobilemessage', 'controller', 'triggerMemberDelete', 'before'))
                $oModuleController->insertTrigger('member.deleteMember', 'mobilemessage', 'controller', 'triggerMemberDelete', 'before');

            // 회원수정시 트리거 - 2010/02/07
            if (!$oModuleModel->getTrigger('member.updateMember', 'mobilemessage', 'controller', 'triggerMemberUpdate', 'after'))
                $oModuleController->insertTrigger('member.updateMember', 'mobilemessage', 'controller', 'triggerMemberUpdate', 'after');

            // Document Registration Trigger - 2010/02/03
            if (!$oModuleModel->getTrigger('document.insertDocument', 'mobilemessage', 'controller', 'triggerInsertDocument', 'after'))
                $oModuleController->insertTrigger('document.insertDocument', 'mobilemessage', 'controller', 'triggerInsertDocument', 'after');

            // Comment Registration Trigger - 2010/02/03
            if (!$oModuleModel->getTrigger('comment.insertComment', 'mobilemessage', 'controller', 'triggerInsertComment', 'after'))
                $oModuleController->insertTrigger('comment.insertComment', 'mobilemessage', 'controller', 'triggerInsertComment', 'after');

            // 가입환영 메시지 추가. - 2009/08/14
            $config = $oModuleModel->getModuleConfig('mobilemessage');
            if (!isset($config->welcome_member))
            {
                $config->welcome_member = "[회원가입완료]\n%user_name%(%user_id%)님 가입을 축하드립니다.";
                $config->welcome_admin = "[회원가입알림]\n%user_name%(%user_id%)님이 가입했습니다.";
                $oModuleController->insertModuleConfig('mobilemessage', $config);
            }

            // 그룹변경알림 메시지 추가 - 2010/05/31
            if (!isset($config->group_change_message)) {
                $config->group_change_message = "[그룹변경알림]\n%user_name%(%user_id%)님 %groups% 그룹에 추가되었습니다.";
                $oModuleController->insertModuleConfig('mobilemessage', $config);
            }

            // country 필드 추가 - 2010/04/18
            if (!$oDB->isColumnExists('mobilemessage_mapping', 'country'))
            {
                $oDB->addColumn('mobilemessage_mapping', 'country', 'varchar', 10, '82', true);
                $oDB->addIndex('mobilemessage_mapping', 'idx_country', 'country', false);
            }

            // country 필드 추가 - 2010/04/18
            if (!$oDB->isColumnExists('mobilemessage_validation', 'country'))
            {
                $oDB->addColumn('mobilemessage_validation', 'country', 'varchar', 10, '82', true);
                $oDB->addIndex('mobilemessage_validation', 'idx_country', 'country', false);
            }

            // country 필드 추가 - 2010/04/18
            if (!$oDB->isColumnExists('mobilemessage_prohibit', 'country'))
            {
                $oDB->addColumn('mobilemessage_prohibit', 'country', 'varchar', 10, '82', true);
                $oDB->addIndex('mobilemessage_prohibit', 'idx_country', 'country', false);
            }

            // nonmember_index 필드 추가 - 2010/05/03
            if (!$oDB->isColumnExists('mobilemessage_noticom', 'nonmember_index')) {
                $oDB->addColumn('mobilemessage_noticom', 'nonmember_index', 'number', 11, 0, true);
            }
            // nonmember_index 필드 추가 - 2010/05/03
            if (!$oDB->isColumnExists('mobilemessage_notidoc', 'nonmember_index')) {
                $oDB->addColumn('mobilemessage_notidoc', 'nonmember_index', 'number', 11, 0, true);
            }

            // extra_vars 필드 추가 - 2010/06/03
            if (!$oDB->isColumnExists('mobilemessage_notidoc', 'extra_vars')) {
                $oDB->addColumn('mobilemessage_notidoc', 'extra_vars', 'text');
            }

            // extra_vars 필드 추가 - 2010/06/03
            if (!$oDB->isColumnExists('mobilemessage_noticom', 'extra_vars')) {
                $oDB->addColumn('mobilemessage_noticom', 'extra_vars', 'text');
            }

            // country 필드 추가 - 2010/07/02
            if (!$oDB->isColumnExists('mobilemessage', 'country'))
            {
                $oDB->addColumn('mobilemessage', 'country', 'varchar', 10, '82', true);
            }

            // except_id_list 필드 추가 - 2010/08/05
            if (!$oDB->isColumnExists('mobilemessage_notidoc', 'except_id_list')) {
                $oDB->addColumn('mobilemessage_notidoc', 'except_id_list', 'varchar', 250, 'N', true);
            }

            // except_id_list 필드 추가 - 2010/08/05
            if (!$oDB->isColumnExists('mobilemessage_noticom', 'except_id_list')) {
                $oDB->addColumn('mobilemessage_noticom', 'except_id_list', 'varchar', 250, 'N', true);
            }

            // subfolder 필드 추가 - 2011/02/01
            if (!$oDB->isColumnExists('purplebook', 'subfolder')) {
                $oDB->addColumn('purplebook', 'subfolder', 'number', 11, '0', true);
            }
            // subnode 필드 추가 - 2011/02/01
            if (!$oDB->isColumnExists('purplebook', 'subnode')) {
                $oDB->addColumn('purplebook', 'subnode', 'number', 11, '0', true);
            }
            // member_srl 필드 추가 - 2011/03/10
            if (!$oDB->isColumnExists('purplebook', 'member_srl')) {
                $oDB->addColumn('purplebook', 'member_srl', 'number', 11, '0', true);
                $oDB->addIndex('purplebook', 'idx_member_srl', 'member_srl', false);
            }
            // shared 필드 추가 - 2011/03/10
            if (!$oDB->isColumnExists('purplebook', 'shared')) {
                $oDB->addColumn('purplebook', 'shared', 'number', 11, '0', true);
            }
            // member_srl 필드 추가 - 2011/03/14
            if (!$oDB->isColumnExists('mobilemessage_recent_receiver', 'member_srl')) {
                $oDB->addColumn('mobilemessage_recent_receiver', 'member_srl', 'number', 11, '0', true);
                $oDB->addIndex('mobilemessage_recent_receiver', 'idx_member_srl', 'member_srl', false);
            }
            // member_srl 필드 추가 - 2011/03/14
            if (!$oDB->isColumnExists('mobilemessage_keeping', 'member_srl')) {
                $oDB->addColumn('mobilemessage_keeping', 'member_srl', 'number', 11, '0', true);
                $oDB->addIndex('mobilemessage_keeping', 'idx_member_srl', 'member_srl', false);
            }

        }

        /**
         * @brief 캐시파일 재생성
         **/
        function recompileCache() {
        }
    }
?>
