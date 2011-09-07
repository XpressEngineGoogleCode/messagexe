<?php
/* vi:set ts=4 sw=4 expandtab enc=utf8: */
require_once("mobilemessage.cellphone.util.php");

class Cellphone
{
    var $args;
    var $error_message;

    function Cellphone($args)
    {
        $this->args = $args;
    }

    function SendSMS($callno, $callback, $message, $callname, $reservdate, $reservflag, $gid, &$sms, &$errcnt)
    {
        global $lang;

        $oMobilemessageController = &getController("mobilemessage");
        $current_version = $oMobilemessageController->getXEVerInt();
        $v115 = $oMobilemessageController->getVerInt("1.1.5");

        $msg_len = strlen($message);
        $smsCount = 0;

        $smsCount = $smsCount + ceil($msg_len / 80);
        
        for ($cnt = 0; $cnt < $smsCount; $cnt++)
        {
            if ($this->args->using_point)
            {
                $oPointModel = &getModel('point');
                $rest_point = $oPointModel->getPoint($this->args->member_srl, true);
                if ($rest_point < $this->args->point_for_sms)
                {
                    $this->error_message .= "\n{$lang->warning_not_enough_point}";
                    return false;
                }
                $oPointController = &getController('point');
                if ($current_version > $v115)
                {
                    $oPointController->setPoint($this->args->member_srl, $this->args->point_for_sms, 'minus');
                }
                else
                {
                    $oPointController->setPoint($this->args->member_srl, ($rest_point - $this->args->point_for_sms));
                }
            }

            $mid = Utility::keygen();
            $smsContent = Utility::cutout($message, 80);
            $logged_info = Context::get('logged_info');
            if ($logged_info)
                $args->userid = $logged_info->user_id;
            $args->mid = $mid;
            $args->gid = $gid;
            $args->mtype = "SMS";
            $args->callno = $callno;
            $args->callback = $callback;
            $args->content = iconv("euc-kr", "utf-8//TRANSLIT", $smsContent);
            if ($reservflag == "1") {
                $args->reservflag = 'Y';
                $args->reservdate = $reservdate;
            } else {
                $args->reservflag = 'N';
            }

            $oMobilemessageController->insertMobilemessage($args);

            if ($reservflag == "1")
                $ret = $sms->addsms($callno, $callback, $smsContent, $callname, $reservdate, $mid, $gid);
            else
                $ret = $sms->addsms($callno, $callback, $smsContent, $callname, "", $mid, $gid);
            if ($ret == false)
                $errcnt++;
            $message = substr($message, strlen($smsContent));
        }
        return true;
    }

    function SendLMS($callno, $callback, $subject, $message, $callname, $reservdate, $reservflag, $gid, &$sms, &$errcnt)
    {
        global $lang;

        $oMobilemessageController = &getController("mobilemessage");
        $current_version = $oMobilemessageController->getXEVerInt();
        $v115 = $oMobilemessageController->getVerInt("1.1.5");

        if ($this->args->using_point)
        {
            $oPointModel = &getModel('point');
            $rest_point = $oPointModel->getPoint($this->args->member_srl, true);
            if ($rest_point < $this->args->point_for_lms)
            {
                $this->error_message .= "\n{$lang->warning_not_enough_point}";
                return false;
            }
            $oPointController = &getController('point');
            if ($current_version > $v115)
            {
                $oPointController->setPoint($this->args->member_srl, $this->args->point_for_lms, 'minus');
            }
            else
            {
                $oPointController->setPoint($this->args->member_srl, ($rest_point - $this->args->point_for_lms));
            }
        }

        $mid = Utility::keygen();
        $logged_info = Context::get('logged_info');
        if ($logged_info)
            $args->userid = $logged_info->user_id;
        $args->mid = $mid;
        $args->gid = $gid;
        $args->mtype = "LMS";
        $args->callno = $callno;
        $args->callback = $callback;
        $args->content = iconv("euc-kr", "utf-8//TRANSLIT", $message);
        if ($reservflag == "1") {
            $args->reservflag = 'Y';
            $args->reservdate = $reservdate;
        } else {
            $args->reservflag = 'N';
        }
        $oMobilemessageController->insertMobilemessage($args);

        if ($reservflag == "1")
            $ret = $sms->addlms($callno, $callback, $subject, $message, $callname, $reservdate, $mid, $gid);
        else
            $ret = $sms->addlms($callno, $callback, $subject, $message, $callname, "", $mid, $gid);
        if ($ret == false)
            $errcnt++;

        return true;
    }


    function OnSend()
    {
        global $lang;

        if ($this->args->using_point && (!$this->args->point_for_sms || !$this->args->point_for_lms))
        {
            $lang->success_sent = "\n[설정오류] 기본모듈의 차감 포인트 설정을 확인하세요.";
            return false;
        }

        require_once($this->args->module_path . 'coolsms.php');
        $sms = new coolsms();
        $sms->appversion("MXE/{$this->args->version} XE/" . __ZBXE_VERSION__);
        $sms->charset("euckr");
        $sms->setuser($this->args->userid, $this->args->passwd);
        $sms->emptyall();

        $callback = Utility::riddash(Context::get("cellphone_callback"));
        $text = Context::get("cellphone_text");
        $text = stripslashes($text);
        $text = str_replace("\r", "", $text);
        $reservflag = Context::get("cellphone_reservflag");
        $reservdate = Context::get("cellphone_reservdate");
        $type = Context::get("cellphone_mtype");
        $subject = "";

        // euckr로 변환
        $text = iconv("utf-8", "euc-kr//TRANSLIT", $text);

        if ($type == "LMS")
        {
            $subject = Utility::cutout($text, 20);
        }

        $phonelist = split("\n", Context::get("cellphone_telnumlist"));

        $gid = Utility::keygen();
        $errcnt = 0;
        foreach ($phonelist as $line)
        {
            $message = $text;

            $phone = split("([\W\w]*)[ ,\t\r\n]+", $line);
            if (!$phone)
                continue;

            $callno = Utility::riddash($phone[0]);
            if ($callno == "")
                continue;

            if (isset($phone[1]))
                $callname = $phone[1];
            else 
                $callname = "";

            $callname = iconv("utf-8", "euc-kr//TRANSLIT", $callname);

            if ($type == "SMS")
                $this->SendSMS($callno, $callback, $message, $callname, $reservdate, $reservflag, $gid, $sms, $errcnt);
            else if ($type == "LMS")
                $this->SendLMS($callno, $callback, $subject, $message, $callname, $reservdate, $reservflag, $gid, $sms, $errcnt);
        }

        if ($errcnt > 0)
        {
            $this->error_message .= "\n" . $sms->lasterror();
        }

        if (!$sms->connect())
        {
            $this->error_message .= "\n서버에 연결할 수 없습니다.";
        }

        if ($sms->send() == FALSE)
        {
            $this->error_message .= "\n전송한 메시지가 없습니다.";
        } else {
            $result = $sms->getr();

            $succ = 0;
            $fail = 0;
            $err_nospare = FALSE;
            foreach ($result as $row)
            {
                if ($row["RESULT-CODE"] == "00")
                    $succ++;
                else
                    $fail++;
                if ($row["RESULT-CODE"] == "30")
                    $err_nospare = TRUE;
            }
            $this->error_message .= "\n전송하였습니다. 성공: {$succ} 개, 실패: {$fail} 개";
            if ($err_nospare)
                $this->error_message .= "\n잔액이 모두 소진되었습니다.";

        }
        $sms->disconnect();
        $sms->emptyall();

        $lang->success_sent = $this->error_message;

        return true;
    }
}
?>
