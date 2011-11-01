<?php
    /**
     * vi:set ts=4 sw=4 expandtab enc=utf8:
     * @file   ko.lang.php
     * @author diver(diver@coolsms.co.kr)
     * @brief  한국어
     **/
    $lang->user_id = '아이디';
    $lang->user_name = '이름';
    $lang->phone_number = '폰번호';
    $lang->cellphone = '핸드폰';
    $lang->cellphone_number = '핸드폰 번호';
    $lang->cmd_delete = '삭제';
    $lang->cmd_remove_photo = '사진삭제';
    $lang->total = '전체';
    $lang->is_admin = '최고관리 권한';
    $lang->group = '소속 그룹';
    $lang->denied = '사용중지';
    $lang->search_target = '검색대상';
    $lang->search_target_list = array(
        'user_id' => '아이디',
        'user_name' => '이름',
        'nick_name' => '닉네임',
        'email_address' => '메일 주소',
        'regdate' => '가입일시',
        'regdate_more' => '가입일시(이상)',
        'regdate_less' => '가입일시(이하)',
        'last_login' => '최근 로그인 일시',
        'last_login_more' => '최근 로그인 일시(이상)',
        'last_login_less' => '최근 로그인 일시(이하)',
        'extra_vars' => '확장변수',
    );
    $lang->available_sms_number = '가능 SMS 건수: ';
    $lang->arranged_sms_number = '예정 SMS 건수: ';
    $lang->available_lms_number = '가능 LMS 건수: ';
    $lang->arranged_lms_number = '예정 LMS 건수: ';
    $lang->available_mms_number = '가능 MMS 건수: ';
    $lang->arranged_mms_number = '예정 MMS 건수: ';
    $lang->reservation_datetime = '예약일시';
    $lang->th_reserv_datetime = '예약일시';
    $lang->direct_send = '바로전송';
    $lang->reserv_send = '예약전송';
    $lang->number_to_send = '전송할 건수: ';
    $lang->new_folder = '새 폴더';
    $lang->reserv_send_datetime = '예약전송시간';
    $lang->hour = '시';
    $lang->min = '분';
    $lang->period = '기간';
    $lang->regdate = '등록일';
    $lang->recv_status = '수신상태';
    $lang->status = '상태';
    $lang->result = '결과';
    $lang->msg_content = '메시지 내용';
    $lang->recv_number = '수신번호';
    $lang->callback_number = '회신번호';
    $lang->type = '형식';
    $lang->accept_datetime = '접수일시';
    $lang->message_id = '메시지ID';
    $lang->subject = '제목';
    $lang->carrier = '이통사';
    $lang->recv_datetime = '전송일시';
    $lang->sms = 'SMS';
    $lang->lms = 'MMS장문';
    $lang->mms = 'MMS포토';

    // message
    $lang->msg_input_country_code = '국가번호를 입력하세요';
    $lang->msg_input_phone_number = '전화번호를 입력하세요';
    $lang->msg_inter_not_support = '해외문자를 지원하지 않습니다';
    $lang->msg_check_address_item = '체크된 주소록이 없습니다.\n왼쪽 주소록목록에서 선택해주세요.';
    $lang->msg_persons_added = ' 명을 추가했습니다';
    $lang->msg_no_checked_address_item = '체크된 폴더가 없습니다.\n왼쪽 폴더목록에서 체크박스에 체크하세요.'; 
    $lang->msg_no_checked_folder = '선택된 폴더가 없습니다. 주소록 폴더를 선택하세요.';
    $lang->msg_persons_registered = ' 명을 등록하였습니다.';
    $lang->msg_input_content = '내용을 입력해 주세요';
    $lang->msg_input_recipient = '받는 사람을 입력해 주세요';
    $lang->msg_input_sender = '보내는사람의 전화번호를 정확히 입력하세요\n입력 예) 15881004 , 021231234, 0101231234';
    $lang->msg_bytes_limit = ' 바이트 이상의 메세지는 전송하실 수 없습니다.';
    $lang->msg_accepted = '접수하였습니다';
    $lang->msg_check_result_in_history = '전송결과는 전송내역에서 확인하세요';
    $lang->msg_set_point = '위젯설정에서 포인트 차감 사용으로 되어 있으나 차감할 포인트가 설정되어있지 않습니다.';
    $lang->msg_not_enough_money = '사용가능한 잔액이 모자랍니다. 취소를 선택하고 충전 후 사용하세요';
    $lang->msg_not_enough_point = '포인트가 부족합니다.';
    $lang->msg_will_you_try = '전송을 시도하시겠습니까?';
    $lang->msg_will_you_send = '전송하시겠습니까?';
    $lang->msg_invalid_number = '유효하지 않은 전화번호입니다';
    $lang->msg_will_you_continue = '계속 진행하시겠습니까?';
    $lang->msg_already_exists = '이미 추가된 전화번호입니다';
    $lang->msg_choose_folder_to_move = '이동할 폴더를 선택하세요';
    $lang->msg_choose_folder_to_copy = '복사할 폴더를 선택하세요';
    $lang->msg_choose_folder_to_modify = '수정할 폴더를 선택하세요';
    $lang->msg_choose_folder_to_remove = '삭제할 폴더를 선택하세요';
    $lang->msg_choose_folder_to_add = '명단을 추가할 폴더를 선택하세요';
    $lang->msg_choose_person_to_move = '이동할 명단을 선택하세요';
    $lang->msg_check_person_to_copy = '복사할 명단을 체크하세요';
    $lang->msg_check_person_to_remove = '삭제할 명단을 체크하세요';
    $lang->msg_will_you_move = '%u건의 명단을 [%s]폴더로 옮기겠습니까?';
    $lang->msg_will_you_copy = '%u건의 명단을 [%s]폴더로 복사하시겠습니까?';
    $lang->msg_will_you_remove = '%u건의 명단을 삭제하시겠습니까?';
    $lang->msg_canceled = '취소되었습니다.';
    $lang->msg_no_selected_folder = '선택된 폴더가 없습니다.';
    $lang->msg_input_name = '이름을 입력하세요.';
    $lang->msg_input_phone_number = $lang->phone_number . '를 입력하세요.';
    $lang->msg_are_you_sure_to_remove_folder = '하위 모든 폴더 및 정보가 삭제됩니다.\n정말 삭제하시겠습니까?';
    $lang->msg_no_selected_item = '선택된 항목이 없습니다.';
    $lang->msg_will_you_request_cancel = '취소요청을 하시겠습니까?';
    $lang->msg_request_cancel_complete = '취소요청을 완료하였습니다.';
    $lang->msg_dup_deleted = '중복번호 %u 개를 제거하였습니다.';
    $lang->msg_login_required = '로그인 후 사용하실 수 있습니다';
?>
