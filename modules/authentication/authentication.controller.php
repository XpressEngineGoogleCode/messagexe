<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
 * @class  authenticationController
 * @author wiley@nurigo.net
 * @brief  authenticationController
 */
class authenticationController extends authentication 
{
	/**
	 * @brief trigger for member insertion.
	 * @param $obj : member object.
	 **/
	function triggerInsertMember(&$obj) 
	{
		$oMemberModel = &getModel('member');
	}

	function triggerDeleteMember(&$obj) 
	{
		$oMemberModel = &getModel('member');
	}

	function triggerUpdateMember(&$obj) 
	{
		$oMemberModel = &getModel('member');
	}

	/**
	 * @brief Mapping정보 Insert
	 **/
	function insertMapping(&$args) {
		// delete
		$query_id = "mobilemessage.deleteMapping";
		$output = executeQuery($query_id, $args);
		if (!$output->toBool()) return $output;

		// insert
		$query_id = "mobilemessage.insertMapping";
		$output = executeQuery($query_id, $args);
		if (!$output->toBool()) return $output;

		return new Object();
	}
}
?>
