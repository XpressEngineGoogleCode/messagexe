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
	}
?>
