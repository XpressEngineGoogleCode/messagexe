<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
 * @class  authentication_update_memberAdminController
 * @author NURIGO(contact@nurigo.net)
 * @brief  authentication_update_memberAdminController
 */
class authentication_update_memberAdminController extends authentication_update_member 
{
	/**
	 * @brief constructor
	 */
	function init() 
	{
	}

	function procAuthentication_update_memberAdminCopy()
	{
		$cellphone_fieldname = Context::get('cellphone_fieldname');
		$field_type = Context::get('field_type');

		$oMemberModel = &getModel('member');

		$output = executeQueryArray('authentication.getEntireMemberList');
		$data = $output->data;
		foreach($data as $key=>$member)
		{
			$args->member_srl = $member->member_srl;
			$output2 = executeQuery('member.getMemberInfoByMemberSrl', $args);
			$member_info = $output2->data;
			$extra_vars = unserialize($member_info->extra_vars);
			$number = array();
			if($field_type == 'telnum')
			{
				if(strlen($member->clue) > 10)
				{
					$number[] = substr($member->clue, 0, 3);
					$number[] = substr($member->clue, 3, 4);
					$number[] = substr($member->clue, 7, 4);
				} else {
					$number[] = substr($member->clue, 0, 3);
					$number[] = substr($member->clue, 3, 3);
					$number[] = substr($member->clue, 6, 4);
				}
			}
			else
			{
				$number = $member->clue;
			}
			$extra_vars->{$cellphone_fieldname} = $number;
			$args->extra_vars = serialize($extra_vars);
			executeQuery('authentication_update_member.updateMember', $args);
			$count++;
		}
		$this->setMessage(sprintf('%u 개 복사완료', $count));
		$redirectUrl = getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispAuthentication_update_memberAdminIndex');
		$this->setRedirectUrl($redirectUrl);
	}
}
/* End of file authentication_update_member.admin.controller.php */
/* Location: ./modules/authentication_update_member/authentication_update_member.admin.controller.php */
