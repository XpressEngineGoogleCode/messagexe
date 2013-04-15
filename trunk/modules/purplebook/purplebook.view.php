<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  purplebookView
 * @author wiley(wiley@nurigo.net)
 * @brief  purplebookView
 */
class purplebookView extends purplebook
{
	var $use_point;
	var $sms_point;
	var $lms_point;
	var $alert_message="";

	function init()
	{
		// 템플릿 경로 설정
		if (!$this->module_info->skin) $this->module_info->skin = 'default';
		$this->setTemplatePath($this->module_path."skins/{$this->module_info->skin}");
	}

	function dispPurplebookIndex()
	{
		global $lang;
		$oPurplebookModel = &getModel('purplebook');

		$lang_list = array('sms','lms','mms','reserv_send','direct_send','msg_not_enough_money','available_sms_number','arranged_sms_number','msg_will_you_try','reservation_datetime','number_to_send','msg_will_you_send','msg_not_enough_money','available_lms_number','arranged_lms_number','available_mms_number','arranged_mms_number','msg_login_required');

		$oPurplebookController = &getController('purplebook');
		Context::set('ticket', $oPurplebookController->getTicket());

		$widget_lang = new StdClass();
		foreach ($lang_list as $val)
		{
			$widget_lang->{$val} = $lang->{$val};
		}
		Context::set('widget_lang', $widget_lang);

		$callback = $oPurplebookModel->getDefaultCallbackNumber();
		Context::set('callback', $callback);

		$this->setTemplateFile('address');
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
/* End of file purplebook.view.php */
/* Location: ./modules/purplebook/purplebook.view.php */
