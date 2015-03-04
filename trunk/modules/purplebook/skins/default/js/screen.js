/**
 * @fileoverview 문자메시지 내용 입력창
 * @requires address.html > #smsMessage > #smsPurplebookContentInput
 */

var MAX_SCREEN = 3;
var timeoutHandle = null;

(function($) {
	jQuery(function($) {
		// 처음 screen 내용 저장
		$current = get_active_textarea();
		initial_content = $current.val();

		/**
		 * 문자내용의 byte를 세어 출력함 
		 */
		$(document).on('keyup', '#smsPurplebookContentInput .phonescreen', function(event) { 
			if (timeoutHandle) clearTimeout(timeoutHandle);
			timeoutHandle = setTimeout(function() { update_screen(); timeoutHandle = null; }, 200);
		});

		/**
		 * 문자내용의 byte를 세어 출력함 (firefox는 keyup이벤트가 안먹히기 때문에 focusout으로 처리)
		 */
		$('.phonescreen','#smsPurplebookContentInput').focusout( function() { 
			update_screen();
		});

		/**
		 * screen클릭시 해당 screen 을 active 상태로
		 */
		$(document).on('click', '#smsPurplebookContentInput .phonescreen', function(event) { 
			set_active_textarea(this);
		});

		/**
		 * save cursor position
		 */
		$(document).on('select click change keyup', '#smsPurplebookContentInput .phonescreen', function() {
			storeCaret(this);
		});

		/**
		 * 초기에 입력되어있는 문자메시지 제거
		 */
		$(document).on('click', '#smsPurplebookContentInput #main_screen', function() { 
			$current = get_active_textarea();
			if ($current.val()==initial_content) $current.val('');
		});

		/**
		 * sms분할
		 */
		$('#smsSplit','#smsMessage').click(function() {
			update_screen();
		});

		/**
		 * mms장문
		 */
		$('#mmsSend','#smsMessage').click(function() {
			update_screen();
		});

		/**
		 * textarea close
		 */
		$(document).on('click', '#smsPurplebookContentInput .close', function() {
			$(this).parent().parent().remove();
			update_screen();
			return false;
		});

		/**
		 * screen 추가
		 */
		$(document).on('click', '#smsPurplebookContentInput .btn_addwindow', function() {
			extend_screen(this);
			update_screen();
		});

		/**
		 * scrren 안에 내용 지우기
		 */
		$(document).on('click', '#smsPurplebookContentInput .btn_clear', function() {
			set_active_textarea($('textarea',$(this).parent().parent()).val('').focus());
			return false;
		});

		/**
		 * screen 내용 저장
		 */
		$(document).on('click', '#smsPurplebookContentInput .btn_record', function() {
			var content = $('textarea',$(this).parent().parent()).val();
			pb_keep_message_content(content);
			return false;
		});

		/**
			screen에 text추가
			@constructor 
		*/ 
		$(document).on('click', '#merge1', function() {
			insert_merge('{name}');
		});
		$(document).on('click', '#merge2', function() {
			insert_merge('{memo1}');
		});
		$(document).on('click', '#merge3', function() {
			insert_merge('{memo2}');
		});
		$(document).on('click', '#merge4', function() {
			insert_merge('{memo3}');
		});

		function insert_merge(merge) {
			$current = get_active_textarea();
			text = $current.val();
			$current.val(text + merge);
			display_type_switch();
			display_cost();
			display_bytes();
		}
		/**
			screen에 text추가 END
			@constructor
		*/

		/**
		 * 사진삭제
		 */
		$(document).on("click", '#btn_detach_pic', function() {
			XE.filepicker.cancel('file_srl');
			jQuery('#btn_attach_pic_box').show();
			jQuery('#btn_delete_pic_box').hide();
			update_screen();
			return false;
		});

		/**
		 * screen 안에 byte수 클릭시 return false?
		 */
		$(document).on('click', '#smsMessage .btn_bytes', function() {
			return false;
		});

		/**
			특수문자, 사진추가, 머지기능 버튼 위치설정
			@constructor
		*/
		$("body").append('<div id="pb_left_btn_box"><div id="btn_pop_chars_box"><button id="btn_pop_chars" class="left_btn">특수문자</button></div><div id="btn_attach_pic_box"><button id="btn_attach_pic" class="left_btn">사진추가</button></div><div id="btn_delete_pic_box"><button id="btn_detach_pic" class="left_btn">사진삭제</button></div><div id="btn_pop_merge_box"><button id="btn_pop_merge" class="left_btn">머지기능</button></div></div>');

		var left_button_location = $("#pb_btn_location").offset();

		var location_left = parseInt($("body").css("margin-left")) + parseInt($("body").css("padding-left")) + parseInt($("body").css("border-left-width"));

		$("#pb_left_btn_box").css({
			"position":"absolute",
			"top":left_button_location.top + 2,
			"left":left_button_location.left - location_left - 67,
			"width":"100px",
			"height":"100px",
			"z-index":"50"
		});
		/**
			특수문자, 사진추가, 머지기능 버튼 위치설정 END
			@constructor
		*/
	});
}) (jQuery);

/**
 * 스크린 확장
 */
function extend_screen(obj) {
	var npages = get_page_count();

	if (npages >= MAX_SCREEN) {
		//alert('분할창을 3개 까지 제한입니다.');
		return;
	}

	var html = '<li><div class="top_btn"><button class="btn_record" href="#" title="문자저장">문자저장</button><button class="pop_messages" href="#" title="불러오기">불러오기</button></div><div class="text_area" style="overflow:-moz-scrollbars-vertical; overflow-x:hidden; overflow-y:scroll;"><textarea class="phonescreen on" style="overflow:hidden; height:106px"></textarea></div><div class="text_btn"><a class="btn_bytes" href="#"><span>0bytes</span></a><a class="btn_clear" href="#"><span>Clear</span></a><a class="close" href="#"><span>close</span></a><button class="btn_addwindow" href="#" title="입력창 추가">창추가</button></div></li>'

	if (typeof(obj)=='object') {
		var $new_li = jQuery(html);
		var ta = jQuery('.phonescreen',$new_li)[0];
		jQuery(obj).parent().parent().after($new_li);
		set_active_textarea(ta);
		jQuery('#smsSplit','#smsMessage').attr('checked','checked');
		return;
	}

	var $current = get_active_textarea();
	var content = $current.val();
	var sliceByte = SliceBytePerLayer(content);

	if (sliceByte.length > 1) {
		$current.val(sliceByte[0]);

		slice_length = sliceByte.length;
		if (slice_length > 3) {
			alert('내용이 너무 길어 문자가 짤렸습니다.');
			slice_length = 3;
		}

		for (var i = 1; i < slice_length; i++) {
			jQuery('.phonescreen','#smsPurplebookContentInput li').removeClass('on');
			var $li = jQuery(html);
			jQuery('#smsPurplebookContentInput').append($li);
			$ta = jQuery('.phonescreen',$li);
			$ta.focus();
			$ta.val(sliceByte[i]);
		}
	}
	jQuery('#smsSplit','#smsMessage').attr('checked','checked');
}

/**
 * 스크린 합치기
 */
function join_screen() {
	var $content_input = jQuery('#smsPurplebookContentInput');
	var size = jQuery('li', $content_input).size();
	if (size <= 1) return;
	var $first = jQuery('.phonescreen', jQuery('li', $content_input).eq(0));

	var content = $first.val();

	/**
	 * get content
	 */
	for (var i = 1; i < size; i++) {
		$li = jQuery('li', $content_input).eq(i);
		$textarea = jQuery('.phonescreen', $li);
		content += $textarea.val();
	}
	/**
	 * delete
	 */
	for (var i = 1; i < size; i++) {
		var idx = jQuery('li', $content_input).size() - 1;
		jQuery('li', $content_input).eq(idx).remove();
	}
	$first.val(content);
}

/**
 * 스크린 업데이트
 */
function update_screen() {
	display_preview();
	display_addwindow();
}

/**
 * 현재 메시지 내용 저장
 * @param {content.string} 메시지내용
 */
function pb_keep_message_content(content) {
	var params = new Array();
	params['content'] = content;
	var response_tags = new Array('error','message');
	exec_xml('purplebook','procPurplebookSaveMessage', params, function() { alert('내용을 저장하였습니다'); }, response_tags);
}

/**
 * 창늘리기 버튼 보여주기/숨기기
 */
function display_addwindow() {
	var $content_input = jQuery('#smsPurplebookContentInput');
	var size = jQuery('li', $content_input).size();
	var file_srl = jQuery('input[name=file_srl]', '#smsMessage').val();

	if (size == MAX_SCREEN || file_srl) {
		jQuery('.btn_addwindow', $content_input).hide();
	} else {
		jQuery('.btn_addwindow', $content_input).show();
	}
}

/**
 * textarea byte수 계산후 처리 
 */
function display_bytes() {
	var $content_input = jQuery('#smsPurplebookContentInput');
	var size = jQuery('li', $content_input).size();

	for (var i = 0; i < size; i++) {
		li = jQuery('li', $content_input)[i];
		$textarea = jQuery('.phonescreen', li);
		var bytes_idx = getTextBytes($textarea.val());
		var bytes = bytes_idx[0];
		var lastidx = bytes_idx[1];
		jQuery('.btn_bytes', li).text(bytes + 'bytes');
		if (i < (size-1) || i == (MAX_SCREEN-1)) {
			var content = $textarea.val();
			var sliceByte = SliceBytePerLayer(content);
			if (sliceByte.length > 1) {
				//$textarea.blur();
				$textarea.val(sliceByte[0]);
				bytes_idx = getTextBytes($textarea.val());
				bytes = bytes_idx[0];
				lastidx = bytes_idx[1];
			}
		}

		var msg_type = getMsgType();
		if (msg_type != 'sms' && bytes > MMS_BYTES_LIMIT) {
		   $textarea.val($textarea.val().substr(0, lastidx)); 
		   alert(MMS_BYTES_LIMIT + 'bytes 까지 입력가능 합니다.');
		}
	}
}

jQuery(document).ready(function (){
	/**
		특수문자, 사진추가, 머지기능 버튼 효과
		@constructor
	*/
	left_button_location("#btn_pop_chars_box");
	left_button_location("#btn_attach_pic_box");
	left_button_location("#btn_delete_pic_box");
	left_button_location("#btn_pop_merge_box");
	/**
		특수문자, 사진추가, 머지기능 버튼 효과 END
		@constructor
	*/

	jQuery('.phonescreen').autoResize({extraSpace:10, animate:false, limit:99999});
});

/**
 * 창 리사이즈시 left_button 위치 변경
 */
jQuery(window).resize(function () {
	var left_button_location = jQuery("#pb_btn_location").offset();
	var location_left = parseInt(jQuery("body").css("margin-left")) + parseInt(jQuery("body").css("padding-left")) + parseInt(jQuery("body").css("border-left-width"));

	jQuery("#pb_left_btn_box").css({
		"top":left_button_location.top + 2,
		"left":left_button_location.left - location_left - 67,
	});
});

/**
 * current texarea 
 */
function get_active_textarea(jquery_obj) {
	if (typeof(jquery_obj)=='undefined') jquery_obj = true;
	var size = jQuery('.phonescreen.on','#smsPurplebookContentInput').size();
	if (size) {
		var context = jQuery('.phonescreen.on','#smsPurplebookContentInput')[0];
	} else {
		var context = jQuery('#main_screen','#smsPurplebookContentInput')[0];
	}
	if (jquery_obj) return jQuery(context);
	return context;
}

function set_active_textarea(obj) {
	if (!(obj instanceof jQuery)) {
		obj = jQuery(obj);
	}
	jQuery('.phonescreen','#smsPurplebookContentInput').removeClass('on');
	obj.addClass('on').focus();
}

/**
 * 미리보기
 */
function display_preview() {
	var $current = get_active_textarea();

	bytes = getTextBytes($current.val())[0];
   
	if (get_switch_value()=='SMS') {
		if (bytes > 90) {
			extend_screen();
		}
	}

	if (get_switch_value()=='MMS') {
		join_screen();
	}

	display_type_switch();
	display_cost();
	display_bytes();
}

/**
 * 문자당 가격 얼만지 표시
 */
function display_cost() {
	var nlist = list_counting();
	var npages = get_page_count();
	var msg_count = nlist * npages;

	var msg_type = getMsgType();
	switch(msg_type) {
		case "sms":
			each_price = 20;
			msg_type = 'SMS';
			break;
		case "lms":
			each_price = 50;
			msg_type = 'MMS장문';
			break;
		case "mms":
			each_price = 200;
			msg_type = 'MMS포토';
			break;
	}

	var cost = msg_count * each_price;

	jQuery('#projectedType','#smsMessage').text(msg_type);
	jQuery('#projectedCount','#smsMessage').text('' + msg_count);
	jQuery('#projectedCost','#smsMessage').text('' + cost);
}

/**
 * 메시지 타입 변경 보여주기/숨기기
 */
function display_type_switch() {
	var content = get_all_content();
	var bytes = getTextBytes(content)[0];
	var npages = get_page_count();
	var file_srl = jQuery('input[name=file_srl]','#smsMessage').val();
	if (!file_srl && (bytes > 90 || npages > 1)) {
		show_msgtype_switch();
	} else {
		hide_msgtype_switch();
	}
}

function get_last_textarea() {
	if (typeof(jquery_obj)=='undefined') jquery_obj = true;
	var size = jQuery('li','#smsPurplebookContentInput').size();
	return jQuery('li','#smsPurplebookContentInput').eq(size-1);
}

function show_msgtype_switch() {
	jQuery('.msgtype_switch', '#smsMessage').show();
}

function hide_msgtype_switch() {
	jQuery('.msgtype_switch', '#smsMessage').hide();
}

function getMsgType() {
	var msgtype = 'sms';
	var content = get_all_content();
	if (getTextBytes(content)[0] > 90) {
		if (jQuery('#mmsSend','#smsMessage').attr('checked')) msgtype = 'lms';
	}
	var file_srl = jQuery('input[name=file_srl]', '#smsMessage').val();
	if (file_srl) msgtype = 'mms';

	return msgtype;
}

function get_all_content() {
	var $content_input = jQuery('#smsPurplebookContentInput');
	var size = jQuery('li', $content_input).size();

	var content = '';

	for (var i = 0; i < size; i++) {
		$li = jQuery('li', $content_input).eq(i);
		$textarea = jQuery('.phonescreen', $li);
		content += $textarea.val();
	}
	return content;
}

function get_switch_value() {
	if (jQuery('#mmsSend','#smsMessage').attr('checked')) return 'MMS';
	return 'SMS';
}

function left_button_location(id) {
	if(!id) return;

	jQuery(id)
		.mouseenter(function() {
			var width = (jQuery('button', id).width() + 10) + 'px';
			jQuery(id).animate({width:width}, 100);
			
		})
		.mouseleave(function() {
			jQuery(id).animate({width:"0px"}, 200);
		});
}


function AddChar(ch) {
	var retChr;
	switch (ch) {
		case 1:
			retChr = "♥";
			break;
		case 2:
			retChr = "♡";
			break;
		case 3:
			retChr = "★";
			break;
		case 4:
			retChr = "☆";
			break;
		case 5:
			retChr = "▶";
			break;
		case 6:
			retChr = "▷";
			break;
		case 7:
			retChr = "◀";
			break;
		case 8:
			retChr = "◁";
			break;
		case 9:
			retChr = "∩";
			break;
		case 10:
			retChr = "●";
			break;
		case 11:
			retChr = "■";
			break;
		case 12:
			retChr = "○";
			break;
		case 13:
			retChr = "□";
			break;
		case 14:
			retChr = "▲";
			break;
		case 15:
			retChr = "▼";
			break;
		case 16:
			retChr = "▒";
			break;
		case 17:
			retChr = "♨";
			break;
		case 18:
			retChr = "※";
			break;
		case 19:
			retChr = "™";
			break;
		case 20:
			retChr = "℡";
			break;
		case 21:
			retChr = "♬";
			break;
		case 22:
			retChr = "♪";
			break;
		case 23:
			retChr = "☞";
			break;
		case 24:
			retChr = "☜";
			break;
		case 25:
			retChr = "♂";
			break;
		case 26:
			retChr = "♀";
			break;
		case 27:
			retChr = "㈜";
			break;
		case 28:
			retChr = "⊙";
			break;
		case 29:
			retChr = "◆";
			break;
		case 30:
			retChr = "◇";
			break;
		case 31:
			retChr = "♣";
			break;
		case 32:
			retChr = "♧";
			break;
		case 33:
			retChr = "☎";
			break;
		case 34:
			retChr = "∑";
			break;
		case 35:
			retChr = "▣";
			break;
		case 36:
			retChr = "㉿";
			break;
		case 37:
			retChr = "『";
			break;
		case 38:
			retChr = "』";
			break;
		case 39:
			retChr = "◐";
			break;
		case 40:
			retChr = "◑";
			break;
		case 41:
			retChr = "ㆀ";
			break;
		case 42:
			retChr = "†";
			break;
		case 43:
			retChr = "з";
			break;
		case 44:
			retChr = "▦";
			break;
		case 45:
			retChr = "☆(~.^)/";
			break;
		case 46:
			retChr = "s(^o^)s";	
			break;
		case 47:
			retChr = "＆(☎☎)＆";
			break;
		case 48:
			retChr = "(*^.^)♂";
			break;
		case 49:
			retChr = "(o^^)o";
			break;
		case 50:
			retChr = "o(^^o)";
			break;
		case 51:
			retChr = "=◑.◐=";
			break;
		case 52:
			retChr = "_(≥▽≤)ノ";
			break;
		case 53:
			retChr = "q⊙.⊙p";
			break;
		case 54:
			retChr = "o(>_<)o";
			break;
		case 55:
			retChr = "^.^";
			break;
		case 56:
			retChr = "(^.^)Ｖ";
			break;
		case 57:
			retChr = "*^^*";
			break;
		case 58:
			retChr = "^o^~~♬";
			break;
		case 59:
			retChr = "^.~";
			break;
		case 60:
			retChr = "S(*^__^*)S";
			break;
		case 61:
			retChr = "^△^";
			break;
		case 62:
			retChr = "＼(*^▽^*)ノ";
			break;
		case 63:
			retChr = "^L^";
			break;
		case 64:
			retChr = "^ε^";
			break;
		case 65:
			retChr = "^_^";
			break;
		case 66:
			retChr = "(ノ^Ｏ^)ノ";
			break;
		case 67:
			retChr = "^0^";
			break;
		default:
			retChr = "";
			break;
	}

	var current = get_active_textarea(false);
	insertsmilie(current,retChr);
}

function SliceBytePerLayer(str) {
	var sliceByte	= new Array();
	var length		= 0;
	var start_idx	= 0;

	for(var i = 0; i < str.length; i++)
	{
		if (escape(str.charAt(i)) == "%0D") {
		} else if (escape(str.charAt(i)).length > 4 || str.charAt(i) == "°" || str.charAt(i) == "¿") {
			length += 2;
		} else if ( str.charAt(i) != '\\r' || str.charAt(i) != '\\n' ) {
			length++;
		}

		if (length >= 90 || i == str.length - 1) {
			if (length > 90) i--;

			sliceByte[sliceByte.length] = str.substring(start_idx, i + 1);
	
			length = 0;
			start_idx = i + 1;
		}
	}

	return sliceByte;
}

/**
 * 텍스트 바이트 얻기
 * @param {text.string} text
 */
function getTextBytes(text) {
	var idx = 0;
	var bytes = 0;

	if (typeof(text)=='undefined') text = '';

	for(var i = 0; i < text.length; i++)
	{
		var ch = text.charAt(i);
		if (escape(ch).length > 4)
		{
			bytes += 2;
		} else if (ch != '\r')
		{
			bytes++;
		}
		if (bytes <= MMS_BYTES_LIMIT) {
			idx = i + 1;
		}
	}
	return [bytes, idx];
}

/**
 * 이모티콘 넣기
 */
function insertsmilie(t, smilieface) {
	if (t.createTextRange && t.caretPos) {
		var caretPos = t.caretPos;
		caretPos.text = smilieface;
		t.focus();
	} else {
		t.value+=smilieface;
		t.focus();
	}
}

/**
 * 예약전송 데이타 가져오기 (날짜등)
 */
function texting_pickup_reservdate() {
	var reserv_date = document.getElementById("inputReservationDate");
	var reserv_hour = document.getElementById("inputReservationHour");
	var reserv_min = document.getElementById("inputReservationMinute");
	var hour = reserv_hour.options[reserv_hour.selectedIndex].value;
	var minute = reserv_min.options[reserv_min.selectedIndex].value;
	return reserv_date.value.replace(/-/g,'') + texting_zeroPad(hour, 2) + texting_zeroPad(minute, 2);
}

function texting_zeroPad(n, digits) {
	n = n.toString();
	while (n.length < digits) {
		n = '0' + n;
	}
	return n;
}
