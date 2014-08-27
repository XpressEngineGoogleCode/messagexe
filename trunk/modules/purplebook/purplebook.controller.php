<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  purplebookController
 * @author NURIGO(contact@nurigo.net)
 * @brief  purplebookController
 */
class purplebookController extends purplebook 
{
	function init() 
	{
		$oModel = &getModel('purplebook');
		$this->config = $oModel->getModuleConfig();
	}

	function minusPoint($point) 
	{
		$logged_info = Context::get('logged_info');
		if(!$logged_info)
		{
			return new Object(-1, 'msg_login_required');
		}

		$oPointModel = &getModel('point');
		$rest_point = $oPointModel->getPoint($logged_info->member_srl, TRUE);
		if($rest_point < $point)
		{
			return new Object(-1, 'msg_not_enough_point');
		}

		$oPointController = &getController('point');
		$oPointController->setPoint($logged_info->member_srl, $point, 'minus');

		return new Object();
	}

	/**
	 * @brief procPurplebookSendMsg
	 **/
	function procPurplebookSendMsg($args=FALSE) 
	{
		$all_args = Context::getRequestVars();

		if(!$this->grant->send) return new Object(-1, 'msg_not_permitted');
		$module_srl = Context::get('module_srl');
		$oPurplebookModel = &getModel('purplebook');
		$module_info = $oPurplebookModel->getModuleInstConfig($module_srl);
		if($module_info->module != 'purplebook') return new Object(-1,'msg_invalid_request');

		if($args && $args->basecamp) $basecamp = $args->basecamp;

		$encode_utf16 = Context::get('encode_utf16');
		$decoded = $this->getJSON('data');

		$error_count=0;
		if(!is_array($decoded))
		{
			$decoded = array($decoded);
		}

		$calc_point = 0;
		$msg_arr = array();
		$args = new StdClass();
		$args->extension = array();
		$delimiter = $this->getDelimiter();

		$logged_info = Context::get('logged_info');
		if(!$logged_info)
		{
			Context::set('message', Context::getLang('msg_login_required'));
			return;
		}

		foreach($decoded as $key => $row)
		{
			// 국가코드 체크
			if(substr($row->recipient, 0, 1) == '+' || substr($row->recipient, 0, 2) == '00')
			{
				require_once('purplebook.utility.php');
				$csutil = new CSUtility();

				// 시작위치 설정
				$startPos = 1;
				if (substr($row->recipient, 0, 2) == '00') $startPos = 2;

				// 뒷자리부터 국가코드 체크
				for($i = 6; $i > 0; $i--)
				{
					$country_code = $csutil->checkCountryCode(substr($row->recipient, $startPos, $i));
					if($country_code > 0) 
					{
						$args->extension[$key]->country = $country_code;
						$row->recipient = '0' . substr($row->recipient, $startPos + $i);
						break;
					}
				}
			}

			// 머지기능
			if($row->node_id)
			{
				// 창이 여러개일때 체크 
				if($first_num != $row->recipient)
				{
					$vars->member_srl = $logged_info->member_srl;
					$vars->node_id = $row->node_id;
					$output=executeQuery('purplebook.getPurplebook', $vars);
					if(!$output->toBool()) return $output;

					$merge_name = $row->refname;
					$merge_memo1 = $output->data->memo1;
					$merge_memo2 = $output->data->memo2;
					$merge_memo3 = $output->data->memo3;
				}

				$merge = array('{name}', '{memo1}', '{memo2}', '{memo3}');
				$change_string = array($merge_name, $merge_memo1, $merge_memo2, $merge_memo3);

				$row->text = str_replace($merge, $change_string, $row->text);
			}

			// set arggument
			$args->type = $row->msgtype;
			$args->sender_no = $row->callback;
			$args->subject = $row->subject;
			$args->country_code = $row->country;
			$args->reservdate = $row->reservdate;
			$args->attachment = $row->file_srl;
			$args->extension[$key]->text = $row->text;
			$args->extension[$key]->to = $row->recipient;

			if($args->type == 'sms') $calc_point += $module_info->sms_point;
			if($args->type == 'lms') $calc_point += $module_info->lms_point;
			if($args->type == 'mms') $calc_point += $module_info->mms_point;

			if(!$first_num) $first_num = $row->recipient;
		}

		$args->extension = json_encode($args->extension);

		// minus point
		if($module_info->use_point=='Y')
		{
			$output = $this->minusPoint($calc_point);
			if(!$output->toBool()) return $output;
		}

		// send messages
		$oTextmessageController = &getController('textmessage');
		$output = $oTextmessageController->sendMessage($args, $basecamp);

		$this->add('data', $output->get('data'));
		$this->add('success_count', $output->get('success_count'));
		$this->add('failure_count', $output->get('failure_count'));
		$this->add('alert_message', $output->getMessage());
		if($output->get('error_code')) $this->add('error_code', $output->get('error_code'));
	}

	/**
	 * @brief 주소록 등록
	 * @param[in] node_id, user_id, node_route, node_name, node_type, phone_num
	 **/
	function insertPurplebook(&$args) 
	{
		$args->node_id = getNextSequence();
		$output = executeQuery('purplebook.insertPurplebook', $args);
		$output->node_id = $args->node_id;
		return $output;
	}

	/**
	 * @brief node_id의 node_route를 구해서 node_route로 검색하여 하위 폴더 갯수를 구하여 업댓.
	 * @param[in] node_id : 업댓할 node_id
	 **/
	function updateSubfolder($member_srl, $node_id) 
	{
		$subfolder = 0;

		// check node_id
		if(!$node_id)
		{
			return new Object(-1, 'msg_invalid_request');
		}

		// get node_route
		$args->node_id = $node_id;
		$args->member_srl= $member_srl;
		$output = executeQuery('purplebook.getPurplebook', $args);
		if(!$output->toBool())
			return $output;
		$node_route = $output->data->node_route . $node_id . '.';

		// get subfolder count
		unset($args);
		$args->node_id = $node_id;
		$args->node_route = $node_route;
		$output = executeQuery('purplebook.getSubfolder', $args);
		if(!$output->toBool())
			return $output;
		if($output->data) $subfolder = $output->data->subfolder;

		// update subfolder count
		unset($args);
		$args->subfolder = $subfolder;
		$args->node_id = $node_id;
		$output = executeQuery('purplebook.updateSubfolder', $args);
		return $output;
	}

	/**
	 * @brief node_id의 node_route를 구해서 node_route로 검색하여 하위 명단 갯수를 구하여 업댓
	 * @param[in] node_id : 업댓할 node_id
	 **/
	function updateSubnode($member_srl, $node_id) 
	{
		$subnode = 0;

		$args->node_id = $node_id;
		$args->member_srl = $member_srl;
		$output = executeQuery('purplebook.getPurplebook', $args);
		if(!$output->toBool())	return $output;
		$node_route = $output->data->node_route . $node_id . '.';

		unset($args);
		$args->node_route = $node_route;
		$output = executeQuery('purplebook.getSubnode', $args);
		if(!$output->toBool())	return $output;
		if($output->data) $subnode = $output->data->subnode;

		unset($args);
		$args->subnode = $subnode;
		$args->node_id = $node_id;
		$output = executeQuery('purplebook.updateSubnode', $args);
		return $output;
	}

	/**
	 * @brief 주소록 수정
	 * @param[in] 대상필드: node_id
	 * @param[in] 수정필드: node_route, node_name, node_type, phone_num
	 **/
	function updatePurplebook($args) 
	{
		if(!$args->node_id)
			return new Object(-1, 'msg_invalid_request');
		$query_id = 'purplebook.updatePurplebook';
		return executeQuery($query_id, $args);
	}

	/**
	 * @brief 주소록 명단 삭제
	 * @param[in] member_srl
	 * @param[in] node_id
	 **/
	function deletePurplebook($args) 
	{
		$query_id = 'purplebook.deletePurplebook';
		return executeQuery($query_id, $args);
	}

	/**
	 * @brief 
	 **/
	function checkTime($ft_hour, $ft_min, $tt_hour, $tt_min) 
	{
		$cur_dt = getdate();
		$cur_dt_hour = $cur_dt["hours"];
		$cur_dt_min = $cur_dt["minutes"];
		$cur_dt_hourmin = $cur_dt_hour * 100 + $cur_dt_min;

		$ft_hourmin = intval($ft_hour) * 100 + intval($ft_min);
		$tt_hourmin = intval($tt_hour) * 100 + intval($tt_min);

		if($ft_hourmin < $tt_hourmin) {
			if($cur_dt_hourmin >= $ft_hourmin && $cur_dt_hourmin <= $tt_hourmin) return TRUE;
		} else {
			if($cur_dt_hourmin >= $ft_hourmin || $cur_dt_hourmin <= $tt_hourmin) return TRUE;
		}

		return FALSE;
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
				if($obj->mon == "Y") return TRUE;
			case 2:
				if($obj->tue == "Y") return TRUE;
			case 3:
				if($obj->wed == "Y") return TRUE;
			case 4:
				if($obj->thu == "Y") return TRUE;
			case 5:
				if($obj->fri == "Y") return TRUE;
			case 6:
				if($obj->sat == "Y") return TRUE;
			case 7:
				if($obj->sun == "Y") return TRUE;
		}

		return FALSE;
	}



	function getMsgType($msgtype, &$msg) 
	{
		$oModel = &getModel('purplebook');
		$config = $oModel->getModuleConfig();
		switch(strtoupper($msgtype))
		{
			case 'SMS': 
			default:
				$msgtype = 'SMS';
				break;
			case 'LMS':
			case 'AUTO':
				require_once('purplebook.utility.php');
				$csutil = new CSUtility();
				if($csutil->strlen_utf8($msg->content, TRUE) > $config->limit_bytes)
					$msgtype = 'LMS';
				else
					$msgtype = 'SMS';
				break;
			case 'MMS':
				if(count($msg->attachment) > 0) {
					$msgtype = 'MMS';
				} else {
					require_once('purplebook.utility.php');
					$csutil = new CSUtility();
					if($csutil->strlen_utf8($msg->content, TRUE) > $config->limit_bytes)
						$msgtype = 'LMS';
					else
						$msgtype = 'SMS';
				}
				break;
		}
		return $msgtype;
	}

	function getCallbackNumber($callback_number_type, $callback_number_direct, $member_srl, $member_phonenum=FALSE) 
	{
		$oMemberModel = &getModel('member');
		$oModel = &getModel('purplebook');
		$config = $oModel->getModuleConfig();
		$callback_number = "";
		switch($callback_number_type)
		{
			case 'self':
				return 'self';
			case 'writer':
				if($member_srl) {
					$member_info = $oMemberModel->getMemberInfoByMemberSrl($member_srl);
					if(!isset($config->cellphone_fieldname)) break;
					if($member_info->{$config->cellphone_fieldname}) {
						$phonenum = $member_info->{$config->cellphone_fieldname};
						if(is_array($phonenum)) $phonenum = join($phonenum);
					}
					if($phonenum) {
						$callback_number = $phonenum;
					}
				}
				if($member_phonenum) {
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

	function procPurplebookFilePicker()
	{
		$oPurplebookModel = &getModel('purplebook');
		//$this->setTemplatePath($this->module_path.'tpl');
		if(!$this->module_info->skin) $this->module_info->skin = 'default';
		$this->setTemplatePath($this->module_path."skins/{$this->module_info->skin}");

		$this->setLayoutFile('default_layout');
		$this->setTemplateFile('filepicker');

		$logged_info = Context::get('logged_info');
		if(!$logged_info)
		{
			Context::set('message', Context::getLang('msg_login_required'));
			return;
		}

		$vars = Context::gets('addfile','filter');
		$source_file = $vars->addfile['tmp_name'];
		if(!is_uploaded_file($source_file))
		{
			Context::set('message', Context::getLang('msg_invalid_request'));
			return;
		}

		// check file format, size
		$ext = strtolower(substr(strrchr($vars->addfile['name'],'.'),1));
		if($vars->filter) $filter = explode(',',$vars->filter);
		else $filter = array('jpg','jpeg','gif','png');
		if(!in_array($ext,$filter))
		{
			Context::set('message', Context::getLang('msg_invalid_file_format'));
			return;
		}


		// 파일 정보 구함
		list($width, $height, $type, $attrs) = @getimagesize($source_file);
		switch($type)
		{
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
		
		$file_srl = getNextSequence();
		$path = $oPurplebookModel->getFilePickerPath($file_srl);
		$save_filename = sprintf('%s%s.%s',$path, $file_srl, $type);

		// create directory
        if(!is_dir($path))
        {
            FileHandler::makeDir($path);
        }

        if(!FileHandler::moveFile($source_file, $save_filename))
        {
            Context::set('message', Context::getLang('msg_error_occured'));
            return;
        }

		$output = $this->insertFile($save_filename, $file_srl);
		if(!$output->toBool())
		{
			Context::set('message', $output->getMessage());
			return;
		}

		Context::set('filename', $save_filename);
		Context::set('purplebook_file_srl', $file_srl);

		$this->setLayoutFile('default_layout');
		$this->setTemplateFile('filepicker_selected');
	}

	function insertFile($save_filename, $file_srl)
	{
		$logged_info = Context::get('logged_info');
		if(!$logged_info) return new Object(-1, 'login_required');

		// 파일 정보 구함
		list($width, $height, $type, $attrs) = @getimagesize($save_filename);
		if($type == 3) $ext = 'png';
		elseif($type == 2) $ext = 'jpg';
		else $ext = 'gif';


		// insert
		$args->file_srl = $file_srl;
		$args->member_srl = $logged_info->member_srl;
		$args->filename = $save_filename;
		$args->fileextension = $ext;
		$args->filesize = filesize($save_filename);

		$output = executeQuery('purplebook.insertFilePicker', $args);
		$output->save_filename = $save_filename;
		$output->purplebook_file_srl = $vars->purplebook_file_srl;
		return $output;
	}

	/**
	 * @return true : has permission, false : no permission
	 **/
	function checkPermission($node_id) 
	{
		// login check
		$logged_info = Context::get('logged_info');
		if(!$logged_info) return FALSE;

		// check permission for node_id
		$args->node_id = $node_id;
		$output = executeQuery('purplebook.getNodeInfoByNodeId',$args);
		if(!$output->toBool() || !$output->data) return FALSE;
		if($output->data->member_srl != $logged_info->member_srl) return FALSE;
		return TRUE;
	}

	function procPurplebookUpdateName() 
	{
		// login check
		$logged_info = Context::get('logged_info');
		if(!$logged_info) return new Object(-1, 'msg_invalid_request');

		$node_id = Context::get('node_id');
		$node_name = Context::get('name');

		// check permission for node_id
		if(!$this->checkPermission($node_id)) return new Object(-1, 'msg_no_permission');

		$args->member_srl = $logged_info->member_srl;
		$args->node_id = $node_id;
		$args->node_name = $node_name;
		$output = executeQuery('purplebook.updatePurplebookName', $args);
		return $output;
	}

	function procPurplebookUpdatePhone() 
	{
		// login check
		$logged_info = Context::get('logged_info');
		if(!$logged_info) return new Object(-1, 'msg_invalid_request');

		$node_id = Context::get('node_id');
		$phone_num = Context::get('phone_num');

		// check permission for node_id
		if(!$this->checkPermission($node_id)) return new Object(-1, 'msg_no_permission');

		$args->member_srl = $logged_info->member_srl;
		$args->node_id = $node_id;
		$args->phone_num = $phone_num;
		$output = executeQuery('purplebook.updatePurplebookPhone', $args);
		return $output;
	}

	/**
	 * @brief copy nodes
	 **/
	function procPurplebookCopy() 
	{
		$logged_info = Context::get('logged_info');
		if(!$logged_info) return new Object(-1, 'msg_not_logged');

		$node_list = $this->getJSON('node_list');
		$node_id = Context::get('node_id');

		// get node_route
		if(in_array($node_id,array('f.','s.','t.')))
		{
			$node_route = $node_id;
		}
		else
		{
			$args->node_id = $node_id;
			$output = executeQuery('purplebook.getNodeInfoByNodeId',$args);
			if(!$output->toBool() || !$output->data) return $output;
			$node_route = $output->data->node_route . $output->data->node_id . '.';
		}

		foreach ($node_list as $node_id)
		{
			unset($args);
			$args->node_id = $node_id;
			$output = executeQuery('purplebook.getNodeInfoByNodeId', $args);
			if($output->data)
			{
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

	function procPurplebookSaveMessage() 
	{
		$logged_info = Context::get('logged_info');
		if(!$logged_info) return new Object(-1, 'login_required');
		$args->message_srl = getNextSequence();
		$args->member_srl = $logged_info->member_srl;
		$args->content = Context::get('content');
		$output = executeQuery('purplebook.insertMessage', $args);
		if(!$output->toBool()) return $output;
	}

	/**
	 * @brief 주소록 Node 추가
	 **/
	function procPurplebookAddNode() 
	{
		$logged_info = Context::get('logged_info');
		if(!$logged_info) return new Object(-1, 'msg_login_required');

		$parent_node = Context::get('parent_node');
		// deny adding to trashcan and folder shared
		if(in_array($parent_node, array('t.','s.')))
			return new Object(-1, 'msg_cannot_create_folder');

		// get node_route
		if(in_array($parent_node, array('f.','t.','s.')))
		{
			$node_route = $parent_node;
		}
		else
		{
			// get parent node
			$args->node_id = $parent_node;
			$output = executeQuery('purplebook.getNodeInfoByNodeId', $args);
			if(!$output->toBool()) return $output;
			if(!$output->data) return new Object(-1, 'msg_invalid_request');

			// check for permission
			if($output->data->member_srl != $logged_info->member_srl) return new Object(-1,'msg_no_permission');

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
		$args->memo1 = Context::get('memo1');
		$args->memo2 = Context::get('memo2');
		$args->memo3 = Context::get('memo3');

		$this->insertPurplebook($args);

		if(!in_array($parent_node, array('f.','t.','s.')))
		{
			if($args->node_type=='1') $this->updateSubfolder($logged_info->member_srl, $parent_node);
			if($args->node_type=='2') $this->updateSubnode($logged_info->member_srl, $parent_node);
		}

		$this->add('id', $args->node_id);
		$this->add('node_id', $args->node_id);
		$this->add('node_route', $args->node_route);
		$this->add('node_name', $args->node_name);
		if($args->node_type=='1') $this->add('rel','folder');
	}

	/**
	 * @brief 전체보기창 Excel로 주소록에 추가
	 **/
	function procPurplebookExcelLoad()
	{
		$logged_info = Context::get('logged_info');
		if(!$logged_info) return new Object(-1, 'msg_login_required');

		// 강제적으로 요청을 JSON으로 한다.
		Context::setRequestMethod("JSON");

		// excel 파일을 읽기위한 php파일 가져오기 
		require_once('excel_reader2.php');

		$vars = Context::getRequestVars();

		$ext = substr(strrchr($vars->excel_file["name"],"."),1); //확장자앞 .을 제거하기 위하여 substr()함수를 이용
		$ext = strtolower($ext); //확장자를 소문자로 변환

		if($ext == null) return new Object(-1, 'msg_not_found_file'); // 파일 존재 여부 검사
		if($ext != 'xls') return new Object(-1, "msg_excel_check_extension");  //확장자 검사

		$data = new Spreadsheet_Excel_Reader();
		//$data->setOutputEncoding('CP949');
		$data->read($vars->excel_file["tmp_name"]); // 엑셀파일 읽기

		// numRows가 가로 numCols가 세로
		for ($i = 1; $i <= $data->sheets[0]['numCols']; $i++) {
			for ($j = 1; $j < $data->sheets[0]['numRows']; $j++) {
				// 로드된 excel파일을 순서에 맞춰서 array로 정렬
				$array_test[$data->sheets[0]['cells'][1][$i]][] = $data->sheets[0]['cells'][$j+1][$i];
			}
		}

		// 타이틀이 하나라도 들어있지 않다면 리턴 false
		if(!array_key_exists('name',$array_test)) return new Object(-1, "msg_excel_name_empty");
		if(!array_key_exists('number',$array_test)) return new Object(-1, "msg_excel_number_empty");

		$parent_node = Context::get('parent_node');

		// get node_route
		if(in_array($parent_node, array('f.','t.','s.')))
		{
			$node_route = $parent_node;
		}
		else
		{
			// get parent node
			$args->node_id = $parent_node;
			$output = executeQuery('purplebook.getNodeInfoByNodeId', $args);
			if(!$output->toBool()) return $output;
			if(!$output->data) return new Object(-1, 'msg_invalid_request');

			// check for permission
			if($output->data->member_srl != $logged_info->member_srl) return new Object(-1,'msg_no_permission');

			$node_route = $output->data->node_route . $parent_node . '.';
		}

		$list = array();
		for($i = 0; $i < count($array_test['name']); $i++)
		{
			$args = new StdClass();
			$args->member_srl = $logged_info->member_srl;
			$args->user_id = $logged_info->user_id;
			$args->parent_node = $vars->parent_node;
			$args->node_route = $node_route;
			$args->node_name = $array_test['name'][$i];
			$args->node_type = '2';
			$args->phone_num = str_replace('-', '', $array_test['number'][$i]);
			$args->memo1 = $array_test['memo1'][$i];
			$args->memo2 = $array_test['memo2'][$i];
			$args->memo3 = $array_test['memo3'][$i];

			// purplebook table에 업로드
			$this->insertPurplebook($args);

			$list[] = $args;
		}

		if(!in_array($parent_node, array('f.','t.','s.')))
		{
			$this->updateSubnode($logged_info->member_srl, $parent_node);
		}

		$this->add('list',$list);
	}


	/**
	 * @brief 주소록 Node 추가
	 **/
	function procPurplebookAddList() 
	{
		$logged_info = Context::get('logged_info');
		if(!$logged_info) return new Object(-1, 'msg_login_required');

		$data = $this->getJSON('data');
		$parent_node = Context::get('parent_node');

		// get node_route
		if(in_array($parent_node, array('f.','t.','s.')))
		{
			$node_route = $parent_node;
		}
		else
		{
			// get parent node
			$args->node_id = $parent_node;
			$output = executeQuery('purplebook.getNodeInfoByNodeId', $args);
			if(!$output->toBool()) return $output;
			if(!$output->data) return new Object(-1, 'msg_invalid_request');

			// check for permission
			if($output->data->member_srl != $logged_info->member_srl) return new Object(-1,'msg_no_permission');

			$node_route = $output->data->node_route . $parent_node . '.';
		}

		$list = array();
		foreach ($data as $obj)
		{
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

		if(!in_array($parent_node, array('f.','t.','s.')))
		{
			$this->updateSubnode($logged_info->member_srl, $parent_node);
		}

		$this->add('return_data',$list);
	}

	/**
	 * @brief 주소록
	 **/
	function procPurplebookRenameNode() 
	{
		$logged_info = Context::get('logged_info');
		if(!$logged_info) return new Object(-1, 'msg_invalid_request');

		$node_id = Context::get('node_id');
		$node_name = Context::get('node_name');

		// check permission for node_id
		if(!$this->checkPermission($node_id)) return new Object(-1, 'msg_no_permission');

		$args->node_id = $node_id;
		$args->node_name = $node_name;
		if(!$args->node_name) return;
		$output = $this->updatePurplebook($args);
		return $output;
	}

	function copyNode($member_srl, $node_id, $parent_id) 
	{
		// get destination
		$args->node_id = $parent_id;
		$args->member_srl = $member_srl;
		$output = executeQuery('purplebook.getPurplebook', $args);
		if(!$output->toBool()) return $output;
		$dest_node = $output->data;

		// new route
		$new_args->node_id = $node_id;
		$new_args->node_route = $dest_node->node_route . $dest_node->node_id . '.';

		// get current node
		unset($args);
		$args->node_id = $node_id;
		$args->member_srl = $member_srl;
		$output = executeQuery('purplebook.getPurplebook', $args);
		if(!$output->toBool()) return $output;
		$current = $output->data;

		// copy current node
		unset($args);
		$args = clone($current);
		$args->node_route = $new_args->node_route;
		$output = $this->insertPurplebook($args);
		if(!$output->toBool()) return $output;
		$new_node_id = $output->node_id;

		// copy children
		$search_args->member_srl = $member_srl;
		$search_args->node_route = $current->node_route . $current->node_id . '.';
		//$search_args->node_type = '2';
		$output = executeQueryArray('purplebook.getPurplebookChildrenByNodeRoute', $search_args);
		if(!$output->toBool()) return $output;
		$new_route = $new_args->node_route . $new_node_id . '.';
		if($output->data)
		{
			foreach($output->data as $no => $val)
			{
				$val->node_route = $new_route;
				$old_node_id = $val->node_id;
				if($val->node_type = '1' && $val->subfolder > 0)
				{
					$new_node_id = $res->node_id;
					$this->copyNode($member_srl, $old_node_id, $new_node_id);
				}
				else
				{
					$res = $this->insertPurplebook($val);
				}
			}
		}

		if($parent_id) $this->updateSubfolder($member_srl, $parent_id);
	}

	function moveNode($node_id, $parent_id) 
	{
		$logged_info = Context::get('logged_info');
		if(!$logged_info) return;

		// get destination
		if(in_array($parent_id, array('f.','t.','s.')))
		{
			$dest_route = $parent_id;
		} else
		{
			$args->node_id = $parent_id;
			$args->member_srl = $logged_info->member_srl;
			$args->user_id = $logged_info->user_id;
			$output = executeQuery('purplebook.getPurplebook', $args);
			if(!$output->toBool()) return $output;
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
		$output = executeQuery('purplebook.getPurplebook', $args);
		if(!$output->toBool()) return $output;
		$search_args->member_srl = $logged_info->member_srl;
		$search_args->user_id = $logged_info->user_id;
		$search_args->node_route = $output->data->node_route . $output->data->node_id . '.';
		$previous_node = $this->getPostNode($output->data->node_route);
		$output = executeQueryArray('purplebook.getPurplebookByNodeRoute', $search_args);
		if(!$output->toBool()) return $output;
		$old_route = $search_args->node_route;
		$new_route = $new_args->node_route . $node_id . '.';
		if($output->data)
		{
			foreach ($output->data as $no => $val) {
				$val->node_route = str_replace($old_route, $new_route, $val->node_route);
				executeQuery('purplebook.updatePurplebook', $val);
			}
		}

		// update current
		$output = executeQuery('purplebook.updatePurplebook', $new_args);
		if(!$output->toBool()) return $output;

		// root folder has no node_id.
		if($previous_node) $this->updateSubfolder($logged_info->member_srl, $previous_node);
		if($parent_id) $this->updateSubfolder($logged_info->member_srl, $parent_id);
	}

	function procPurplebookMoveNode() 
	{
		$logged_info = Context::get('logged_info');
		if(!$logged_info) return new Object(-1, 'msg_log_required');

		$parent_id = Context::get('parent_id');
		$node_id = Context::get('node_id');
		$copy = Context::get('copy');

		// check permission for parent_id
		if(!in_array($parent_id,array('f.','s.','t.')))
		{
			if(!$this->checkPermission($parent_id)) return new Object(-1, 'msg_no_permission');
			/*
			$args->node_id = $parent_id;
			$output = executeQuery('purplebook.getNodeInfoByNodeId',$args);
			if(!$output->toBool() || !$output->data) return $output;
			if($output->data->member_srl != $logged_info->member_srl) return new Object(-1, 'msg_no_permission');
			 */
		}

		// check permission for node_id
		if(!$this->checkPermission($node_id)) return new Object(-1, 'msg_no_permission');

		if($copy)
		{
			//$this->copyNode($logged_info->user_id, $node_id, $parent_id);
		}
		else
		{
			// move
			$this->moveNode($node_id, $parent_id);
		}
	}

	function procPurplebookMoveList() 
	{
		$logged_info = Context::get('logged_info');
		if(!$logged_info) return new Object(-1, 'msg_log_required');

		$parent_id = Context::get('parent_id');
		$node_list = $this->getJSON('node_list');

		// check permission for parent_id
		if(!in_array($parent_id,array('f.','s.','t.')))
		{
			if(!$this->checkPermission($parent_id)) return new Object(-1, 'msg_no_permission');
		/*
			$args->node_id = $parent_id;
			$output = executeQuery('purplebook.getNodeInfoByNodeId',$args);
			if(!$output->toBool() || !$output->data) return $output;
			if($output->data->member_srl != $logged_info->member_srl) return new Object(-1, 'msg_no_permission');
		 */
		}

		foreach ($node_list as $node_id)
		{
			// check permission for node_id
			if(!$this->checkPermission($node_id)) return new Object(-1, 'msg_no_permission');
			/*
			$args->node_id = $node_id;
			$output = executeQuery('purplebook.getNodeInfoByNodeId',$args);
			if(!$output->toBool() || !$output->data) return $output;
			if($output->data->member_srl != $logged_info->member_srl) return new Object(-1, 'msg_no_permission');
			 */
			$this->moveNode($node_id, $parent_id);
		}
	}

	/**
	 * @brief 주소록 Node 삭제
	 **/
	function procPurplebookDeleteNode() 
	{
		$node_id = Context::get('node_id');
		$logged_info = Context::get('logged_info');
		if(!$logged_info) return new Object(-1, 'msg_login_required');

		// get destination
		if(in_array($node_id, array('f.','t.','s.')))
		{
			$node_route = $node_id;
		}
		else
		{
			// get parent node
			$args->node_id = $node_id;
			$args->member_srl = $logged_info->member_srl;
			$output = executeQuery('purplebook.getPurplebook', $args);
			if(!$output->toBool()) return $output;
			$parent_node = $this->getPostNode($output->data->node_route);
			$node_route = $output->data->node_route . $node_id . '.';
		}
		unset($args);

		// delete share info.
		$args->member_srl = $logged_info->member_srl;
		$args->node_route = $node_route;
		$args->node_type = '1';
		$output = executeQueryArray('purplebook.getPurplebookByNodeRoute', $args);
		if(!$output->toBool()) return $output;
		unset($args);
		$shared_ids = array();
		if($output->data)
		{
			foreach ($output->data as $no=>$val)
			{
				$shared_ids[] = $val->node_id;
			}
		}
		if(count($shared_ids))
		{
			$args->node_ids = implode(',', $shared_ids);
			$output = executeQuery('purplebook.deleteSharedFolders', $args);
			if(!$output->toBool()) return $output;
		}

		// delete subfolder
		$args->member_srl = $logged_info->member_srl;
		$args->node_route = $node_route;
		$output = executeQuery('purplebook.deletePurplebookByNodeRoute', $args);
		if(!$output->toBool()) return $output;
		unset($args);

		// delete self
		if(!in_array($node_id, array('f.','t.','s.')))
		{
			$args->member_srl = $logged_info->member_srl;
			$args->node_id = $node_id;
			$output = executeQuery('purplebook.deletePurplebook', $args);
			if(!$output->toBool()) return $output;
		}
		unset($args);

		// update parent subfolder
		if($parent_node)
		{
			$output = $this->updateSubfolder($logged_info->member_srl, $parent_node);
			if(!$output->toBool()) return $output;
		}
	}

	function procPurplebookShareNode() 
	{
		$logged_info = Context::get('logged_info');
		if(!$logged_info) return new Object(-1, 'msg_login_required');

		$node_id = Context::get('node_id');
		$user_id = Context::get('user_id');

		$oMemberModel = &getModel('member');
		$member_info = $oMemberModel->getMemberInfoByUserId($user_id);
		if(!$member_info) return new Object(-1, 'msg_not_exists_member');

		// check myself
		if($member_info->member_srl==$logged_info->member_srl) return new Object(-1, 'msg_cannot_share_oneself');
		
		$args->share_member = $member_info->member_srl;
		$args->node_id = $node_id;
		$output = executeQueryArray('purplebook.getSharedFolder', $args);
		if(!$output->toBool()) return $output;
		if(count($output->data)) return new Object(-1, 'msg_exist_shared_folder');

		$output = executeQuery('purplebook.deleteSharedFolder', $args);
		if(!$output->toBool()) return $output;
		$output = executeQuery('purplebook.insertSharedFolder', $args);
		if(!$output->toBool()) return $output;

		// get shared count
		$args->node_id = $node_id;
		$output = executeQuery('purplebook.getSharedCount', $args);
		if(!$output->toBool()) return $output;
		$shared_count = 0;
		if($output->data) $shared_count = $output->data->shared;

		// update shared count
		$args->node_id = $node_id;
		$args->shared = $shared_count;
		$output = executeQuery('purplebook.updateShared', $args);
		if(!$output->toBool()) return $output;

		$this->add('node_id', $node_id);
		$this->add('member_srl', $member_info->member_srl);
		$this->add('user_id', $member_info->user_id);
		$this->add('nick_name', $member_info->nick_name);
		$this->add('shared_count', $shared_count);

		$this->setMessage('msg_folder_shared');
	}

	function procPurplebookUnshareNode() 
	{
		$logged_info = Context::get('logged_info');
		if(!$logged_info) return new Object(-1, 'msg_login_required');

		$node_id = Context::get('node_id');
		$member_srl = Context::get('member_srl');

		// delete shared folder
		$args->share_member = $member_srl;
		$args->node_id = $node_id;
		$output = executeQuery('purplebook.deleteSharedFolder', $args);
		if(!$output->toBool()) return $output;

		// count up exist shared folders
		$args->node_id = $node_id;
		$output = executeQuery('purplebook.getSharedCount', $args);
		if(!$output->toBool()) return $output;
		$shared_count = 0;
		if($output->data) $shared_count = $output->data->shared;

		// update count
		$args->node_id = $node_id;
		$args->shared = $shared_count;
		$output = executeQuery('purplebook.updateShared', $args);
		if(!$output->toBool()) return $output;

		$this->add('member_srl', $member_srl);
		$this->add('shared_count', $shared_count);

		$this->setMessage('msg_folder_unshared');
	}

	function procPurplebookSaveCallbackNumber() 
	{
		$logged_info = Context::get('logged_info');
		if(!$logged_info) return new Object(-1, 'msg_login_required');

		$args->member_srl = $logged_info->member_srl;
		$output = executeQuery('purplebook.getCountCallbackNumber', $args);
		if(!$output->toBool()) return $output;
		if($output->data->count >= 5) return new Object(-1, 'msg_callback_limit');

		$args->callback_srl = getNextSequence();
		$args->member_srl = $logged_info->member_srl;
		$args->user_id = $logged_info->user_id;
		$args->phonenum = preg_replace("/[^0-9]/", "", Context::get('phonenum'));
		if(!$args->phonenum) return new Object(-1, '번호를 올바르게 입력해 주세요.');
		return executeQuery('purplebook.insertCallbackNumber', $args);
	}

	function procPurplebookDeleteCallbackNumber() 
	{
		$callback_srl = Context::get('callback_srl');
		if(!$callback_srl) return new Object(-1, 'msg_invalid_request');

		$args->callback_srl = $callback_srl;
		return executeQuery('purplebook.deleteCallbackNumber', $args);
	}

	function procPurplebookSetDefaultCallbackNumber() 
	{
		$logged_info = Context::get('logged_info');
		if(!$logged_info) return new Object(-1, 'msg_login_required');

		$phonenum = preg_replace("/[^0-9]/", "", Context::get('phonenum'));

		$args->member_srl = $logged_info->member_srl;
		$args->flag_default = 'N';
		$output = executeQuery('purplebook.updateCallbackNumber', $args);

		$args->member_srl = $logged_info->member_srl;
		$args->phonenum = $phonenum;
		$args->flag_default = 'Y';
		$output = executeQuery('purplebook.updateCallbackNumber', $args);
		return $output;
	}

	function procPurplebookSaveReceiverNumber() 
	{
		$logged_info = Context::get('logged_info');
		if(!$logged_info)
		{
			return new Object(-1, 'login_required');
		}

		$args->receiver_srl = getNextSequence();
		$args->member_srl = $logged_info->member_srl;
		$args->user_id = $logged_info->user_id;
		$args->ref_name = Context::get('ref_name');
		$args->phone_num = Context::get('phone_num');

		$output = executeQuery('purplebook.deleteReceiver', $args);
		if(!$output->toBool())
		{
			return $output;
		}
		$output = executeQuery('purplebook.insertReceiver', $args);
		if(!$output->toBool())
		{
			return $output;
		}
	}

	function procPurplebookDeleteReceiverNumber() 
	{
		$logged_info = Context::get('logged_info');
		if(!$logged_info)
		{
			return new Object(-1, 'login_required');
		}

		$args->member_srl = $logged_info->member_srl;
		$args->receiver_srl = Context::get('receiver_srl');
		$output = executeQuery('purplebook.deleteReceiverByReceiverSrl', $args);
		if(!$output->toBool())	return $output;
		$this->setMessage('success_deleted');
	}

	function procPurplebookDeleteMessage() 
	{
		$logged_info = Context::get('logged_info');
		if(!Context::get('is_logged') || !$logged_info) return new Object(-1, 'login_required');

		$args->member_srl = $logged_info->member_srl;
		$args->message_srl = Context::get('message_srl');
		$output = executeQuery('purplebook.deleteRecentMessage', $args);
		if(!$output->toBool()) return $output;
		$this->setMessage('success_deleted');
	}
	
	function procPurplebookPurplebookDownload() 
	{
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

		$args->member_srl = $logged_info->member_srl;
		$args->node_route = $node_route;
		$args->node_type = '2';

		$oPurplebookModel = &getModel('purplebook');
		$output = executeQueryArray('purplebook.getPurplebookByNodeRoute', $args);

		require_once('purplebook.utility.php');
		$csutil = new CSUtility();
		Context::set('csutil', $csutil);
		Context::set('data', $output->data);

		$this->setLayoutFile('default_layout');
		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile('purplebook_download');
	}

	function getDelimiter()
	{
		for ($i = 0; $i < 5; $i++) {
			$result = $result . "@" . mt_rand(1, 9);
		}
		return $result;
	}

	// 주소록 업데이트
	function procPurplebookUpdateList()
	{
		// 강제적으로 요청을 JSON으로 한다.
		Context::setRequestMethod("JSON");

		$logged_info = Context::get('logged_info');
		if(!$logged_info) return new Object(-1, 'msg_invalid_request');

		$vars = Context::getRequestVars();

		for($i=1; $i <= $vars->list_count; $i++)
		{
			$node_id = "node_id_" . $i;
			$node_name = "node_name_" . $i;
			$phone_num = "phone_num_" . $i;
			$memo1 = "memo1_" . $i;
			$memo2 = "memo2_" . $i;
			$memo3 = "memo3_" . $i;

			$args->node_id = $vars->{$node_id};
			$args->node_name = $vars->{$node_name};
			$args->phone_num = $vars->{$phone_num};
			$args->memo1 = $vars->{$memo1} . "";
			$args->memo2 = $vars->{$memo2} . "";
			$args->memo3 = $vars->{$memo3} . "";

			$output = executeQuery('purplebook.updatePurplebook', $args);
			if(!$output->toBool()) return $output;
		}
	}
	
	// 주소록 개별 업데이트
	function procPurplebookUpdate()
	{
		$logged_info = Context::get('logged_info');
		if(!$logged_info) return new Object(-1, 'msg_invalid_request');

		$vars = Context::getRequestVars();

		$args->node_id = $vars->node_id;
		$args->node_name = $vars->n_name;
		$args->phone_num = $vars->phone_num;
		$args->memo1 = $vars->memo1;
		$args->memo2 = $vars->memo2;
		$args->memo3 = $vars->memo3;

		$output = executeQuery('purplebook.updatePurplebook', $args);
		if(!$output->toBool()) return new Object(-1, 'query error : updatePurplebook, line 1339');
	}

	// node_ids 로 개별 삭제
	function procPurplebookDelete()
	{
		$vars = Context::getRequestVars();
		$node_ids = $this->getJSON('node_ids');

		foreach($node_ids as $val)
		{
			Context::set('node_id', $val);

			$this->procPurplebookDeleteNode();

			Context::set('node_id', null);
		}
	}

	// 예약취소
	function procPurplebookCancelMessages()
	{
		$oTextMessageController = &getController('textmessage');

		$message_ids = $this->getJSON('message_ids');

		foreach($message_ids as $val)
		{
			$output = $oTextMessageController->cancelMessage($val);
		}
	}
}
/* End of file purplebook.controller.php */
/* Location: ./modules/purplebook/purplebook.controller.php */
