function delCookie(name, path) //cookie 삭제 
{
	alert(name);
	document.cookie = name + "="
		+ ((path == null) ? "" : "; path=" + path)
		+ ""
		+ "; expires=Thu, 01-Jan-70 00:00:01 GMT";
}
/*
jQuery(document).ready(function (){

	exec_xml('authentication', 'procAuthenticationUpdateStatus', params, completeUpdate, responses);

});
*/
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

	jQuery("#authcodesend").html('<button onclick="authcodeSend(); return false;" id="submit_authcode" >다시 보내기</button>');

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
	jQuery("#authcode_box_3").html('');
	jQuery("#authcode_box_3").append('<div><span>확인중...</span></div>');
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

	if(r_status == 2 && r_code == 00)
	{
		jQuery("#authcode_box_3").html('');
		jQuery("#authcode_box_3").append('<div><span>이상없음.</span></div>');
	}

	else if(r_status == 2 && r_code != 0)
	{
		r_code = parseInt(r_code, 10);
		switch(r_code)
		{
			case 10:
			jQuery("#authcode_box_3").html('잘못된 번호');
			break;

			case 11:
			jQuery("#authcode_box_3").html('상위 서비스망 스팸 인식됨');
			break;

			case 12:
			jQuery("#authcode_box_3").html('이통사 전송불가');
			break;

			case 13:
			jQuery("#authcode_box_3").html('필드값 누락');
			break;

			case 20:
			jQuery("#authcode_box_3").html('등록된 계정이 아니거나 패스워드 틀림');
			break;

			case 21:
			jQuery("#authcode_box_3").html('존재하지 않는 메시지');
			break;

			case 30:
			jQuery("#authcode_box_3").html('가능한 전송 잔량이 없음');
			break;

			case 40:
			jQuery("#authcode_box_3").html('전송시간 초과');
			break;

			case 41:
			jQuery("#authcode_box_3").html('단말기 Busy');
			break;

			case 42:
			jQuery("#authcode_box_3").html('음영지역');
			break;

			case 43:
			jQuery("#authcode_box_3").html('단말기 파워 오프');
			break;

			case 44:
			jQuery("#authcode_box_3").html('단말기 메시지 저장갯수 초과');
			break;

			case 45:
			jQuery("#authcode_box_3").html('단말기 일시 서비스 정지');
			break;

			case 46:
			jQuery("#authcode_box_3").html('기타 단말기 문제');
			break;

			case 47:
			jQuery("#authcode_box_3").html('착신 거절');
			break;

			case 48:
			jQuery("#authcode_box_3").html('Unkonwn error');
			break;

			case 49:
			jQuery("#authcode_box_3").html('Format Error');
			break;

			case 50:
			jQuery("#authcode_box_3").html('sms서비스 불가 단말기');
			break;

			case 51:
			jQuery("#authcode_box_3").html('착신측의 호불가 상태');
			break;
			
			case 52:
			jQuery("#authcode_box_3").html('이통사 서버 운영자 삭제');
			break;

			case 53:
			jQuery("#authcode_box_3").html('서버 메시지 Que Full');
			break;

			case 54:
			jQuery("#authcode_box_3").html('스팸인식');
			break;

			case 55:
			jQuery("#authcode_box_3").html('스팸, nospam.or.kr에 등록된 번호');
			break;

			case 56:
			jQuery("#authcode_box_3").html('전송실패(무선망단)');
			break;

			case 57:
			jQuery("#authcode_box_3").html('전송실패(무선망->단말기단)');
			break;

			case 58:
			jQuery("#authcode_box_3").html('전송경로 없음.');
			break;

			case 60:
			jQuery("#authcode_box_3").html('취소');
			break;

			case 70:
			jQuery("#authcode_box_3").html('허용되지 않은 IP주소');
			break;

			case 99:
			jQuery("#authcode_box_3").html('대기상태');
			break;
		}

	}

	else
	{
		setTimeout("updateStatus()", 2000);
	}
}

