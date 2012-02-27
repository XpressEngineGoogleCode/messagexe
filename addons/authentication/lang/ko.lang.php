<?php
    /**
     * vi:set ts=4 sw=4 expandtab enc=utf8:
     * @file   ko.lang.php
     * @author diver(diver@coolsms.co.kr)
     * @brief  한국어
     **/

    $lang->cellphone = '핸드폰';    // 휴대폰으로 표기되길 원하시면 '핸드폰'을 '휴대폰'으로 수정하세요.

    // 핸드폰인증
    $lang->validation = '인증';
    $lang->cellphone_validation = $lang->cellphone . $lang->validation;

    $lang->cmd_input_phone_number = "{$lang->cellphone}번호입력";
    $lang->cmd_input_authcode = '인증번호입력';
    $lang->cmd_receive_validation_code = '인증번호받기';

    $lang->desc_input_phone_number = "인증받을 {$lang->cellphone}번호를 입력합니다.";
    $lang->alert_check_cellphone_fieldname = "[설정오류] {$lang->cellphone}번호 필드명 설정을 확인하세요.";
    $lang->alert_no_cellphone_fieldname_input = "[설정오류] {$lang->cellphone}번호 필드명이 입력되지 않았습니다.";
    $lang->alert_no_validation_fieldname_input = '[설정오류] 인증번호 필드명이 입력되지 않았습니다.';
    $lang->alert_no_validation_code_match = '인증번호가 일치하지 않습니다. 다시 입력하세요.';
    $lang->alert_module_not_installed = 'MessageXE 기본모듈이 설치되어있지 않습니다.';
    $lang->alert_check_field_names = '[설정오류] 기본모듈의 핸드폰번호 필드와 인증번호 필드 설정을 확인하세요.';
    $lang->alert_no_match_phone_valcode = '폰번호와 인증번호가 일치하지 않습니다.';
    $lang->msg_wait_for_resend = '초 이내에 재전송할 수 없습니다. 잠시만 기다려 주세요.';
    $lang->msg_already_registered = "이미 등록된 {$lang->cellphone}입니다.";
    $lang->msg_prohibited_number = '이전에 가입했던 번호이거나 관리자에 의해 금지된 번호입니다. 관리자에게 문의하세요.';
?>
