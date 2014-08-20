<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
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

	function getModuleInstConfig($module_srl)
	{
		$oModuleModel = &getModel('module');
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
		if(!$module_info) $module_info = new StdClass();
		if(!$module_info->use_point) $module_infoi->use_point = 'Y';
		if(!$module_info->sms_point) $module_infoi->sms_point = 20;
		if(!$module_info->lms_point) $module_infoi->lms_point = 50;
		if(!$module_info->mms_point) $module_infoi->mms_point = 200;
		return $module_info;
	}

	/*
	function getPurplebookStatusListByMessageId()
	{
		$oTextmessageModel = &getModel('textmessage');
		$oTextmessageController = &getController('textmessage');

		// message ids
		$message_ids_arr = explode(',', Context::get('message_ids'));

		$sms = $oTextmessageModel->getCoolSMS();
		$result_array = Array();

		foreach($message_ids_arr as $message_id)
		{
			if(!$message_id) return;
			$option->mid = $message_id;
			$result = $sms->sent($option);

			$args->message_id = $message_id;
			$args->status = $result->data[0]->status;
			$args->resultcode = $result->data[0]->result_code;
			$args->carrier = $result->data[0]->carrier;
			$args->senddate = $result->data[0]->sent_time;

			$result_array[] = $args;

			unset($args);
		}

		$this->add('data', $result_array);
	}
	*/

	function getAddressList($args) {
		$query_id = 'purplebook.getPurplebookList';
		return executeQueryArray($query_id, $args);
	}

	function getPurplebookListPaging($args) {
		$query_id = 'purplebook.getPurplebookListPaging';
		return executeQueryArray($query_id, $args);
	}

	/**
	 * @brief 전체 검색
	 */
	function getPurplebookSearch()
	{
		$logged_info = Context::get('logged_info');
		if (!$logged_info) return new Object(-1, 'msg_invalid_request');
		$search_word = Context::get('search_word');
		$args->user_id = $logged_info->user_id;
		$args->search_word = $search_word;
		$args->page = Context::get('page');
		$args->list_count = 100;
		$output = executeQueryArray('purplebook.getPurplebookSearch', $args);
		if (!$output->toBool()) return $output;
		$this->add('total_count', $output->total_count);
		$this->add('total_page', $output->total_page);
		$this->add('page', $output->page);
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

		// get cash info
		$result = $oTextmessageModel->getCashInfo();

		$this->add('cash', $result->get('cash'));
		$this->add('point', $result->get('point'));
		//$this->add('mdrop', $result["DROP"]);
		$this->add('sms_price', '20');
		$this->add('lms_price', '50');
		$this->add('mms_price', '200');
	}

	/**
	 * @brief System Point 가져오기
	 **/
	function getPurplebookPointInfo()
	{
		$logged_info = Context::get('logged_info');
		if (!$logged_info) return new Object(-1, 'msg_login_required');

		$oPointModel = &getModel('point');
		$rest_point = $oPointModel->getPoint($logged_info->member_srl, true);

		$this->add('point', $rest_point);
		$this->add('msg_not_enough_point', Context::getLang('msg_not_enough_point'));
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

		// 검색어가 있을시
		if(Context::get('search_keyword'))
		{
			$args->search_node_name = Context::get('search_keyword');
			$args->search_phone_num = Context::get('search_keyword');
			$args->search_memo1 = Context::get('search_keyword');
			$args->search_memo2 = Context::get('search_keyword');
			$args->search_memo3 = Context::get('search_keyword');
		}

		// 페이지
		if(Context::get("page")) $args->page = Context::get("page");
		else $args->page = 1;

		$args->list_count = 20;
		// 리스트 카운트
		if(Context::get("list_count"))
		{
			Context::set("full_list_count", Context::get("list_count"));
			$args->list_count = Context::get("list_count");
		}

		$output = $this->getAddressList($args);

		// 페이지 설정
		$this->add('page_navigation', $output->page_navigation);
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('page_navigation', $output->page_navigation);

		// 리스트 시작 Number 
		$start_num = ($args->page - 1) * $args->list_count + 1;
		Context::set('start_num', $start_num);

		if((!is_array($output->data) || !count($output->data)) && $args->node_type == '1' && $args->node_route == '.') return;

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
				$obj->attr->memo1 = $row->memo1;
				$obj->attr->memo2 = $row->memo2;
				$obj->attr->memo3 = $row->memo3;

				$obj->data = $row->node_name;
				if ($row->subfolder > 0) $obj->state = "closed";
				$data[] = $obj;
			}
		}
		$this->add('total_count', $output->total_count);
		$this->add('total_page', $output->total_page);
		$this->add('data', $data);
		$config = $this->getModuleConfig();
		$this->add('base_url', $config->callback_url);

		if(Context::get('full_address_view')){
			Context::set('list', $data); // 주소록 리스트 설정 

			if(Context::get('full_fix_mode')) Context::set('full_fix_mode', Context::get('full_fix_mode')); // 수정모드일때 

			$oModuleModel = &getModel("module");
			$oTemplate = &TemplateHandler::getInstance();

			$module_info = $oModuleModel->getModuleInfoByMid(Context::get('g_mid'));

			$path = $this->module_path."skins/".$module_info->skin;
			$file_name = "full_address_list.html";
			$data = $oTemplate->compile($path, $file_name);

			$this->add('list_templete', $data); // 템플릿파일 설정
		}
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
		if(!Context::get('is_logged') || !$logged_info) return new Object(-1, 'msg_login_required');

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
		if(!Context::get('is_logged') || !$logged_info) return new Object(-1, 'msg_login_required');

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
		if(!Context::get('is_logged') || !$logged_info) return new Object(-1, 'msg_login_required');

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

	// 전체보기 개별수정폼 템플릿 가져오기
	function getPurplebookUpdateAddress()
	{
		$logged_info = Context::get('logged_info');
		if(!$logged_info) return new Object(-1, 'msg_not_logged');

		$oModuleModel = &getModel("module");
		$oTemplate = &TemplateHandler::getInstance();

		$args->member_srl = $logged_info->member_srl;
		$args->node_id = Context::get('node_id');
		$output = executeQuery('purplebook.getPurplebook', $args);
		if(!$output->toBool()) return $output;

		Context::set('address_info', $output->data);

		$module_info = $oModuleModel->getModuleInfoByMid(Context::get('g_mid'));

		$path = $this->module_path."skins/".$module_info->skin;
		$file_name = "full_address_update.html";
		$data = $oTemplate->compile($path, $file_name);

		$this->add('list_templete', $data); // 템플릿파일 설정
	}

	// 전송결과 템플릿 가져오기
	function getPurplebookSendResult()
	{
		$logged_info = Context::get('logged_info');
		if(!$logged_info) return new Object(-1, 'msg_not_logged');

		$oModuleModel = &getModel("module");
		$oTemplate = &TemplateHandler::getInstance();

		$vars = Context::getRequestVars();

		//$args->s_start = date("Ymd",strtotime ("-1 days")) . "000000";
		$args->count = 10;

		// 리스트 카운트
		if(Context::get("list_count")) 
		{
			$args->count = Context::get("list_count");
			Context::set("full_send_result_count", Context::get("list_count"));
		}

		// 검색날짜가 있으면
		if($vars->s_start)
		{
			$args->s_start = $vars->s_start;
			Context::set('send_result_start_date', $vars->s_start);
		}
		if($vars->s_end)
		{
			$args->s_end = $vars->s_end;
			Context::set('send_result_end_date', $vars->s_end);
		}

		// 페이지 설정
		if($vars->page) $args->page = $vars->page;

		// status 설정
		if($vars->status)
		{
			// 대기, 취소
			$args->s_resultcode = $vars->status;

			// 성공
			if($vars->status == "00")
			{
				$args->s_status = "2";
				$args->s_resultcode =  "00";
			}
			else if($vars->status == "fail") 
			{
				//실패
				$args->s_resultcode = null;
				$args->notin_resultcode = "00,99,60";
			}

			Context::set("full_send_result_status", $vars->status);
		}

		//검색어 설정
		if($vars->search_keyword) $args->s_rcpt = trim($vars->search_keyword);

		$oTextmessageModel = &getModel('textmessage');
		$sms = &$oTextmessageModel->getCoolSMS();
		$output = $sms->sent($args);

		debugPRint('o-1');
		debugPrint($output);
		debugPrint($vars);
		debugPrint($args);

		// 리스트 있을때
		if($output->data)
		{
			// Status를 Stirng 으로 변환
			foreach($output->data as $k => $v)
			{
				switch($v->status)
				{
					case "0":
						$v->status_t = "대기";
						break;
					case "1":
						$v->status_t = "전송중";
						break;
					case "2":
						$v->status_t = "완료";
						break;
					case "3":
						$v->status_t = "완료";
						break;
					default:
						$v->status_t = "오류";
						break;
				}

				if($v->result_code != '00' && $v->result_code != '99' && $v->result_code != '60') $v->result = 'fail';
			}

			$page = $output->page;
			// Set Contents
			Context::set('page', $page);
			Context::set('total_count', $output->total_count);
			Context::set('total_page', ceil($output->total_count/$output->list_count));
			Context::set('send_result_list', $output->data);
		}
		else // 리스트가 없을떄
		{
			$page = 1;
			Context::set('page', page);
			Context::set('total_count', 0);
			Context::set('total_page', 1);
		}

		// 시작번호
		$start_num = ($page - 1) * $args->count + 1;
		Context::set('start_num', $start_num);

		$module_info = $oModuleModel->getModuleInfoByMid(Context::get('g_mid'));

		$path = $this->module_path."skins/".$module_info->skin;
		$file_name = "full_send_result_list.html";
		$data = $oTemplate->compile($path, $file_name);

		$this->add('list_templete', $data); // 템플릿파일 설정
	}

	// 레이어 템플릿 가져오기
	function getPopupLayer()
	{
		$logged_info = Context::get('logged_info');
		if(!Context::get('is_logged') || !$logged_info) return new Object(-1, 'msg_login_required');

		$oModuleModel = &getModel("module");
		$oTemplate = &TemplateHandler::getInstance();

		if(!Context::get('g_mid') || !Context::get('layer_name')) return false;

		$module_info = $oModuleModel->getModuleInfoByMid(Context::get('g_mid'));

		$path = $this->module_path."skins/".$module_info->skin;
		$file_name = Context::get('layer_name').".html";

		$data = $oTemplate->compile($path, $file_name);

		$this->add('data', $data);
	}
}
/* End of file purplebook.model.php */
/* Location: ./modules/purplebook/purplebook.model.php */
