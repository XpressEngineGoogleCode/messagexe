<?php
	/**
	 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
	 * @class  notificationAdminModel
	 * @author diver(diver@coolsms.co.kr)
	 * @brief  notificationAdminModel
	 */
	class notificationAdminModel extends notification {
		function getNotificationAdminDelete() {
			// load notification info
			$args->notification_srl = Context::get('notification_srl');
			$output = executeQuery("notification.getNotiComInfo", $args);
			$id_list = $output->data->id_list;
			$group_srl_list = $output->data->group_srl_list;
			$notification_info = $output->data;

			$args->notification_srls = Context::get('notification_srl');
			$output = executeQueryArray("notification.getModuleInfoByNotificationSrl", $args);
			$mid_list = array();
			if ($output->data) {
				foreach ($output->data as $no => $val) {
					$mid_list[] = $val->mid;
				}
			}
			$notification_info->mid_list = join(',', $mid_list);

			Context::set('notification_info', $notification_info);

			$oTemplate = &TemplateHandler::getInstance();
			$tpl = $oTemplate->compile($this->module_path.'tpl', 'delete');
			$this->add('tpl', str_replace("\n"," ",$tpl));
		}
	}
?>
