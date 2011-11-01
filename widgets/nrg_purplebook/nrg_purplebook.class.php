<?php
    /**
     * vi:set sw=4 ts=4 expandtab enc=utf8:
     * @class nrg_purplebook
     * @author wiley (wiley@nurigo.net)
     * @brief purplebook widget
     **/

    class nrg_purplebook extends WidgetHandler {
        /**
         * @brief 위젯의 실행 부분
         **/
        function proc($args) {
            $oMobilemessageModel = &getModel('mobilemessage');
            $config = $oMobilemessageModel->getModuleConfig();

            $oMemberModel = &getModel('member');
            $logged_info = &$oMemberModel->getLoggedInfo();

            $callback = $oMobilemessageModel->getDefaultCallbackNumber();
            if (!$callback) {
                if ($logged_info && $config->cellphone_fieldname)
                    eval("if (isset(\$logged_info->{$config->cellphone_fieldname})) { \$callback = \$logged_info->{$config->cellphone_fieldname}; }");

                if (is_string($callback) && preg_match('/\|\@\|/i', $callback)) $callback = explode('|@|', $callback);

                if (is_array($callback)) $callback = join($callback);
            }

            // loading language pack.
            Context::loadLang($this->widget_path."lang");
            $lang = new StdClass();
            $path = $this->widget_path."lang";
            if (Context::get('lang_type')) {
                if(substr($path,-1)!='/') $path .= '/';
                $filename = sprintf('%s%s.lang.php', $path, Context::get('lang_type'));
                if(!file_exists($filename)) $filename = sprintf('%s%s.lang.php', $path, 'ko');
                if(file_exists($filename)) @include($filename);
            }
            Context::set('widget_lang', $lang);

            // default value
            if (!$args->use_point) $args->use_point = 'N';
            if (!$args->sms_point) $args->sms_point = 0;
            if (!$args->lms_point) $args->lms_point = 0;
            if (!$args->mms_point) $args->mms_point = 0;

            Context::set('callback', $callback);
            Context::set('widget_info', $args);
            $oMobilemessageController = &getController('mobilemessage');
            Context::set('ticket', $oMobilemessageController->getTicket());

            // 템플릿 컴파일
            $tpl_path = sprintf('%sskins/%s', $this->widget_path, $args->skin);
            $tpl_file = 'address';
            Context::set('tpl_path', $this->tpl_path);

            $oTemplate = &TemplateHandler::getInstance();
            return $oTemplate->compile($tpl_path, $tpl_file);
        }
    }
?>
