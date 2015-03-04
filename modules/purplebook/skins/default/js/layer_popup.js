/**
 * @fileoverview layer popup handling 
 */

(function($) {
	jQuery(function($) {
		/**
		 * 미리보기, 전체보기, 전송결과 layer set
		 */
		$("body").append('<div id="pb_layer_box" style="z-index:99"><div id="pb_view_all"></div><div id="pb_result"></div><div id="pb_preview"></div></div>');

		/**
			layer popup handling
			@constructor 
		*/ 

		/**
		 * 주소록 이동 레이어
		 */
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

		/**
		 * 주소록 복사 레이어
		 */
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

		/**
		 * 주소록 추가 레이어
		 */
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

		/**
		 * 최근입력번호 레이어
		 */
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

		/**
		 * 중복번호 버튼 레이어
		 */
		$('.pop_overlap','#smsPurplebook').click(function() {
			$obj = jQuery('#layer_overlap');
			show_and_hide($obj);
			
			return false;
		});

		/**
		 * 저장된 입력내용 레이어
		 */
		$(document).on('click', '#smsPurplebookContentInput .pop_messages', function() {
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

		/**
		 * 머지기능 레이어
		 */
		$(document).on('click', '#btn_pop_merge', function() {
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

		/**
		 * 특수문자창 레이어
		 */
		$(document).on("click", '#btn_pop_chars', function() {
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

		/**
		 * 받는사람목록에서 주소록으로 복사 레이어
		 */
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

		/**
		 * 대량추가 레이어
		 */
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

		/**
		 * 예약전송 레이어
		 */
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

		/**
		 * 전체보기 레이어 
		 */
		$(document).on('click', '#pb_view_all_button', function() {
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

		/**
		 * 전송결과 레이어 
		 */
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

		/**
		 * 미리보기 레이어 
		 */
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

		/**
		 * 발신번호관리창 레이어
		 */
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

		/**
		 * 사진추가 레이어
		 */
		$(document).on("click", '#btn_attach_pic', function() {
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

			return false;
		});

		/**
			layer popup handling END
		*/ 


		/**
			layer popup set
			@constructor 
		*/ 

		/**
		 * 주소록 
		 */
		layer_popup_set('#layer_append', '<div id="layer_append" class="layer draggable"></div>', '.pop_append', '#smsPurplebook');

		/**
		 * 중복번호
		 */
		layer_popup_set('#layer_overlap', '<div id="layer_overlap" class="layer draggable"></div>', '.pop_overlap', '#smsPurplebook');

		/**
		 * 대량추가
		 */
		layer_popup_set('#layer_mass', '<div id="layer_mass" class="layer draggable"></div>', '#smsPurplebookAddBulk');

		/**
		 * 최근입력번호
		 */
		layer_popup_set('#layer_recent', '<div id="layer_recent" class="layer draggable"></div>', '.pop_recent', '#smsPurplebook');

		/**
		 * 문자내용 불러오기 
		 */
		layer_popup_set('#layer_messages', '<div id="layer_messages" class="layer draggable"></div>', '.pop_messages', '#smsPurplebookContentInput');

		/**
		 * 머지기능
		 */
		layer_popup_set('#layer_merge', '<div id="layer_merge" class="layer draggable"></div>', '#btn_pop_merge');

		/**
		 * 특수문자
		 */
		layer_popup_set('#layer_chars', '<div id="layer_chars" class="layer draggable"></div>', '#btn_pop_chars');

		/**
		 * 사진추가
		 */
		layer_popup_set('#layer_upload', '<div id="layer_upload" class="layer draggable"></div>', '#btn_attach_pic');

		/**
		 * 발신번호관리
		 */
		layer_popup_set('#layer_sendid', '<div id="layer_sendid" class="layer draggable"></div>', '.btn_show_layer', '#smsMessage .right_button');

		/**
		 * 예약발송
		 */
		layer_popup_set('#layer_reserv', '<div id="layer_reserv" class="layer draggable"></div>', '#btnSimplePhoneReserv', '#smsMessage');

		/**
		 * 전송현황
		 */
		layer_popup_set('#layer_status', '<div id="layer_status" class="layer draggable"></div>', '#btnSimplePhoneSend');

		/**
		 * 주소록 복사
		 */
		layer_popup_set('#layer_copy', '<div id="layer_copy" class="layer draggable"></div>', '.pop_copy', '#smsPurplebook');

		/**
		 * 주소록 이동
		 */
		layer_popup_set('#layer_move', '<div id="layer_move" class="layer draggable"></div>', '.pop_move', '#smsPurplebook');

		/**
		 * 받는사람 주소록 복사
		 */
		layer_popup_set('#layer_addrbook', '<div id="layer_addrbook" class="layer draggable"></div>', '.pop_addrbook', '#smsPurplebook');

		/**
		 * 정보보기
		 */
		layer_popup_set('#layer_properties', '<div id="layer_properties" class="layer draggable"></div>', '#smsMessage');

		/**
		 * 폴더공유
		 */
		layer_popup_set('#layer_share', '<div id="layer_share" class="layer draggable"></div>', '#smsMessage');

		/**
			layer popup set END
		*/ 

		/**
		 * 레이어창 닫기
		 */
		$(document).on('click', '.btn_closex', function() {
			$obj = $(this).parents('[id^=layer_]');
			show_and_hide($obj);
			return false;
		});

		/**
		 * draggable layer
		 */
		$('.layer.draggable').draggable({appendTo:'body',cursor:'crosshair',scroll:false,delay:300});
	});
}) (jQuery);

/**
 * layer popup 생성 및 위치설정
 * @param {layer_id.string} layer_id
 * @param {content.string} <div></div>
 * @param {layer_location.string} popup 위치
 * @param {layer_location.string} popup 위치2
 */
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

/**
 * 폴더공유창 띄우기 
 * @param {obj.obj} li object
 */
function pb_share_folder(obj) {
	var $node = jQuery(obj);
	var params = new Array();
	params['node_id'] = $node.attr('node_id');
	var response_tags = new Array('error','message','data');
	exec_xml('purplebook', 'getPurplebookSharedUsers', params, function(ret_obj,response_tags) { completeGetSharedUsers(obj,ret_obj,response_tags) }, response_tags);
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

/**
 * 최근번호창 띄우기
 */
function pb_load_recent_numbers() {
	var params = new Array();
	var response_tags = new Array('error','message','data');
	exec_xml('purplebook','getPurplebookLatestNumbers', params, completeLoadRecentNumbers, response_tags);
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

/**
 * 저장해둔 메시지 띄우기 (머지기능)
 */
function pb_load_saved_messages() {
	var params = new Array();
	var response_tags = new Array('error','message','data');
	exec_xml('purplebook','getPurplebookSavedMessages', params, completeLoadSavedMessages, response_tags);
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
