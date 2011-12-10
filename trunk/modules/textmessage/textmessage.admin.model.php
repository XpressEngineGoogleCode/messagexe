<?php
    /**
     * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
     * @class  textmessageAdminModel
     * @author wiley(wiley@nurigo.net)
     * @brief  textmessageAdminModel
     */ 
    class textmessageAdminModel extends textmessage
    {
        function getTextmessageAdminCancelReserv() {
            $args->message_id = trim(Context::get('message_id'));
            $output = executeQueryArray('textmessage.getTextmessages', $args);
            Context::set('mobilemessage_list', $output->data);

            require_once('textmessage.utility.php');
            $csutil = new CSUtility();
            Context::set('csutil', $csutil);

            $oTemplate = &TemplateHandler::getInstance();
            $tpl = $oTemplate->compile($this->module_path.'tpl', 'cancel');

            $this->add('tpl', str_replace("\n"," ",$tpl));
		}

        function getTextmessageAdminCancelGroup() {
            $args->group_ids = "'" . implode("','", explode(',', trim(Context::get('group_ids')))) . "'";
            $output = executeQueryArray('textmessage.getTextmessageGroups', $args);
            Context::set('group_list', $output->data);

            require_once('textmessage.utility.php');
            $csutil = new CSUtility();
            Context::set('csutil', $csutil);

            $oTemplate = &TemplateHandler::getInstance();
            $tpl = $oTemplate->compile($this->module_path.'tpl', 'cancel_group');
            $this->add('tpl', str_replace("\n"," ",$tpl));
        }

        function getTextmessageAdminDeleteMessages() {
            $args->message_id = trim(Context::get('message_ids'));
            $output = executeQueryArray('textmessage.getTextmessages', $args);
            Context::set('mobilemessage_list', $output->data);

            require_once('textmessage.utility.php');
            $csutil = new CSUtility();
            Context::set('csutil', $csutil);

            $oTemplate = &TemplateHandler::getInstance();
            $tpl = $oTemplate->compile($this->module_path.'tpl', 'delete_messages');

            $this->add('tpl', str_replace("\n"," ",$tpl));
		}

        function getTextmessageAdminDeleteGroup() {
            $args->group_ids = "'" . implode("','", explode(',', trim(Context::get('group_ids')))) . "'";
            $output = executeQueryArray('textmessage.getTextmessageGroups', $args);
            Context::set('group_list', $output->data);

            require_once('textmessage.utility.php');
            $csutil = new CSUtility();
            Context::set('csutil', $csutil);

            $oTemplate = &TemplateHandler::getInstance();
            $tpl = $oTemplate->compile($this->module_path.'tpl', 'delete_group');
            $this->add('tpl', str_replace("\n"," ",$tpl));
        }

		function getTextmessageAdminUnfinishedMessages() {
			$args->page = Context::get('page');
			$output = executeQueryArray('textmessage.getUnfinishedMessages',$args);
			$this->add('total_count', $output->total_count);
			$this->add('total_page', $output->total_page);
			$this->add('page', $output->page);
			$this->add('data',$output->data);
		}
	}
?>
