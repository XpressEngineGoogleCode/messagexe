<?php
    /**
     * vi:set sw=4 ts=4 expandtab enc=utf8:
     * @class  MessageSender
     * @author diver(diver@coolsms.co.kr)
     * @brief  MessageSender
     */
    class MessageSender extends mobilemessage {
        function MessageSender() {
            $oModel = &getModel('mobilemessage');
            $this->config = $oModel->getModuleConfig();
            require_once($this->module_path."coolsms.php");
            $this->sms = new coolsms();
            $sln_reg_key = $oModel->getSlnRegKey();
            if ($sln_reg_key) $this->sms->set_sln_reg_key($sln_reg_key);
            $this->sms->appversion("MXE/" . $this->version . " XE/" . __ZBXE_VERSION__);
            $this->sms->setuser($this->config->cs_userid, $this->config->cs_passwd);
            $this->sms->charset('utf8');
            $this->sms->emptyall();
            if ($config->encode_utf16=='Y') $this->sms->encode_utf16();
            $this->group_id = coolsms::keygen();
        }

        function addMessage(&$args) {
            $mid = coolsms::keygen();

            if (!$args->type) $args->type = 'SMS';
            $args->type = strtoupper($args->type);
            if (!in_array($args->type, array('SMS', 'LMS', 'MMS'))) $type = 'SMS';
            $type = $args->type;
            $message = $args->message;
            $recipient = $args->recipient;
            $callback = $args->callback;
            $reservdate = $args->reservdate;
            $subject = $args->subject;
            // ref_userid
            if (isset($args->ref_userid))
                $ref_userid = $args->ref_userid;
            else
                $ref_userid = '';
            // ref_username
            if (isset($args->ref_username))
                $ref_username = $args->ref_username;
            else
                $ref_username = '';
            $country = $args->country;
            if (!$country) $country = $this->config->default_country;


            $msg = new StdClass();
            $msg->rcvnum = $recipient;
            $msg->callback = $callback;
            $msg->msg = $message;
            $msg->callname = $ref_username;
            $msg->reservdate = $reservdate;
            $msg->msgid = $mid;
            $msg->type = $type;
            $msg->groupid = $this->group_id;
            $msg->country = $country;
            if ($args->attachment) $msg->attachment;

            $this->sms->addobj($msg);


            // DB INSERT
            $oMobilemessageController = &getController("mobilemessage");
            unset($args);
            $args = new StdClass();
            if ($user_id === true) {
                $logged_info = Context::get('logged_info');
                if ($logged_info)
                    $args->userid = $logged_info->user_id;
            } else if ($user_id === false) {
                // do nothing
            } else {
                $args->userid = $user_id;
            }
            $args->gid = $this->group_id;
            $args->mid = $mid;
            $args->mtype = $type;
            $args->country = $country;
            $args->callno = $recipient;
            $args->callback = $callback;
            $args->subject = $subject;
            $args->content = $message;
            $args->reservdate = $reservdate;
            if ($reservdate)
                $args->reservflag = "Y";
            if ($ref_userid)
                $args->ref_userid = $ref_userid;
            if ($ref_username)
                $args->ref_username = $ref_username;
            $oMobilemessageController->insertMobilemessage($args);
        }

        function sendMessages() {
            if (!$this->sms->connect()) {
                // cannot connect to server
                return false;
            }
            return $this->sms->send();
        }

        function getResult() {
            return $this->sms->getr();
        }
    }
?>
