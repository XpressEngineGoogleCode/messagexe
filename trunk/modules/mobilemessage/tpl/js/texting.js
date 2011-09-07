/**
 * vi:set ts=4 sw=4 expandtab enc=utf8: 
 * MessageXE
 * http://message.xpressengine.net/
 **/

var texting_bytes_limit = 2000;

var DDD = new Array("02", "031", "033", "032", "042", "043", "041", "053", "054", "055", "052", "051", "063", "061", "062", "064", "011", "012", "013", "014", "015", "016", "017", "018", "019", "010", "070");

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

function calc_sms(cashinfo)
{
    return Math.floor(cashinfo.cash / 22) + Math.floor(cashinfo.point / 22) + cashinfo.mdrop;
}

function calc_lms(cashinfo)
{
    return Math.floor(cashinfo.cash / 50) + Math.floor(cashinfo.point / 50);
}

function getTextBytes(text)
{
    var idx = 0;
    var bytes = 0;

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
    var prefix = new RegExp("^01(0|1|6|7|8|9)([-\)])?[0-9]{3,4}(-)?[0-9]{4}$")
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

function cellphone_clear_text()
{
    obj = document.getElementById("smsPurplebookTextMessage");
    obj.value = "";
    obj.focus();
    cellphone_check_bytes();
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

    obj = document.getElementById("smsPurplebookTextMessage");
    insertAtCursorPosition(obj, retChr);

    obj.scrollTop = obj.scrollHeight;
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
    var reg = new RegExp("^[0-9]{0,3}(-)?[0-9]{3,4}(-)?[0-9]{4}$")
    return reg.test(str)
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

// 받는사람수 업데이트
function updateNumofPnumber()
{
    var r_num = $('li', '.rnumlist_body ul').size();
    $('#receiver_num').text( r_num );
  
    /*
    if(r_num > max_rnum) $('#receiver_num').css('color','red');
    else $('#receiver_num').css('color','#787878');
    */
    $('#receiver_num').css('color','#787878');
    
    //받는사람이 업데이트되면 받는사람에 추가된 주소체크
    //addedAddrCheck();
    return r_num;
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

function addGroup(groupSrl, groupName)
{
    if (addGroup.sequence == undefined)
        addGroup.sequence = 0;
    jQuery('#grouplist').append('<li><span class="sequence">' + ++addGroup.sequence + '</span><span class="chkGroup" group_srl="' + groupSrl + '" title="체크 후 오른쪽 추가버턴을 누르세요."></span><span class="groupName" group_srl="' + groupSrl + '" title="선택하시면 아래에 ' + groupName + '그룹의 멤버목록이 나타납니다.">' + groupName + '</span></li>');
}

function getGroupList(ret_obj) {
    var group_list = ret_obj['grouplist'];
    if (typeof(group_list)=='undefined') return;

    for (var i = 0; i < group_list.item.length; i++)
    {
        addGroup(group_list.item[i].group_srl, group_list.item[i].title);
    }
}

function initGroupList() {
    var params = new Array();
    var response_tags = new Array('error', 'message', 'grouplist');
    exec_xml('mobilemessage', 'getMobilemessageGroupList', params, getGroupList, response_tags);
}

(function($) {

 	// 받는사람수 업데이트
	function updateNumofPnumber()
	{
		var r_num = $('li', '.rnumlist_body ul').size();
	  	$('#receiver_num').text( r_num );
	  
        /*
	  	if(r_num > max_rnum) $('#receiver_num').css('color','red');
	  	else $('#receiver_num').css('color','#787878');
        */
	  	$('#receiver_num').css('color','#787878');
	  	
	  	//받는사람이 업데이트되면 받는사람에 추가된 주소체크
	  	//addedAddrCheck();
		return r_num;
	}

	// 받은사람 목록에 존재하는 번호인지 조사
	function isExistNum(newNum)
	{
		var pureNum = toOnlyNumber(newNum, "-", "");
		var exist = false;
		var $listAll = $('li', '.rnumlist_body ul')
		
		$listAll.each(function(idx) {
				var eNum = toOnlyNumber($('.number',this).text(), "-", "");
				if(pureNum == eNum)
				{
					exist = true;
					return;
				}
		});
		
		return exist;
			
	}

 	function addNum(newNum, rName, userid) 
	{
        newNum = newNum.replace(/-/g,'');
        if (userid == undefined) userid = '';

        /*
		// 전화번호 포멧검사
		if(!checkPhoneFormat(newNum)) {
			if (!confirm("유효하지 않은 전화번호입니다 (" + newNum + ")\n계속 진행하시겠습니까?"))
			    return false;
		}

		// 이미 존재하는 번호인지 검사
        if ($('#tel'+newNum).length > 0)
		{
			if (!confirm("이미 추가되어 있는 전화번호입니다 (" + newNum + ")\n계속 진행하시겠습니까?"))
			    return false
		}
        */

		// 이상이 없을 경우 추가 (개별선택, 삭제 이벤트 포함)
        $('#rnumlist').append('<li userid="' + userid + '"><span class="chkRecipient"></span><span class="name">' + rName + '</span><span id="tel' + newNum + '" class="number" phonenum="' + newNum + '">'+ newNum +'</span><span class="delRecipient" title="삭제">삭제</span></li>');
   
		return true;
	}


    function addMember(seq, obj)
    {
        $('#custlist').append('<li class="memberInfo"><span class="sequence">' + seq + '</span><span class="chkMember"></span><span class="id">' + obj.user_id + '</span><span class="name" member_srl="' + obj.member_srl + '">' + obj.user_name + '</span><span class="number">' + getSimpleDashTel(obj.cellphone.replace(/\|\@\|/g, '')) + '</span></li>');
    }

    function parseJSON(data) {
        phonenum = data.data.phonenum;
        name = data.data.name;
        if (!phonenum.length)
            return 2; // 폰번호없음
        if (!name.length)
            name = "none";
        if (!addNum(data.data.phonenum, name))
            return 0;

        return 1; // 성공
    }

	// 받는사람 직접추가
	function addDirectNumber() 
	{ 
        var new_num = $('#inputDirectNumber').val();
        if(new_num == "")
        { 
            alert("전화번호를 입력하세요");
            return false;
        }
        addNum(new_num, $('#inputDirectName').val());
        updateNumofPnumber();

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

        for (var i = 0; i < lengthList; i++)
        {
            var strLine = '';
            row = pattern.exec(arrayList[i]);
            if (!row) continue;

            rePhone = row[1].replace(/[-\(\) ]/g, "");
            reName = row[2];

            if (!addNum(rePhone, reName))
            {
                break;
            }

            countList++;
        }
        updateNumofPnumber();

        $('#telnumlist').val('');
        $('#layerBulkAdd').css('display', 'none');
        $('span.total', '.phoneNumberList').text('총 0 명');

        return countList;
    }

    function send_json(content, r_num)
    {
        if (send_json.progress_count == undefined)
        {
            send_json.progress_count=0;
        }
        var success_count=0;
        var failure_count=0;

        $.ajax({
            type : "POST"
            , contentType: "application/json; charset=utf-8"
            , url : request_uri
            , data : { 
                        module : "mobilemessage"
                        , act : "procMobilemessageSendMsg"
                        , data : JSON.stringify(content)
                        , ticket : $('#ticket').val()
                        , use_point : $('#use_point').val()
                        , sms_point : $('#sms_point').val()
                        , lms_point : $('#lms_point').val()
                     }
            , dataType : "json"
            , success : function (data) {
                send_json.progress_count += content.length;

                if (data.error == -1)
                {
                    $.modal.close();
                    alert(data.message);
                    return;
                }

                failure_count += data.failure_count;
                success_count += data.success_count;

                if (typeof(data.alert_message) != 'undefined' && data.alert_message.length > 0)
                    alert(data.alert_message);

                if (send_json.progress_count == r_num)
                {
                    $.modal.close();
                    alert('접수하였습니다.\n성공: ' + success_count + ', 실패: ' + failure_count + '\n전송결과는 전송내역에서 확인하세요.');
                    $('form#fo_dummy').submit();
                }
            }
            , error : function (xhttp, textStatus, errorThrown) { 
                send_json.progress_count += content.length;
                alert(errorThrown + " " + textStatus); 
                if (send_json.progress_count == r_num)
                {
                    $.modal.close();
                    alert('접수하였습니다.\n성공: ' + success_count + ', 실패: ' + failure_count + '\n전송결과는 전송내역에서 확인하세요.');
                }
            }
        });
    }

    function load_members_ex(query1, query2)
    {
        $('#progressModal').modal({
            containerCss: {
                backgroundColor:"#eee"
                , height:40
                , width:200
            }
        });

        query = query1;
        if (query2)
            query = query + '&' + query2;


        $('#custlist').html('');
        $.ajax({
            type: "POST",
            contentType: "application/json; charset=utf-8",
            url: "./",
            data: query,
            dataType: "json",
            success:function(data) {
                if (data.member_list.length > 0)
                {
                    for (var i = 0; i < data.member_list.length; i++)
                    {
                        addMember(i+1, data.member_list[i]);
                    }
                    $('#smsMembersFooterPages').html('');
                    for (var i = 1; i <= data.total_page; i++)
                    {
                        if (data.page == i)
                            $('#smsMembersFooterPages').append('<li class="smsMembersPage on">' + i + '</li>');
                        else
                            $('#smsMembersFooterPages').append('<li class="smsMembersPage">' + i + '</li>');
                    }
                    $('#excelDownload').attr('href', data.base_url + '?module=mobilemessage&act=dispMobilemessageExcelDownload&' + query2);
                }
                else
                {
                    $('#smsMembersFooterPages').html('');
                    $('#smsMembersFooterPages').append('<li>검색된 데이터가 없습니다.</li>');
                }
                $.modal.close();
            }
        });
    }

    function load_members(group_srl, page)
    {
        if (page == undefined)
            page = 1;

        //var query = 'module=mobilemessage&act=dispMobilemessageMembersInGroupPaging&group_srl=' + group_srl + '&page=' + page;
        var query = 'group_srl=' + group_srl + '&page=' + page;

        load_members_ex('module=mobilemessage&act=dispMobilemessageMembersInGroupPaging', query);
    }

    function getMsgType()
    {
        msgtype = 'sms';
        if ($('#btnSimplePhoneSMS').hasClass('on'))
            msgtype = 'sms';
        if ($('#btnSimplePhoneMMS').hasClass('on'))
            msgtype = 'lms';

        return msgtype;
    }

    function clone(obj){
        if(obj == null || typeof(obj) != 'object')
            return obj;

        var temp = new obj.constructor(); // changed (twice)
        for(var key in obj)
            temp[key] = clone(obj[key]);

        return temp;
    }

    function sendMessage()
    {
        //받는사람 번호 구성
        var r_num = $('li', '.rnumlist_body ul').size();

        $('#progressModal').modal({
            overlay:80
            , overlayCss: {backgroundColor:"#222"}
            , containerCss: {
                backgroundColor:"#eee"
                , height:40
                , width:200
            }
        });

        $('#progressModal').css('display', 'none');
        $('#progressModal').css('display', 'block');

        setTimeout(function() {
            content_list = new Array();

            var add_count=0;
            $('li', '#rnumlist').each( function(i) {
                var callno = $('.number', this).text();
                var ref_username = $('.name', this).text();
                var ref_userid = $(this).attr('userid');

                msgtype = 'sms';
                if ($('#btnSimplePhoneSMS').hasClass('on'))
                    msgtype = 'sms';
                if ($('#btnSimplePhoneMMS').hasClass('on'))
                    msgtype = 'lms';

                if ($('#smsPurplebookReservFlag').val() == '1')
                {
                    var content = {
                        "msgtype": msgtype
                        , "recipient": callno
                        , "callback": $('#smsPurplebookCallback').val()
                        , "text": $('#smsPurplebookTextMessage').val()
                        , "splitlimit": "10"
                        , "refname": ref_username
                        , "refid": ref_userid
                        , "reservdate": texting_pickup_reservdate()
                    }
                }
                else
                {
                    var content = {
                        "msgtype": msgtype
                        , "recipient": callno
                        , "callback": $('#smsPurplebookCallback').val()
                        , "text": $('#smsPurplebookTextMessage').val()
                        , "splitlimit": "10"
                        , "refname": ref_username
                        , "refid": ref_userid
                    }
                }
                content_list.push(content);

                if (content_list.length >= 100)
                {
                    send_json(clone(content_list), r_num);
                    content_list.length = 0;
                }

                add_count++;

            });
            if (content_list.length)
            {
                send_json(clone(content_list), r_num);
                content_list.length = 0;
            }
        }, 500);
    }

    function do_after_get_cashinfo(cashinfo)
    {
        //받는사람 번호 구성
        var r_num = $('li', '.rnumlist_body ul').size();

        //받는사람이 없을 경우
        if(r_num == 0)
        {
            alert('받는사람을 입력하세요');
            return false;
        }

        reservflag = document.getElementById("smsPurplebookReservFlag").value;
        if (reservflag == "1")
            word_send = "예약";
        else
            word_send = "발송";

        sms_avail = calc_sms(cashinfo);
        lms_avail = calc_lms(cashinfo);

        var count = $('li', '.rnumlist_body ul').size();
        if (getMsgType() == "sms")
        {
            bytes = getTextBytes($('#smsPurplebookTextMessage').val())[0];
            npages = Math.ceil(bytes / 80);

            if ((count * npages) > sms_avail)
            {
                if (!confirm("사용가능 SMS 건수가 모자랍니다. 취소를 선택하고 충전 후 사용하세요.\n"
                        + word_send + "가능 SMS 건수: " + sms_avail  + "\n"
                        + word_send + "예정 SMS 건수: " + (count * npages) + "\n"
                        + "그래도 " + sms_avail + "건 " + word_send + "시도 하시겠습니까?"))
                {
                    return false;
                }
            }
            else
            {
                var message = '';
                if (reservflag == '1')
                    message += '예약일시 : ' + date_format(texting_pickup_reservdate()) + '\n';
                message += (count * npages) + "건의 일반문자(SMS)를 " + word_send + "하시겠습니까?";
                if (!confirm(message))
                {
                    return false;
                }
            }
        }
        else
        {
            if (count > lms_avail)
            {
                if (!confirm("사용가능 LMS 건수가 모자랍니다. 취소를 선택하고 충전 후 사용하세요.\n"
                    + word_send + "가능 LMS 건수: " + lms_avail  + "\n"
                    + word_send + "예정 LMS 건수: " + count + "\n"
                    + "그래도 " + (count - lms_avail) + "건 " + word_send + "시도 하시겠습니까?"))
                {
                    return false;
                }
            }
            else
            {
                var message = '';
                if (reservflag == '1')
                    message += '예약일시 : ' + date_format(texting_pickup_reservdate()) + '\n';
                message += count + "건의 장문문자(LMS)를 " + word_send + "하시겠습니까?";
                if (!confirm(message))
                {
                    return false;
                }
            }
        }
        //if (confirm(r_num + " 명에게 " + word_send + "하시겠습니까?"))
    
        sendMessage();
    }

    function get_cashinfo()
    {
        var obj = new Object();
        obj.cash = 0;
        obj.point = 0;
        obj.mdrop = 0;

        $.ajax({
            type : "POST"
            , contentType: "application/json; charset=utf-8"
            , url : "./"
            , data : { 
                        module : "mobilemessage"
                        , act : "dispMobilemessageGetCashInfo"
                     }
            , dataType : "json"
            , success : function (data) {
                obj.cash = parseInt(data.cash);
                obj.point = parseInt(data.point);
                obj.mdrop  = parseInt(data.mdrop);

                do_after_get_cashinfo(obj);
            }
            , error : function (xhttp, textStatus, errorThrown) { 
                alert(errorThrown + " " + textStatus); 
            }
        });
    }

    function get_pointinfo(callback_func)
    {
        $.ajax({
            type : "POST"
            , contentType: "application/json; charset=utf-8"
            , url : "./"
            , data : { 
                        module : "mobilemessage"
                        , act : "dispMobilemessageGetPointInfo"
                     }
            , dataType : "json"
            , success : function (data) {
                point = parseInt(data.point);

                if ($('#smsCurrentPoint'))
                    $('#smsCurrentPoint span:first').text(point);

                obj = new Object();
                obj.cash = 0;
                obj.point = point;
                obj.mdrop = 0;

                reservflag = document.getElementById("smsPurplebookReservFlag").value;
                if (reservflag == "1")
                    word_send = "예약";
                else
                    word_send = "발송";

                sms_avail = calc_sms(obj);
                lms_avail = calc_lms(obj);

                var count = $('li', '.rnumlist_body ul').size();
                if (getMsgType() == "sms")
                {
                    bytes = getTextBytes($('#smsPurplebookTextMessage').val())[0];
                    npages = Math.ceil(bytes / 80);

                    if ((count * npages) > sms_avail)
                    {
                        alert(data.msg_not_enough + "\n"
                                + "현재 포인트: " + point + "\n"
                                + word_send + "가능 SMS 건수: " + sms_avail  + "\n"
                                + word_send + "예정 SMS 건수: " + (count * npages) + "\n"
                             );
                        return false;
                    }
                }
                else
                {
                    if (count > lms_avail)
                    {
                        alert(data.msg_not_enough + "\n"
                            + "현재 포인트: " + point + "\n"
                            + word_send + "가능 LMS 건수: " + lms_avail  + "\n"
                            + word_send + "예정 LMS 건수: " + count + "\n"
                            );
                        return false;
                    }
                }
                callback_func(point);
            }
            , error : function (xhttp, textStatus, errorThrown) { 
                alert(errorThrown + " " + textStatus); 
            }
        });
    }

    function display_bytes()
    {
        var bytes_idx = getTextBytes($('#smsPurplebookTextMessage').val());
        var count = bytes_idx[0];
        var lastidx = bytes_idx[1];

        if (count > 80 && $('#btnSimplePhoneSMS').hasClass('on'))
        {
            rest = (count % 80);
            if (rest == 0)
                rest = 80;
            $('span:first', '.smsPurplebookByteArea').text(Math.ceil(count / 80) + ' 건 ' + rest);
        }
        else
        {
            $('span:first', '.smsPurplebookByteArea').text(count);
        }

        if(count > 80)
        {
            if ($('#smsPurplebookTextMessage').attr('fixit') == 'sms')
            {
                $('#btnSimplePhoneSMS').addClass('on');
                $('#btnSimplePhoneMMS').removeClass('on');
            }
            else
            {
                $('#btnSimplePhoneMMS').addClass('on');
                $('#btnSimplePhoneSMS').removeClass('on');
            }
        }
        else if(count <= 80)
        {
            $('#btnSimplePhoneMMS').removeClass('on');
            $('#btnSimplePhoneSMS').removeClass('on');
        }
        
        if ($('#btnSimplePhoneMMS').hasClass('on'))
            texting_bytes_limit = 2000;
        else
            texting_bytes_limit = 800;

        if(count > texting_bytes_limit)
        {
            alert(texting_bytes_limit + 'byte를 초과하였습니다.\r\n초과된 부분은 자동으로 삭제됩니다.');
            $('#smsPurplebookTextMessage').val( $('#smsPurplebookTextMessage').val().substr(0, lastidx) );
            $('span:last', '.smsPurplebookByteArea').text(lastidx);
        }
    }

    function add_group_to_recipient()
    {
            $('#progressModal').modal({
                containerCss: {
                    backgroundColor:"#eee"
                    , height:40
                    , width:200
                }
            });

            var list = new Array();
            var size = $('span.chkGroup.on', '#grouplist li').size();
            var count=0;
            var each_count=0;

            $('span.chkGroup.on', '#grouplist li').each(function() {
                var group_srl = $(this).attr('group_srl');
                $.ajax({
                    type: "POST",
                    contentType: "application/json; charset=utf-8",
                    url: "./",
                    data: {module:"mobilemessage", act:"dispMobilemessageMembersInGroup", group_srl:group_srl, nonphone_skip:true},
                    dataType: "json",
                    success:function(data) {
                        if (isArray(data.member_list))
                        {
                            for (var i = 0; i < data.member_list.length; i++)
                            {
                                list.push({
                                    cellphone : data.member_list[i].cellphone.replace(/\|\@\|/g, '')
                                    , user_name : data.member_list[i].user_name
                                    , user_id : data.member_list[i].user_id
                                });
                            }
                        }
                        else if (data.member_list)
                        {
                            list.push({
                                cellphone : data.member_list.cellphone.replace(/\|\@\|/g, '')
                                , user_name : data.member_list.user_name
                            });
                        }
                        count++;

                        if (count == size)
                        {
                            var succ_count=0;
                            for (var i = 0; i < list.length; i++)
                            {
                                if (list[i].cellphone.length <= 0)
                                    continue;
                                if (!addNum(list[i].cellphone, list[i].user_name, list[i].user_id))
                                    break;
                                succ_count++;
                            }
                            updateNumofPnumber();
                            $.modal.close();
                            alert(succ_count + ' 명을 추가했습니다.');
                        }
                    }
                    , error : function (xhttp, textStatus, errorThrown) { 
                        alert(errorThrown + " " + textStatus); 
                        count++;
                        if (count == size)
                        {
                            var succ_count=0;
                            for (var i = 0; i < list.length; i++)
                            {
                                if (list[i].cellphone.length <= 0)
                                    continue;
                                if (!addNum(list[i].cellphone, list[i].user_name, list[i].user_id))
                                    break;
                                succ_count++;
                            }
                            updateNumofPnumber();
                            $.modal.close();
                            alert(succ_count + ' 명을 추가했습니다.');
                        }
                    }
                });
                each_count++;
            });
            if (each_count == 0)
            {
                alert('체크된 그룹이 없습니다.\n왼쪽 그룹목록의 체크박스에 체크하세요.');
                $.modal.close();
            }
    }

    function add_members_to_recipient()
    {
            $('#progressModal').modal({
                containerCss: {
                    backgroundColor:"#eee"
                    , height:40
                    , width:200
                }
            });

            setTimeout(function() {
                var succ_count=0;
                var list = new Array();
                $('span.chkMember.on', '#custlist li').each(function() {
                    list.push($(this));
                });
                if (list.length == 0)
                {
                    alert('체크된 회원이 없습니다.\n왼쪽 회원목록에서 선택해주세요.');
                    $.modal.close();
                    return;
                }
                for (var i = 0; i < list.length; i++)
                {
                    var obj = list[i];
                    var phonenum = $('.number', $(obj).parent()).text();
                    var name = $('.name', $(obj).parent()).text();
                    var id = $('.id', $(obj).parent()).text();
                    if (phonenum.length <= 0)
                        continue;
                    if (!addNum(phonenum, name, id))
                    {
                        updateNumofPnumber();
                        $.modal.close();
                        return;
                    }
                    succ_count++;
                }
                updateNumofPnumber();
                $.modal.close();
                alert(succ_count + ' 명을 추가했습니다.');
            }, 500);
    }

    function add_folder_to_recipient()
    {
        var t = $.tree.plugins.checkbox.get_checked($.tree.reference('smsPurplebookTree'));
        if (t.length == 0)
        {
            alert('체크된 폴더가 없습니다.\n왼쪽 폴더목록에서 체크박스에 체크하세요.');
            return;
        }

        $('#progressModal').modal({
            containerCss: {
                backgroundColor:"#eee"
                , height:40
                , width:200
            }
        });

        var list = new Array();
        var succ_count=0;
        var progress_count=0;
        for (i = 0; i < t.length; i++)
        {
            node_route = $(t[i]).attr('node_route') + $(t[i]).attr('node_id') + '.';

            $.ajax({
                type : "POST"
                , contentType: "application/json; charset=utf-8"
                , url : "./"
                , data : { 
                            module : "mobilemessage"
                            , act : "dispMobilemessagePurplebookList"
                            , node_route : node_route
                            , node_type : '2'
                         }
                , dataType : "json"
                , success : function (data) {
                    for (i = 0; i < data.purplebook_data.length; i++)
                    {
                        node_name = data.purplebook_data[i].attributes.node_name;
                        phone_num = data.purplebook_data[i].attributes.phone_num;

                        list.push({node_name:node_name, phone_num:phone_num});
                    }

                    progress_count++;
                    if (progress_count == t.length)
                    {
                        for (i = 0; i < list.length; i++)
                        {
                            obj = list[i];
                            if (!addNum(obj.phone_num, obj.node_name))
                                break;
                            succ_count++;
                        }

                        updateNumofPnumber();
                        $.modal.close();
                        alert(succ_count + ' 명을 추가했습니다.');
                    }
                }
                , error : function (xhttp, textStatus, errorThrown) { 
                    updateNumofPnumber();
                    $.modal.close();
                    alert(errorThrown + " " + textStatus); 
                }
            });
        }

    }

    function add_addrs_to_recipient()
    {
            $('#progressModal').modal({
                containerCss: {
                    backgroundColor:"#eee"
                    , height:40
                    , width:200
                }
            });

            setTimeout(function() {
                var succ_count=0;
                var list = new Array();
                $('span.chkPurplebookMembers.on', '.smsPurplebookListPaneBody ul li').each(function() {
                    list.push($(this));
                });
                if (list.length == 0)
                {
                    alert('체크된 주소록이 없습니다.\n왼쪽 주소록목록에서 선택해주세요.');
                    $.modal.close();
                    return;
                }
                for (var i = 0; i < list.length; i++)
                {
                    var obj = list[i];
                    var phonenum = $('.nodePhone', $(obj).parent()).text();
                    var name = $('.nodeName', $(obj).parent()).text();
                    if (phonenum.length <= 0)
                        continue;
                    if (!addNum(phonenum, name))
                    {
                        updateNumofPnumber();
                        $.modal.close();
                        return;
                    }
                    succ_count++;
                }
                updateNumofPnumber();
                $.modal.close();
                alert(succ_count + ' 명을 추가했습니다.');
            }, 500);
    }

    function updatePurplebookListCount()
    {
         var total = $('li', '#smsPurplebookList').size();
         $('#smsPurplebookListCount').text(' (' + total + ' 명)');
    }

    function extract_node_text(node)
    {
        htmlstr = $(':first', node).html();
        htmlstr = htmlstr.replace(/<INS>&nbsp;<\/INS>/g, '');
        htmlstr = htmlstr.replace(/&nbsp;/g, '');
        return $.trim($('<a>' + htmlstr + '</a>').text());
    }

    function load_purplebook_list()
    {
        var node = $.tree.reference('smsPurplebookTree').selected;
        if (!node)
            return;

        node_route = node.attr('node_route') + node.attr('node_id') + '.';
        $('#smsPurplebookSelectedFolderName').text(extract_node_text(node));

        $('#progressModal').modal({
            containerCss: {
                backgroundColor:"#eee"
                , height:40
                , width:200
            }
        });

        $.ajax({
            type : "POST"
            , contentType: "application/json; charset=utf-8"
            , url : "./"
            , data : { 
                        module : "mobilemessage"
                        , act : "dispMobilemessagePurplebookList"
                        , node_route : node_route
                        , node_type : '2'
                     }
            , dataType : "json"
            , success : function (data) {
                if (data.error == -1)
                {
                   alert(data.message);
                   return -1;
                }
                $('#smsPurplebookList').html('');
                for (i = 0; i < data.purplebook_data.length; i++)
                {
                    node_id = data.purplebook_data[i].attributes.node_id;
                    node_name = data.purplebook_data[i].attributes.node_name;
                    phone_num = data.purplebook_data[i].attributes.phone_num;
                    $('#smsPurplebookList').append('<li node_id="' + node_id + '"><span class="chkPurplebookMembers"></span><span class="nodeName">' + node_name + '</span><span class="nodePhone">' + phone_num + '</span></li>');
                }
                updatePurplebookListCount();
                $.modal.close();
            }
            , error : function (xhttp, textStatus, errorThrown) { 
                $.modal.close();
                alert(errorThrown + " " + textStatus); 
            }
        });
    }

    function add_bulklist_to_purplebook()
    {

        text = $('#telnumlist').val();
        if (text == "") {
            return 0;
        }

        var node = $.tree.reference('smsPurplebookTree').selected;
        if (!node)
        {
            alert('선택된 폴더가 없습니다.');
            return;
        }
        var node_route = node.attr('node_route') + node.attr('node_id') + '.';

        var reVal = text;
        var rePhone = '';
        var reName = '';

        var arrayList = reVal.split("\n");
        var lengthList = arrayList.length;
        var pattern = /([0-9-()]{8,15})[ ,\t]*([\W\w]*)/;

        var list = new Array();

        $('#progressModal').modal({
            containerCss: {
                backgroundColor:"#eee"
                , height:40
                , width:200
            }
        });

        for (var i = 0; i < lengthList; i++)
        {
            var strLine = '';
            row = pattern.exec(arrayList[i]);
            if (!row) continue;

            rePhone = row[1].replace(/[-\(\) ]/g, "");
            reName = row[2];

            list.push({ node_name: reName, phone_num: rePhone });
        }

        if (list.length > 0)
        {
            $.ajax({
                type : "POST"
                , contentType: "application/json; charset=utf-8"
                , url : "./"
                , data : { 
                            module : "mobilemessage"
                            , act : "dispMobilemessagePurplebookRegister"
                            , node_route : node_route
                            , data : JSON.stringify(list)
                         }
                , dataType : "json"
                , success : function (data) {
                    if (data.error == -1)
                    {
                        $.modal.close();
                        alert(data.message);
                        return;
                    }
                    /*
                    for (i = 0; data.data.length; i++)
                    {
                        $('#smsPurplebookList').append('<li node_id="' + data.data[i].node_id + '"><span class="chkPurplebookMembers"></span><span class="nodeName">' + data.data[i].node_name + '</span><span class="nodePhone">' + data.data[i].phone_num + '</span></li>');
                    }
                    */
                    load_purplebook_list();
                    updatePurplebookListCount();
                    $.modal.close();
                    alert(data.data.length + ' 명을 등록하였습니다.');
                }
                , error : function (xhttp, textStatus, errorThrown) { 
                    $.modal.close();
                    alert(errorThrown + " " + textStatus); 
                }
            });
        }
    }

    function panes_init()
    {
        var obj = $(':first', '#smsResourceTab');
        if (!obj)
            return;
        if (obj.attr('id') == 'btnMembers')
        {
            obj.addClass('membersOn');
            $('#btnPurplebook').removeClass('purplebookOn');
            $('#smsPurplebook').css('display', 'none');
            $('#smsOrganization').css('display', 'block');
            $('#addGroupToRecipient').attr('title', '왼쪽에 체크된 그룹의 멤버들을 추가합니다.');
            $('#addMemberToRecipient').attr('title', '왼쪽에 체크된 멤버들을 추가합니다.');
        } else {
            obj.addClass('purplebookOn');
            $('#btnMembers').removeClass('membersOn');
            $('#smsOrganization').css('display', 'none');
            $('#smsPurplebook').css('display', 'block');
            $('#addGroupToRecipient').attr('title', '왼쪽에 체크된 폴더의 명단을 추가합니다.');
            $('#addMemberToRecipient').attr('title', '왼쪽에 체크된 명단을 추가합니다.');
        }
    }

    function addReplaceVar(varStr) {
        $('#smsPurplebookTextMessage').val($('#smsPurplebookTextMessage').val() + varStr);
        $('#smsPurplebookTextMessage').focus();
    }


    jQuery(function($) {
        // sarissa bugfix with MSIE & jquery 1.4.1
        if (typeof Sarissa != 'undefined') {
            jQuery.ajaxSetup({
                xhr: function() {
                        if (Sarissa.originalXMLHttpRequest) {
                                return new 
Sarissa.originalXMLHttpRequest();
                        } else if (typeof ActiveXObject != 
'undefined') {
                                return new 
ActiveXObject("Microsoft.XMLHTTP");
                        } else {
                                return new XMLHttpRequest(); 
                        }
                } 
            });
        }

        var initial_content = $('#smsPurplebookTextMessage').val();

        panes_init();
        initGroupList();


        $('.smsMenu *').each(function() {
            if ($(this).attr('title')) $(this).tipsy({fade:true});
        });

        $('.smsReplaceVar button').each(function() {
            if ($(this).attr('title')) $(this).tipsy({fade:true,gravity:'w'});
        });

        $('form#formPurplebookSimplePhone').submit(function() {

            //내용을 입력하였는지 검사
            if(!$('[name=smsPurplebookTextMessage]').val() || $('[name=smsPurplebookTextMessage]').val() == initial_content) {
                alert("내용을 입력해 주세요.");
                $('[name=smsPurplebookTextMessage]').focus();
                return false;
            }

            //발신전화번호 검사
            var sNum = $('#smsPurplebookCallback').val();
            if(!checkCallbackNumber(sNum))
            {
                alert('보내는사람의 전화번호를 정확히 입력하세요\n입력 예) 15881004 , 021231234, 0101231234');
                $('#smsPurplebookCallback').focus().select();
                return false;
            }		


            //받는사람 번호 구성
            var r_num = $('li', '.rnumlist_body ul').size();

            //받는사람이 없을 경우
            if(r_num == 0)
            {
                alert('받는사람을 입력하세요');
                return false;
            }

            // 캐쉬정보 확인후 발송루틴 호출
            if ($('#use_point').val() == 'Y')
                get_pointinfo(get_cashinfo);
            else
                get_cashinfo();

            return false;
        });

        $('#addGroupToRecipient').click(function() {
            if ($('#btnPurplebook').hasClass('purplebookOn'))
            {
                add_folder_to_recipient();
            }
            else
            {
                add_group_to_recipient();
            }
        });

        $('#addMemberToRecipient').click(function() {
            if ($('#btnPurplebook').hasClass('purplebookOn'))
                add_addrs_to_recipient();
            else
                add_members_to_recipient();

        });

        // 받는사람 전체선택
        $('#sel_allNum').toggle(
            function () { 
                $('#selRnum_toggle').addClass("on");
                $('span.chkRecipient', '.rnumlist_body ul li').addClass("on");
                return false;
            },
            function () { 
                $('#selRnum_toggle').removeClass("on");
                $('span.chkRecipient', '.rnumlist_body ul li').removeClass("on");
            }
        );
        $('#selRnum_toggle').toggle(
            function () { 
                $('#selRnum_toggle').addClass("on");
                $('span.chkRecipient', '.rnumlist_body ul li').addClass("on");
            },
            function () { 
                $('#selRnum_toggle').removeClass("on");
                $('span.chkRecipient', '.rnumlist_body ul li').removeClass("on");
            }
        );

        $('#selGroup_toggle').toggle(
            function () { 
                $('#selGroup_toggle').addClass("on");
                $('.chkGroup').addClass("on");
            },
            function () { 
                $('#selGroup_toggle').removeClass("on");
                $('.chkGroup').removeClass("on");
            }
        );
        $('.chkGroup').live('click', function() {
            $(this).toggleClass("on");
        });


        // 그룹선택
        $('.groupName').live('click', function() {
            $('.groupName').each(function() {
                $(this).removeClass("groupNameOn");
            });
            $(this).toggleClass("groupNameOn");
            load_members($(this).attr("group_srl"));
            load_members_ex.method = 'select';
        });

        // 검색버턴 클릭
        $('#btnMemberSearch').click(function() {

            query = $('#smsMemberSearchForm').serialize();
            //query += '&module=mobilemessage&act=dispMobilemessageMembersInGroupPaging';
            query1 = 'module=mobilemessage&act=dispMobilemessageMembersInGroupPaging';
            load_members_ex(query1, query);
            load_members_ex.method = 'search';

            return false;
        });

        $('#selCustomer_toggle').toggle(
            function () { 
                $('#selCustomer_toggle').addClass("on");
                $('.chkMember').addClass("on");
                return false;
            },
            function () { 
                $('#selCustomer_toggle').removeClass("on");
                $('.chkMember').removeClass("on");
            }
        );

        $('.memberInfo').live('click', function() {
            $('span.chkMember', $(this)).toggleClass("on");
        });

        // 받는사람::LI 클릭
        $('.rnumlist_body ul li').live('click', function() {
            $('.chkRecipient', $(this)).toggleClass("on");
        });

        // 받는사람::삭제 버턴 클릭
        $('.delRecipient').live('click', function() {
            $(this).parent().remove();
            updateNumofPnumber();
        });

        // 받는사람::선택된 받는사람 삭제
        $('#del_selNum').click(function () { 
            $('#progressModal').modal({
                containerCss: {
                    backgroundColor:"#eee"
                    , height:40
                    , width:200
                }
            });

            var $chkLi = $('span.chkRecipient.on', '.rnumlist_body ul li').parent();
            $chkLi.remove();
            updateNumofPnumber();
            
            $.modal.close();
            return false;
        });

        // 대량추가 선택
        $('#btn_bulkAdd').click(function() {
            if ($('#layerBulkAdd').css('display') == 'none')
                $('#layerBulkAdd').css('display', 'block');
            else
                $('#layerBulkAdd').css('display', 'none');
            return false;
        });

        $('#btnBulkAddClose').click(function() {
            $('#layerBulkAdd').css('display', 'none');
        });

        // 받는사람 전화번호 직접추가 버튼 클릭시 
	    $('#btnDirectAdd').click(function() {
            addDirectNumber();
            return false;
        });

        // Enter Key Submit 방지
        $('input,#smsPurplebook > *').keypress(function(event) {
            if (event.keyCode == 13)
                return false;
        });

        $('#inputDirectNumber').keyup(function() {
            $(this).val(getDashTel($(this).val()));
        });
        
        $('#inputDirectNumber').keypress(function(event) {
            if (event.keyCode == 13)
            {
                addDirectNumber();
                return false;
            }
        });

	
        // 문자내용의 byte를 세어 출력함 
        $('#smsPurplebookTextMessage').keyup(function(event) { 
            display_bytes();
        });

        // 예약전송 눌렀을 때
        $('#btnSimplePhoneReserv').click(function() {
            if ($('#smsReservationPane').css('display') == 'block')
            {
                $('#smsReservationPane').css('display', 'none');
            }
            else
            {
                $('#smsReservationPane').css('display', 'block');
            }
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
        $('#btnVerifyList').click(function() {
            var obj = cellphone_generalize($('#telnumlist').val());
            $('#telnumlist').val(obj.text);
            $('span.total', '.phoneNumberList').text('총 ' + obj.count + ' 명');
        });

        // 대량추가
        $('#btnAddList').click(function() {
            alert(addRecipient($('#telnumlist').val()) + " 명을 추가했습니다.");
        });
        // 비우기
        $('#btnEmptyList').click(function() {
            $('#telnumlist').val('');
        });

        $('#telnumlist').click(function() {
            if (!$(this).attr('firstclick'))
            {
                $(this).val('');
                $(this).attr('firstclick', true);
            }
        });

        $('#btnSimplePhoneSMS').click(function() {
            $(this).addClass('on');
            $('#btnSimplePhoneMMS').removeClass('on');
            $('#smsPurplebookTextMessage').attr('fixit', 'sms');
            display_bytes();
            return false;
        });
        $('#btnSimplePhoneMMS').click(function() {
            $(this).addClass('on');
            $('#btnSimplePhoneSMS').removeClass('on');
            $('#smsPurplebookTextMessage').attr('fixit', 'lms');
            display_bytes();
            return false;
        });

        $('.smsMembersPage').live('click', function() {
            if (load_members_ex.method == 'search')
            {
                query = $('#smsMemberSearchForm').serialize();
                //query += '&module=mobilemessage&act=dispMobilemessageMembersInGroupPaging&page=' + $(this).text();
                query1 = '&module=mobilemessage&act=dispMobilemessageMembersInGroupPaging&page=' + $(this).text();
                load_members_ex(query1, query);
            }
            else
            {
                load_members($('.groupName.groupNameOn').attr('group_srl'), $(this).text());
            }
        });

        $('#btnMembers').click(function() {
            $(this).addClass('membersOn');
            $('#btnPurplebook').removeClass('purplebookOn');
            $('#smsPurplebook').css('display', 'none');
            $('#smsOrganization').css('display', 'block');
            $('#addGroupToRecipient').attr('title', '왼쪽에 체크된 그룹의 멤버들을 추가합니다.');
            $('#addMemberToRecipient').attr('title', '왼쪽에 체크된 멤버들을 추가합니다.');
            return false;
        });


        $('#btnPurplebook').click(function() {
            $(this).addClass('purplebookOn');
            $('#btnMembers').removeClass('membersOn');
            $('#smsOrganization').css('display', 'none');
            $('#smsPurplebook').css('display', 'block');
            $('#addGroupToRecipient').attr('title', '왼쪽에 체크된 폴더의 명단을 추가합니다.');
            $('#addMemberToRecipient').attr('title', '왼쪽에 체크된 명단을 추가합니다.');
            return false;
        });

        $('#btnPurplebookRegister').click(function() {
            add_bulklist_to_purplebook();
        });

        $('#smsReplaceVarName').click(function() {
            addReplaceVar('%name%');
        });
        $('#smsReplaceVarNickname').click(function() {
            addReplaceVar('%nick_name%');
        });
        $('#smsReplaceVarId').click(function() {
            addReplaceVar('%user_id%');
        });
        $('#smsReplaceVarEmail').click(function() {
            addReplaceVar('%email_address%');
        });
        $('#smsReplaceVarHomepage').click(function() {
            addReplaceVar('%homepage%');
        });
        $('#smsReplaceVarBlog').click(function() {
            addReplaceVar('%blog%');
        });
        $('#smsReplaceVarBirthday').click(function() {
            addReplaceVar('%birthday%');
        });

        $('#btn_delDup').click(function() {
            $('#tfonProgressModal').modal({
                containerCss: {
                    backgroundColor:"#eee"
                    , height:40
                    , width:200
                }
            });

            setTimeout(function() {
                var count=0;
                $('li', '#rnumlist').each( function(i) {
                    var callno = $('.number', this).attr('phonenum');
                    var country = $('.number', this).attr('country');
                    //var fullNum = makeFullNumber(country, callno, false);

                    var length = $('[id=tel'+callno+']').size();
                    if (length > 1) {
                        $('#tel'+callno).parent().remove();
                        count++;
                    }
                });
                updateNumofPnumber();
                $.modal.close();
                alert('중복번호 ' + count + '개를 제거했습니다.');
            }, 500);

            return false;
        });
    });
}) (jQuery);
