/**
 * @fileoverview purplebook에서 사용되는 util code들 입니다.
 */

var MMS_BYTES_LIMIT = 2000;

/**
 * tipsy
 */
jQuery('input, a, img, button','#smsPurplebook,#smsMessage').filter(function(index) { return !jQuery(this).hasClass('help'); }).tipsy();

function getRandomNumber(range) {
	return Math.floor(Math.random() * range);
}

function getRandomChar() {
	var chars = "0123456789abcdefghijklmnopqurstuvwxyzABCDEFGHIJKLMNOPQURSTUVWXYZ";
	return chars.substr( getRandomNumber(62), 1 );
}

function randomID(size) {
	var str = "";
	for(var i = 0; i < size; i++)
	{
		str += getRandomChar();
	}
	return str;
}

/**
 * save cursor position
 */
function storeCaret(ftext) {
	if (ftext.createTextRange) {
		ftext.caretPos = document.selection.createRange().duplicate();
	}
}

/**
 * 전화번호 - 붙여서 리턴
 * @param {tel.string} phone num
 * @return dashed telnum
 */
function getDashTel(tel) {
	tel = tel.replace(/-/g,'');
	DDD = new Array("02", "031", "033", "032", "042", "043", "041", "053", "054", "055", "052", "051", "063", "061", "062", "064", "011", "012", "013", "014", "015", "016", "017", "018", "019", "010", "070");

	if (tel == null || tel.length < 4) return tel;
	if (tel.indexOf("-") != -1) return tel;

	for (var i = 0; DDD.length > i; i++) 
	{
		if (tel.substring(0, DDD[i].length) == DDD[i] ) 
		{
			if (tel.length < 9) {
				return tel.substring(0, DDD[i].length) + "-"+ tel.substring(DDD[i].length, tel.length);
			} else {
				return tel.substring(0, DDD[i].length) + "-"+ tel.substring(DDD[i].length, tel.length - 4) + "-" + tel.substring(tel.length - 4, tel.length);
			}
		}
	}

	return tel;
}

/**
 * 날짜 ####-##-## ##:## 이런형식으로 
 * @param {date_str.string} 날짜 string
 * @return date
 */
function date_format(date_str) {
	res = '';

	if (date_str.length >= 4) res += date_str.substr(0, 4);
	if (date_str.length >= 6) res += '-' + date_str.substr(4, 2);
	if (date_str.length >= 8) res += '-' + date_str.substr(6, 2);
		
	if (date_str.length >= 10) res += ' ' + date_str.substr(8, 2);
	if (date_str.length >= 12) res += ':' + date_str.substr(10, 2);
	if (date_str.length >= 14) res += ':' + date_str.substr(12, 2);

	return res;
}

/**
 * 전화번호 포멧 검사
 */
function checkPhoneFormat(str) {
	var reg = new RegExp("^[01]|^[++]|^[00][0-9](-)?[0-9]{3,4}(-)?[0-9]{4}$")
	return reg.test(str)
}

/**
 * 전화번호 길이 검사
 */
function checkCallbackNumber(str) {
	if (str.length < 7) {
		return false;
	}
	return true;
}

/**
 * 전화번호에서 '-'제거
 */
function toOnlyNumber(str,s,d){
	var i=0;

	while (i > -1) {
		i = str.indexOf(s);
		str = str.substr(0,i) + d + str.substr(i+1,str.length);
	}
	return str;
}

/**
 * isNumeric() 
 */
function isNumeric(obj) {
   try { 
	 return (((obj - 0) == obj) && (obj.length > 0)); 
   } catch (e) { 
	 return false; 
   } // try 
} 

/**
 * isArray() 
 */
function isArray(obj) {
   if (!obj) { return false; } 
   try { 
	 if (!(obj.propertyIsEnumerable("length")) 
	   && (typeof obj === "object") 
	   && (typeof obj.length === "number")) { 
		 for (var idx in obj) { 
		   if (!isNumeric(idx)) return false; 
		 } // for (var idx in object) 
		 return true; 
	 } else { 
	   return false; 
	 } // if (!(obj.propertyIsEnumerable("length"))... 
   } catch (e) { 
	 return false; 
   } // try 
}

function clone(obj) {
	if (obj == null || typeof(obj) != 'object')
		return obj;

	var temp = new obj.constructor(); // changed (twice)
	for(var key in obj)
		temp[key] = clone(obj[key]);

	return temp;
}

/**
 * obj show and hide
 */
function show_and_hide($obj, $extra, opt) {
	if (typeof($extra)=='undefined') $extra = null;
	if (typeof(opt)=='undefined') opt = {};
	if (typeof(opt.show_func)=='undefined') opt.show_func = null;
	if (typeof(opt.hide_func)=='undefined') opt.hide_func = null;
	if (typeof(opt.before_func)=='undefined') opt.before_func = null;
	if (typeof(opt.uppermost)=='undefined') opt.uppermost = true;
	if (typeof(opt.force_show)=='undefined') opt.force_show = false;

	if (opt.before_func) {
		if (!opt.before_func.call()) return false;
	}

	if ($obj.css('display') == 'none') {
		$obj.css('display', 'block');
		if (opt.uppermost) {
			$obj.css('z-index','999');
			$obj.parents().css('z-index','999');
		}
		if ($extra) $extra.css('display','none');
		if (opt.show_func) opt.show_func.call();
	} else {
		if (opt.force_show) return true;
		$obj.css('display', 'none');
		if (opt.uppermost) {
			$obj.css('z-index','0');
			$obj.parents().css('z-index','0');
		}
		if (opt.hide_func) opt.hide_func.call();
	}
	return true;
}

/**
 * 1000단위 콤마 붙이는 함수
 */
function add_num_comma(num) {
	return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

/**
 * 받은사람 목록에 존재하는 번호인지 조사
 */
function isExistNum(newNum) {
	var pureNum = toOnlyNumber(newNum, "-", "");
	var exist = false;
	var $listAll = jQuery('li', '#smsPurplebookTargetList')
	
	$listAll.each(function(idx) {
		var eNum = toOnlyNumber(jQuery('.number',this).text(), "-", "");
		if (pureNum == eNum)
		{
			exist = true;
			return;
		}
	});
	
	return exist;
}

/**
 * loading 팝업
 */
function p_show_waiting_message() {
	var waiting_obj = jQuery('#waitingforserverresponse');
	if (waiting_obj.length) {
		var d = jQuery(document);
		waiting_obj.html('잠시만 기다려주세요.').css({
			'top'  : (d.scrollTop()+20)+'px',
			'left' : (d.scrollLeft()+20)+'px',
			'visibility' : 'visible'
		});
	}
}

/**
 * loading 팝업 숨기기
 */
function p_hide_waiting_message() {
	var waiting_obj = jQuery('#waitingforserverresponse');
	waiting_obj.css('visibility','hidden');
}

function get_page_count() {
	return jQuery('li', '#smsPurplebookContentInput').size();
}
