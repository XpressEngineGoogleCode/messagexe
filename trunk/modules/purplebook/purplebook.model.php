<?php
/**
 * vi:set sw=4 ts=4 expandtab enc=utf8:
 * @class  purplebookModel
 * @author diver(diver@coolsms.co.kr)
 * @brief  purplebookModel
 */
class purplebookModel extends purplebook
{

    function init()
    {
    }

    /**
     * 모듈 환경설정값 가져오기
     */
    function getModuleConfig()
    {
        if (!$GLOBALS['__purplebook_config__'])
        {
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('purplebook');
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

            $GLOBALS['__purplebook_config__'] = $config;
        }
        return $GLOBALS['__purplebook_config__'];
    }

    /**
     * 환경값 읽어오기
     */
    function getConfig()
    {
        $oModuleModel = &getModel('module');
        $config = $oModuleModel->getModuleConfig('purplebook');

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
        if ($sln_reg_key) $sms->enable_resale();
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

    function getConfigValue(&$obj, $key, $type=null)
    {
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

    function getPurplebookStatusListByMessageId()
    {
        $message_ids_arr = explode(',', Context::get('message_ids'));
        $args->message_ids = "'" . implode("','", $message_ids_arr) . "'";
        $output = executeQueryArray('purplebook.getStatusListByMessageId', $args);
        $this->add('data', $output->data);
    }

    function getAddressList($args) {
        $query_id = 'purplebook.getPurplebookList';
        return executeQueryArray($query_id, $args);
    }

    function getPurplebookListPaging($args) {
        $query_id = 'purplebook.getPurplebookListPaging';
        return executeQueryArray($query_id, $args);
    }

    function getPurplebookSearch()
    {
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
        $output = executeQueryArray('purplebook.getPurplebookSearch', $args);
        if (!$output->toBool()) return $output;
        $this->add('data', $output->data);
    }

    function getFilePickerPath($purplebook_file_srl)
    {
        return sprintf("./files/attach/purplebook/%s",getNumberingPath($purplebook_file_srl,3));
    }

    /**
     * @brief CashInfo 가져오기 - Content-Type: JSON
     **/
    function getPurplebookCashInfo($args=false)
    {
        $config = $this->getModuleConfig($args);

        $oTextmessageModel = &getModel('textmessage');
        $sms = &$oTextmessageModel->getCoolSMS();

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
    function getPurplebookPointInfo()
    {
        global $lang;

        $logged_info = Context::get('logged_info');
        if (!$logged_info) return new Object(-1, 'msg_login_required');

        $oPointModel = &getModel('point');
        $rest_point = $oPointModel->getPoint($logged_info->member_srl, true);

        $this->add('point', $rest_point);
        $this->add('msg_not_enough', $lang->warning_not_enough_point);
    }

    function getSharedNodes($member_srl)
    {
        $args->share_member = $member_srl;
        $output = executeQueryArray('purplebook.getSharedNodes', $args);
        return $output;
    }

    /**
     * @brief 주소록
     **/
    function getPurplebookList()
    {
        $node_id = Context::get('node_id');
        $node_type = Context::get('node_type');
        $rel = Context::get('rel');

        $logged_info = Context::get('logged_info');
        if (!$logged_info) return new Object(-1, 'msg_login_required');

        $data = array();

        if ($node_id=='root')
        {
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

        if($node_id=='all')
        {
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

        if($node_type=='1'&&$node_id=='s.')
        {
            $output = $this->getSharedNodes($logged_info->member_srl);
            if (!$output->toBool()) return $output;
            if ($output->data) {
                foreach ($output->data as $no => $val) {
                    $args->node_id = $val->node_id;
                    $out2 = executeQuery('purplebook.getNodeInfoByNodeId', $args);
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
        switch ($node_id)
        {
            case "f.":
            case "t.":
            case "s.":
                $node_route = $node_id;
                break;
            default:
                if ($node_id)
                {
                    //$args->user_id = $logged_info->user_id;
                    $args->node_id = $node_id;
                    $output = executeQuery('purplebook.getNodeInfoByNodeId', $args);
                    if (!$output->toBool()) return $output;
                    $node_route = $output->data->node_route . $node_id . '.';
                    $user_id = $output->data->user_id;
                }
                else
                {
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
            $query_id = 'purplebook.getFolderList';
        } else {
            $query_id = 'purplebook.getPurplebookList';
        }
        $output = executeQueryArray($query_id, $args);
         */
        $output = $this->getAddressList($args);

        if((!is_array($output->data) || !count($output->data)) && $args->node_type == '1' && $args->node_route == '.')
        {
            return;
        }

        if(is_array($output->data))
        {
            foreach ($output->data as $no => $row)
            {
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

    function getPurplebookCallbackNumbers() 
    {
        $logged_info = Context::get('logged_info');
        if (!$logged_info) return new Object(-1, 'msg_login_required');
        $args->member_srl = $logged_info->member_srl;
        $output = executeQueryArray('purplebook.getCallbackNumbers', $args);
        if (!$output->toBool()) return $output;
        $this->add('data', $output->data);
    }

    function getDefaultCallbackNumber()
    {
        $logged_info = Context::get('logged_info');
        if (!$logged_info) return false;
        $args->member_srl = $logged_info->member_srl;
        $output = executeQueryArray('purplebook.getDefaultCallbackNumber', $args);
        if (!$output->toBool()) return false;
        if ($output->data && count($output->data) > 0) return $output->data[0]->phonenum;
        return false;
    }

    function getPurplebookSharedUsers()
    {
        $logged_info = Context::get('logged_info');
        if (!$logged_info) return new Object(-1, 'msg_invalid_request');

        $node_id = Context::get('node_id');
        if (in_array($node_id, array('f.','s.','t.'))) return new Object(-1, 'msg_cannot_share_root');

        $args->node_id = $node_id;
        $output = executeQuery('purplebook.getNodeInfoByNodeId', $args);
        $node_info = $output->data;
        if ($logged_info->member_srl != $node_info->member_srl) return new Object(-1, 'msg_no_permission_to_share');

        $args->node_id = $node_id;
        $output = executeQueryArray('purplebook.getSharedUsers', $args);
        if (!$output->toBool()) return $output;
        $this->add('data',$output->data);
    }

    function getRootFolderName($node_id)
    {
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

    function getPurplebookProperties()
    {
        $logged_info = Context::get('logged_info');
        if(!$logged_info) return new Object(-1, 'msg_invalid_request');

        $node_id = Context::get('node_id');
        $data = array();

        // address root folder
        if($node_id == 'f.')
        {
            $info = new StdClass();
            $info->name = '폴더명';
            $info->value = '주소록 폴더';
            $data[] = $info;
            $this->add('data',$data);
            return;
        }

        // root of shared folder
        if($node_id == 's.')
        {
            $info = new StdClass();
            $info->name = '폴더명';
            $info->value = '공유 폴더';
            $data[] = $info;

            $this->add('data',$data);
            return;
        }

        // trashcan
        if($node_id == 't.')
        {
            $info = new StdClass();
            $info->name = '폴더명';
            $info->value = '휴지통';
            $data[] = $info;
            $this->add('data',$data);
            return;
        }

        $args->node_id = $node_id;
        $output = executeQuery('purplebook.getNodeInfoByNodeId', $args);
        if (!$output->toBool()) return $output;
        $node_info = $output->data;;

        /*
        $info = new StdClass();
        $info->name = '폴더명';
        $info->value = $node_info->node_name;
        $data[] = $info;
         */
        if($node_info->node_type=='1')
        {
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
            $output = executeQueryArray('purplebook.getSharedUsers', $args);
            if(!$output->toBool()) return $output;
            $shared_count = count($output->data);
            if($shared_count)
            {
                if($node_info->member_srl == $logged_info->member_srl)
                {
                    $info = new StdClass();
                    $info->name = '공유정보';
                    if ($shared_count > 1)
                        $info->value = sprintf("%s 외 %u 명", $output->data[0]->nick_name, $shared_count);
                    else
                        $info->value = sprintf("%s", $output->data[0]->nick_name);
                    $info->value = sprintf("<a href=\"#\" onclick=\"obj=document.getElementById('node_%u');pb_share_folder(obj);\">%s</a>", $node_id, $info->value);
                    $data[] = $info;
                }
                else
                {
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
        }
        else
        {
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
            if(in_array($parent_node, array('f','s','t')))
            {
                $info = new StdClass();
                $info->name = '폴더명';
                $info->value = $this->getRootFolderName($parent_node.'.');
                $data[] = $info;
            }
            else
            {
                if ($parent_node)
                {
                    $args->node_id = $parent_node;
                    $output = executeQuery('purplebook.getNodeInfoByNodeId',$args);
                    if(!$output->toBool()) return $output;
                    $parent_node_info = $output->data;;
                    if($parent_node_info)
                    {
                        $info = new StdClass();
                        $info->name = '폴더명';
                        //$info->value = sprintf("<a href=\"#\" onclick=\"jQuery('#smsPurplebookTree').jstree('search','node_%s'); return false;\">%s</a>", $parent_node_info->node_id, $parent_node_info->node_name);
                        $info->value = $parent_node_info->node_name;
                        $data[] = $info;
                    }
                }
            }
            if($node_info->member_srl != $logged_info->member_srl)
            {
                $oMemberModel = &getModel('member');
                $member_info = $oMemberModel->getMemberInfoByMemberSrl($node_info->member_srl);
                if($member_info)
                {
                    $info = new StdClass();
                    $info->name = '소유자';
                    $info->value = sprintf('<a href="#popup_menu_area" class="member_%u" onclick="return false">%s</a>', $member_info->member_srl, $member_info->nick_name);
                    $data[] = $info;
                }
            }

        }

        $this->add('data',$data);
    }

    function getPurplebookLatestNumbers()
    {
        $logged_info = Context::get('logged_info');
        if(!Context::get('is_logged') || !$logged_info) return new Object(-1, 'login_required');

        $args->member_srl = $logged_info->member_srl;
        $output = executeQueryArray('purplebook.getRecentReceivers', $args);
        if(!$output->toBool()) return $output;
        $latest_numbers = array();
        if($output->data)
        {
            foreach($output->data as $no => $row)
            {
                $obj = new stdclass();
                $obj->receiver_srl = $row->receiver_srl;
                $obj->phone_num = $row->phone_num;
                $obj->ref_name = $row->ref_name;
                $latest_numbers[] = $obj;
            }
        }
        $this->add('data', $latest_numbers);
    }

    function getPurplebookSavedMessages()
    {
        $logged_info = Context::get('logged_info');
        if(!Context::get('is_logged') || !$logged_info) return new Object(-1, 'login_required');

        $args->member_srl = $logged_info->member_srl;
        $output = executeQueryArray('purplebook.getKeepingInfo', $args);
        if(!$output->toBool()) return $output;
        $latest_messages = array();
        if($output->data)
        {
            foreach($output->data as $no => $row)
            {
                $obj = new stdclass();
                $obj->message_srl = $row->message_srl;
                $obj->content = $row->content;
                $latest_messages[] = $obj;
            }
        }
        $this->add('data', $latest_messages);
    }

    function getPurplebookSearchFolder()
    {
        $logged_info = Context::get('logged_info');
        if(!Context::get('is_logged') || !$logged_info) return new Object(-1, 'login_required');

        $search = Context::get('search');
        $args->member_srl = $logged_info->member_srl;
        if(substr($search,0,5)=='node_')
        {
            $args->node_id = substr($search,5);
        }
        $output = executeQueryArray('purplebook.getSearchFolder', $args);
        if(!$output->toBool()) return $output;

        $data = array();
        if($output->data)
        {
            foreach($output->data as $no => $val)
            {
                $data[] = $val->node_id;
            }
        }
        $this->add('data', $data);
    }
}
/* End of file purplebook.model.php */
/* Location: ./modules/purplebook/purplebook.model.php */
