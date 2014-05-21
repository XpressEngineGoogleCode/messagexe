	/*
jQuery(document).ready(function (){
	var today = new Date();
	time_now = today.format("yymmddHHMMss");
	time_now = parseInt(time_now);
});
	*/

function getAuthCode(input_id)
{
	// check user_id
	if(input_id)
	{
		var user_id = jQuery('#user_id').val();
		if(!user_id.length)
		{
			alert ("아이디를 입력하세요."); 
			jQuery('#user_id').focus();
			return false;
		}
	}

	// check country code
	var country_code = jQuery("#country_code").val();
	if(!country_code.length)
	{
		alert ("국가를 선택해 주세요."); 
		jQuery('#country_code').focus();
		return false;
	}

	// check phone number
	var phonenum = '';
	jQuery("#phone input[name^=phonenum]").each(
		function()
		{
			$cell = jQuery(this);
			var cellval = $cell.val();
			if(!cellval.length)
	   		{
				alert('휴대폰번호를 입력하세요.');
				$cell.focus();
				phonenum = '';
				return false;
			}
			phonenum += cellval;
		}
	);
	if(!phonenum.length) return false;

	// call an ajax to get the auth-code 
	var params = new Array();
	var responses = ['error','message','authentication_srl','message_id'];
	params['module'] = 'authentication';
	if(user_id) params['user_id'] = user_id;
	params['phonenum'] = phonenum;
	params['country_code'] = country_code;
	exec_xml('authentication', 'procAuthenticationSendAuthCode', params, completeSendAuthCode, responses);
}

function completeSendAuthCode(ret_obj)
{
	setCookie('authentication_srl', ret_obj['authentication_srl']);

	jQuery('#get_authcode').addClass('reget');
	jQuery('#message_id').val(ret_obj['message_id']);
	jQuery('#authentication_srl').val(ret_obj['authentication_srl']);

	alert(ret_obj['message']);

	jQuery('#authcode').focus();
}

function updateStatus()
{
	message_id = jQuery('#message_id').val();
	if(!message_id)
	{
		alert('인증번호 받기 버튼을 클릭하세요. 인증번호 전송 후 확인이 가능합니다.');
		return false;
	}
	else
	{
		jQuery("#footer #notice").html('확인중...');

		var params = new Array();	
		var responses = ['error','message', 'result'];

		params['message_id'] = message_id;

		exec_xml('authentication', 'procAuthenticationUpdateStatus', params, completeUpdate, responses);
	}
}

function completeUpdate(ret_obj)
{
	var r_status = ret_obj['result']['STATUS'];
	var r_code = ret_obj['result']['RESULT-CODE'];
	var r_code = parseInt(r_code, 10);
	var $notice = jQuery('#footer #notice');

	if(r_status == 2 && r_code == 00)
	{
		$notice.html('전송완료. 만약 메시지가 도착하지 않으셨다면 스팸함을 확인해주세요.');
	}

	else if(r_status == 2 && r_code != 0)
	{
		switch(r_code)
		{
			case 10:
			$notice.html('잘못된 번호입니다. 번호를 확인해주세요.');
			break;

			case 11:
			$notice.html('상위 서비스망이 스팸 인식됬습니다.');
			break;

			case 12:
			$notice.html('이통사 전송불가 입니다.');
			break;

			case 13:
			$notice.html('필드값이 누락 되었습니다.');
			break;

			case 20:
			$notice.html('등록된 계정이 아니거나 패스워드가 틀립니다.');
			break;

			case 21:
			$notice.html('존재하지 않는 메시지 입니다.');
			break;

			case 30:
			$notice.html('가능한 전송 잔량이 없습니다.');
			break;

			case 31:
			$notice.html('전송할 수 없는 번호입니다.');
			break;

			case 32:
			$notice.html('미가입자 입니다.');
			break;

			case 40:
			$notice.html('전송시간 초과입니다.');
			break;

			case 41:
			$notice.html('단말기 Busy');
			break;

			case 42:
			$notice.html('음영지역 입니다.');
			break;

			case 43:
			$notice.html('단말기 파워 오프 입니다.');
			break;

			case 44:
			$notice.html('단말기 메시지 저장갯수 초과 입니다.');
			break;

			case 45:
			$notice.html('단말기 일시 서비스 정지 입니다.');
			break;

			case 46:
			$notice.html('기타 단말기 문제 입니다.');
			break;

			case 47:
			$notice.html('착신 거절 입니다.');
			break;

			case 48:
			$notice.html('Unkonwn error');
			break;

			case 49:
			$notice.html('Format Error');
			break;

			case 50:
			$notice.html('sms서비스 불가 단말기 입니다.');
			break;

			case 51:
			$notice.html('착신측의 호불가 상태 입니다.');
			break;
			
			case 52:
			$notice.html('이통사 서버 운영자 삭제 입니다.');
			break;

			case 53:
			$notice.html('서버 메시지 Que Full');
			break;

			case 54:
			$notice.html('스팸인식입니다.');
			break;

			case 55:
			$notice.html('스팸, nospam.or.kr에 등록된 번호 입니다.');
			break;

			case 56:
			$notice.html('전송실패(무선망단) 입니다.');
			break;

			case 57:
			$notice.html('전송실패(무선망->단말기단) 입니다.');
			break;

			case 58:
			$notice.html('전송경로가 없습니다.');
			break;

			case 60:
			$notice.html('취소하셨습니다.');
			break;

			case 70:
			$notice.html('허용되지 않은 IP주소 입니다.');
			break;

			case 99:
			$notice.html('대기상태입니다.');
			break;
		}

	}

	else
	{
		setTimeout("updateStatus()", 2000);
	}
}

function completeVerifyAuthcode(ret_obj)
{
	alert(ret_obj['message']);
	location.reload();
}

function verifyAuthCode()
{
	message_id = jQuery('#message_id').val();
	if(!message_id)
	{
		alert('인증번호 받기 버튼을 클릭하세요. 인증번호 전송 후 확인이 가능합니다.');
		return false;
	}

	var params = new Array();
	var responses = ['error','message'];

	params['authentication_srl'] = jQuery("#authentication_srl").val();
	params['authcode'] = jQuery('#authcode').val();
	params['accept_agreement'] = 'Y'; // 약관 체크
	var user_id = jQuery('#user_id').val();
	if(user_id) params['user_id'] = user_id;

	exec_xml('authentication', 'procAuthenticationVerifyAuthCode', params, completeVerifyAuthcode, responses);
}



/*
 * date 함수  사용법은 http://blog.stevenlevithan.com/archives/date-time-format
 */

/*
var dateFormat = function () {
	var	token = /d{1,4}|m{1,4}|yy(?:yy)?|([HhMsTt])\1?|[LloSZ]|"[^"]*"|'[^']*'/g,
		timezone = /\b(?:[PMCEA][SDP]T|(?:Pacific|Mountain|Central|Eastern|Atlantic) (?:Standard|Daylight|Prevailing) Time|(?:GMT|UTC)(?:[-+]\d{4})?)\b/g,
		timezoneClip = /[^-+\dA-Z]/g,
		pad = function (val, len) {
			val = String(val);
			len = len || 2;
			while (val.length < len) val = "0" + val;
			return val;
		};

	// Regexes and supporting functions are cached through closure
	return function (date, mask, utc) {
		var dF = dateFormat;

		// You can't provide utc if you skip other args (use the "UTC:" mask prefix)
		if (arguments.length == 1 && Object.prototype.toString.call(date) == "[object String]" && !/\d/.test(date)) {
			mask = date;
			date = undefined;
		}

		// Passing date through Date applies Date.parse, if necessary
		date = date ? new Date(date) : new Date;
		if (isNaN(date)) throw SyntaxError("invalid date");

		mask = String(dF.masks[mask] || mask || dF.masks["default"]);

		// Allow setting the utc argument via the mask
		if (mask.slice(0, 4) == "UTC:") {
			mask = mask.slice(4);
			utc = true;
		}

		var	_ = utc ? "getUTC" : "get",
			d = date[_ + "Date"](),
			D = date[_ + "Day"](),
			m = date[_ + "Month"](),
			y = date[_ + "FullYear"](),
			H = date[_ + "Hours"](),
			M = date[_ + "Minutes"](),
			s = date[_ + "Seconds"](),
			L = date[_ + "Milliseconds"](),
			o = utc ? 0 : date.getTimezoneOffset(),
			flags = {
				d:    d,
				dd:   pad(d),
				ddd:  dF.i18n.dayNames[D],
				dddd: dF.i18n.dayNames[D + 7],
				m:    m + 1,
				mm:   pad(m + 1),
				mmm:  dF.i18n.monthNames[m],
				mmmm: dF.i18n.monthNames[m + 12],
				yy:   String(y).slice(2),
				yyyy: y,
				h:    H % 12 || 12,
				hh:   pad(H % 12 || 12),
				H:    H,
				HH:   pad(H),
				M:    M,
				MM:   pad(M),
				s:    s,
				ss:   pad(s),
				l:    pad(L, 3),
				L:    pad(L > 99 ? Math.round(L / 10) : L),
				t:    H < 12 ? "a"  : "p",
				tt:   H < 12 ? "am" : "pm",
				T:    H < 12 ? "A"  : "P",
				TT:   H < 12 ? "AM" : "PM",
				Z:    utc ? "UTC" : (String(date).match(timezone) || [""]).pop().replace(timezoneClip, ""),
				o:    (o > 0 ? "-" : "+") + pad(Math.floor(Math.abs(o) / 60) * 100 + Math.abs(o) % 60, 4),
				S:    ["th", "st", "nd", "rd"][d % 10 > 3 ? 0 : (d % 100 - d % 10 != 10) * d % 10]
			};

		return mask.replace(token, function ($0) {
			return $0 in flags ? flags[$0] : $0.slice(1, $0.length - 1);
		});
	};
}();

// Some common format strings
dateFormat.masks = {
	"default":      "ddd mmm dd yyyy HH:MM:ss",
	shortDate:      "m/d/yy",
	mediumDate:     "mmm d, yyyy",
	longDate:       "mmmm d, yyyy",
	fullDate:       "dddd, mmmm d, yyyy",
	shortTime:      "h:MM TT",
	mediumTime:     "h:MM:ss TT",
	longTime:       "h:MM:ss TT Z",
	isoDate:        "yyyy-mm-dd",
	isoTime:        "HH:MM:ss",
	isoDateTime:    "yyyy-mm-dd'T'HH:MM:ss",
	isoUtcDateTime: "UTC:yyyy-mm-dd'T'HH:MM:ss'Z'"
};

// Internationalization strings
dateFormat.i18n = {
	dayNames: [
		"Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat",
		"Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"
	],
	monthNames: [
		"Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec",
		"January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"
	]
};

// For convenience...
Date.prototype.format = function (mask, utc) {
	return dateFormat(this, mask, utc);
};

*/