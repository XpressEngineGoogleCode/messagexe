<?php
    /**
     * vi:set ts=4 sw=4 expandtab fileencoding=utf8:
     * @file   ko.lang.php
     * @author diver(diver@coolsms.co.kr)
     * @brief  한국어
     **/

    $lang->cellphone = '핸드폰';    // 휴대폰으로 표기되길 원하시면 '핸드폰'을 '휴대폰'으로 수정하세요.
    $lang->phone_number = '폰번호';
    $lang->cash = '캐쉬';
    $lang->point = '포인트';
    $lang->mdrop = '문자방울';
    $lang->cellphone_number = "{$lang->cellphone}번호";
    $lang->validation_code = '인증번호';
    $lang->serviceid = "서비스ID";
    $lang->password = "비밀번호";
    $lang->cellphone_fieldname = "{$lang->phone_number} 필드";
    $lang->validationcode_fieldname = "인증번호 필드";
    $lang->countrycode = "국가번호";
    $lang->countrycode_fieldname = "국가번호 필드";
    $lang->mobilemessage = "{$lang->cellphone} 문자";
    $lang->sender = '발송자';
    $lang->receiver = '받는사람';
    $lang->callno = '수신번호';
    $lang->callback = '회신번호';
    $lang->default_callback = '기본 회신번호';
    $lang->default_country = '기본 국가번호';
    $lang->content = '메시지 내용';
    $lang->mstat = '전송상태';
    $lang->rcode = '결과코드';
    $lang->senddate = '전송(취소)일시';
    $lang->carrier = '이통사';
    $lang->regdate = '등록일시';
    $lang->mtype = '분류';
    $lang->limit_date = '제한일';
    $lang->memo = '메모';
    $lang->reservation_date = '예약일시';
    $lang->mon = '월';
    $lang->tue = '화';
    $lang->wed = '수';
    $lang->thu = '목';
    $lang->fri = '금';
    $lang->sat = '토';
    $lang->sun = '일';

    $lang->stat_wait = '대기중';
    $lang->stat_incarry = '전송중';
    $lang->stat_complete = '전송완료';

    //$lang->success_sent = '전송을 완료하였습니다.';
    $lang->success_valcode = '인증번호를 발송하였습니다.';
    $lang->success_canceled = '취소 요청을 완료하였습니다.';
    $lang->cancel_failed = '취소작업중 오류가 발생하였습니다.';
    $lang->login_required = '먼저 로그인 하세요.';
    $lang->warning_check_setuser = '서비스ID 및 패스워드 설정을 확인하세요.';
    $lang->warning_connot_connect = '서버에 접속할 수 없습니다. 나중에 다시 시도해 주세요.';
    $lang->warning_no_record_to_sync = '동기화할 데이터가 없습니다.';
    $lang->warning_not_enough_point = '포인트가 부족합니다.';
    $lang->error_cannot_connect = '서버에 연결할 수 없습니다.';

    $lang->cmd_mobilemessage_texting = '문자 전송';
    $lang->cmd_mobilemessage_logview = '전송 내역';
    $lang->cmd_mapping_list = '매핑 정보';
    $lang->cmd_report_all = '전송결과 동기화';
    $lang->cmd_delete = '삭제';
    $lang->cmd_append = '추가';
    $lang->cmd_reserv_cancel = '예약취소';
    $lang->cmd_find_id = '아이디 찾기';
    $lang->cmd_find_pw = '비밀번호 찾기';
    $lang->cmd_change_pw = '비밀번호 변경';
    $lang->cmd_recv_valcode = '인증번호 받기';
    $lang->cmd_prohibit = '금지 번호';
    $lang->cmd_notidoc_setup = '새글알림설정';
    $lang->cmd_noticom_setup = '댓글알림설정';
    $lang->cmd_welcome = '가입 환영';
    $lang->cmd_attach_photo = '사진첨부';

    $lang->msg_please_wait = '잠시만 기다려주세요...';
    $lang->msg_prohibited_number = '이전에 가입했던 번호이거나 관리자에 의해 금지된 번호입니다. 관리자에게 문의하세요.';
    $lang->msg_invalid_ticket = '올바르지 않은 티켓값입니다.';
    $lang->msg_invalid_file_format = 'JPG, GIF, PNG 등 그림파일만 업로드 가능합니다.';
    $lang->msg_callback_limit = '등록갯수를 초과하였습니다. 5개 이상 등록하실 수 없습니다.';
    $lang->msg_login_required = '로그인 후 사용하실 수 있습니다';
    $lang->msg_folder_shared = '공유되었습니다';
    $lang->msg_folder_unshared = '공유해제되었습니다';
    $lang->msg_exist_shared_folder = '이미 공유되었습니다';
    $lang->msg_cannot_share_oneself = '자신에게 공유할 수 없습니다';
    $lang->msg_cannot_share_root = '공유할 수 없습니다';
    $lang->msg_no_permission_to_share = '공유할 수 있는 권한이 없습니다';
    $lang->msg_no_permission = '권한이 없습니다';
    $lang->msg_cannot_create_folder = '폴더를 생성할 수 없습니다';

    $lang->about_mobilemessage = 'SMS, LMS, MMS 등의 모바일 메시지와 연계하여 XE 관리자 및 사용자 편의를 제공합니다.';
    $lang->about_sendsms = '직접추가 혹은 [주소록]에서 추가된 발송대상에 대해서는 이름 %name% 외의 치환변수는 적용되지 않습니다.';
    $lang->about_logview = '전송결과가 자동으로 갱신되지 않으면 기본설정에서 [기본 URL 설정] 항목이 제대로 입력되어 있는지 확인하세요.';
    $lang->about_mappinglist = '아이디와 폰번호의 일치여부를 판별하기 위한 기본데이터로 아이디, 비밀번호 찾기 등에 사용됩니다.<br />회원가입, 탈퇴, 정보수정 할 때 자동으로 갱신됩니다.';
    $lang->about_prohibit = '핸드폰인증 애드온 사용시 해당되는 내용입니다.<br />금지번호로 등록된 폰번호로 회원가입을 할 수 없도록 합니다.';

    $lang->about_serviceid = '<a href="http://www.coolsms.co.kr/acct/signup.php" target="_blank">www.coolsms.co.kr</a> 에서 회원가입한 아이디를 입력합니다.';
    $lang->about_service_password = '<a href="http://www.coolsms.co.kr/acct/signup.php" target="_blank">www.coolsms.co.kr</a> 에서 회원가입때 입력한 비밀번호를 입력합니다.';
    $lang->about_cellphone_fieldname = "회원의 폰번호를 받기위해 생성하신 추가필드의 입력항목이름을 입력해 주세요.<br />만약 핸드폰번호를 받기위한 추가필드를 생성하지 않으셨다면 <a href=\"?module=admin&act=dispMemberAdminJoinFormList\">가입폼관리</a>에서 전화번호형식(phone)으로 추가해 주세요.";
    $lang->about_validationcode_fieldname = "핸드폰인증 애드온 사용시 반드시 설정해야할 항목입니다. 매뉴얼을 참고하세요.<br />핸드폰인증 애드온을 사용하지 않으신다면 비워두세요.";
    $lang->about_countrycode_fieldname = "회원별 국가번호가 저장될 수 있도록 <a href=\"?module=admin&act=dispMemberAdminJoinFormList\">가입폼관리</a>에서 한줄입력칸(text)형식으로 추가해 주세요. 해외인증 혹은 국제문자를 사용하지 않으면 비워두세요.";

    // 핸드폰인증
    $lang->cmd_receive_validation_code = '인증번호받기';
    $lang->about_input_phone_number = "인증받을 {$lang->cellphone}번호를 입력합니다.";
    $lang->alert_check_cellphone_fieldname = "[설정오류] {$lang->cellphone}번호 필드명 설정을 확인하세요.";
    $lang->alert_no_cellphone_fieldname_input = "[설정오류] {$lang->cellphone}번호 필드명이 입력되지 않았습니다.";
    $lang->alert_no_validation_fieldname_input = '[설정오류] 인증번호 필드명이 입력되지 않았습니다.';
    $lang->alert_no_validation_code_match = '인증번호가 일치하지 않습니다. 다시 입력하세요.';
?>
