/**
 * @fileoverview 문자보내기
 * @전송루틴 submit_message -> get_pointinfo -> completeGetPointInfo -> get_cashinfo -> completeGetCashInfo -> do_after_get_cashinfo -> sendMessage -> sendMessageData -> send_json
 */

/**
 * 메시지보내기
 */
function submit_messages() {
	$current = get_active_textarea();

	/**
	 * 내용을 입력하였는지 검사
	 */
	if (!$current.val() || $current.val() == initial_content) {
		alert("내용을 입력해 주세요.");
		$current.focus();
		return false;
	}

	/**
	 * 발신전화번호 검사
	 */
	var sNum = jQuery('#smsPurplebookCallback').val();
	if (!checkCallbackNumber(sNum)) {
		alert('보내는사람의 전화번호를 정확히 입력하세요\n입력 예) 15881004 , 021231234, 0101231234');
		jQuery('#smsPurplebookCallback').focus().select();
		return false;
	}		

	/**
	 * 받는사람 번호 구성
	 */
	var r_num = list_counting();

	/**
	 * 받는사람이 없을 경우
	 */
	if (r_num == 0) {
		alert('받는사람을 입력하세요');
		return false;
	}

	/**
	 * 캐쉬정보 확인후 발송루틴 호출
	 */
	if (g_use_point == 'Y') {
		get_pointinfo();
	} else {
		get_cashinfo();
	}

	return false;
}

/**
 * 문자 발송 procPurplebookSendMsg 호출
 * @param {content.obj} 받는사람 목록 list들
 */
function send_json(content) {
	/**
	 * 전송상태 전체,성공건수 초기화
	 */
	if (typeof(send_json.total_count)=='undefined') send_json.total_count=0;
	if (typeof(send_json.progress_count)=='undefined') send_json.progress_count=0;
	if (send_json.progress_count == 0) {
		send_json.success_count=0;
		send_json.failure_count=0;
	}

	content_list = content;

	var data = JSON.stringify(content);
	/**
	 * for ie8
	 */
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
				 }
		, dataType : "json"
		, success : function (data) {
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
			
			pb_display_progress();

			$content_input = jQuery('#smsPurplebookContentInput');
			size = jQuery('li', $content_input).size();

			/**
			 * display 별로 전송
			 */
			sendMessageData.display+=1;
			if (sendMessageData.display < size) {
				$li = jQuery('li', $content_input).eq(sendMessageData.display);
				$scr = jQuery('.phonescreen', $li);

				/**
				 * 해당 스크린의 내용을 val["text"]에 담아 다시 send_json 요청
				 */
				jQuery.each(content_list, function (i, val) {
					val["text"] = $scr.val();
					val["delay_count"] = sendMessageData.display*2;
				});

				send_json(content_list);
			} else {
				sendMessageData.display = 0;

				/**
				 * 발송간격 설정이 안되있으면 문자 호출
				 */
				if (!sendMessageData.send_timer) sendMessageData();
			}
		}
		, error : function (xhttp, textStatus, errorThrown) { 
			send_json.progress_count += content.length;
			alert(errorThrown + " " + textStatus); 
		}
	});
}

/**
 * 메시지 데이타 send_json으로 전송
 */
function sendMessageData() {
	if (typeof(sendMessageData.index)=='undefined') sendMessageData.index=0;

	$list = jQuery('li','#smsPurplebookTargetList');

	pb_display_progress();

	/**
	 * index가 발송대상 리스트보다 많거나 전송상태가 complete면 접수완료
	 */
	if (sendMessageData.index >= $list.size() || sendMessageData.send_status == 'complete') {
		sendMessageData.send_status = 'complete';
		sendMessageData.list_status = 'f_complete';
		/**
		 * 전송간격 시간대 별로 전송하는 것 종료
		 */
		if (sendMessageData.send_timer) {
			clearInterval(sendMessageData.send_timer);
			sendMessageData.send_timer=false;
		}

		jQuery('.text','#layer_status').text('접수가 완료되었습니다.');
		jQuery('#layer_status_close','#layer_status').text('닫기');
		jQuery('#btn_result','#layer_status').css('display','');

		return false;
	}

	/**
	 * 전송상태가 일시정지면
	 */
	if (sendMessageData.send_status == 'pause') {
		return false; 
	}

	var content_list = new Array();

	/**
	 * 발송대상에 있던 folder를 쪼개서 content_list에 넣는다
	 */
	content_list = getFolderMessageList();

	/**
	 * folder집어넣기가 완료되면 개별발송건들을 content_list에 넣는다
	 */
	if (sendMessageData.list_status == 'f_complete') content_list = getMessageList(content_list);

	/**
	 *  첫번째 스크린 문자내용을 집어 넣는다
	 */
	$li = jQuery('li', $content_input).eq(sendMessageData.display);
	$scr = jQuery('.phonescreen', $li);

	jQuery.each(content_list, function (i, val) {
		val["text"] = $scr.val();
		val["delay_count"] = sendMessageData.display*2;
	});

	send_json(content_list);

	return true;
}

/**
 * 발송대상의 content 정보를 받아서 형식에 맞게 고쳐준다
 */
function getMessageList(content_list) {
	var speed = g_send_speed;

	/**
	 * 발송간격설정이 체크되있으면 설정된 만큼 메시지를 잘라서 보낸다
	 */
	if (jQuery("#message_interval_check").is(':checked')) {
		var speed = jQuery("#message_send_limit").val();
	}

	$list = jQuery('li','#smsPurplebookTargetList');

	var msgtype = getMsgType();
	var $content_input = jQuery('#smsPurplebookContentInput');

	content_count = 0;
	/**
	 * 넘겨받은 contnet_list 가 있다면
	 */
	if (content_list.length > 0) content_count = parseInt(content_list[0].count) % parseInt(content_list[0].list_count);

	/**
	 * 주소록 폴더에서 담다가 남은게 없다면 content_list를 새로 만든다
	 */
	if (typeof(content_list)=='undefined') var content_list = new Array();

	/**
	 * 시작지점 설정
	 */
	i = 0;
	if (content_count) i = content_count;

	for (i; i < speed; i++) {
		if (sendMessageData.index >= $list.size()) break;

		$li = $list.eq(sendMessageData.index);
		target_list = $li;

		/**
		 * folder일경우 넘어간다.
		 */
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

		/**
		 * file이 있으면
		 */
		if (file_srl) content["file_srl"] = file_srl;
		content_list.push(content);

		sendMessageData.index++;
	}

	return content_list;
}

/**
 * 발송대상에 폴더가 있을 경우 정보를 받아서 형식에 맞게 고쳐준다
 */
function getFolderMessageList() {
	var speed = g_send_speed;

	/**
	 * 발송간격설정이 체크되있으면 설정된 만큼 메시지를 잘라서 보낸다
	 */
	if (jQuery("#message_interval_check").is(':checked')) {
		var speed = jQuery("#message_send_limit").val();
	}

	var file_srl = jQuery('input[name=file_srl]', '#smsMessage').val();
	var msgtype = getMsgType();

	list = jQuery(".pb_folder_address");

	var content_list = new Array();

	if (sendMessageData.list_status == 'f_complete') return content_list;

	/**
	 * Folder page 설정
	 */
	if (!sendMessageData.page) sendMessageData.page = 1;

	/**
	 * folder list 가 없다면 완료처리 한다.
	 */
	if (list.size() == 0) {
		sendMessageData.list_status = 'f_complete';
		return content_list;
	}

	/**
	 * 전체 카운트 및 페이지 구하기
	 */
	var total_count	= 0;
	var index = 0;
	for (i = 0; i < list.size(); i++) {
		index = i;
		target_list = jQuery(".pb_folder_address").eq(i);
		total_count = total_count + parseInt(target_list.attr('count'));

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

		/**
		 * 예약전송
		 */
		if (jQuery('#smsPurplebookReservFlag').val() == '1') content["reservdate"] = texting_pickup_reservdate();

		/**
		 * file이 있으면
		 */
		if (file_srl) content["file_srl"] = file_srl;

		/**
		 * content push
		 */
		content_list.push(content);
	}
	
	var total_page = Math.ceil(total_count / speed);
	sendMessageData.page++;

	if (sendMessageData.page > total_page) {
		sendMessageData.page = 1;
		sendMessageData.list_status = 'f_complete';
		sendMessageData.index = index;
	}

	return content_list;
}

/**
 * progress bar set and sendMessageData 호출
 */
function sendMessage() {
	if (sendMessageData.send_timer) clearInterval(sendMessageData.send_timer);

	/**
	 * display progress
	 */
	send_json.total_count = 0;
	send_json.success_count = 0;
	send_json.failure_count = 0;
	send_json.error_code = null;

	/**
	 * clear status text
	 */
	jQuery('.text','#layer_status').text('전송중입니다...');

	/**
	 * clear send_json attributes
	 */
	$list = jQuery('li','#smsPurplebookTargetList');
	$content_input = jQuery('#smsPurplebookContentInput');
	var total_count = list_counting() * jQuery('li', $content_input).size();
	send_json.progress_count = 0;
	send_json.total_count = total_count;

	pb_display_progress();

	/**
	 * clear sending index
	 */
	sendMessageData.index = 0;
	sendMessageData.send_status = 'sending';
	sendMessageData.display = 0;
	sendMessageData.page = 1;

	var params = new Array();
	var response_tags = new Array('error','message','data');

	params['g_mid'] = g_mid;
	params['layer_name'] = 'layer_status';

	var layer_id = '#layer_status';

	/**
	 *  문자 전송 상태창 띄우기
	 */
	exec_xml('purplebook', 'getPopupLayer', params, function(ret_obj) {
		if (ret_obj["data"]) {
			jQuery(layer_id).html(ret_obj["data"]);
			if (jQuery(layer_id).css('display') == 'block') jQuery(layer_id).html('');

			$obj = jQuery(layer_id);
			show_and_hide($obj,null,{force_show:true});
		}
	}, response_tags);

	/**
	 *  reset 전송완료후 버튼
	 */
	jQuery('#layer_status_close','#layer_status').text('취소');
	jQuery('#btn_result','#layer_status').css('display','none');

	/**
	 *  전송간격설정이 체크되있으면 전송간격 시간을 가져와 전송요청한다.
	 */
	if (jQuery("#message_interval_check").is(':checked')) {
		g_send_interval = jQuery("#message_send_interval").val() * 1000 * 60;
		sendMessageData.send_timer = setInterval(function() { sendMessageData(); }, g_send_interval);
	} else {
		sendMessageData();
	}
}

/**
 * 사용자 point info 가져오기
 */
function get_pointinfo() {
	var params = new Array();
	var response_tags = new Array('error','message','point','msg_not_enough_point');
	exec_xml('purplebook', 'getPurplebookPointInfo', params, completeGetPointInfo, response_tags);
}

/**
 * point 차감 후 cash차감 으로
 */
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
	switch (getMsgType()) {
		case "sms" : 
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
			break;
		case "lms" :
			if (count > lms_avail) {
				alert(ret_obj['msg_not_enough_point'] + "\n"
					+ "현재 포인트: " + point + "\n"
					+ word_send + "가능 LMS 건수: " + lms_avail  + "\n"
					+ word_send + "예정 LMS 건수: " + count
					);
				return false;
			}
			break;
		case "mms" :
			if (count > mms_avail) {
				alert(ret_obj['msg_not_enough_point'] + "\n"
					+ "현재 포인트: " + point + "\n"
					+ word_send + "가능 MMS 건수: " + mms_avail  + "\n"
					+ word_send + "예정 MMS 건수: " + count
					);
				return false;
			}
			break;
	}
	get_cashinfo();
}

/** 
 * 사용자 캐쉬가져오기
 */
function get_cashinfo() {
	var params = new Array();
	var response_tags = new Array('error','message','cash','point','sms_price','lms_price','mms_price','deferred_payment');
	exec_xml('purplebook', 'getPurplebookCashInfo', params, completeGetCashInfo, response_tags);
}

/**
 * 캐쉬정보 obj에 저장후 do_after_get_cashinfo로
 */
function completeGetCashInfo(ret_obj, response_tags) {
	var obj = new Object();
	obj.cash = parseInt(ret_obj['cash']);
	obj.point = parseInt(ret_obj['point']);
	obj.sms_price = parseInt(ret_obj['sms_price']);
	obj.lms_price = parseInt(ret_obj['lms_price']);
	obj.mms_price = parseInt(ret_obj['mms_price']);
	obj.deferred_payment = ret_obj['deferred_payment'];
	do_after_get_cashinfo(obj);
}

/**
 * 사용자에게 전송확인후 캐쉬차감 및 sendMessage 호출
 */
function do_after_get_cashinfo(cashinfo) {
	/**
	 * 받는사람수
	 */
	var r_num = list_counting(); 
	/**
	 * 예약여부
	 */
	var reservflag = document.getElementById("smsPurplebookReservFlag").value; 
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

	/**
	 *받는사람이 없을 경우
	 */
	if (r_num == 0) {
		alert('받는사람을 입력하세요');
		return false;
	}

	/**
	 * 후불사용자면 가능건수 계산을 넘어간다
	 */
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

		sendMessage();
		return;
	}

	/**
	 * 가능건수 계산
	 */
	sms_avail = calc_sms(cashinfo, cashinfo.sms_price);
	lms_avail = calc_lms(cashinfo, cashinfo.lms_price);
	mms_avail = calc_mms(cashinfo, cashinfo.mms_price);

	if (msg_type == "sms") {
		npages = get_page_count();

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

	sendMessage();
}

/**
 * progress바 표시
 */
function pb_display_progress() {
	/**
	 * get total count
	 */
	$list = jQuery('li','#smsPurplebookTargetList');
	$content_input = jQuery('#smsPurplebookContentInput');
	var total_count = list_counting() * jQuery('li', $content_input).size();
	send_json.total_count = total_count;

	/**
	 * calculate percentage
	 */
	var percent = send_json.progress_count / send_json.total_count * 100;

	/**
	 * display progress
	 */
	jQuery('.progressBar','#layer_status').progressbar({
		value: percent
	});
	jQuery('.total_count','#layer_status').text(send_json.total_count);
	jQuery('.success_count','#layer_status').text(send_json.success_count);
	jQuery('.failure_count','#layer_status').text(send_json.failure_count);
	jQuery('.error_code','#layer_status').text("");
	if (send_json.error_code) jQuery('.error_code','#layer_status').text(send_json.error_code);
}

/**
 * sms 가능건수 계산
 * @param {cashinfo.obj} 캐쉬정보
 * @param {point.int} 포인트
 */
function calc_sms(cashinfo, point) {
	if (point == undefined) point = 20;
	return Math.floor(cashinfo.cash / point) + Math.floor(cashinfo.point / point);
}

/**
 * lms 가능건수 계산
 */
function calc_lms(cashinfo, point) {
	if (point == undefined) point = 50;
	return Math.floor(cashinfo.cash / point) + Math.floor(cashinfo.point / point);
}

/**
 * mms 가능건수 계산
 */
function calc_mms(cashinfo, point) {
	if (point == undefined) point = 200;
	return Math.floor(cashinfo.cash / point) + Math.floor(cashinfo.point / point);
}
