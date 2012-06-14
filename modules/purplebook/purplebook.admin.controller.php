<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  purplebookAdminController
 * @author wiley(wiley@nurigo.net)
 * @brief  purplebookAdminController
 */
class purplebookAdminController extends purplebook
{
	function init() 
	{
	}

	function procPurplebookAdminInsertModInst() 
	{
		// module 모듈의 model/controller 객체 생성
		$oModuleController = &getController('module');
		$oModuleModel = &getModel('module');

		$args = Context::getRequestVars();
		$args->module = 'purplebook';

		// module_srl이 넘어오면 원 모듈이 있는지 확인
		if($args->module_srl) {
			$module_info = $oModuleModel->getModuleInfoByModuleSrl($args->module_srl);
			if($module_info->module_srl != $args->module_srl) unset($args->module_srl);
		}

		// module_srl의 값에 따라 insert/update
		if(!$args->module_srl) {
			$output = $oModuleController->insertModule($args);
			$msg_code = 'success_registed';
		} else {
			$output = $oModuleController->updateModule($args);
			$msg_code = 'success_updated';
		}

		if(!$output->toBool()) return $output;

		$this->add('module_srl',$output->get('module_srl'));
		$this->setMessage($msg_code);

		$redirectUrl = getNotEncodedUrl('', 'module', 'admin', 'act', 'dispPurplebookAdminInsertModInst','module_srl',$output->get('module_srl'));
		$this->setRedirectUrl($redirectUrl);
	}

	function procPurplebookAdminDeleteModInst() {
		$module_srl = Context::get('module_srl');

		$oModuleController = &getController('module');
		$output = $oModuleController->deleteModule($module_srl);
		if(!$output->toBool()) return $output;

		$this->add('module','purplebook');
		$this->add('page',Context::get('page'));
		$this->setMessage('success_deleted');

		$returnUrl = getNotEncodedUrl('', 'module', 'admin', 'act', 'dispPurplebookAdminModInstList');
		$this->setRedirectUrl($returnUrl);
	}


	function updateSubnodeCount() 
	{
		// extract all subfolder
		$args->node_type = '1';
		$output = executeQueryArray('purplebook.getPurplebookAllSubnode', $args);
		if (!$output->toBool()) return new Object(-1, 'msg_invalid_request');
		$count=0;
		foreach ($output->data as $no => $val) {
			// get subfolder count
			unset($args);
			$args->node_route = $val->node_route . $val->node_id . '.';
			$args->node_id = $val->node_id;
			$out1 = executeQuery('purplebook.getSubfolder', $args);
			if (!$out1->toBool()) return new Object(-1, 'msg_error_occured');
			$subfolder = $out1->data->subfolder;

			// get subnode count
			unset($args);
			$args->node_route = $val->node_route . $val->node_id . '.';
			$out2 = executeQuery('purplebook.getSubnode', $args);
			if (!$out2->toBool()) return new Object(-1, 'msg_error_occured');
			$subnode = $out2->data->subnode;

			// update subfolder count
			unset($args);
			$args->node_id = $val->node_id;
			$args->subfolder = $subfolder;
			$out = executeQuery('purplebook.updateSubfolder', $args);
			if (!$out->toBool()) return new Object(-1, 'msg_error_occured');

			// update subnode count
			unset($args);
			$args->node_id = $val->node_id;
			$args->subnode = $subnode;
			$out = executeQuery('purplebook.updateSubnode', $args);
			if (!$out->toBool()) return new Object(-1, 'msg_error_occured');

			$count++;
		}
		return $count;
	}

	function updateMemberSrl()
	{
		$oMemberModel = &getModel('member');

		$output = executeQueryArray('purplebook.getAllNodes');
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
					executeQuery('purplebook.updateNodeMemberSrl', $args);
					unset($member_info);
				}
				$count++;
			}
		}
		return $count;
	}

	function updateNodeRoute()
	{
		$oMemberModel = &getModel('member');

		$output = executeQueryArray('purplebook.getAllNodes');
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
					executeQuery('purplebook.updateNodeRoute', $args);
					$count++;
				}
			}
		}
		return $count;
	}

	function procPurplebookAdminUpdateSubnodeCount()
	{
		$count1 = $this->updateMemberSrl();
		$this->add('update1_count', $count1);
		$count2 = $this->updateSubnodeCount();
		$this->add('update2_count', $count2);
		$count3 = $this->updateNodeRoute();
		$this->add('update3_count', $count3);
	}
}
/* End of file purplebook.admin.controller.php */
/* Location: ./modules/purplebook/purplebook.admin.controller.php */
