jQuery(document).ready(function (){

	jQuery("#authcode_resend").hide();

});

function authcodeSend()
{
	var params = new Array();	
	var responses = ['error','message','authentication_srl','authcode_mid','message_id'];

	params['module'] = 'authentication';
	params['country'] = jQuery("#country").val();
	params['phone_1'] = jQuery("#phone_1").val();
	params['phone_2'] = jQuery("#phone_2").val();
	params['phone_3'] = jQuery("#phone_3").val();
	params['authcode_mid'] = jQuery("#authcode_mid").val();
	params['message_id'] = ''; 

	if(!params['country'] || !params['phone_1'] || !params['phone_2'] || !params['phone_3'])
	{
		alert ("국가 및 휴대폰 번호를 전부 입력해주세요."); 
		return false;
	}

	jQuery("#authcode_send").hide();
	jQuery("#authcode_resend").show();

	exec_xml('authentication', 'procAuthenticationSendAuthCode', params, completeSend, responses);
}

function completeSend(ret_obj)
{
	setCookie('message_id', ret_obj['message_id']);
	setCookie('authentication_srl', ret_obj['authentication_srl']);
	setCookie('authcode_mid', ret_obj['authcode_mid']);
	message_id = parseInt(ret_obj['message_id'], 10);
	
	jQuery("#authcode_box").html('');
	jQuery("#authcode_box_2").html('');

	jQuery("#authcode_box").html('');
	jQuery("#authcode_box_2").html('');

	jQuery("#authcode_box").append('<form method="post" action="./"><input type="hidden" name="act" value="procAuthenticationCompare" /><input type="hidden" name="authentication_srl" value='+getCookie('authentication_srl')+' /><input type="hidden" name="authcode_mid" value="'+getCookie('authcode_mid')+'" />인증번호를 입력하세요. <input type="text" id="authcode" name="authcode" /><input type="submit" value="확인" /></form>');

}

function updateStatus()
{
	jQuery("#footer").html('');
	jQuery("#footer").append('<div><span>확인중...</span></div>');
	message_id = getCookie('message_id');

	var params = new Array();	
	var responses = ['error','message', 'result'];

	params['message_id'] = message_id;

	exec_xml('authentication', 'procAuthenticationUpdateStatus', params, completeUpdate, responses);
}

function completeUpdate(ret_obj)
{
	message_id = getCookie('message_id');

	r_status = ret_obj['result']['STATUS'];
	r_code = ret_obj['result']['RESULT-CODE'];

	r_code = parseInt(r_code, 10);

	if(r_status == 2 && r_code == 00)
	{
		jQuery("#footer").html('');
		jQuery("#footer").append('<div><span>전송완료. 만약 메시지가 도착하지 않으셨다면 스팸함을 확인해주세요.</span></div>');
	}

	else if(r_status == 2 && r_code != 0)
	{
		switch(r_code)
		{
			case 10:
			jQuery("#footer").html('잘못된 번호입니다. 번호를 확인해주세요.');
			break;

			case 11:
			jQuery("#footer").html('상위 서비스망이 스팸 인식됬습니다.');
			break;

			case 12:
			jQuery("#footer").html('이통사 전송불가 입니다.');
			break;

			case 13:
			jQuery("#footer").html('필드값이 누락 되었습니다.');
			break;

			case 20:
			jQuery("#footer").html('등록된 계정이 아니거나 패스워드가 틀립니다.');
			break;

			case 21:
			jQuery("#footer").html('존재하지 않는 메시지 입니다.');
			break;

			case 30:
			jQuery("#footer").html('가능한 전송 잔량이 없습니다.');
			break;

			case 40:
			jQuery("#footer").html('전송시간 초과입니다.');
			break;

			case 41:
			jQuery("#footer").html('단말기 Busy');
			break;

			case 42:
			jQuery("#footer").html('음영지역 입니다.');
			break;

			case 43:
			jQuery("#footer").html('단말기 파워 오프 입니다.');
			break;

			case 44:
			jQuery("#footer").html('단말기 메시지 저장갯수 초과 입니다.');
			break;

			case 45:
			jQuery("#footer").html('단말기 일시 서비스 정지 입니다.');
			break;

			case 46:
			jQuery("#footer").html('기타 단말기 문제 입니다.');
			break;

			case 47:
			jQuery("#footer").html('착신 거절 입니다.');
			break;

			case 48:
			jQuery("#footer").html('Unkonwn error');
			break;

			case 49:
			jQuery("#footer").html('Format Error');
			break;

			case 50:
			jQuery("#footer").html('sms서비스 불가 단말기 입니다.');
			break;

			case 51:
			jQuery("#footer").html('착신측의 호불가 상태 입니다.');
			break;
			
			case 52:
			jQuery("#footer").html('이통사 서버 운영자 삭제 입니다.');
			break;

			case 53:
			jQuery("#footer").html('서버 메시지 Que Full');
			break;

			case 54:
			jQuery("#footer").html('스팸인식입니다.');
			break;

			case 55:
			jQuery("#footer").html('스팸, nospam.or.kr에 등록된 번호 입니다.');
			break;

			case 56:
			jQuery("#footer").html('전송실패(무선망단) 입니다.');
			break;

			case 57:
			jQuery("#footer").html('전송실패(무선망->단말기단) 입니다.');
			break;

			case 58:
			jQuery("#footer").html('전송경로가 없습니다.');
			break;

			case 60:
			jQuery("#footer").html('취소하셨습니다.');
			break;

			case 70:
			jQuery("#footer").html('허용되지 않은 IP주소 입니다.');
			break;

			case 99:
			jQuery("#footer").html('대기상태입니다.');
			break;
		}

	}

	else
	{
		setTimeout("updateStatus()", 2000);
	}
}

