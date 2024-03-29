function post_validation() {
	// 부모창이 닫혔는지 검사
	if (!window.opener || window.opener.closed)
	{
		alert("부모창이 닫혔습니다. 더 이상 진행할 수 없습니다.");
		return;
	}

	// 회원가입폼 가져오기
	f = window.opener.document.getElementById("fo_insert_member");

	// 회원가입폼 존재 확인
	if (!f)
	{
		alert("[오류] 회원가입폼이 존재하지 않습니다.");
		return;
	}

	// 인증번호 대입
	jQuery('input[name='+validationcode_fieldname+']',f).val(validation_code);

	// set country code
	if (countrycode_fieldname != "") {
		objs = window.opener.document.getElementsByName(countrycode_fieldname);
		if (objs.length > 0) {
			objs[0].value = countrycode;

			// set flag
			obj = window.opener.document.getElementById("phoneNumberValidatorFlag");
			for (i = 0; i < obj.options.length; i++) {
				if (obj.options[i].value == countrycode) {
					obj.options[i].selected = true;
					break;
				}
			}
		}
	}

	// 폰번호 필드 가져오기
	//objs = window.opener.document.getElementsByName("{$phonenumber_fieldname}");
	objs = jQuery('input[name^=' +phonenumber_fieldname+ ']', window.opener.document);

	// 폰번호 필드 존재 확인
	if (objs.length < 3)
	{
		alert("[오류] 부모창에 핸드폰번호 필드가 존재하지 않습니다. 모듈설정에서 핸드폰번호 필드명이 제대로 입력되었는지 확인하세요.");
		return;
	}

	// 폰번호 대입
	objs[0].value = phonenumber1;
	objs[1].value = phonenumber2;
	objs[2].value = phonenumber3;

	alert("인증번호를 확인하였습니다. 계속 진행하세요.");
	window.close();
}

(function($) {
	jQuery(function($) {
		post_validation();
	});
}) (jQuery);
