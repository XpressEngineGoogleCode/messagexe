var DDD = new Array("02", "031", "033", "032", "042", "043", "041", "053", "054", "055", "052", "051", "063", "061", "062", "064", "011", "012", "013", "014", "015", "016", "017", "018", "019", "010", "070");
var texting_bytes_limit = 2000;
var max_screen = 3;
var initial_content;
var GROUPID_SEED_SIZE = 10;
var timeoutHandle = null;
var deferred_payment = "N";

function getRandomNumber(range)
{
	return Math.floor(Math.random() * range);
}

function getRandomChar()
{
	var chars = "0123456789abcdefghijklmnopqurstuvwxyzABCDEFGHIJKLMNOPQURSTUVWXYZ";
	return chars.substr( getRandomNumber(62), 1 );
}

function randomID(size)
{
	var str = "";
	for(var i = 0; i < size; i++)
	{
		str += getRandomChar();
	}
	return str;
}

function getStatusText(stat) {
    switch (stat) {
        case '9':
        case '0':
            return '대기';
        case '1':
            return '전송중';
        case '2':
            return '완료';
        default:
            return 'Unknown';
    }
}

function getResultCodeText(code) {
    switch (code) {
        case "00": return "정상";
        case "10": return "잘못된 번호";
        case "11": return "상위 서비스망 스팸 인식됨";
        case "12": return "상위 서버 오류";
        case "13": return "잘못된 필드값";
        case "20": return "등록된 계정이 아니거나 패스워드가 틀림";
        case "21": return "존재하지 않는 메시지 ID";
        case "30": return "잔액이 없음";
        case "31": return "전송할 수 없음";
        case "32": return "미가입자";
        case "40": return "전송시간 초과";
        case "41": return "단말기 busy";
        case "42": return "음영지역";
        case "43": return "단말기 Power off";
        case "44": return "단말기 메시지 저장갯수 초과";
        case "45": return "단말기 일시 서비스 정지";
        case "46": return "기타 단말기 문제";
        case "47": return "착신거절";
        case "48": return "Unknown error";
        case "49": return "Format Error";
        case "50": return "SMS서비스 불가 단말기";
        case "51": return "착신측의 호불가 상태";
        case "52": return "이통사 서버 운영자 삭제";
        case "53": return "서버 메시지 Que Full";
        case "54": return "SPAM";
        case "55": return "SPAM, nospam.or.kr 에 등록된 번호";
        case "56": return "전송실패(무선망단)";
        case "57": return "전송실패(무선망->단말기단)";
        case "58": return "전송경로 없음";
        case "60": return "예약취소";
        case "70": return "[Agent] 등록된 IP주소와 틀림.";
        case "99": return "전송대기";
        default: return "Unknown error";
    }
}

function getResultClass(mstat, rcode) {
    switch (mstat) {
        case '9':
        case '0':
            return 'wait';
        case '1':
            if (rcode=='00' || rcode=='99') {
                return 'send';
            } else {
                return 'error';
            }
        case '2':
            if (rcode=='00') {
                return 'complete';
            } else {
                return 'error';
            }
        default:
            return 'unkown';
    }
    return 'unkown';
}

function getCarrierText(carrier) {
    switch (carrier) {
        case 'SKT':
            return 'SKT';
        case 'KTF':
            return 'Olleh KT';
        case 'LGT':
            return 'LGU+';
    }
    return '';
}

function getResultText(mstat, rcode) {
    var stat = getStatusText(mstat);
    var code_stat='';
    if (rcode=='00') {
        return stat + '(정상)';
    } else if (rcode=='99') {
        return stat;
    } else {
        return stat + '(오류:' + getResultCodeText(rcode) + ')';
    }
}
function pb_modify_name(obj) {
    if (obj.tagName.toUpperCase()=='LI') {
        var $li = jQuery(obj);
        var $nodeName = $li.children('.nodeName');
    } else {
        var $li = jQuery(obj).parent();
        var $nodeName = $li.children('.nodeName');
    }
    var node_id = $li.attr('node_id');
    var pos = $nodeName.position();
    var name = document.createElement('input');
    name.type = "text";
    name.name = "node_name";
    name.className = "modifyName";
    name.value = $nodeName.text();
    jQuery(name).css( {position:'absolute', 'left':pos.left+'px', 'top':pos.top+'px' } );
    jQuery(name).keyup(function(event) {
        if (event.keyCode == 13) {
            jQuery(this).focusout();
        }
        if (event.keyCode == 27) {
            name.value = $nodeName.text();
            jQuery(this).focusout();
        }
    });
    jQuery(name).focusout(function() {
        var params = new Array();
        params['node_id'] = node_id;
        params['name'] = jQuery(this).val();
        if (jQuery(this).val() != $nodeName.text()) {
            exec_xml('purplebook', 'procPurplebookUpdateName', params, function() { $nodeName.text(params['name']); });
        }
        jQuery(this).remove();
    });
    $li.append(name);
    jQuery(name).focus();
}

function pb_modify_phone(obj) {
    if (obj.tagName.toUpperCase()=='LI') {
        var $li = jQuery(obj);
        var $nodePhone = $li.children('.nodePhone');
    } else {
        var $li = jQuery(obj).parent();
        var $nodePhone = $li.children('.nodePhone');
    }
    var node_id = $li.attr('node_id');
    var pos = $nodePhone.position();
    var phonenum = document.createElement('input');
    phonenum.type = "text";
    phonenum.name = "phone_num";
    phonenum.className = "modifyPhone";
    phonenum.value = $nodePhone.text();

	// 전화번호의 첫글자가 +나 숫자가면 제거
	start_num = phonenum.value.substring(0, 1);
	start_num = start_num.replace(/[^+]/,'');

	// 전화번호가 숫자가 아니면 제거
    phonenum.value = phonenum.value.replace(/[^0-9]/g,'');
	phonenum.value = start_num + phonenum.value;

    jQuery(phonenum).css( {position:'absolute', 'left':pos.left+'px', 'top':pos.top+'px' } );
    jQuery(phonenum).keyup(function(event) {
        //jQuery(this).val(getDashTel(jQuery(this).val()));
        if (event.keyCode == 13) {
            jQuery(this).focusout();
        }
        if (event.keyCode == 27) {
            phonenum.value = $nodePhone.text();
            jQuery(this).focusout();
        }
    });
    jQuery(phonenum).focusout(function() {
        $this = jQuery(this);
        var params = new Array();
        params['node_id'] = node_id;
        params['phone_num'] = $this.val();
        if ($this.val() != $nodePhone.text()) {
            exec_xml('purplebook', 'procPurplebookUpdatePhone', params, function() { 
				$nodePhone.text(getDashTel(params['phone_num'])); $this.remove(); 

				// 국가코드 체크 
				if (params['phone_num'].charAt(0) == '+' || params['phone_num'].substring(0, 2) == '00') {
					countryCheck = false;
					startPos = 1;
					if (params['phone_num'].substring(0, 2) == '00') startPos = 2;

					for(var i = 6; i > 0; i--){
						if ((idx = jQuery.inArray(params['phone_num'].substring(startPos, i), country_codes)) > -1) {
							countryCheck = true;
							break;
						}
					}

					if (countryCheck == true){
						$li.css('color','');
						$li.attr("original-title", "수정되었습니다.");
					} else{
						$li.css('color','red');
						$li.attr("original-title", "잘못된 국가번호입니다.");
					}
				}
			});
        } else {
            $this.remove();
        }
    });
    $li.append(phonenum);
    jQuery(phonenum).focus();
}

function completeGetProperties(node, ret_obj, response_tags) {
    var $node = jQuery(node);
    var $layer = jQuery('#layer_properties');
    var $extra = jQuery('#layer_share');
	var params = new Array();
	var response_tags = new Array('error','message','data');

	jQuery('.title p',$layer).text($node.attr('node_name'));

	obj = ret_obj;

	params['g_mid'] = g_mid;
	params['layer_name'] = 'layer_properties';

	layer_id = '#layer_properties';

	exec_xml('purplebook', 'getPopupLayer', params, function(ret_obj) {
		if (ret_obj["data"]) {
			jQuery(layer_id).html(ret_obj["data"]);
			if (jQuery(layer_id).css('display') == 'block') jQuery(layer_id).html('');

			$obj = jQuery(layer_id);
			show_and_hide($obj, $extra);

			$list = jQuery('#properties_list','#layer_properties').empty();
			if (obj['data']) {
				var data = obj['data']['item'];
				if (!jQuery.isArray(data)) {
					data = new Array(data);
				}
				for (var i = 0; i < data.length; i++) {
					$list.append('<li>' + data[i].name + ' : ' + data[i].value + '</li>');
				}
			}
		}
	}, response_tags);
}

function completeUnshareNode(node_id, ret_obj, response_tags) {
    var member_srl = ret_obj['member_srl'];
    var shared_count = ret_obj['shared_count'];

    // remove
    jQuery('#sn_'+member_srl,'#smsPurplebook #share_list').remove();

    // refresh
    if (shared_count == 0) {
        var node = document.getElementById('node_'+node_id);
        var p = jQuery.jstree._reference(node)._get_parent(node);
        jQuery('#smsPurplebookTree').jstree('refresh',p);
    }

    alert('공유해제했습니다');
}

function completeShareNode(ret_obj, response_tags) {
    var node_id = ret_obj['node_id'];
    var member_srl = ret_obj['member_srl'];
    var user_id = ret_obj['user_id'];
    var nick_name = ret_obj['nick_name'];
    var shared_count = ret_obj['shared_count'];
    var list_size = jQuery('li','#smsPurplebook #share_list').size();

    if (ret_obj['error']==-1) {
        alert(ret_obj['message']);
        return false;
    }

    // append
    var $list = jQuery('#share_list','#smsPurplebook');
    $list.append('<li id="sn_' + member_srl + '" node_id="' + node_id + '" member_srl="' + member_srl + '"><span class="user_id">' + user_id + '</span><span class="nick_name">' + nick_name + '</span><span class="delete" title="삭제">삭제</span></li>');

    // refresh parent node
    if (list_size == 0) {
        var node = document.getElementById('node_'+node_id);
        var p = jQuery.jstree._reference(node)._get_parent(node);
        jQuery('#smsPurplebookTree').jstree('refresh',p);
    }
}

function completeGetSharedUsers(node, ret_obj, response_tags) {
    if (ret_obj['error']==-1) {
        alert(ret_obj['messsage']);
        return false;
    }

    var $layer = jQuery('#layer_share');
    var $extra = jQuery('#layer_properties');
    var $node = jQuery(node);
	var params = new Array();
	var response_tags = new Array('error','message','data');

    pb_share_folder.node = node;
    pb_share_folder.node_id = $node.attr('node_id');
    jQuery('.title p',$layer).text($node.attr('node_name') + ' 공유하기');

	obj = ret_obj;

	params['g_mid'] = g_mid;
	params['layer_name'] = 'layer_share';

	layer_id = '#layer_share';
	exec_xml('purplebook', 'getPopupLayer', params, function(ret_obj) {
		if (ret_obj["data"]) {
			jQuery(layer_id).html(ret_obj["data"]);
			if (jQuery(layer_id).css('display') == 'block') jQuery(layer_id).html('');
			$obj = jQuery(layer_id);
			show_and_hide($obj, $extra);

			$list = jQuery('#share_list','#layer_share').empty();

			if (obj['data']) {
				var data = obj['data']['item'];
				if (!jQuery.isArray(data)) {
					data = new Array(data);
				}

				for (var i = 0; i < data.length; i++) {
					$list.append('<li id="sn_' + data[i].member_srl + '" node_id="' + data[i].node_id + '" member_srl="' + data[i].member_srl + '"><span class="user_id">' + data[i].user_id + '</span><span class="nick_name">' + data[i].nick_name + '</span><span class="delete" title="삭제">삭제</span></li>');
				}
			}
		}
	}, response_tags);
}

function pb_view_properties(obj) {
    var $node = jQuery(obj);
    var params = new Array();
    params['node_id'] = $node.attr('node_id');
    var response_tags = new Array('error','message','data');
    exec_xml('purplebook', 'getPurplebookProperties', params, function(ret_obj,response_tags) { completeGetProperties(obj,ret_obj,response_tags); }, response_tags);
}

function pb_excel_download(obj) {
    var $node = jQuery(obj);
    window.open(current_url.setQuery('module','purplebook').setQuery('act','procPurplebookPurplebookDownload').setQuery('node_type','2').setQuery('node_id', $node.attr('node_id')), '_excel_download');
}

function pb_share_folder(obj) {
    var $node = jQuery(obj);

    var params = new Array();
    params['node_id'] = $node.attr('node_id');
    var response_tags = new Array('error','message','data');
    exec_xml('purplebook', 'getPurplebookSharedUsers', params, function(ret_obj,response_tags) { completeGetSharedUsers(obj,ret_obj,response_tags) }, response_tags);
}

function clearTrash() {
    if (!confirm('휴지통을 비우시겠습니까?')) return false;
    var params = new Array();
    params['node_id'] = 't.';
    var response_tags = new Array('error','message');
    exec_xml('purplebook', 'procPurplebookDeleteNode', params, function() {
		/*
        var sel = jQuery('#smsPurplebookTree').jstree("_get_node", jQuery('node_2'));
        */
        var obj = document.getElementById('node_2');
        jQuery('#smsPurplebookTree').jstree('refresh',obj);
        alert('휴지통을 비웠습니다'); 
    }, response_tags);
    return false;
}

function storeCaret(ftext) {
    if (ftext.createTextRange) {
        ftext.caretPos = document.selection.createRange().duplicate();
    }
}

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

function filepicker_selected() {
    jQuery('.text_area','#smsMessage').scrollTop(60);
    jQuery('#btn_attach_pic_box').hide();
    jQuery('#btn_delete_pic_box').show();
    jQuery('#mmsSend','#smsMessage').attr('checked','checked');
    update_screen();

    $obj = jQuery('#layer_upload');
    show_and_hide($obj);
}

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

function p_hide_waiting_message() {
    var waiting_obj = jQuery('#waitingforserverresponse');
    waiting_obj.css('visibility','hidden');
}

function getDashTel(tel)
{
    tel = tel.replace(/-/g,'');

    if (tel == null || tel.length < 4)
        return tel;

    if (tel.indexOf("-") != -1)
        return tel;

    for (var i = 0; DDD.length > i; i++) 
    {
        if (tel.substring(0, DDD[i].length) == DDD[i] ) 
        {
            if (tel.length < 9)
                return tel.substring(0, DDD[i].length) + "-"+ tel.substring(DDD[i].length, tel.length);
            else
                return tel.substring(0, DDD[i].length) + "-"+ tel.substring(DDD[i].length, tel.length - 4) + "-" + tel.substring(tel.length - 4, tel.length);
        }
    }
    return tel;
}

function getSimpleDashTel(tel)
{
    tel = tel.replace(/-/g,'');
    switch(tel.length)
    {
        case 10:
            initial = tel.substring(0, 3);
            medium = tel.substring(3, 6);
            _final = tel.substring(6, 10);
            break;
        case 11:
            initial = tel.substring(0, 3);
            medium = tel.substring(3, 7);
            _final = tel.substring(7, 11);
            break;
        default:
            return tel;
    }
    return initial + '-' + medium + '-' + _final;
}

/*
function getDashTel(tel){
    tel = tel.replace(/-/g,'');

    if (tel == null || tel.length < 4)
        return tel;

    if (tel.indexOf("-") != -1)
        return tel;

    for (var i = 0; DDD.length > i; i++) 
    {
        if (tel.substring(0, DDD[i].length) == DDD[i] ) 
        {
            if(tel.length < 9)
                return tel.substring(0, DDD[i].length) + "-"+ tel.substring(DDD[i].length, tel.length);
            else
                return tel.substring(0, DDD[i].length) + "-"+ tel.substring(DDD[i].length, tel.length - 4) + "-" + tel.substring(tel.length - 4, tel.length);
        }
    }
    return tel;
}
*/

function date_format(date_str)
{
    res = '';

    if (date_str.length >= 4)
        res += date_str.substr(0, 4);
    if (date_str.length >= 6)
        res += '-' + date_str.substr(4, 2);
    if (date_str.length >= 8)
        res += '-' + date_str.substr(6, 2);

    if (date_str.length >= 10)
        res += ' ' + date_str.substr(8, 2);
    if (date_str.length >= 12)
        res += ':' + date_str.substr(10, 2);
    if (date_str.length >= 14)
        res += ':' + date_str.substr(12, 2);

    return res;
}


function ifnull(str)
{
    if (str.length == 0)
        return '-';
    return str;
}

function calc_sms(cashinfo, point)
{
    if (point == undefined) point = 20;
    return Math.floor(cashinfo.cash / point) + Math.floor(cashinfo.point / point);
}

function calc_lms(cashinfo, point)
{
    if (point == undefined) point = 50;
    return Math.floor(cashinfo.cash / point) + Math.floor(cashinfo.point / point);
}

function calc_mms(cashinfo, point)
{
    if (point == undefined) point = 200;
    return Math.floor(cashinfo.cash / point) + Math.floor(cashinfo.point / point);
}
function getTextBytes(text)
{
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
        if (bytes <= texting_bytes_limit) {
            idx = i + 1;
        }
    }
    return [bytes, idx];
}

function cellphone_instant_switch(obj)
{
    if (obj.value == "2")
    {
        o = document.getElementById("cellphone_date");
        o.disabled = false;
        o = document.getElementById("cellphone_time");
        o.disabled = false;
    } else {
        o = document.getElementById("cellphone_date");
        o.disabled = true;
        o = document.getElementById("cellphone_time");
        o.disabled = true;
    }
}

function cellphone_generalize(text)
{
    if (text == "") {
        var obj = new Object();
        obj.text = '';
        obj.count = 0;
        return obj;
    }

    var reVal = text;
    var rePhone = '';
    var reName = '';
    var countList = 0;
    var HTML = '';

    var arrayList = reVal.split("\n");
    var lengthList = arrayList.length;
    var spacer = "              ";
    var pattern = /([0-9-()]{8,15})[ ,\t]*([\W\w]*)/;
    var prefix = new RegExp("^0[1-9](0|1|6|7|8|9)([-\)])?[0-9]{3,4}(-)?[0-9]{4}$")
    for (var i = 0; i < lengthList; i++)
    {
        var strLine = '';
        row = pattern.exec(arrayList[i]);
        if (!row) continue;

        if (prefix.test(row[1]))
        {
            rePhone = row[1].replace(/[-\(\) ]/g, "");
            reName = row[2];
            strLine = rePhone + spacer.substr(0, spacer.length - rePhone.length) + reName + "\r\n";
            HTML += strLine;

            countList++;
        }
    }

    var obj = new Object();
    obj.text = HTML.substr(0, HTML.length - 2);
    obj.count = countList;

    return obj;
}


function close_emoticon_display()
{
    obj = document.getElementById('special_chars');
    obj.style.display = "none";
}

function AddChar(ch)
{
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

function close_reservation_display()
{
    obj = document.getElementById('smsReservationPane');
    obj.style.display = "none";
}

function texting_zeroPad(n, digits) {
	n = n.toString();
	while (n.length < digits) {
		n = '0' + n;
	}
	return n;
}

function texting_pickup_reservdate()
{
    var reserv_date = document.getElementById("inputReservationDate");
    var reserv_hour = document.getElementById("inputReservationHour");
    var reserv_min = document.getElementById("inputReservationMinute");
    var hour = reserv_hour.options[reserv_hour.selectedIndex].value;
    var minute = reserv_min.options[reserv_min.selectedIndex].value;
    return reserv_date.value.replace(/-/g,'') + texting_zeroPad(hour, 2) + texting_zeroPad(minute, 2);
}

function prepare_direct()
{
    reservflag = document.getElementById("smsPurplebookReservFlag");
    reservflag.value = "0";
}

function prepare_reservation()
{
    reservflag = document.getElementById("smsPurplebookReservFlag");
    reservflag.value = "1";
}

 //전화번호 포멧 검사
function checkPhoneFormat(str) {
    var reg = new RegExp("^01(0|1|6|7|8|9)(-)?[0-9]{3,4}(-)?[0-9]{4}$")
    return reg.test(str)
}

 //전화번호 포멧 검사
function checkCallbackNumber(str) {
    if (str.length < 7) {
        return false;
    }
    return true;
/*
    var reg = new RegExp("^[0-9]{0,3}(-)?[0-9]{3,4}(-)?[0-9]{4}$")
    return reg.test(str)
*/
}
//전화번호에서 '-'제거
function toOnlyNumber(str,s,d){
    var i=0;

    while (i > -1) {
        i = str.indexOf(s);
        str = str.substr(0,i) + d + str.substr(i+1,str.length);
    }
    return str;
}

/*
function getByteSize(str) {
       var tmpStr;
       var temp=0;
       var onechar;
       var tcount;
       tcount = 0;

       tmpStr = new String(str);
       temp = tmpStr.length;

		for (k=0;k<temp;k++) {
			onechar = tmpStr.charAt(k);             
            
            if (escape(onechar).length > 4) {
                 tcount += 2;
            } else if (onechar!='\r') {
				tcount++;
            }                        
       }
      
		return tcount;
}
*/

// 폴더를 포함한 받는사람수를 카운팅한다.
function list_counting() {
	li_size = jQuery('li', '#smsPurplebookTargetList').size();

	total = 0;
	for (i=0; i<li_size; i++) {
		target_li = jQuery('li', '#smsPurplebookTargetList')[i];
		folder_id = "folder_" + target_li.getAttribute('node_id');

		if (folder_id == target_li.getAttribute('id')) {
			total = total + parseInt(target_li.getAttribute('count'));
		} else {
			total++;
		}
	}
	return total;
}
// 받는사람수 업데이트
function updateTargetListCount(total_count) {
    total = list_counting();

    if (total_count) {
       jQuery('#smsPurplebookTargetListCount').text(' (' + total + ' 명 / 총 ' + total_count + ' 명)');
	} else {
       jQuery('#smsPurplebookTargetListCount').text(' (' + total + ' 명)');
	}

    return total;
}

function updateExceptListCount() {
    var size = jQuery('#smsPurplebookExceptList li').size();
    jQuery('#smsPurplebookExceptNum').text(size);
    jQuery('.pop_overlap .number','#smsPurplebook').text(size);
}

function isNumeric(obj) { 
   try { 
     return (((obj - 0) == obj) && (obj.length > 0)); 
   } catch (e) { 
     return false; 
   } // try 
} // isNumeric() 

function isArray(obj) { 
   if (!obj) { return false; } 
   try { 
     if (!(obj.propertyIsEnumerable("length")) 
       && (typeof obj === "object") 
       && (typeof obj.length === "number")) { 
         for (var idx in obj) { 
           if (!isNumeric(idx)) { return false; } 
         } // for (var idx in object) 
         return true; 
     } else { 
       return false; 
     } // if (!(obj.propertyIsEnumerable("length"))... 
   } catch (e) { 
     return false; 
   } // try 
} // isArray() 

function send_json(content)
{
    if (typeof(send_json.total_count)=='undefined') send_json.total_count=0;
    if (typeof(send_json.progress_count)=='undefined') send_json.progress_count=0;
    if (send_json.progress_count == 0) {
        send_json.success_count=0;
        send_json.failure_count=0;
    }
    if (typeof(send_json.groupid_seed)=='undefined') send_json.groupid_seed = randomID(GROUPID_SEED_SIZE);

	content_list = content;

	var data = JSON.stringify(content);
	// for ie8
	data = unescape(data.replace(/\\u/g, '%u'));

    jQuery.ajax({
        type : "POST"
        , contentType: "application/json; charset=utf-8"
        , url : "./"
        , data : { 
                    module : "purplebook"
                    , act : "procPurplebookSendMsg"
                    , data : data
                    , module_srl : g_module_srl
                    , use_point : g_use_point
                    , sms_point : g_sms_point
                    , lms_point : g_lms_point
                    , mms_point : g_mms_point
                    , groupid_seed : send_json.groupid_seed
					, deferred_payment : deferred_payment
                 }
        , dataType : "json"
        , success : function (data) {
			//send_json.progress_count += content.length;
			size = content.length;
			for (var i = 0; i < size; i++) {
				if (content[i]["count"]) {
					send_json.progress_count += parseInt(content[i]["list_count"]);
				} else {
					send_json.progress_count++;
				}
			}

            if (data.error == -1) {
                p_hide_waiting_message();
                alert(data.message);
                return;
            }

            send_json.failure_count += data.failure_count;
            send_json.success_count += data.success_count;
			if (data.error_code) send_json.error_code = data.error_code;
            //if (data.alert_message.length > 0) alert(data.alert_message);
			
            pb_display_progress();

			// display 별로 전송
			$content_input = jQuery('#smsPurplebookContentInput');
			size = jQuery('li', $content_input).size();

			sendMessageData.display+=1;
			if (sendMessageData.display < size) {
				$li = jQuery('li', $content_input).eq(sendMessageData.display);
				$scr = jQuery('.phonescreen', $li);

				jQuery.each(content_list, function (i, val) {
					val["text"] = $scr.val();
					val["delay_count"] = sendMessageData.display*2;
				});

				send_json(content_list);
			} else {
				sendMessageData.display = 0;

				// 발송간격 설정이 안되있으면 문자 다시호출
				if (!sendMessageData.send_timer) sendMessageData();
			}
        }
        , error : function (xhttp, textStatus, errorThrown) { 
            send_json.progress_count += content.length;
            alert(errorThrown + " " + textStatus); 
        }
    });
}

function clone(obj) {
    if (obj == null || typeof(obj) != 'object')
        return obj;

    var temp = new obj.constructor(); // changed (twice)
    for(var key in obj)
        temp[key] = clone(obj[key]);

    return temp;
}

function pb_display_progress() {
    // get total count
    $list = jQuery('li','#smsPurplebookTargetList');
    $content_input = jQuery('#smsPurplebookContentInput');
    var total_count = list_counting() * jQuery('li', $content_input).size();
    send_json.total_count = total_count;

    // calculate percentage
    var percent = send_json.progress_count / send_json.total_count * 100;

    // display progress
	jQuery('.progressBar','#layer_status').progressbar({
		value: percent
	});
    jQuery('.total_count','#layer_status').text(send_json.total_count);
    jQuery('.success_count','#layer_status').text(send_json.success_count);
    jQuery('.failure_count','#layer_status').text(send_json.failure_count);
	jQuery('.error_code','#layer_status').text("");
	if (send_json.error_code) jQuery('.error_code','#layer_status').text(send_json.error_code);
}

function sendMessageData() {
    if (typeof(sendMessageData.index)=='undefined') sendMessageData.index=0;

    $list = jQuery('li','#smsPurplebookTargetList');

    pb_display_progress();

    if (sendMessageData.index >= $list.size() || sendMessageData.send_status == 'complete') {
        sendMessageData.send_status = 'complete';

		if (sendMessageData.send_timer) {
			clearInterval(sendMessageData.send_timer);
			sendMessageData.send_timer=false;
		}

		deferred_payment = 'N';
		
        jQuery('.text','#layer_status').text('접수가 완료되었습니다.');
		jQuery('#layer_status_close','#layer_status').text('닫기');
		jQuery('#btn_result','#layer_status').css('display','');

        return false;
    }

	if (sendMessageData.send_status == 'pause') {
		return false; 
	}

	var content_list = new Array();

	// folder 먼저 집어넣기 
	content_list = messageInputFolder();

	// 개별 집어넣기
	if (sendMessageData.send_status == 'f_complete') content_list = messageInput(content_list);

	// 첫번째 문자 텍스트를 설정해준다.
	$li = jQuery('li', $content_input).eq(sendMessageData.display);
	$scr = jQuery('.phonescreen', $li);

	jQuery.each(content_list, function (i, val) {
		val["text"] = $scr.val();
		val["delay_count"] = sendMessageData.display*2;
	});

	send_json(content_list);

    return true;
}

function messageInput(content_list) {
	var speed = g_send_speed;

	// 발송간격설정이 체크되있으면 설정된 만큼 메시지를 잘라서 보낸다
	if (jQuery("#message_interval_check").is(':checked')) {
	   	var speed = jQuery("#message_send_limit").val();
	}

	$list = jQuery('li','#smsPurplebookTargetList');

	var msgtype = getMsgType();
    var $content_input = jQuery('#smsPurplebookContentInput');

	content_count = 0;
	// 넘겨받은 contnet_list 가 있다면
	if (content_list.length > 0) content_count = parseInt(content_list[0].count) % parseInt(content_list[0].list_count);

	// 주소록 폴더에서 담다가 남은게 없다면 content_list를 새로 만든다
    if (typeof(content_list)=='undefined') var content_list = new Array();

	// 시작지점 설정
	i = 0;
	if (content_count) i = content_count;

    for (i; i < speed; i++) {
        if (sendMessageData.index >= $list.size()) break;

        $li = $list.eq(sendMessageData.index);
		target_list = $li;

		// folder일경우 넘어간다.
		folder_id = "folder_" + target_list.attr('node_id');
		if (folder_id == target_list.attr('id')) {
			sendMessageData.index+=1;
			continue;
		}

        var callno = jQuery('.number', $li).text();
        var ref_username = jQuery('.name', $li).text();
        var ref_userid = false;
        if ($li.attr('userid')) ref_userid = $li.attr('userid');
        var file_srl = jQuery('input[name=file_srl]', '#smsMessage').val();

		if ($li.attr('node_id')) {
			node_id = $li.attr('node_id');
		} else {
			node_id = '';
		}

        $content_input = jQuery('#smsPurplebookContentInput');
        var size = jQuery('li', $content_input).size();

		if (jQuery('#smsPurplebookReservFlag').val() == '1') {
			var content = {
				"msgtype": msgtype
				, "recipient": callno
				, "callback": jQuery('#smsPurplebookCallback').val()
				, "splitlimit": "0"
				, "refname": ref_username
				, "refid": ref_userid
				, "reservdate": texting_pickup_reservdate()
				, "node_id": node_id
			}
		} else {
			var content = {
				"msgtype": msgtype
				, "recipient": callno
				, "callback": jQuery('#smsPurplebookCallback').val()
				, "splitlimit": "0"
				, "refname": ref_username
				, "refid": ref_userid
				, "node_id": node_id
			}
		}

		// file이 있으면
		if (file_srl) content["file_srl"] = file_srl;
		content_list.push(content);

		sendMessageData.index++;
    }

	return content_list;
}

function messageInputFolder() {
	var speed = g_send_speed;

	// 발송간격설정이 체크되있으면 설정된 만큼 메시지를 잘라서 보낸다
	if (jQuery("#message_interval_check").is(':checked')) {
	   	var speed = jQuery("#message_send_limit").val();
	}

	var file_srl = jQuery('input[name=file_srl]', '#smsMessage').val();
	var msgtype = getMsgType();

	list = jQuery(".pb_folder_address");

    var content_list = new Array();

	if (sendMessageData.send_status == 'f_complete') return content_list;

	// Folder Idx 설정
	if (!sendMessageData.index) sendMessageData.index = 0;

	// sendMessageData.display 설정
	if (!sendMessageData.display) sendMessageData.display = 0;

	// page 설정
	if (!sendMessageData.page) sendMessageData.page = 1;

	// folder list 가 없다면 완료처리 한다.
	if (list.size() == 0) {
		sendMessageData.send_status = 'f_complete';
		return content_list;
	}

	for (i = 0; i < list.size(); i++) {
		target_list = jQuery(".pb_folder_address").eq(sendMessageData.index);
		total_page = Math.ceil(target_list.attr('count') / speed);

		$content_input = jQuery('#smsPurplebookContentInput');
		var size = jQuery('li', $content_input).size();

		if (jQuery('#smsPurplebookReservFlag').val() == '1') {
			var content = {
				"msgtype": msgtype
				, "callback": jQuery('#smsPurplebookCallback').val()
				, "splitlimit": "0"
				, "node_route": target_list.attr('node_route')
				, "count": target_list.attr('count')
				, "reservdate": texting_pickup_reservdate()
			}
		} else {
			var content = {
				"msgtype": msgtype
				, "callback": jQuery('#smsPurplebookCallback').val()
				, "splitlimit": "0"
				, "node_route": target_list.attr('node_route')
				, "count": target_list.attr('count')
			}
		}

		content["page"] = sendMessageData.page;
		content["list_count"] = speed;

		// 예약전송
		if (jQuery('#smsPurplebookReservFlag').val() == '1') content["reservdate"] = texting_pickup_reservdate();

		// file이 있으면
		if (file_srl) content["file_srl"] = file_srl;

		// content push
		content_list.push(content);

		// 폴더의 주소록이 제한 갯수를 넘었을경우
		if (parseInt(target_list.attr('count')) > speed) {
			sendMessageData.page += 1;

			// Page가 최총 페이지에 도달하면 다음폴더로 이동
			if (sendMessageData.page > total_page) {
				sendMessageData.page = 1;
				sendMessageData.index += 1;
			}

			// 마지막 폴더일경우 완료처리를 해준다
			if (sendMessageData.index >= list.size()) {
				sendMessageData.send_status= 'f_complete';
				sendMessageData.index = 0;
			}

			return content_list;
		}

		sendMessageData.index += 1;

		// 마지막 폴더일경우 완료처리를 해준다
		if (sendMessageData.index >= list.size()) {
			sendMessageData.send_status= 'f_complete';
			sendMessageData.index = 0;
		}

		return content_list;
	}
}

function sendMessage() {
    if (typeof(sendMessage.update_timer)=='undefined') sendMessage.update_timer=0;
	if (sendMessageData.send_timer) clearInterval(sendMessageData.send_timer);
    if (sendMessage.update_timer) clearInterval(sendMessage.update_timer);

	// display progress
	send_json.total_count = 0;
	send_json.success_count = 0;
	send_json.failure_count = 0;
	send_json.error_code = null;

    // clear status text
    jQuery('.text','#layer_status').text('전송중입니다...');
    // clear status
    jQuery('.status','#smsPurplebookTargetList li').remove();
    // clear send_json attributes
    $list = jQuery('li','#smsPurplebookTargetList');
    $content_input = jQuery('#smsPurplebookContentInput');
    var total_count = list_counting() * jQuery('li', $content_input).size();
    send_json.progress_count = 0;
    send_json.total_count = total_count;
    send_json.groupid_seed = randomID(GROUPID_SEED_SIZE);

    pb_display_progress();

    // clear sending index
    sendMessageData.index = 0;
	sendMessageData.send_status = 'sending';
	sendMessageData.display = 0;
	sendMessageData.page = 1;


    // pop status layer
	var params = new Array();
	var response_tags = new Array('error','message','data');

	params['g_mid'] = g_mid;
	params['layer_name'] = 'layer_status';

	layer_id = '#layer_status';

	exec_xml('purplebook', 'getPopupLayer', params, function(ret_obj) {
		if (ret_obj["data"]) {
			jQuery(layer_id).html(ret_obj["data"]);
			if (jQuery(layer_id).css('display') == 'block') jQuery(layer_id).html('');

			$obj = jQuery(layer_id);
			show_and_hide($obj,null,{force_show:true});
		}
	}, response_tags);

	// reset 전송완료후 버튼
	jQuery('#layer_status_close','#layer_status').text('취소');
	jQuery('#btn_result','#layer_status').css('display','none');

	// 발송간격설정이 체크되있으면 전송간격 시간을 가져와 SEND한다
	if (jQuery("#message_interval_check").is(':checked')) {
	   	g_send_interval = jQuery("#message_send_interval").val() * 100 * 60;

		sendMessageData.send_timer = setInterval(function() { sendMessageData();  }, g_send_interval);
	} else {
		sendMessageData();
	}
}

function get_switch_value() {
    if (jQuery('#mmsSend','#smsMessage').attr('checked')) return 'MMS';
    return 'SMS';
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

function do_after_get_cashinfo(cashinfo)
{
    var r_num = list_counting(); // 받는사람수
    var reservflag = document.getElementById("smsPurplebookReservFlag").value; // 예약여부
    var msg_type = getMsgType();
	var count = list_counting();
	var message = '';
	message += '['
	if (msg_type == 'sms') {
        message += getLang('sms') + ' ';
	} else if (msg_type == 'lms') {
        message += getLang('lms') + ' ';
	} else if (msg_type == 'mms') {
        message += getLang('mms') + ' ';
	} else {
        alert('unknown message type');
	}

    if (reservflag == '1') {
        message += getLang('reserv_send');
	} else {
        message += getLang('direct_send');
	}
    message += ']\n';

    //받는사람이 없을 경우
    if (r_num == 0) {
        alert('받는사람을 입력하세요');
        return false;
    }


	// 후불사용자면 가능건수 계산을 넘어간다
	if (cashinfo.deferred_payment == 'Y') {
		if (reservflag == '1') message += getLang('reservation_datetime', ': ') + date_format(texting_pickup_reservdate()) + '\n';

		if (msg_type == "sms") {
			npages = get_page_count();
			message += getLang('number_to_send') + (count * npages) + '\n';
		} else {
			message += getLang('number_to_send') + (count) + '\n';
		}
		message += getLang('msg_will_you_send');

		if (!confirm(message)) return false;

		deferred_payment = cashinfo.deferred_payment;
		sendMessage();
		return;
	}

    /*
    if (reservflag == "1")
        word_send = "예약";
    else
        word_send = "발송";
        */

    // 가능건수 계산
    sms_avail = calc_sms(cashinfo, cashinfo.sms_price);
    lms_avail = calc_lms(cashinfo, cashinfo.lms_price);
    mms_avail = calc_mms(cashinfo, cashinfo.mms_price);

    if (msg_type == "sms") {
        npages = get_page_count();
        /*
        bytes = getTextBytes(jQuery('#smsPurplebookTextMessage').val())[0];
        npages = Math.ceil(bytes / 90);
        */

        if ((count * npages) > sms_avail) {
            message += getLang('msg_not_enough_money') + "\n"
                    + getLang('available_sms_number') + sms_avail  + "\n"
                    + getLang('arranged_sms_number') + (count * npages) + "\n";
			alert(message);
            return false;
        } else {
            if (reservflag == '1') message += getLang('reservation_datetime', ': ') + date_format(texting_pickup_reservdate()) + '\n';
            message += getLang('number_to_send') + (count * npages) + '\n';
            message += getLang('msg_will_you_send');
            if (!confirm(message)) return false;
        }
    } else if (msg_type == "lms") {
        if (count > lms_avail) {
            message += getLang('msg_not_enough_money') + "\n"
                    + getLang('available_lms_number') + lms_avail  + "\n"
                    + getLang('arranged_lms_number') + (count) + "\n";
            alert(message);
            return false;
        } else {
            if (reservflag == '1') message += getLang('reservation_datetime', ': ') + date_format(texting_pickup_reservdate()) + '\n';
            message += getLang('number_to_send') + count + '\n';
            if (!confirm(message)) return false;
        }
    } else if (msg_type == "mms") {
        if (count > mms_avail) {
            message += getLang('msg_not_enough_money') + "\n"
                    + getLang('available_mms_number') + mms_avail  + "\n"
                    + getLang('arranged_mms_number') + (count) + "\n";
            alert(message);
            return false;
        } else {
            if (reservflag == '1') message += getLang('reservation_datetime', ': ') + date_format(texting_pickup_reservdate()) + '\n';
            message += getLang('number_to_send') + count + '\n';
            if (!confirm(message)) return false;
        }
    } else {
        alert('no msg type input');
    }

    //if (confirm(r_num + " 명에게 " + word_send + "하시겠습니까?"))

    sendMessage();
}


function completeGetPointInfo(ret_obj, response_tags) {
    var point = parseInt(ret_obj['point']);
    if (jQuery('#smsCurrentPoint')) jQuery('#smsCurrentPoint span:first').text(point);

    obj = new Object();
    obj.cash = 0;
    obj.point = point;
    

    reservflag = document.getElementById("smsPurplebookReservFlag").value;
    if (reservflag == "1") {
        word_send = "예약";
	} else {
        word_send = "발송";
	}

    sms_point = parseInt(g_sms_point);
    lms_point = parseInt(g_lms_point);
    mms_point = parseInt(g_mms_point);
    if (!sms_point || !lms_point || !mms_point) {
        alert('포인트 차감 사용으로 되어 있으나 차감할 포인트가 설정되어있지 않습니다.');
        return false;
    }

    sms_avail = calc_sms(obj, sms_point);
    lms_avail = calc_lms(obj, lms_point);
    mms_avail = calc_mms(obj, mms_point);

    var count = list_counting();
    if (getMsgType() == "sms") {

		var content = get_all_content();
        bytes = getTextBytes(content)[0];
        npages = Math.ceil(bytes / 90);

        if ((count * npages) > sms_avail) {
            alert(ret_obj['msg_not_enough_point'] + "\n"
                    + "현재 포인트: " + point + "\n"
                    + word_send + "가능 SMS 건수: " + sms_avail  + "\n"
                    + word_send + "예정 SMS 건수: " + (count * npages)
                 );
            return false;
        }
    } else if (getMsgType() == 'lms') {
        if (count > lms_avail) {
            alert(ret_obj['msg_not_enough_point'] + "\n"
                + "현재 포인트: " + point + "\n"
                + word_send + "가능 LMS 건수: " + lms_avail  + "\n"
                + word_send + "예정 LMS 건수: " + count
                );
            return false;
        }
    } else {
        if (count > mms_avail) {
            alert(ret_obj['msg_not_enough_point'] + "\n"
                + "현재 포인트: " + point + "\n"
                + word_send + "가능 MMS 건수: " + mms_avail  + "\n"
                + word_send + "예정 MMS 건수: " + count
                );
            return false;
        }
	}
    get_cashinfo();
}

function completeGetCallbackList(ret_obj, response_tags) {
    $list = jQuery('#smsPurplebookCallbackList').empty();
    if (ret_obj['data']) {
        var data = ret_obj['data']['item'];
        if (!jQuery.isArray(data)) {
            data = new Array(data);
        }
        for (var i = 0; i < data.length; i++) {
            if (data[i].flag_default == 'Y') {
                on = ' on';
			} else {
                on = '';
			}
            $list.append('<li callback_srl="' + data[i].callback_srl + '"><span class="default' + on + '"></span><span class="phonenum">' + data[i].phonenum + '</span><span class="deleteCallback" title="삭제">삭제</span></li>');
        }
    }
}

function SliceBytePerLayer(str)
{
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


function show_msgtype_switch() {
    jQuery('.msgtype_switch', '#smsMessage').show();
}

function hide_msgtype_switch() {
    jQuery('.msgtype_switch', '#smsMessage').hide();
}
 
/* current texarea */
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

function get_last_textarea() {
    if (typeof(jquery_obj)=='undefined') jquery_obj = true;
    var size = jQuery('li','#smsPurplebookContentInput').size();
    return jQuery('li','#smsPurplebookContentInput').eq(size-1);
}

function get_page_count() {
    return jQuery('li', '#smsPurplebookContentInput').size();
}

function extend_screen(obj) {
    var npages = get_page_count();

    if (npages >= max_screen) {
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
    //$current.blur();
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

function join_screen() {
    var $content_input = jQuery('#smsPurplebookContentInput');
    var size = jQuery('li', $content_input).size();
    if (size <= 1) return;
    var $first = jQuery('.phonescreen', jQuery('li', $content_input).eq(0));

    var content = $first.val();

    // get content
    for (var i = 1; i < size; i++) {
        $li = jQuery('li', $content_input).eq(i);
        $textarea = jQuery('.phonescreen', $li);
        content += $textarea.val();
    }
    // delete
    for (var i = 1; i < size; i++) {
        var idx = jQuery('li', $content_input).size() - 1;
        jQuery('li', $content_input).eq(idx).remove();
    }
    $first.val(content);
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

function delete_photo() {
    XE.filepicker.cancel('file_srl');
    jQuery('#btn_delete_pic_box').hide();
    jQuery('#btn_attach_pic_box').show();
}

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
        if (i < (size-1) || i == (max_screen-1)) {
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
        if (msg_type != 'sms' && bytes > texting_bytes_limit) {
           $textarea.val($textarea.val().substr(0, lastidx)); 
           alert(texting_bytes_limit + 'bytes 까지 입력가능 합니다.');
        }
    }


}

function display_addwindow() {
    var $content_input = jQuery('#smsPurplebookContentInput');
    var size = jQuery('li', $content_input).size();
    var file_srl = jQuery('input[name=file_srl]', '#smsMessage').val();

    if (size == max_screen || file_srl) {
        jQuery('.btn_addwindow', $content_input).hide();
    } else {
        jQuery('.btn_addwindow', $content_input).show();
    }
}
function update_screen() {
    display_preview();
    display_addwindow();
}

/**
 * @brief 선택된 받는 사람 명단을 삭제
 */
function removeSelectedRecipients() {
    p_show_waiting_message();
    var $chkLi = jQuery('span.checkbox.on', '#smsPurplebookTargetList li').parent();
	if (!$chkLi.size()) {
		alert('삭제할 대상을 선택하세요');
		return false;
	}
    $chkLi.remove();
    updateTargetListCount();
	alert($chkLi.size() + ' 건을 삭제하였습니다');
    p_hide_waiting_message();
}

function scrollBottomTargetList() {
    var $list = jQuery('#smsPurplebookTargetList');
    $list.attr({scrollTop: $list.attr('scrollHeight')}, 1000);
}

function refreshCallbackList() {
    var params = new Array();
    var response_tags = new Array('error','message','data');
    exec_xml('purplebook', 'getPurplebookCallbackNumbers', params, completeGetCallbackList, response_tags);
}

function deleteCallback(callback_srl) {
    var params = new Array();
    params['callback_srl'] = callback_srl;
    var response_tags = new Array('error','message');
    exec_xml('purplebook', 'procPurplebookDeleteCallbackNumber', params, function() { refreshCallbackList(); }, response_tags);
}

function request_default_number(phonenum) {
    var params = new Array();
    params['phonenum'] = phonenum;
    var response_tags = new Array('error','message');
    exec_xml('purplebook', 'procPurplebookSetDefaultCallbackNumber', params, function() { refreshCallbackList(); }, response_tags);
}


/**
 * 폴더 선택 목록 카운트 업데이트
 */
function updatePurplebookListCount(total_count)
{
     var list_count = jQuery('li', '#smsPurplebookList').size();
     if (total_count) jQuery('#smsPurplebookListCount').text(list_count + ' / ' + total_count);
     else jQuery('#smsPurplebookListCount').text(list_count);
	 if (list_count < total_count) {
		jQuery('<li id="pb_show_more" style="text-align:center; cursor:pointer;">100개 더보기</li>').appendTo('#smsPurplebookList').click(function() {
			pb_load_list.page++;
			pb_load_list();
			jQuery(this).remove();
		});
	 }
}

/**
 * 전체 검색 목록 카운트 업데이트
 */
function pb_update_search_count(total_count)
{
     var list_count = jQuery('li', '#smsPurplebookList').size();
     if (total_count) jQuery('#smsPurplebookListCount').text(list_count + ' / ' + total_count);
     else jQuery('#smsPurplebookListCount').text(list_count);
	 if (list_count < total_count) {
		jQuery('<li id="pb_show_more" style="text-align:center; cursor:pointer;">100개 더보기</li>').appendTo('#smsPurplebookList').click(function() {
			pb_search_list.page++;
			pb_search_list();
			jQuery(this).remove();
		});
	 }
}

function add_to_list(node_id, node_name, phone_num)
{
	// 국가코드 체크 
	countryCheck = true;
	if (phone_num.charAt(0) == '+' || phone_num.substring(0, 2) == '00') {
		startPos = 1;
		if (phone_num.substring(0, 2) == '00') startPos = 2;

		for(var i = 6; i > 0; i--){
			countryCode = null;
			if ((idx = jQuery.inArray(phone_num.substring(startPos, i), country_codes)) > -1) {
				countryCode = country_codes[idx];
				break;
			}
		}
		if (!countryCode) countryCheck = false;
	}

	if (countryCheck == false){
		jQuery('#smsPurplebookList').append('<li node_id="' + node_id + '" class="jstree-draggable" style="color:red;" original-title="잘못된 국가번호입니다."><span class="checkbox"></span><span class="nodeName" title="' + node_name + '">' + node_name + '</span><span class="nodePhone">' + getSimpleDashTel(phone_num) + '</span></li>');

		jQuery('li','#smsPurplebookList').filter(function(index) { return !jQuery(this).hasClass('help'); }).tipsy();

	}else {
		jQuery('#smsPurplebookList').append('<li node_id="' + node_id + '" class="jstree-draggable"><span class="checkbox"></span><span class="nodeName" title="' + node_name + '">' + node_name + '</span><span class="nodePhone">' + getSimpleDashTel(phone_num) + '</span></li>');
	}
}

/**
 * @brief 검색 결과
 */
function completePurplebookSearch(ret_obj, response_tags) {
    $list = jQuery('#smsPurplebookList','#smsPurplebook');
    if (ret_obj['data']) {
        var data = ret_obj['data']['item'];
        if (!jQuery.isArray(data)) {
            data = new Array(data);
        }
        for (var i = 0; i < data.length; i++) {
            $list.append('<li node_id="' + data[i].node_id + '" class="jstree-draggable"><span class="checkbox"></span><span class="nodeName" title="' + data[i].node_name + '">' + data[i].node_name + '</span><span class="nodePhone">' + getSimpleDashTel(data[i].phone_num) + '</span></li>');
        }
        pb_update_search_count(ret_obj['total_count']);
    }
}

function pb_keep_message_content(content) {
    var params = new Array();
    params['content'] = content;
    var response_tags = new Array('error','message');
    exec_xml('purplebook','procPurplebookSaveMessage', params, function() { alert('내용을 저장하였습니다'); }, response_tags);
}


/**
 * @brief 전체 검색
 */
function pb_search_list(search_word) {
	if (typeof(search_word)!='undefined') {
		pb_search_list.search_word = search_word;
		jQuery('#smsPurplebookList','#smsPurplebook').empty();
	}
	if (typeof(pb_search_list.page)=='undefined') pb_search_list.page = 1;
    var params = new Array();
    params['search_word'] = pb_search_list.search_word;
	params['page'] = pb_search_list.page;
    var response_tags = new Array('error','message','data','total_count','total_page','page');
    exec_xml('purplebook','getPurplebookSearch', params, completePurplebookSearch, response_tags);
}

function completeLoadRecentNumbers(ret_obj,response_tags) {
    $list = jQuery('#recent_list').empty();
    if (ret_obj['data']) {
        var data = ret_obj['data']['item'];
		
        if (!jQuery.isArray(data)) {
            data = new Array(data);
        }

		jQuery("#pb_recent_count").html(data.length);

        for (var i = 0; i < data.length; i++) {
            $list.append('<li><span class="name">' + data[i].ref_name + '</span><span class="phonenum">' + data[i].phone_num + '</span><span class="delete" receiver_srl="' + data[i].receiver_srl + '"></span></li>');
        }
    }
}

function completeLoadSavedMessages(ret_obj,response_tags) {
    $list = jQuery('#message_list').empty();
    if (ret_obj['data']) {
        var data = ret_obj['data']['item'];
        if (!jQuery.isArray(data)) {
            data = new Array(data);
        }
        for (var i = 0; i < data.length; i++) {
            $list.append('<li title="' + data[i].content + '"><span class="content">' + data[i].content.substring(0,16) + '</span><span class="delete" message_srl="'+data[i].message_srl+'"></span></li>');
        }
    } else {
        $list.append('<li>저장된 내용이 없습니다</li>');
    }

}

function pb_load_saved_messages() {
    var params = new Array();
    var response_tags = new Array('error','message','data');
    exec_xml('purplebook','getPurplebookSavedMessages', params, completeLoadSavedMessages, response_tags);
}

function pb_load_recent_numbers() {
    var params = new Array();
    var response_tags = new Array('error','message','data');
    exec_xml('purplebook','getPurplebookLatestNumbers', params, completeLoadRecentNumbers, response_tags);
}

function pb_scrolled(e) {
	var list = jQuery('#smsPurplebookList');
	var context = list[0];
	if (list.scrollTop() + list.innerHeight() >= context.scrollHeight) {
		pb_load_list.page++;
		pb_load_list();
	}
}

/**
 * @brief 선택된 폴더의 연락처 목록을 가져와서 선택 목록 영역에 출력한다.
 * @param node : jQuery Object(node) 혹은 string 타입의 node_id
 */
function pb_load_list(node, refresh) {

	// node를 넘겨받지 않았다면 선택된 폴더(jquery obj)를 사용한다.
    if (typeof(node)=='undefined' || node == null) {
        var selected_folders = jQuery('#smsPurplebookTree').jstree('get_selected');
        if (selected_folders.length > 0) {
            node = jQuery(selected_folders[0]);
        }
    } else {
		pb_load_list.page = 1;
		jQuery('#smsPurplebookList').html('');
	}

	if (typeof(pb_load_list.page)=='undefined') {
		pb_load_list.page = 1
	}

	// 서버로부터 데이터를 전송받고 있습니다... 요런 메시지 출력인거 같은데 정상적으로 작동 안하는 듯??
    p_show_waiting_message();

	// node_id 구하기
    var req_node_id = '';
    if (typeof(node)=='string') { // node가 string
        req_node_id = node;
        node = jQuery('#'+req_node_id);
    } else { // node가 jquery object
        req_node_id = node.attr('node_id');
    }

	// 선택된 폴더이름을 표시... 하지만 html 에 없는 걸?? ----> 제거해야 할 듯
	jQuery('#smsPurplebookSelectedFolderName').text(node.attr('node_name'));

	// 새로고침하는 경우
	if (refresh) {
		pb_load_list.page = 1;
		jQuery('#smsPurplebookList').html('');
	}


    jQuery.ajax({
        type : "POST"
        , contentType: "application/json; charset=utf-8"
        , url : "./"
        , data : { 
                    module : "purplebook"
                    , act : "getPurplebookList"
                    , node_id : req_node_id
                    , node_type : '2'
					, page : pb_load_list.page
					, list_count : 100
                 }
        , dataType : "json"
        , success : function (data) {
            if (data.error == -1) {
               alert(data.message);
               return -1;
            }
            //jQuery('#smsPurplebookList').html('');

            for (i = 0; i < data.data.length; i++)
            {
                node_id = data.data[i].attr.node_id;
                node_name = data.data[i].attr.node_name;
                phone_num = data.data[i].attr.phone_num;
                add_to_list(node_id, node_name, phone_num);
            }

            jQuery('#btnPurplebookExcelDownload').attr('href', data.base_url + '?module=purplebook&act=dispPurplebookPurplebookDownload&node_type=2&node_id=' + req_node_id);

			updatePurplebookListCount(data.total_count);
			p_hide_waiting_message();
        }
        , error : function (xhttp, textStatus, errorThrown) { 
            p_hide_waiting_message();
            alert(errorThrown + " " + textStatus); 
        }
    });
}

// 중복번호창 불러오기
function pb_load_overlap() {
	var params = new Array();
	var response_tags = new Array('error','message','data');

	params['g_mid'] = g_mid;
	params['layer_name'] = 'layer_overlap';

	layer_id = '#layer_overlap';

	exec_xml('purplebook', 'getPopupLayer', params, function(ret_obj) {
		if (ret_obj["data"]) {
			jQuery(layer_id).html(ret_obj["data"]);
			if (jQuery(layer_id).css('display') == 'block') jQuery(layer_id).html('');
		}
	}, response_tags);
}

function purplebook_move_node(node_id, dest_id) {
    jQuery.ajax({
        type: 'POST',
        dataType: "json",
        contentType: "application/json; charset=utf-8",
        async : false,
        url: "./",
        data : { 
            module : "purplebook"
            , act : "procPurplebookMoveNode"
            , node_id : node_id
            , parent_id : dest_id
        },
        success : function (r) {
            if (r.error==-1) {
                alert(r.message);
            } else {
                var selected_folders = jQuery('#smsPurplebookTree').jstree('get_selected');
                if (selected_folders.length > 0) {
                    var node = jQuery(selected_folders[0]);
                    pb_load_list(node);
                }
                /*
                if(data.rslt.cy && jQuery(data.rslt.oc).children("UL").length) {
                    data.inst.refresh(data.inst._get_parent(data.rslt.oc));
                }
                */
            }
        }
    });
}

function init_target_tree(element_id, img_base)
{
    init_target_tree.img_base = img_base;
    jQuery(element_id).jstree({ 
        // the list of plugins to include
        "plugins" : [ "themes", "json_data", "ui", "crrm", "search", "types", "hotkeys" ],
        // Plugin configuration

        // I usually configure the plugin that handles the data first - in this case JSON as it is most common
        "json_data" : { 
            // I chose an ajax enabled tree - again - as this is most common, and maybe a bit more complex
            // All the options are the same as jQuery's except for `data` which CAN (not should) be a function
            "ajax" : {
                contentType: "application/json; charset=utf-8",
                // the URL to fetch the data
                "url" : "./",
                // this function is executed in the instance's scope (this refers to the tree instance)
                // the parameter is the node being loaded (may be -1, 0, or undefined when loading the root nodes)
                "data" : function (n) { 
                    if (typeof(init_target_tree.initial)=='undefined') {
                        init_target_tree.initial = 1;
                        node_id = 'root';
                    }
                    if (typeof(n.attr) != 'undefined') {
                        node_id = n.attr('node_id');
                    }
                    // the result is fed to the AJAX request `data` option
                    return { 
                        module : "purplebook"
                        , act : "getPurplebookList"
                        , node_id : node_id
                        , node_type : "1"
                    }; 
                },
                "success" : function(d) { 
                    if (d.error == -1) {
                        jQuery('#smsPurplebookTargetTree').html(d.message);
                        return;
                    }
                    /*
                    if (typeof(d.data)=='undefined' || d.data.length == 0) {
                        return;
                    }
                    */
                    /*
                    for(i = 0; i < d.data.length; i++) {
                        if (d.data[i].attr.subfolder > 0) {
                            d.data[i].data = "[" + d.data[i].attr.subfolder + "]" + d.data[i].data;
                        }
                        if (d.data[i].attr.subnode > 0) {
                            d.data[i].data = d.data[i].data + "(" + d.data[i].attr.subnode + ")";
                        }
                    }
                    */
                    return d.data; 
                }
            }
        },
        // Configuring the search plugin
        "search" : {
            // As this has been a common question - async search
            // Same as above - the `ajax` config option is actually jQuery's object (only `data` can be a function)
            "ajax" : {
                "url" : "./",
                // You get the search string as a parameter
                "data" : function (str) {
                    return { 
                        "operation" : "search", 
                        "search_str" : str 
                    }; 
                }
            }
        },
        // Using types - most of the time this is an overkill
        // Still meny people use them - here is how
        "types" : {
            // I set both options to -2, as I do not need depth and children count checking
            // Those two checks may slow jstree a lot, so use only when needed
            "max_depth" : -2,
            "max_children" : -2,
            // I want only `drive` nodes to be root nodes 
            // This will prevent moving or creating any other type as a root node
            "valid_children" : [ "drive" ],
            "types" : {
                // The default type
                "default" : {
                    // I want this type to have no children (so only leaf nodes)
                    // In my case - those are files
                    "valid_children" : "none",
                    // If we specify an icon for the default type it WILL OVERRIDE the theme icons
                    "icon" : {
                        "image" : img_base + "file.png"
                    }
                },
                // The `folder` type
                "folder" : {
                    // can have files and other folders inside of it, but NOT `drive` nodes
                    "valid_children" : [ "default", "folder" ],
                    "icon" : {
                        "image" : img_base + "folder.png"
                    }
                },
                // The `folder` type
                "shared_folder" : {
                    // can have files and other folders inside of it, but NOT `drive` nodes
                    "valid_children" : [ "folder" ],
                    "icon" : {
                        "image" : img_base + "shared_folder.png"
                    }
                },
                // The `drive` nodes 
                "root" : {
                    // can have files and folders inside, but NOT other `drive` nodes
                    "valid_children" : [ "folder" ],
                    "icon" : {
                        "image" : img_base + "root.png"
                    },
                    // those options prevent the functions with the same name to be used on the `drive` type nodes
                    // internally the `before` event is used
                    "start_drag" : false,
                    "move_node" : false,
                    "delete_node" : false,
                    "remove" : false
                }
            }
        },
        // For UI & core - the nodes to initially select and open will be overwritten by the cookie plugin

        // the UI plugin - it handles selecting/deselecting/hovering nodes
        "ui" : {
            // this makes the node with ID node_4 selected onload
            "initially_select" : [ "node_4" ]
        },
        // the core plugin - not many options here
        "core" : { 
            // just open those two nodes up
            // as this is an AJAX enabled tree, both will be downloaded from the server
            "initially_open" : [ "node_2" , "node_3" ] 
        }
    });

}

function display_status(node_id)
{
    jQuery.ajax({
        type : "POST"
        , contentType: "application/json; charset=utf-8"
        , url : "./"
        , data : { 
                    module : "purplebook"
                    , act : "getPurplebookList"
                    , node_id : node_id
                    , node_type : '1'
					, page : 1
                 }
        , dataType : "json"
        , success : function (data) {
            if (data.error == -1)
            {
               alert(data.message);
               return -1;
            }
            for (i = 0; i < data.data.length; i++)
            {
                node_id = data.data[i].attr.node_id;
                node_name = data.data[i].attr.node_name;
                phone_num = data.data[i].attr.phone_num;
				var node = jQuery('#'+node_id);
				var pos = node.position();
				var width = node.outerWidth();
				alert(pos.left);
				jQuery('<span>' + data.data[i].attr.subnode + '</span>').appendTo('#smsPurplebook .left_list').css({
					'position':'absolute'
					, 'top':pos.top + 'px'
					, 'left':(pos.left + width) + 'px'
					, 'background-color':'red'
				});
            }
        }
        , error : function (xhttp, textStatus, errorThrown) { 
            alert(errorThrown + " " + textStatus); 
        }
    });
}
   
function init_purplebook_tree(img_base)
{
    init_purplebook_tree.img_base = img_base;
    jQuery("#smsPurplebookTree").jstree({
        // the list of plugins to include
        "plugins" : [ "themes", "json_data", "ui", "crrm", "cookies", "dnd", "search", "types", "hotkeys", "contextmenu" ],
        // Plugin configuration

        // I usually configure the plugin that handles the data first - in this case JSON as it is most common
        "json_data" : { 
            // I chose an ajax enabled tree - again - as this is most common, and maybe a bit more complex
            // All the options are the same as jQuery's except for `data` which CAN (not should) be a function
            "ajax" : {
                contentType: "application/json; charset=utf-8",
                // the URL to fetch the data
                "url" : "./",
                // this function is executed in the instance's scope (this refers to the tree instance)
                // the parameter is the node being loaded (may be -1, 0, or undefined when loading the root nodes)
                "data" : function (n) { 
                    p_show_waiting_message();
                    if (typeof(init_purplebook_tree.initial)=='undefined') {
                        init_purplebook_tree.initial = 1;
                        node_id = 'all';
                    }
                    if (typeof(n.attr) != 'undefined') {
                        node_id = n.attr('node_id');
                    }
                    // the result is fed to the AJAX request `data` option
                    return { 
                        module : "purplebook"
                        , act : "getPurplebookList"
                        , node_id : node_id
                        , node_type : "1"
                    }; 
                },
                "success" : function(d) { 
                    p_hide_waiting_message();
                    if (d.error == -1) {
                        jQuery('#smsPurplebookTree').html(d.message);
                        return;
                    }
                    /*
                    if (typeof(d.data)=='undefined' || d.data.length == 0) {
                        alert('none');
                        return d.data;
                    }
                    */
					/*
                    for(i = 0; i < d.data.length; i++) {
						console.log(d.data[i]);
                        if (d.data[i].attr.subnode > 0) {
                            d.data[i].data = "[" + d.data[i].attr.subnode + "]" + d.data[i].data;
                        }
                    }
					*/
                    return d.data; 
                }
            }
        },
        // we dont use this because cannot support json_data.
        "search" : {
            "ajax" : {
                contentType: "application/json; charset=utf-8",
                "url" : "./",
                "data" : function (str) {
                    return { 
                        module : "purplebook"
                        , act : "getPurplebookSearchFolder"
                        , search : str
                    }; 
                },
                "success" : function(d) { 
                    for(i = 0; i < d.data.length; i++) {
                        d.data[i] = '#node_'+d.data[i];
                    }
                    return d.data;
                }
            }
        },
        // Using types - most of the time this is an overkill
        // Still meny people use them - here is how
        "types" : {
            // I set both options to -2, as I do not need depth and children count checking
            // Those two checks may slow jstree a lot, so use only when needed
            "max_depth" : 12,
            "max_children" : -2,
            // I want only `drive` nodes to be root nodes 
            // This will prevent moving or creating any other type as a root node
            "valid_children" : [ "root","shared","trashcan" ],
            "types" : {
                "default" : {
                    // I want this type to have no children (so only leaf nodes)
                    // In my case - those are files
                    "valid_children" : "none",
                    // If we specify an icon for the default type it WILL OVERRIDE the theme icons
                    "icon" : {
                        "image" : img_base + "file.png"
                    }
                },
                "folder" : {
                    // can have files and other folders inside of it, but NOT `drive` nodes
                    "valid_children" : [ "folder","shared_folder" ],
                    "icon" : {
                        "image" : img_base + "folder.png"
                    }
                },
                "shared_folder" : {
                    "valid_children" : [ "folder","shared_folder" ],
                    "icon" : {
                        "image" : img_base + "shared_folder.png"
                    }
                },
                "root" : {
                    "valid_children" : [ "folder","shared_folder" ],
                    "icon" : {
                        "image" : img_base + "root.png"
                    },
                    "start_drag" : false,
                    "move_node" : false,
                    "delete_node" : false,
                    "remove" : false
                },
                "trashcan" : {
                    "valid_children" : [ "folder" ],
                    "icon" : {
                        "image" : img_base + "trashcan.png"
                    },
                    "start_drag" : false,
                    "move_node" : false,
                    "delete_node" : false,
                    "remove" : false
                },
                "shared" : {
                    "valid_children" : [ "folder" ],
                    "icon" : {
                        "image" : img_base + "folder_public.png"
                    },
                    "start_drag" : false,
                    "dnd_show" : false,
                    "dnd_open" : false,
                    "dnd_enter" : false,
                    "dnd_finish" : false,
                    "move_node" : false,
                    "delete_node" : false,
                    "remove" : false
                }
            }
        },
        // For UI & core - the nodes to initially select and open will be overwritten by the cookie plugin

        // the UI plugin - it handles selecting/deselecting/hovering nodes
        "ui" : {
            // this makes the node with ID node_4 selected onload
            "initially_select" : [ "node_0" ]
        },
        // the core plugin - not many options here
        "core" : { 
            "html_titles" : "html"
            ,"strings" : { loading : "로딩중 ...", new_node : "새폴더" }
        },
        "dnd" : {
            "drag_check" : function() {
                return {
                    after : false
                    , before : false
                    , inside : true
                };
            }
            , "drag_finish" : function(data) {
                $o = jQuery(data.o);
                $r = jQuery(data.r);
                if (!$o.hasClass('jstree-draggable')) {
                    $o = $o.parent();
                }
                purplebook_move_node($o.attr('node_id'), $r.attr('node_id'));
            }
            , "drop_check" : function(data) {
                return true;
            }
            , "drop_finish" : function() {
                return true;
            }
        },
        "contextmenu" : {
            "items" : {
				"create" : {
					"separator_before"	: false,
					"separator_after"	: true,
					"label"				: "만들기",
					"action"			: function (obj) { this.create(obj); }
				},
				"rename" : {
					"separator_before"	: false,
					"separator_after"	: false,
					"label"				: "이름변경",
					"action"			: function (obj) { this.rename(obj); }
				},
				"remove" : {
					"separator_before"	: false,
					"icon"				: false,
					"separator_after"	: false,
					"label"				: "삭제",
					"action"			: function (obj) { this.remove(obj); }
				},
                "cut" : {
                    "separator_before"	: true,
                    "separator_after"	: false,
                    "label"				: "잘라내기",
                    "action"			: function (obj) { this.cut(obj); }
                },
                "paste" : {
                    "separator_before"	: false,
                    "icon"				: false,
                    "separator_after"	: false,
                    "label"				: "붙여넣기",
                    "action"			: function (obj) { this.paste(obj); }
                },
                "share" : {
                    "separator_before"	: true,
                    "icon"				: false,
                    "separator_after"	: false,
                    "label"				: "공유",
                    "action"			: function (obj) { this.share(obj); }
                },
                "properties" : {
                    "separator_before"	: false,
                    "icon"				: false,
                    "separator_after"	: false,
                    "label"				: "정보보기",
                    "action"			: function (obj) { this.properties(obj); }
                },
                "xldownload" : {
                    "separator_before"	: true,
                    "icon"				: false,
                    "separator_after"	: false,
                    "label"				: "엑셀 다운로드",
                    "action"			: function (obj) { this.xldownload(obj); }
                }
            }
        }
    })
    .bind("create.jstree", function (e, data) {
        //parent_route = data.rslt.parent.attr("node_route");
        parent_node = data.rslt.parent.attr("node_id");

        jQuery.ajax({
            type: "POST",
            dataType: "json",
            contentType: "application/json; charset=utf-8",
            url : "./", 
            data : { 
                module : "purplebook"
                , act : "procPurplebookAddNode"
                , parent_node : parent_node
                , node_type : "1"
                , node_name : data.rslt.name
            }, 
            success : function(r) {
                if (r.error == -1) {
                    jQuery.jstree.rollback(data.rlbk);
                    alert(r.message);
                } else {
                    data.rslt.obj.attr("id", "node_" + r.id).attr('node_id',r.node_id).attr('node_name',r.node_name).attr('node_route',r.node_route).attr('rel','folder');
                }
            }
        });
    })
    .bind("remove.jstree", function (e, data) {

        data.rslt.obj.each(function () {
            /*
            if (!confirm('['+jQuery(this).attr('node_name')+'] 폴더 아래 모든 데이터를 영구 삭제합니다.\n삭제하시겠습니까?')) {
                jQuery.jstree.rollback(data.rlbk);
                return false;
            }
            */
            jQuery.ajax({
                type: 'POST',
                dataType: "json",
                contentType: "application/json; charset=utf-8",
                async : false,
                url: "./",
                data : { 
                    module : "purplebook"
                    , act : "procPurplebookMoveNode"
                    , node_id : this.id.replace("node_","")
                    , parent_id : 't.'
                }, 
                success : function (r) {
                    if (r.error == -1) {
                        alert(r.message);
                    } else {
                        // do nothing
                    }
                }
            });
        });
    })
    .bind("rename.jstree", function (e, data) {
        var node_id = data.rslt.obj.attr("node_id");
        var node_name = data.rslt.new_name;

        jQuery.ajax({
            type: "POST",
            dataType: "json",
            contentType: "application/json; charset=utf-8",
            url : "./", 
            data : { 
                module : "purplebook"
                , act : "procPurplebookRenameNode"
                , node_id : node_id
                , node_name : node_name
            }, 
            success : function(r) {
                if (r.error == -1) {
                    jQuery.jstree.rollback(data.rlbk);
                    alert(r.message);
                }
            }
        });
    })
    .bind("move_node.jstree", function (e, data) {
        data.rslt.o.each(function (i) {
            var node_id = jQuery(this).attr("node_id");
            var parent_id = data.rslt.np.attr("node_id");

            jQuery.ajax({
                type: 'POST',
                dataType: "json",
                contentType: "application/json; charset=utf-8",
                async : false,
                url: "./",
                data : { 
                    module : "purplebook"
                    , act : "procPurplebookMoveNode"
                    , node_id : node_id
                    , parent_id : parent_id
                    , copy : data.rslt.cy ? 1 : 0
                },
                success : function (r) {
                    if (r.error == -1) {
                        jQuery.jstree.rollback(data.rlbk);
                    } else {
                        jQuery(data.rslt.oc).attr("id", "node_" + r.id);
                        if (data.rslt.cy && jQuery(data.rslt.oc).children("UL").length) {
                            data.inst.refresh(data.inst._get_parent(data.rslt.oc));
                        }
                    }
                }
            });
        });
    })
    .bind("select_node.jstree", function(e, data) {
        var node = data.rslt.obj;
        pb_load_list(node);
    });
}

function completeGetCashInfo(ret_obj, response_tags) {
    var obj = new Object();
    obj.cash = parseInt(ret_obj['cash']);
    obj.point = parseInt(ret_obj['point']);
    //obj.mdrop = parseInt(ret_obj['mdrop']);
    obj.sms_price = parseInt(ret_obj['sms_price']);
    obj.lms_price = parseInt(ret_obj['lms_price']);
    obj.mms_price = parseInt(ret_obj['mms_price']);
	obj.deferred_payment = ret_obj['deferred_payment'];
    do_after_get_cashinfo(obj);
}

function get_pointinfo()
{
	var params = new Array();
	var response_tags = new Array('error','message','point','msg_not_enough_point');
	exec_xml('purplebook', 'getPurplebookPointInfo', params, completeGetPointInfo, response_tags);
}

function get_cashinfo()
{
    var obj = new Object();
    obj.cash = 0;
    obj.point = 0;

    var params = new Array();
    var response_tags = new Array('error','message','cash','point','sms_price','lms_price','mms_price','deferred_payment');
    exec_xml('purplebook', 'getPurplebookCashInfo', params, completeGetCashInfo, response_tags);
}

// 현재잔액 표시
function set_balance()
{
    var params = new Array();
    var response_tags = new Array('error','message','cash','point','sms_price','lms_price','mms_price','deferred_payment');
    exec_xml('purplebook', 'getPurplebookCashInfo', params, function (ret_obj){
		cash = parseInt(ret_obj['cash']);
		point = parseInt(ret_obj['point']);

		cash = add_num_comma(cash);
		point = add_num_comma(point);

		jQuery("#pb_balance").html("현재잔액 : " + cash + "(캐쉬) " + point + "(포인트)");
	}, response_tags);
}

// 1000단위 콤마 붙이는 함수
function add_num_comma(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

function submit_messages() {
    $current = get_active_textarea();

    //내용을 입력하였는지 검사
    if (!$current.val() || $current.val() == initial_content) {
        alert("내용을 입력해 주세요.");
        $current.focus();
        return false;
    }

    //발신전화번호 검사
    var sNum = jQuery('#smsPurplebookCallback').val();
    if (!checkCallbackNumber(sNum))
    {
        alert('보내는사람의 전화번호를 정확히 입력하세요\n입력 예) 15881004 , 021231234, 0101231234');
        jQuery('#smsPurplebookCallback').focus().select();
        return false;
    }		


    //받는사람 번호 구성
    var r_num = list_counting();

    //받는사람이 없을 경우
    if (r_num == 0)
    {
        alert('받는사람을 입력하세요');
        return false;
    }

    // 캐쉬정보 확인후 발송루틴 호출
    if (g_use_point == 'Y') {
        get_pointinfo();
	} else {
        get_cashinfo();
	}

    return false;
}

(function($) {

	// 받은사람 목록에 존재하는 번호인지 조사
	function isExistNum(newNum)
	{
		var pureNum = toOnlyNumber(newNum, "-", "");
		var exist = false;
		var $listAll = $('li', '#smsPurplebookTargetList')
		
		$listAll.each(function(idx) {
				var eNum = toOnlyNumber($('.number',this).text(), "-", "");
				if (pureNum == eNum)
				{
					exist = true;
					return;
				}
		});
		
		return exist;
			
	}

    /**
     * 0: ok
     * 1: already exist number
     **/
 	function addNum(newNum, rName, node_id) {
        newNum = newNum.replace(/-/g,'');
        $except_list = jQuery('#smsPurplebookExceptList');

		// 국가코드 +로 시작할 경우 아이디를 찾지 못하기때문에  idNum을 따로 만들어준다
		idNum = newNum;
		if (newNum.charAt(0) == '+') {
			idNum = newNum.substring(1, newNum.length)
		}

		// 이미 존재하는 번호인지 검사
        if ($('#tel'+idNum).length > 0)
		{
            if ($('#dup'+idNum).length > 0) {
                var $count = $('.count', $('#dup'+idNum).parent());
                var countVal = $count.text();

                countVal = parseInt(countVal) + 1;
                $count.text(countVal);
            } else {
				overlap_count = $('#pb_overlap_count').text();
				overlap_val = parseInt(overlap_count) + 1;

				$("#pb_overlap_count").html(overlap_val);

                $except_list.append('<li><span class="name">' + rName + '</span><span id="dup' + idNum + '" class="number">' + newNum + '</span><span class="count">1</span></li>');
            }


			// pop_message 호출
			call_pb_pop_message(".pop_overlap", "중복번호에 추가되었습니다");

            return 1;
		}

		if (!node_id) node_id = '';

		// 이상이 없을 경우 추가 (개별선택, 삭제 이벤트 포함)
        $('#smsPurplebookTargetList').append('<li id="tel' + idNum + '" ' + 'node_id=' + node_id + '><span class="checkbox"></span><span class="name">' + rName + '</span><span class="number" phonenum="' + newNum + '">'+ newNum +'</span><span class="delete" title="삭제">삭제</span><span class="statusBox"></span></li>');
   
		return 0;
	}

    function addGroup(groupSrl, groupName)
    {
        if (addGroup.sequence == undefined)
            addGroup.sequence = 0;
        $('#grouplist').append('<li><span class="sequence">' + ++addGroup.sequence + '</span><span class="chkGroup" group_srl="' + groupSrl + '" title="체크 후 오른쪽 추가버턴을 누르세요."></span><span class="groupName" group_srl="' + groupSrl + '" title="선택하시면 아래에 ' + groupName + '그룹의 멤버목록이 나타납니다.">' + groupName + '</span></li>');
    }

    function addMember(seq, obj)
    {
        $('#custlist').append('<li class="memberInfo"><span class="sequence">' + seq + '</span><span class="chkMember"></span><span class="id">' + obj.user_id + '</span><span class="name" member_srl="' + obj.member_srl + '">' + obj.user_name + '</span><span class="number">' + getSimpleDashTel(obj.cellphone.replace(/\|\@\|/g, '')) + '</span></li>');
    }


	// 받는사람 직접추가
	function addDirectNumber() 
	{ 
        var new_num = $('#inputDirectNumber').val();
        var ref_name = $('#inputDirectName').val();
        if (new_num == "") { 
            alert("전화번호를 입력하세요");
            $('#inputDirectNumber').focus();
            return false;
        }
        addNum(new_num, ref_name);
        updateExceptListCount();
        scrollBottomTargetList();
        updateTargetListCount();

        var params = new Array();
        params['ref_name'] = ref_name;
        params['phone_num'] = new_num;
        var response_tags = new Array('error','message');
        exec_xml('purplebook', 'procPurplebookSaveReceiverNumber', params, function() { }, response_tags);

        $('#inputDirectName').val('');
        $('#inputDirectNumber').val('');
        $('#inputDirectName').focus();
	}

    function addRecipient(text)
    {
        if (text == "") {
            return 0;
        }

        var reVal = text;
        var rePhone = '';
        var reName = '';
        var countList = 0;

        var arrayList = reVal.split("\n");
        var lengthList = arrayList.length;
        var pattern = /([0-9-()]{8,15})[ ,\t]*([\W\w]*)/;

        for (var i = 0; i < lengthList; i++) {
            var strLine = '';
            row = pattern.exec(arrayList[i]);
            if (!row) continue;

            rePhone = row[1].replace(/[-\(\) ]/g, "");
            reName = row[2];

            if (!addNum(rePhone, reName)) countList++;
        }
        updateExceptListCount();
        scrollBottomTargetList();
        updateTargetListCount();

        $('#smsPurplebookBulkList').val('');
        $('#layer_mass').css('display', 'none');
        $('span.total', '#layer_mass').text('총 0 명');

        return countList;
    }

	/**
	 * @brief 주소목록에 선택된 폴더를 받는사람 목록으로 추가한다.
	 */
 	function add_folder(node_route, node_id, f_name) {
        $except_list = jQuery('#smsPurplebookExceptList');

		/*
		// 이미 존재하는 번호인지 검사
        if ($('#folder_' + node_id).length > 0)
		{
			$except_list.append('<li><span class="name">' + f_name + '</span><span id="dup_"' + node_id + '></span></li>');
            return 1;
		}
		*/

		var params = new Array();
		var response_tags = new Array('error','message','data');

		params['node_route'] = node_route + node_id + ".";

		// 최상위 폴더가 들어오면 node_id에 node_route가 들어오기 때문에 처리를 해줘야한다.
		if (node_id == 'f.') {
			params['node_route'] = node_id;
			node_id = 'f';
		}

		exec_xml('purplebook', 'getPurplebookListCount', params, function(ret_obj) {

			// 이상이 없을 경우 추가 (개별선택, 삭제 이벤트 포함)
			$('#smsPurplebookTargetList').append('<li class="pb_folder_address" id="folder_' + node_id + '" ' + 'node_id=' + node_id + ' count=' + ret_obj["data"] + ' node_route=' + params['node_route'] + '><span class="checkbox"></span><span class="name">' + f_name + '</span><span class="number">(' + ret_obj["data"] + '명)' + '</span><span class="delete" title="삭제">삭제</span><span class="statusBox"></span></li>');
			
		}, response_tags);
   
		return 0;
	}

	/**
	 * @brief 폴더의 node_id로 명단을 가져와서 받는사람 목록에 추가한다.
	 */
    function add_folder_to_recipient()
    {
        var t = $('#smsPurplebookTree').jstree("get_selected");
        if (t.length == 0)
        {
            alert('체크된 폴더가 없습니다.\n왼쪽 폴더목록에서 체크박스에 체크하세요.');
            return;
        }

		// 이미 존재하는 폴더인지 검사
        if ($('#folder_'+t.attr('node_id')).length > 0)
		{
            if ($('#f_dup_'+t.attr('node_id')).length > 0) {
                var $count = $('.count', $('#f_dup_'+t.attr('node_id')).parent());
                var countVal = $count.text();

                countVal = parseInt(countVal) + 1;
                $count.text(countVal);
            } else {
				overlap_count = $('#pb_overlap_count').text();
				overlap_val = parseInt(overlap_count) + 1;

				$("#pb_overlap_count").html(overlap_val);

                $except_list.append('<li><span class="name">' + t.attr('node_name') + '</span><span id="f_dup_' + t.attr('node_id') + '" class="number">' + t.attr('count') + '</span><span class="count">1</span></li>');
            }

			// pop_message 호출
			call_pb_pop_message(".pop_overlap", "중복번호에 추가되었습니다");

            return 1;
		}

        p_show_waiting_message();

		// 폴더 추가
		add_folder(t.attr('node_route'), t.attr('node_id'), t.attr('node_name'));

		// 이렇게 안하면 처음에 카운트를 제대로 세지 못한다.
		setTimeout(function() {
			//updateExceptListCount();
			scrollBottomTargetList();
			updateTargetListCount();
			display_cost();
			p_hide_waiting_message();
		}, 500);
    }

	/**
	 * @brief 주소목록에 선택된 명단을 받는사람 목록으로 추가한다.
	 */
    function add_addrs_to_recipient() {
        p_show_waiting_message();

		// 컨텐츠 SET
		var selected_folders = jQuery('#smsPurplebookTree').jstree('get_selected');
		if (selected_folders.length > 0) {
			var node = jQuery(selected_folders[0]);
		}

		// 0.5초 뒤 실행, 이거 왜 이렇게 하는걸까? -_-
        setTimeout(function() {
            var succ_count=0;
            var list = new Array();
            $('span.checkbox.on', '#smsPurplebookList li').each(function() {
                list.push($(this));
            });
            if (list.length == 0) { // 선택 항목이 없다면
                alert('체크된 항목이 없습니다.\n왼쪽 목록에서 선택하세요.');
                p_hide_waiting_message();
                return;
            }
            for (var i = 0; i < list.length; i++) {
                var obj = list[i];
                var phonenum = $('.nodePhone', $(obj).parent()).text(); // 폰번호
                var name = $('.nodeName', $(obj).parent()).text(); // 이름
				var node_id = $(obj).parent().attr('node_id'); // node_id
                if (phonenum.length <= 0) continue;
                if (!addNum(phonenum, name, node_id)) succ_count++; // 실컷 카운팅하지만 뒤에서 안써먹는다-_-
            }

			// 중복번호, 스크롤내리고, 카운트 출력갱신하고, 소요비용 재계산해서 다시 출력하는 함수들을 호출하고 있는데 복잡하다. 개선이 필요한 듯.
            updateExceptListCount();
            scrollBottomTargetList();
            updateTargetListCount();
            display_cost();

            p_hide_waiting_message();
        }, 500);
    }

    function append_address()
    {
		var selected_folders = $('#smsPurplebookTree').jstree('get_selected');
		var node_name = $('#inputPurplebookName').val();
		var phone_num = $('#inputPurplebookPhone').val();
		var memo1 = $('#inputPurplebookMemo1').val();
		var memo2 = $('#inputPurplebookMemo2').val();
		var memo3 = $('#inputPurplebookMemo3').val();

		if (selected_folders.length != 1) {
			alert('선택된 폴더가 없습니다.');
			return;
		}

		var node = $(selected_folders[0]);

		if (node_name.length == 0) {
			alert('이름을 입력하세요.');
			$('#inputPurplebookName').focus();
			return;
		}
		if (phone_num.length == 0) {
			alert('폰번호를 입력하세요.');
			$('#inputPurplebookPhone').focus();
			return;
		}

		if (!checkPhoneFormat(phone_num)) {
			if (!confirm("유효하지 않은 전화번호입니다 (" + phone_num + ")\n계속 진행하시겠습니까?"))
			return false;
		}

		$.ajax({
			type : "POST"
			, contentType: "application/json; charset=utf-8"
			, url : "./"
			, data : { 
						module : "purplebook"
						, act : "procPurplebookAddNode"
						, parent_node : node.attr('node_id')
						, parent_route : node.attr('node_route')
						, node_name : node_name
						, node_type : '2'
						, phone_num : phone_num
						, memo1 : memo1
						, memo2 : memo2
						, memo3 : memo3
					 }
			, dataType : "json"
			, success : function (data) {
				if (data.error == -1)
				{
					alert(data.message);
					return;
				}
				add_to_list(data.node_id, node_name, phone_num);

				$('#inputPurplebookPhone').val('');
				$('#inputPurplebookName').val('');
				$('#inputDirectMemo1').val('');
				$('#inputDirectMemo2').val('');
				$('#inputDirectMemo3').val('');
				$('#inputPurplebookName').focus();

				updatePurplebookListCount();
				pb_load_list(null,true);
			}
			, error : function (xhttp, textStatus, errorThrown) { 
				alert(errorThrown + " " + textStatus); 
			}
		});
    }

    jQuery(function($) {

        // tipsy
        $('input, a, img, button','#smsPurplebook,#smsMessage').filter(function(index) { return !$(this).hasClass('help'); }).tipsy();

        $current = get_active_textarea();
        initial_content = $current.val();

        $('.phonescreen').autoResize({extraSpace:10, animate:false});

		// layer_append.html에서 주소록에 명단 추가
        $('#btnAddAddress').live('click',function() {
            append_address();
            return false;
        });

        $('#btnPurplebookMemberAdd').click(function() {
            var selected_folders = $('#smsPurplebookTree').jstree("get_selected");
            if (selected_folders.length != 1) {
                alert('명단을 추가할 폴더를 한개만 선택하세요.');
                return false;
            }

            if ($('#smsPurplebookAppendPane').css('display') == 'none')
                $('#smsPurplebookAppendPane').css('display', 'block');
            else
                $('#smsPurplebookAppendPane').css('display', 'none');

            $('#smsPurplebookTargetTreePane').css('display', 'none');
            $('#inputPurplebookName').focus();
        });

        $('.pop_move', '#smsPurplebook').click(function() {
            var params = new Array();
			var response_tags = new Array('error','message','data');

			params['g_mid'] = g_mid;
			params['layer_name'] = 'layer_move';

			layer_id = '#layer_move';

			exec_xml('purplebook', 'getPopupLayer', params, function(ret_obj) {
				if (ret_obj["data"]) {
					jQuery(layer_id).html(ret_obj["data"]);
					if (jQuery(layer_id).css('display') == 'block') jQuery(layer_id).html('');

					delete init_target_tree.initial;
					init_target_tree('#smsPurplebookTargetTreeMove',g_tpl_path+'img/');

					$obj = jQuery(layer_id);
					$extra = $('#layer_copy');
					show_and_hide($obj, $extra);
				}
			}, response_tags);

            return false;
        });

        $('.pop_copy','#smsPurplebook').click(function() {
			var params = new Array();
			var response_tags = new Array('error','message','data');

			params['g_mid'] = g_mid;
			params['layer_name'] = 'layer_copy';

			layer_id = '#layer_copy';

			exec_xml('purplebook', 'getPopupLayer', params, function(ret_obj) {
				if (ret_obj["data"]) {
					jQuery(layer_id).html(ret_obj["data"]);
					if (jQuery(layer_id).css('display') == 'block') jQuery(layer_id).html('');

					delete init_target_tree.initial;
					init_target_tree('#smsPurplebookTargetTreeCopy',g_tpl_path+'img/');

					$obj = jQuery(layer_id);
					$extra = $('#layer_move');
					show_and_hide($obj, $extra);
				}
			}, response_tags);

            return false;
        });

        $('.pop_append','#smsPurplebook').click(function() {
			var params = new Array();
			var response_tags = new Array('error','message','data');

			params['g_mid'] = g_mid;
			params['layer_name'] = 'layer_append';

			layer_id = '#layer_append';

			exec_xml('purplebook', 'getPopupLayer', params, function(ret_obj) {
				if (ret_obj["data"]) {
					jQuery(layer_id).html(ret_obj["data"]);
					if (jQuery(layer_id).css('display') == 'block') jQuery(layer_id).html('');
					$obj = jQuery(layer_id);

					show_and_hide($obj,null,{show_func:function(){$('#inputPurplebookName',$obj).focus();}});
				}
			}, response_tags);
			
            return false;
        });

        $('.pop_recent','#smsPurplebook').click(function() {
			var params = new Array();
			var response_tags = new Array('error','message','data');

			params['g_mid'] = g_mid;
			params['layer_name'] = 'layer_recent';

			layer_id = '#layer_recent';

			exec_xml('purplebook', 'getPopupLayer', params, function(ret_obj) {
				if (ret_obj["data"]) {
					jQuery(layer_id).html(ret_obj["data"]);
					if (jQuery(layer_id).css('display') == 'block') jQuery(layer_id).html('');
					$obj = jQuery(layer_id);
					show_and_hide($obj,null,{show_func:pb_load_recent_numbers});
				}
			}, response_tags);
			
            return false;
        });

        $('li','#layer_recent').live('click',function() {
            var name = $('.name',$(this)).text();
            var phonenum = $('.phonenum',$(this)).text();
            addNum(phonenum,name);
            updateExceptListCount();
        });

        // delete recent number
        $('.delete','#layer_recent').live('click',function() {
            var receiver_srl = $(this).attr('receiver_srl');
            var params = new Array();
            params['receiver_srl'] = receiver_srl;
            var response_tags = new Array('error','message');
            exec_xml('purplebook', 'procPurplebookDeleteReceiverNumber', params, function(ret_obj,response_tags) { pb_load_recent_numbers(); }, response_tags);
        });

        // delete recent content
        $('.delete','#layer_messages').live('click',function() {
            var message_srl = $(this).attr('message_srl');
            var params = new Array();
            params['message_srl'] = message_srl;
            var response_tags = new Array('error','message');
            exec_xml('purplebook', 'procPurplebookDeleteMessage', params, function(ret_obj,response_tags) { pb_load_saved_messages(); }, response_tags);
        });

        $('#smsPurplebookDoMove').live('click',function() {
            var selected_folders = $('#smsPurplebookTargetTreeMove').jstree('get_selected');

            if (selected_folders.length != 1) {
                alert('명단을 이동한 폴더를 한개만 선택하세요.');
                return false;
            }

            var $node = $(selected_folders[0]);
            if (!$node) {
                alert('이동할 폴더를 선택하세요.');
                return;
            }
            node_route = $node.attr('node_route') + $node.attr('node_id') + '.';
            node_name = $node.attr('node_name');
            var target_node = $node.attr('node_id');

            p_show_waiting_message();

            var list = new Array();

            $('span.checkbox.on', 'ul#smsPurplebookList li').each(function() {
                list.push($(this).parent().attr('node_id'));
            });

            if (list.length == 0)
            {
                p_hide_waiting_message();
                alert('이동할 명단을 체크하세요.');
                return;
            }

            if (!confirm(list.length + '건의 명단을 [' + node_name + ']폴더로 옮기겠습니까?'))
            {
                p_hide_waiting_message();
                alert('취소했습니다.');
                return;
            }

            $.ajax({
                type : "POST"
                , contentType: "application/json; charset=utf-8"
                , url : "./"
                , data : { 
                            module : "purplebook"
                            , act : "procPurplebookMoveList"
                            , node_list : JSON.stringify(list)
                            , parent_id : target_node
                         }
                , dataType : "json"
                , success : function (data) {
                    p_hide_waiting_message();
                    pb_load_list(null, true);
                    if (data.error == -1)
                        alert(data.message);
                }
                , error : function (xhttp, textStatus, errorThrown) { 
                    p_hide_waiting_message();
                    alert(errorThrown + " " + textStatus); 
                }
            });
            return false;
        });

        $('#smsPurplebookDoCopy').live('click',function() {
            var selected_folders = $('#smsPurplebookTargetTreeCopy').jstree('get_selected');
            if (selected_folders.length != 1) {
                alert('명단을 추가할 폴더를 한개만 선택하세요.');
                return false;
            }

            var $node = $(selected_folders[0]);
            if (!$node) {
                alert('복사할 폴더를 선택하세요.');
                return;
            }
            node_route = $node.attr('node_route') + $node.attr('node_id') + '.';
            node_name = $node.attr('node_name');
            var node_id = $node.attr('node_id');

            p_show_waiting_message();

            var list = new Array();

            $('span.checkbox.on', 'ul#smsPurplebookList li').each(function() {
                list.push($(this).parent().attr('node_id'));
            });

            if (list.length == 0)
            {
                p_hide_waiting_message();
                alert('복사할 명단을 체크하세요.');
                return;
            }

            if (!confirm(list.length + '건의 명단을 [' + node_name + ']폴더로 복사하시겠습니까?'))
            {
                p_hide_waiting_message();
                alert('취소했습니다.');
                return;
            }

            $.ajax({
                type : "POST"
                , contentType: "application/json; charset=utf-8"
                , url : "./"
                , data : { 
                            module : "purplebook"
                            , act : "procPurplebookCopy"
                            , node_list : JSON.stringify(list)
                            , node_id : node_id
                         }
                , dataType : "json"
                , success : function (data) {
                    p_hide_waiting_message();
                    pb_load_list(null,true);
                    if (data.error == -1)
                        alert(data.message);
                }
                , error : function (xhttp, textStatus, errorThrown) { 
                    p_hide_waiting_message();
                    alert(errorThrown + " " + textStatus); 
                }
            });
            return false;
        });


        $('.left_select .btn_delete','#smsPurplebook').click(function() {
            p_show_waiting_message();

            var list = new Array();

            $('span.checkbox.on', 'ul#smsPurplebookList li').each(function() {
                list.push($(this).parent().attr('node_id'));
            });

            if (list.length == 0)
            {
                p_hide_waiting_message();
                alert('삭제할 명단을 체크하세요.');
                return false;
            }

            $.ajax({
                type : "POST"
                , contentType: "application/json; charset=utf-8"
                , url : "./"
                , data : { 
                            module : "purplebook"
                            , act : "procPurplebookMoveList"
                            , node_list : JSON.stringify(list)
                            , parent_id : 't.'
                         }
                , dataType : "json"
                , success : function (data) {
                    p_hide_waiting_message();
                    pb_load_list(null, true);
                    if (data.error == -1) {
                        alert(data.message);
                    }
                }
                , error : function (xhttp, textStatus, errorThrown) { 
                    p_hide_waiting_message();
                    alert(errorThrown + " " + textStatus); 
                }
            });
            return false;
        });

        // 폴더공유 회원추가
        $('#btn_append_id','#layer_share').live('click',function() {
            var user_id = $('#input_user_id','#smsPurplebook').val();
            var params = new Array();
            params['user_id'] = user_id;
            params['node_id'] = pb_share_folder.node_id;
            var response_tags = new Array('error','message','node_id','member_srl','user_id','nick_name','shared_count');
            exec_xml('purplebook', 'procPurplebookShareNode', params, completeShareNode, response_tags);
        });

        // 폴더공유 회원삭제
        $('.delete','#smsPurplebook #share_list').live('click',function() {
            var node_id = $(this).parent().attr('node_id');
            var member_srl = $(this).parent().attr('member_srl');
            var params = new Array();
            params['node_id'] = node_id;
            params['member_srl'] = member_srl;
            var response_tags = new Array('error','message','member_srl','shared_count');
            exec_xml('purplebook', 'procPurplebookUnshareNode', params, function(ret_obj,response_tags) { completeUnshareNode(node_id,ret_obj,response_tags) }, response_tags);

        });

        $('ul#smsPurplebookList li').live('click', function() {
            $('.checkbox', $(this)).toggleClass("on");
        });

        $('#smsPurplebookToggleList').toggle(
            function () { 
                $(this).addClass("on");
                $('.checkbox', 'ul#smsPurplebookList li').addClass("on");
                return false;
            },
            function () { 
                $(this).removeClass("on");
                $('.checkbox', 'ul#smsPurplebookList li').removeClass("on");
            }
        );

        // refresh folder tree
        $('#smsPurplebookRefreshTree').click(function() {
            $('#smsPurplebookTree').html('');
            $('#smsPurplebookList').html('');
            //$('#smsPurplebookSelectedFolderName').html('&nbsp;');
            $('#smsPurplebookListCount').html('');
            //$('#smsPurplebookListPages').html('');

            delete init_purplebook_tree.initial;
            init_purplebook_tree(init_purplebook_tree.img_base);
            return false; // because of a(anchor) tag
        });

        // refresh target tree(copy)
        $('.btn_refresh','#layer_copy').live('click',function() {
            $('#smsPurplebookTargetTreeCopy').html('');
            delete init_target_tree.initial;
            init_target_tree('#smsPurplebookTargetTreeCopy',init_target_tree.img_base);
            return false;
        });

        // refresh target tree(move)
        $('.btn_refresh','#layer_move').live('click',function() {
            $('#smsPurplebookTargetTreeMove').html('');
            delete init_target_tree.initial;
            init_target_tree('#smsPurplebookTargetTreeMove',init_target_tree.img_base);
            return false;
        });

        // refresh target tree(copy to addrbook)
        $('.btn_refresh','#layer_addrbook').live('click',function() {
            $('#smsPurplebookTargetTreeAddrbook').html('');
            delete init_target_tree.initial;
            init_target_tree('#smsPurplebookTargetTreeAddrbook',init_target_tree.img_base);
            return false;
        });


        $('#inputPurplebookPhone').keyup(function(event) {
            $(this).val(getDashTel($(this).val()));
        });
        $('#inputPurplebookPhone').keypress(function(event) {
            if (event.keyCode == 13)
            {
                append_address();
                return false;
            }
        });

        $('.smsPurplebookListPage').live('click', function() {
            pb_load_list($(this).text());
        });

        $('#btn_search','#smsPurplebook').click(function() {
            var search_word = $(this).prev('input').val();
            $('#search_word','#smsPurplebook').select().focus();
            pb_search_list(search_word);
            return false;
        });

        $('#search_word','#smsPurplebook').keypress(function(event) {
            if (event.keyCode == 13) {
                $(this).select();
                pb_search_list($(this).val());
                return false;
            }
        });

        // modify name by double click
        $('.nodeName').live('dblclick', function() {
            pb_modify_name(this);
        });

        // modify phone by double click
        $('.nodePhone').live('dblclick', function() {
            pb_modify_phone(this);
        });


        $('#addRecipients').click(function() {
            add_addrs_to_recipient();
            return false; // bacause of a(anchor) tag
        });

		$('#addFolder').click(function() {
            add_folder_to_recipient();
            return false; // bacause of a(anchor) tag
        });


        // 수신목록 선택전환
        $('#smsPurplebookToggleTarget').toggle(
            function () { 
                $(this).addClass("on");
                $('span.checkbox', '#smsPurplebookTargetList li').addClass("on");
            },
            function () { 
                $(this).removeClass("on");
                $('span.checkbox', '#smsPurplebookTargetList li').removeClass("on");
            }
        );

        // 받는사람::LI 클릭
        $('#smsPurplebookTargetList li').live('click', function() {
            $('.checkbox', $(this)).toggleClass("on");
        });

        // 받는사람::삭제 버턴 클릭
        $('span.delete','ul#smsPurplebookTargetList li').live('click', function() {
            $(this).parent().remove();
            updateTargetListCount();
        });

        // callback number delete
        $('.deleteCallback').live('click', function() {
            deleteCallback($(this).parent().attr('callback_srl'));
        });

        // 받는사람::선택된 받는사람 삭제
        $('#minusRecipients').click(function () { 
            removeSelectedRecipients();
            return false;
        });

        // 대량추가 선택
        $('#smsPurplebookAddBulk').click(function() {
			var params = new Array();
			var response_tags = new Array('error','message','data');

			params['g_mid'] = g_mid;
			params['layer_name'] = 'layer_mass';

			layer_id = '#layer_mass';

			exec_xml('purplebook', 'getPopupLayer', params, function(ret_obj) {
				if (ret_obj["data"]) {
					jQuery(layer_id).html(ret_obj["data"]);
					if (jQuery(layer_id).css('display') == 'block') jQuery(layer_id).html('');

					$obj = jQuery(layer_id);
					show_and_hide($obj);
				}
			}, response_tags);
			
            return false;
        });

        // 받는사람 전화번호 직접추가 버튼 클릭시 
	    $('#btnDirectAdd','#smsPurplebook').click(function() {
            addDirectNumber();
            return false;
        });

        /*
        // Enter Key Submit 방지
        $('input,#smsPurplebook > *').keypress(function(event) {
            if (event.keyCode == 13)
                return false;
        });
        */

        $('#inputDirectNumber','#smsPurplebook').keyup(function() {
            $(this).val(getDashTel($(this).val()));
        });
        
        $('#inputDirectNumber','#smsPurplebook').keypress(function(event) {
            if (event.keyCode == 13)
            {
                addDirectNumber();
                return false;
            }
        });

	
        // 문자내용의 byte를 세어 출력함 
        $('.phonescreen','#smsPurplebookContentInput').live('keyup', function(event) { 
            if (timeoutHandle) clearTimeout(timeoutHandle);
            timeoutHandle = setTimeout(function() { update_screen(); timeoutHandle = null; }, 200);
        });

		// 문자내용의 byte를 세어 출력함 (firefox는 keyup이벤트가 안먹히기 때문에 focusout으로 처리)
		$('.phonescreen','#smsPurplebookContentInput').focusout( function() { 
			update_screen();
        });

        $('.phonescreen','#smsPurplebookContentInput').live('click', function(event) { 
            set_active_textarea(this);
        });

        // save cursor position
        $('.phonescreen','#smsPurplebookContentInput').live('select click change keyup', function() {
            storeCaret(this);
        });
        $('#main_screen','#smsPurplebookContentInput').live('click', function() { 
            $current = get_active_textarea();
            if ($current.val()==initial_content) $current.val('');
        });

        $('#btnSimplePhoneSend').click(function() {
            if (!g_is_logged) {
                alert(getLang('msg_login_required'));
                return false;
            }
            prepare_direct();
            submit_messages();
            return false;
        });

        // 예약전송 눌렀을 때
        $('#btnSimplePhoneReserv','#smsMessage').click(function() {
			var params = new Array();
			var response_tags = new Array('error','message','data');

			params['g_mid'] = g_mid;
			params['layer_name'] = 'layer_reserv';

			layer_id = '#layer_reserv';

			exec_xml('purplebook', 'getPopupLayer', params, function(ret_obj) {
				if (ret_obj["data"]) {
					jQuery(layer_id).html(ret_obj["data"]);
					if (jQuery(layer_id).css('display') == 'block') jQuery(layer_id).html('');

					$obj = jQuery(layer_id);
					show_and_hide($obj);
				}
			}, response_tags);
			
            return false;
        });

        // 특수문자 버턴 클릭
        $('#btnSimplePhoneSpecial').click(function() {
            if ($('#special_chars').css('display') == 'none')
                $('#special_chars').css('display', 'block');
            else
                $('#special_chars').css('display', 'none');
            return false;
        });

        // 대량추가::점검하기
        $('#btnVerifyList').live('click',function() {
            var obj = cellphone_generalize($('#smsPurplebookBulkList').val());
            $('#smsPurplebookBulkList').val(obj.text);
            $('span.total', '#layer_mass').text('총 ' + obj.count + ' 명');
            return false;
        });

        // 대량추가
        $('#btnAddList').live('click',function() {
            alert(addRecipient($('#smsPurplebookBulkList').val()) + " 명을 추가했습니다.");
            update_screen();
            return false;
        });

        // 비우기
        $('#btnEmptyList').live('click',function() {
            $('#smsPurplebookBulkList').val('');
            return false;
        });

        $('#smsPurplebookBulkList').live('click',function() {
            if (!$(this).attr('firstclick'))
            {
                $(this).val('');
                $(this).attr('firstclick', true);
            }
        });

        // sms분할
        $('#smsSplit','#smsMessage').click(function() {
            update_screen();
        });
        // mms장문
        $('#mmsSend','#smsMessage').click(function() {
            update_screen();
        });

        // 중복번호 버튼
        $('.pop_overlap','#smsPurplebook').click(function() {
			$obj = jQuery('#layer_overlap');
			show_and_hide($obj);
			
            return false;
        });

        // 레이어창 닫기
        $('.btn_closex').live('click', function() {
            $obj = $(this).parents('[id^=layer_]');
            show_and_hide($obj);
            return false;
        });

        // textarea close
        $('.close', '#smsPurplebookContentInput').live('click', function() {
            $(this).parent().parent().remove();
            update_screen();
            return false;
        });

        $('#btnPurplebookCallbackList').toggle(
            function() {
                $('.smsPurplebook .callbackListView').css('display','block');
                refreshCallbackList();
            }
            , function() {
                $('.smsPurplebook .callbackListView').css('display','none');
            }
        );

        $('.phonenum', '#smsPurplebookCallbackList').live('click', function() {
            $('#smsPurplebookCallback').val($(this).text()).select();
            $obj = $(this).parents('#layer_sendid');
            show_and_hide($obj);
        });

        $('.default', '#smsPurplebookCallbackList').live('click', function() {
            var phonenum = $('.phonenum',$(this).parent()).text();
            request_default_number(phonenum);
            $('#smsPurplebookCallback').val(phonenum).select();
        });

        $('#smsPurplebookButtonAddCallback').live('click', function() {
            var params = new Array();
            params['phonenum'] = $('#smsPurplebookInputCallback').val();
            var response_tags = new Array('error','message');
            exec_xml('purplebook', 'procPurplebookSaveCallbackNumber', params, function() {
               refreshCallbackList();
               $('#smsPurplebookInputCallback').val('');
            }, response_tags);
            return false;
        });

        /*
        $('#smsPurplebookButtonCallbackListViewClose','.smsPurplebook').click(function() {
            $('.callbackListView','.smsPurplebook').css('display', 'none');
        });
        */

        $('.btn_addwindow','#smsPurplebookContentInput').live('click', function() {
            extend_screen(this);
            update_screen();
        });

        $('.btn_clear','#smsPurplebookContentInput').live('click', function() {
            set_active_textarea($('textarea',$(this).parent().parent()).val('').focus());
            return false;
        });

        $('.btn_record','#smsPurplebookContentInput').live('click', function() {
            var content = $('textarea',$(this).parent().parent()).val();
            pb_keep_message_content(content);
            return false;
        });

        // 저장된 입력내용
        $('.pop_messages','#smsPurplebookContentInput').live('click',function() {
			var params = new Array();
			var response_tags = new Array('error','message','data');

			params['g_mid'] = g_mid;
			params['layer_name'] = 'layer_messages';

			layer_id = '#layer_messages';

			focus_obj = $(this).parent().next().children('textarea');

			exec_xml('purplebook', 'getPopupLayer', params, function(ret_obj) {
				if (ret_obj["data"]) {
					jQuery(layer_id).html(ret_obj["data"]);
					if (jQuery(layer_id).css('display') == 'block') jQuery(layer_id).html('');

					$obj = jQuery(layer_id);

					set_active_textarea(focus_obj);
					show_and_hide($obj, null, {show_func:pb_load_saved_messages});
				}
			}, response_tags);
			
            return false;
        });

        $('li','#layer_messages').live('click',function() {
            var content = $(this).attr('title');
            $current = get_active_textarea();
            $current.val(content);
            $layer = $('#layer_messages');
            show_and_hide($layer);
        });

		// 전체보기 버튼
		$('#pb_view_all_button').live('click',function() {
			var params = new Array();
			var response_tags = new Array('error','message','data');

			params['g_mid'] = g_mid;
			params['layer_name'] = 'view_all';

			layer_id = '#pb_view_all';

			exec_xml('purplebook', 'getPopupLayer', params, function(ret_obj) {
				if (ret_obj["data"]) {
					jQuery(layer_id).html(ret_obj["data"]);
				}
			}, response_tags);
        });

		// 전송결과 버튼
		$("#pb_result_button").click( function(){
			var params = new Array();
			var response_tags = new Array('error','message','data');

			params['g_mid'] = g_mid;
			params['layer_name'] = 'result';

			layer_id = '#pb_result';

			exec_xml('purplebook', 'getPopupLayer', params, function(ret_obj) {
				if (ret_obj["data"]) {
					jQuery(layer_id).html(ret_obj["data"]);
				}
			}, response_tags);
			return false;
		});

		// 미리보기 버튼
		$("#pb_preview_button").click( function(){
			var params = new Array();
			var response_tags = new Array('error','message','data');

			params['g_mid'] = g_mid;
			params['layer_name'] = 'preview';

			layer_id = '#pb_preview';

			exec_xml('purplebook', 'getPopupLayer', params, function(ret_obj) {
				if (ret_obj["data"]) {
					jQuery(layer_id).html(ret_obj["data"]);
				}
			}, response_tags);
			return false;
		});

		// 머지기능
        $('#btn_pop_merge').live('click',function() {
			var params = new Array();
			var response_tags = new Array('error','message','data');

			params['g_mid'] = g_mid;
			params['layer_name'] = 'layer_merge';

			layer_id = '#layer_merge';

			exec_xml('purplebook', 'getPopupLayer', params, function(ret_obj) {
				if (ret_obj["data"]) {
					jQuery(layer_id).html(ret_obj["data"]);
					if (jQuery(layer_id).css('display') == 'block') jQuery(layer_id).html('');
					$obj = jQuery(layer_id);

					show_and_hide($obj, null, {show_func:pb_load_saved_messages});
				}
			}, response_tags);
			
            return false;
        });

		/*
		 * content에 text추가
		 */
		$('#merge1').live('click',function() {
			insert_merge('{name}');
        });
		$('#merge2').live('click',function() {
            insert_merge('{memo1}');
        });
		$('#merge3').live('click',function() {
            insert_merge('{memo2}');
        });
		$('#merge4').live('click',function() {
            insert_merge('{memo3}');
        });

		function insert_merge(merge)
		{
			$current = get_active_textarea();
			text = $current.val();
            $current.val(text + merge);
			display_type_switch();
			display_cost();
			display_bytes();
		}
		/*
		 * END
		 */


        // 발신번호관리창
        $('.btn_show_layer','#smsMessage .right_button').click(function() {
			var params = new Array();
			var response_tags = new Array('error','message','data');

			params['g_mid'] = g_mid;
			params['layer_name'] = 'layer_sendid';

			layer_id = '#layer_sendid';

			exec_xml('purplebook', 'getPopupLayer', params, function(ret_obj) {
				if (ret_obj["data"]) {
					jQuery(layer_id).html(ret_obj["data"]);
					if (jQuery(layer_id).css('display') == 'block') jQuery(layer_id).html('');

					$obj = jQuery(layer_id);
					
					show_and_hide($obj, null, {show_func:refreshCallbackList});
				}
			}, response_tags);

            return false;
        });

        // 특수문자창
        $('#btn_pop_chars').live("click", function() {
			var params = new Array();
			var response_tags = new Array('error','message','data');

			params['g_mid'] = g_mid;
			params['layer_name'] = 'layer_chars';

			layer_id = '#layer_chars';

			exec_xml('purplebook', 'getPopupLayer', params, function(ret_obj) {
				if (ret_obj["data"]) {
					jQuery(layer_id).html(ret_obj["data"]);
					if (jQuery(layer_id).css('display') == 'block') jQuery(layer_id).html('');
					$obj = jQuery(layer_id);
					show_and_hide($obj);
				}
			}, response_tags);
			
            return false;
        });

		// 폴더생성
		$('#btn_create_folder', '#smsPurplebook').click(function() {
			$('#smsPurplebookTree').jstree('create');
		});

		/*
        // 사용법 레이어
        $('#btn_pop_manual','#smsPurplebook').click(function() {
			var params = new Array();
			var response_tags = new Array('error','message','data');

			params['g_mid'] = g_mid;
			params['layer_name'] = 'layer_manual';

			layer_id = '#layer_manual';

			exec_xml('purplebook', 'getPopupLayer', params, function(ret_obj) {
				if (ret_obj["data"]) {
					jQuery(layer_id).html(ret_obj["data"]);
					if (jQuery(layer_id).css('display') == 'block') jQuery(layer_id).html('');

					$obj = jQuery(layer_id);

					show_and_hide($obj,null,{show_func:function(){
						if (!$obj.attr('first_show')) {
							$('.bodyArea','#layer_manual').html('<iframe src="' + g_manual_url + '" frameborder="0" style="border:0 none; width:100%; height:100%; padding:0; margin:0;"></iframe>');
							$obj.attr('first_show',true);
						}
					}});
				}
			}, response_tags);

            return false;
        });
		*/

        // 휴지통 비우기
		/*
        $('#btn_empty_trash','#smsPurplebook').click(function() {
            clearTrash();
            return false;
        });
		*/

		// 엑셀 다운로드
		$('#btn_excel_download','#smsPurplebook').click(function() {
			var selected_folders = jQuery('#smsPurplebookTree').jstree('get_selected');
			if (selected_folders.length > 0) {
				node = jQuery(selected_folders[0]);
			}

			if (!node) {
                alert('폴더를 선택하세요.');
                return;
            }

			pb_excel_download(node);
		});

        // 예약전송
        $('#btn_reserv_send').live('click',function() {
            if (!g_is_logged) {
                alert(getLang('msg_login_required'));
                return false;
            }
            prepare_reservation();
            submit_messages();
            return false;
        });

        /*
        $('#btn_reserv_cancel','#smsMessage').click(function() {
            $obj = $('.layer_reserv','#smsMessage');
            $obj.hide();
            return false;
        });
        */

        // 사진추가
        $('#btn_attach_pic').live("click", function() {
			var params = new Array();
			var response_tags = new Array('error','message','data');

			params['g_mid'] = g_mid;
			params['layer_name'] = 'layer_upload';

			layer_id = '#layer_upload';

			exec_xml('purplebook', 'getPopupLayer', params, function(ret_obj) {
				if (ret_obj["data"]) {
					jQuery(layer_id).html(ret_obj["data"]);
					if (jQuery(layer_id).css('display') == 'block') jQuery(layer_id).html('');

					$obj = jQuery(layer_id);

					var url = request_uri
						.setQuery('module', 'purplebook')
						.setQuery('act', 'dispPurplebookFilePicker')
						.setQuery('input', 'file_srl')
						.setQuery('filter', 'jpg,gif,png,jpeg');

					XE.filepicker.selected = jQuery('[name=file_srl]', '#smsMessage').get(0);

					$('.bodyArea','#layer_upload').html('<iframe src="' + url + '" frameborder="0" style="border:0 none; width:100%; height:100%; padding:0; margin:0;"></iframe>');

					show_and_hide($obj);
				}
			}, response_tags);
			/*
            XE.filepicker.open(jQuery('[name=file_srl]', '#smsMessage').get(0), '');
            */

            return false;
        });

        // 사진삭제
        $('#btn_detach_pic').live("click", function() {
            XE.filepicker.cancel('file_srl');
            //$(this).hide();            
            //jQuery('#btn_attach_pic').show();
			jQuery('#btn_attach_pic_box').show();
			jQuery('#btn_delete_pic_box').hide();
            update_screen();
            return false;
        });

        $('.btn_bytes','#smsMessage').live('click',function() {
            return false;
        });

        /*
        // input type text
        $('.inputTypeText','#smsPurplebook').mousedown(function() {
            $(this).addClass('on');
        });
        $('.inputTypeText','#smsPurplebook').focus(function() {
            $(this).addClass('on');
        });
        $('.inputTypeText','#smsPurplebook').blur(function() {
            if ($(this).val()=='') {
                $(this).removeClass('on');
            } else {
                $(this).addClass('on');
            }
        });
        */

        // cancel sending
        $('#btn_stop','#layer_status').live('click',function() {
            if (!sendMessageData.send_status && !sendMessageData.send_timer) {
                alert('일시중지 할 수 없습니다');
                return false;
            }

			// sendMessageData.send_timer로 보통발송과 발송간격발송 구분
			if (sendMessageData.send_status == 'complete') {
				alert('이미 접수가 완료됬습니다.');
				return false;
			}
			
			// interval send 정지
			clearInterval(sendMessageData.send_timer);
			sendMessageData.send_timer = false;
			
			// send 정지
			sendMessageData.send_status = 'pause';

			$('.text','#layer_status').text('일시중지하였습니다.');
			return false;
			
        });
        // continue sending
        $('#btn_continue','#layer_status').live('click',function() {
            if (sendMessageData.send_status == 'sending') {
                alert('접수중에 있습니다');
                return false;
            }
            if (sendMessageData.send_status == 'complete') {
                alert('이미 접수가 완료됬습니다.');
                return false;
            }

			// 발송간격설정이 체크되있으면 전송간격 시간을 가져온다
			if (jQuery("#message_interval_check").is(':checked')) {
				g_send_interval = jQuery("#message_send_interval").val() * 1000 * 60;
			}

			sendMessageData.send_status = 'sending'; 

			// sendMessageData.send_timer로 보통발송과 발송간격발송 구분
			if (jQuery("#message_interval_check").is(':checked')) {
				g_send_interval = jQuery("#message_send_interval").val() * 100 * 60;
				sendMessageData.send_timer = setInterval(function() { sendMessageData();  }, g_send_interval);
			} else {
				sendMessageData();
			}

            $('.text','#layer_status').text('전송을 재개하였습니다.');
            return false;
        });

        $('.pop_addrbook','#smsPurplebook').click(function() {
			var params = new Array();
			var response_tags = new Array('error','message','data');

			params['g_mid'] = g_mid;
			params['layer_name'] = 'layer_addrbook';

			layer_id = '#layer_addrbook';

			exec_xml('purplebook', 'getPopupLayer', params, function(ret_obj) {
				if (ret_obj["data"]) {
					jQuery(layer_id).html(ret_obj["data"]);
					if (jQuery(layer_id).css('display') == 'block') jQuery(layer_id).html('');

					delete init_target_tree.initial;
					init_target_tree('#smsPurplebookTargetTreeAddrbook',g_tpl_path+'img/');

					$obj = jQuery(layer_id);
					show_and_hide($obj);
				}
			}, response_tags);

            return false;
        });

        // copy to addressbook
        $('.btn_copy','#layer_addrbook').live('click',function() {
            var selected_folders = $('#smsPurplebookTargetTreeAddrbook').jstree('get_selected');
            if (selected_folders.length != 1) {
                alert('명단을 추가할 폴더를 한개만 선택하세요.');
                return false;
            }

            var $node = $(selected_folders[0]);
            if (!$node) {
                alert('복사할 폴더를 선택하세요.');
                return;
            }
            node_route = $node.attr('node_route') + $node.attr('node_id') + '.';
            node_name = $node.attr('node_name');
            var node_id = $node.attr('node_id');

            p_show_waiting_message();

            var list = new Array();

			exist_folder = false;
            $('span.checkbox.on', 'ul#smsPurplebookTargetList li').each(function() {
                var node_name = $('.name',$(this).parent()).text();
                var phone_num = $('.number',$(this).parent()).attr('phonenum');

				// check된 항목이 folder라면 return
				if (!phone_num) {
					exist_folder = true;
					return;
				}
					
                list.push({node_name:node_name,phone_num:phone_num});
            });

			if (exist_folder == true) {
				alert('폴더는 복사할수 없습니다.');
				return false;
			}

            if (list.length == 0)
            {
                p_hide_waiting_message();
                alert('복사할 명단을 체크하세요.');
                return false;
            }

            if (!confirm(list.length + '건의 명단을 [' + node_name + ']폴더로 복사하시겠습니까?'))
            {
                p_hide_waiting_message();
                alert('취소했습니다.');
                return false;
            }

            $.ajax({
                type : "POST"
                , contentType: "application/json; charset=utf-8"
                , url : "./"
                , data : { 
                            module : "purplebook"
                            , act : "procPurplebookAddList"
                            , parent_node : node_id
                            , data : JSON.stringify(list)
                         }
                , dataType : "json"
                , success : function (data) {
                    p_hide_waiting_message();
                    pb_load_list(null, true);
                    if (data.error == -1)
                        alert(data.message);
                }
                , error : function (xhttp, textStatus, errorThrown) { 
                    p_hide_waiting_message();
                    alert(errorThrown + " " + textStatus); 
                }
            });
            return false;
        });

        $('.btn_blank','#smsPurplebook').click(function() {
            return false;
        });

        $('#smsPurplebookList').delegate('li','mousedown', function() { $(this).contextMenu(menu1,{theme:'vista',offsetX:1,offsetY:1}); });

        //$('#smsPurplebookList').scroll(pb_scrolled);

        // progressbar options
        jQuery('.progressBar','#layer_status').progressbar({
			value: 0
		}, 2000);

        // HELP balloon
        jQuery('.help','#smsPurplebook,#smsMessage').qtip({
            style:{
                name:'green'
                ,border: {
                    width:3,
                    radius:8
                }
                ,tip:'bottomMiddle'
            }
            ,position: {
                corner: {
                    target: 'topMiddle'
                    , tooltip: 'bottomMiddle'
                }
            }
        });

		// 발송간격설정
		$("#message_interval_check").change( function(){
			if ($(this).is(':checked'))
			{
				$("#message_send_limit").attr('readonly', false);
				$("#message_send_interval").attr('readonly', false);

				$("#message_send_limit").css('background', 'white');
				$("#message_send_interval").css('background', 'white');
			}
			else 
			{
				$("#message_send_limit").attr('readonly', true);
				$("#message_send_interval").attr('readonly', true);

				$("#message_send_limit").css('background', '#f0f0f0');
				$("#message_send_interval").css('background', '#f0f0f0');
			}
		});

		// 스크롤 탑
		$("#pb_move_top").live("click", function(){
			$('body, html').animate({scrollTop:0}, 100);
		});

		// 스크롤 바텀
		$("#pb_move_bottom").live("click", function(){
			$("html, body").animate({ scrollTop: $(document).height() }, 100);
		});

		// 미리보기, 전체보기, 전송결과 layer set
		$("body").append('<div id="pb_layer_box" style="z-index:99"><div id="pb_view_all"></div><div id="pb_result"></div><div id="pb_preview"></div></div>');

		/* 
		 * 특수문자, 사진추가, 머지기능 버튼 위치설정
		 */
		$("body").append('<div id="pb_left_btn_box"><div id="btn_pop_chars_box"><button id="btn_pop_chars" class="left_btn">특수문자</button></div><div id="btn_attach_pic_box"><button id="btn_attach_pic" class="left_btn">사진추가</button></div><div id="btn_delete_pic_box"><button id="btn_detach_pic" class="left_btn">사진삭제</button></div><div id="btn_pop_merge_box"><button id="btn_pop_merge" class="left_btn">머지기능</button></div></div>');

		var left_button_location = $("#pb_btn_location").offset();

		$("#pb_left_btn_box").css({
			"position":"absolute",
			"top":left_button_location.top + 10,
			"left":left_button_location.left - 67,
			"width":"100px",
			"height":"100px",
			"z-index":"50"
		});
		/*
		 * END
		 */

		/*
		 * 레이어팝업 추가
		 */

		// 사용법 
		//layer_popup_set('#layer_manual', '<div id="layer_manual" class="layer draggable"></div>', '#btn_pop_manual','#smsPurplebook');

		// 주소록 추가
		layer_popup_set('#layer_append', '<div id="layer_append" class="layer draggable"></div>', '.pop_append', '#smsPurplebook');

		// 중복번호
		layer_popup_set('#layer_overlap', '<div id="layer_overlap" class="layer draggable"></div>', '.pop_overlap', '#smsPurplebook');

		// 대량추가
		layer_popup_set('#layer_mass', '<div id="layer_mass" class="layer draggable"></div>', '#smsPurplebookAddBulk');

		// 최근입력번호
		layer_popup_set('#layer_recent', '<div id="layer_recent" class="layer draggable"></div>', '.pop_recent', '#smsPurplebook');

		// 문자내용 불러오기 
		layer_popup_set('#layer_messages', '<div id="layer_messages" class="layer draggable"></div>', '.pop_messages', '#smsPurplebookContentInput');

		// 머지기능
		layer_popup_set('#layer_merge', '<div id="layer_merge" class="layer draggable"></div>', '#btn_pop_merge');

		// 특수문자
		layer_popup_set('#layer_chars', '<div id="layer_chars" class="layer draggable"></div>', '#btn_pop_chars');

		// 사진추가
		layer_popup_set('#layer_upload', '<div id="layer_upload" class="layer draggable"></div>', '#btn_attach_pic');

		// 발신번호관리
		layer_popup_set('#layer_sendid', '<div id="layer_sendid" class="layer draggable"></div>', '.btn_show_layer', '#smsMessage .right_button');

		// 예약발송
		layer_popup_set('#layer_reserv', '<div id="layer_reserv" class="layer draggable"></div>', '#btnSimplePhoneReserv', '#smsMessage');

		// 전송현황
		layer_popup_set('#layer_status', '<div id="layer_status" class="layer draggable"></div>', '#btnSimplePhoneSend');

		// 주소록 복사
		layer_popup_set('#layer_copy', '<div id="layer_copy" class="layer draggable"></div>', '.pop_copy', '#smsPurplebook');

		// 주소록 이동
		layer_popup_set('#layer_move', '<div id="layer_move" class="layer draggable"></div>', '.pop_move', '#smsPurplebook');

		// 받는사람 주소록 복사
		layer_popup_set('#layer_addrbook', '<div id="layer_addrbook" class="layer draggable"></div>', '.pop_addrbook', '#smsPurplebook');

		// 정보보기
		layer_popup_set('#layer_properties', '<div id="layer_properties" class="layer draggable"></div>', '#smsMessage');

		// 폴더공유
		layer_popup_set('#layer_share', '<div id="layer_share" class="layer draggable"></div>', '#smsMessage');

		/*
		 * END
		 */
		
        init_purplebook_tree(g_tpl_path+'img/');

        var option = {
            yearRange:'-0:+1'
            ,mandatory:true
            ,onSelect:function(){
                $("#inputReservationDate").val(this.value);
            }
        };
        $.extend(option,$.datepicker.regional['ko']);
        $("#inputReservationDate").datepicker(option);

        var menu1 = [
            {'이름변경':{
                    onclick:function(menuItem,menu) { pb_modify_name(this); jQuery('.context-menu').remove(); jQuery('.context-menu-shadow').remove(); }
                    ,icon:g_tpl_path+'img/ico_person.gif'
                }
            }
            ,{'전화번호변경':{
                    onclick:function(menuItem,menu) { pb_modify_phone(this); jQuery('.context-menu').remove(); jQuery('.context-menu-shadow').remove();}
                    ,icon:g_tpl_path+'img/ico_phone.gif'
                }
            }
            ,$.contextMenu.separator
            ,{'정보보기':{
                    onclick:function(menuItem,menu) { pb_view_properties(this); jQuery('.context-menu').remove(); jQuery('.context-menu-shadow').remove();} 
                    ,icon:g_tpl_path+'img/icon-attribute.gif'
                }
            }
        ];


		// draggable layer
        $('.layer.draggable').draggable({appendTo:'body',cursor:'crosshair',scroll:false,delay:300});

		// 현재잔액 set
		set_balance();

		// 중복번호 set
		pb_load_overlap();
    });
}) (jQuery);

// layer popup 생성 및 위치설정
function layer_popup_set(layer_id, content, layer_location, layer_location_2){

	if (typeof(layer_location_2) == 'undefined') {
		layer_location = jQuery(layer_location).offset();
	} else {
		layer_location = jQuery(layer_location, layer_location_2).offset();
	}

	jQuery('body').append(content)

	jQuery(layer_id).css({
		"top":layer_location.top - 220,
		"left":layer_location.left
	});
}

/*
// popLayer 닫기
function closeLayer(id) {
	$obj = jQuery(id);
	show_and_hide($obj);
	
	return false;
}
*/

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

jQuery(document).ready(function (){

	// 특수문자, 사진추가, 머지기능 버튼 효과
	left_button_location("#btn_pop_chars_box");
	left_button_location("#btn_attach_pic_box");
	left_button_location("#btn_delete_pic_box");
	left_button_location("#btn_pop_merge_box");

});


// 창 리사이즈시 left_button 위치 변경
jQuery(window).resize(function () {
	var left_button_location = jQuery("#pb_btn_location").offset();

	jQuery("#pb_left_btn_box").css({
		"left":left_button_location.left - 67,
	});
});
