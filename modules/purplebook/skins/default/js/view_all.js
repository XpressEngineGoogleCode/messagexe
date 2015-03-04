/**
 * @fileoverview 주소록 전체보기
 * @requires address.html
 */

/**
 * check that already loaded
 */
if (!pb_already_loaded) var pb_already_loaded = false;

var use_work_mode = "off"; // 작업모드 on/off
var use_fix_mode = false; // 수정모드 유지
var use_list_count = null; // list_view_count 유지
var use_page = 1; // 페이지 유지
var update_dialog = null; // 개별수정창

/**
 * 개별수정폼 보여주기
 */
function pb_update_form(node_id){
	if (!node_id) return false;

	update_dialog = jQuery('#update_address_modal').dialog({title:'수정', width:250, height:300, modal:true, buttons:false, resizable:false});
	
	var params = new Array();
	var response_tags = new Array('error','message','list_template');

	params['g_mid'] = g_mid;
	params['node_id'] = node_id;

	exec_xml('purplebook', 'getPurplebookUpdateAddress', params, function(ret_obj) {
		update_dialog.html(ret_obj["list_template"]);
	}, response_tags);

	jQuery("#update_address_modal").attr("tabindex", -1).focus();
}

/**
 * 개별수정 처리
 */
function pb_update_address(){
	params = new Array();
	params['node_id'] = jQuery("#pb_update_address_form input[name=node_id]").val();
	params['n_name'] = jQuery("#pb_update_address_form input[name=node_name]").val();
	params['phone_num'] = jQuery("#pb_update_address_form input[name=phone_num]").val();
	params['memo1'] = jQuery("#pb_update_address_form input[name=memo1]").val();
	params['memo2'] = jQuery("#pb_update_address_form input[name=memo2]").val();
	params['memo3'] = jQuery("#pb_update_address_form input[name=memo3]").val();

	response_tags = new Array('error','message');
	exec_xml('purplebook', 'procPurplebookUpdate', params, function(ret_obj){
		/**
		 * 화면에 업데이트된 리스트 새로고침 
		 */
		pb_load_address(null);

		/**
		 * 전체보기 Status와 History에 글올리기
		 */
		pb_set_address_status("수정이 완료되었습니다. ");

		/**
		 * 기존 폼 제거
		 */
		jQuery('#update_address_modal').remove();
	}, response_tags);
	return false;
}

/**
 * 작업모드중 페이지 떠날시 물음
 */
jQuery(window).bind('beforeunload', function(){
	if (use_work_mode == "on") return 'Are you sure you want to navigate away from this page?';
});

/**
 * 수정모드에서 인풋박스에 변경사항 있을시 작업모드로 On
 */
function pb_change_value(){
	use_work_mode = "on";
}

/**
 * 수정모드에서 저장
 */
function pb_save_address(){
	jQuery("#pb_fix_mode_form").ajaxSubmit({
		dataType : 'json',
		success : function(data) {
			// procPurplebookUpdateList error 발생시
			if (data.error == -1) {
				alert(data.message);
				return;
			}

			// 작업모드 off
			use_work_mode = "off";

			// 화면에 업데이트된 리스트 새로고침 
			pb_load_address(null, true);

			// 전체보기 Status와 History에 글올리기
			pb_set_address_status("수정이 완료되었습니다. "); 
		},
		error:function(request,status,error){
			// ajaxSubmit 실패시 
			alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error+"\n"+"status:"+status);

			// 전체보기 Status와 History에 글올리기
			pb_set_address_status("수정 실패."); 
		}
	});

	return false;

}

/**
 * 카운터 업데이트
 */
function pb_update_address_count(total_count){
     var total = jQuery('#pb_address_list tr').length;

     if (total_count) jQuery('#pb_count').text(' (' + total + ' 명 / 총 ' + total_count + ' 명)');
     else jQuery('#pb_count').text(' (' + total + ' 명)');
}

/**
 * 폴더주소록 보여주기&숨기기
 */
function pb_address_show(){
	$obj = jQuery("#pb_view_all");
	if ($obj.css('display') == 'block') jQuery($obj.html(''));

	if ($obj.css('display') == 'none') {
		//$obj.css('display','block');
		$obj.fadeIn(400);
	} else { 
		$obj.css('display','none');
	}
	jQuery('body,html').animate({scrollTop: 0}, 300);
}

/**
 * 창 리사이즈할때 마다 갱신
 */
jQuery(window).resize(function () {
	if (jQuery('#pb_view_all').css('display') == 'block') pb_address_resize();
});
 
/**
 * 스크롤할때마다 위치 갱신
 */
jQuery(window).scroll(function () {
	if (jQuery('#pb_view_all').css('display') == 'block') pb_address_resize();
});

/**
 * 폴더주소록 창 사이즈 구하기 
 */
function pb_address_resize(size_change){
	var dialHeight = jQuery(document).height();
	var dialWidth = jQuery(window).width();

	if (typeof(size_change) == 'undefined') {
		jQuery('#pb_view_all').css('width',dialWidth);
	} else {
		jQuery('#pb_view_all').css({'width':dialWidth,'height':dialHeight}); 
	}

	jQuery('#pb_view_all').css('top', '0');
	jQuery('#pb_view_all').css('left', '0');
	jQuery('#pb_view_all').css('position', 'absolute');
}

/**
 * 전체화면 닫기
 */
function pb_close_address() {
	/**
	 * 작업모드일때 페이지 이동시 물어봄
	 */
	if (use_work_mode == "on") {
		if (confirm("작업중입니다. 페이지를 떠나시겠습니까?")) {
		}
		else {
			return;
		}
	}
	if (update_dialog) update_dialog.dialog('close'); // 개별수정창 닫기

	jQuery('#pb_view_all').css('display','none'); // 전체보기 감추기

	pb_load_list(null, true); // 주소록 목록 새로고침
}


/**
 * 전체보기 추가 폼, 히스토리 감추기/보이기
 */
var pb_overlap_menu = '';
function pb_address_menu(id) {
	jQuery("#pb_add_address_excel").css('display','none');
	jQuery("#pb_add_address_direct").css('display','none');
	jQuery("#pb_address_history").css('display','none');

	//jQuery(id).css('display','block');
	
	if (pb_overlap_menu == id) {
		jQuery(id).css('display','none');;
		pb_overlap_menu = '';
	} else {
		jQuery(id).slideDown("slow");
		pb_overlap_menu = id;
	}
}


/**
 * 숨겨진 메뉴들 닫기(개별추가,엑셀추가 등)
 */
function closeFullMenu(id) {
	jQuery(id).css('display','none');
	pb_overlap_menu = '';
}

/**
 * 전체보기 리스트 불러오기
 */
function pb_load_address(page, fix_mode, list_count) {
	/**
	 * 작업모드일때 페이지 이동시 물어봄
	 */
	if (use_work_mode == "on") {
		if (confirm("작업중입니다. 페이지를 떠나시겠습니까?")) {
		} else {
			return;
		}
	}

	// 컨텐츠 SET
	var selected_folders = jQuery('#smsPurplebookTree').jstree('get_selected');

	if (selected_folders.length > 0) {
		var node = jQuery(selected_folders[0]);
	} else {
		return;
	}

	// page
	if (typeof(page)=='undefined' || !page) {
		page = use_page;
	} else {
		use_page = page;
	}
	

    var req_node_id = '';
    if (typeof(node)=='string') {
        req_node_id = node;
        node = jQuery('#'+req_node_id);
    } else { 
        req_node_id = node.attr('node_id');
    }

	var params = new Array();
	var response_tags = new Array('error','message','data','list_template');

	params['g_mid'] = g_mid;
	params['page'] = page;
	params['view_all'] = true;
	params['node_id'] = req_node_id;
	params['node_type'] = '2';

	// 수정모드	
	if (fix_mode == true) {
		params['fix_mode'] = fix_mode; 
		jQuery("#pb_fix_mode_open").css('display','none');
		jQuery("#pb_fix_mode_close").css('display','');
		jQuery("#pb_fix_mode_save").css('display','');

		use_fix_mode = true;
	} else if (fix_mode == false) {
		jQuery("#pb_fix_mode_open").css('display','');
		jQuery("#pb_fix_mode_close").css('display','none');
		jQuery("#pb_fix_mode_save").css('display','none');

		use_fix_mode = false;
	}

	if (use_fix_mode == true) params['fix_mode'] = true;  // 수정모드사용중일때는 해제하지 않는이상 계속 수정모드로 된다.

	search_keyword = jQuery("#pb_search_keyword").val();
	if (search_keyword) params['search_keyword'] = search_keyword; // 검색어 설정  

	if (list_count) {
		params['list_count'] = list_count; // 리스트 카운트
		use_list_count = list_count;
	} else {
		params['list_count'] = 10; // 리스트 카운트
	}

	if (use_list_count) params['list_count'] = use_list_count;

	exec_xml('purplebook', 'getPurplebookList', params, function(ret_obj) {
		jQuery('#pb_address_content').html(ret_obj["list_template"]);
	}, response_tags);
}

/**
 * 주소록에 명단 추가
 */
function pb_address_append() {
	var node_name = jQuery('#inputFullAddressName').val();
	var phone_num = jQuery('#inputFullAddressNumber').val();
	var memo1 = jQuery('#inputFullAddressMemo1').val();
	var memo2 = jQuery('#inputFullAddressMemo2').val();
	var memo3 = jQuery('#inputFullAddressMemo3').val();

	var selected_folders = jQuery('#smsPurplebookTree').jstree('get_selected');
	if (selected_folders.length != 1) {
		alert('선택된 폴더가 없습니다.');
		return;
	}

	var node = jQuery(selected_folders[0]);

	if (node_name.length == 0) {
		alert('이름을 입력하세요.');
		jQuery('#inputFullAddressName').focus();
		return;
	}
	if (phone_num.length == 0) {
		alert('폰번호를 입력하세요.');
		jQuery('#inputFullAddressNumber').focus();
		return;
	}

	if (!checkPhoneFormat(phone_num)) {
		if (!confirm("유효하지 않은 전화번호입니다 (" + phone_num + ")\n계속 진행하시겠습니까?"))
		return false;
	}

	jQuery.ajax({
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
		, success : function (data){
			if (data.error == -1) {
				alert(data.message);
				return;
			}

			// 전체보기 리스트 새로고침
			pb_load_address(null, false);

			// 개별등록 input 값 지우기
			jQuery('#inputFullAddressNumber').val('');
			jQuery('#inputFullAddressName').val('');
			jQuery('#inputFullAddressMemo1').val('');
			jQuery('#inputFullAddressMemo2').val('');
			jQuery('#inputFullAddressMemo3').val('');
			jQuery('#inputFullAddressName').focus();

			// 카운터 올리기
			pb_update_address_count();

			// 전체보기 Status와 History에 글올리기
			pb_set_address_status("개별추가가 완료되었습니다. "); 
		}
		, error : function (xhttp, textStatus, errorThrown) { 
			alert(errorThrown + " " + textStatus); 
		}
	});
}

/**
 * 전체보기 Status와 History에 글올리기
 */
function pb_set_address_status(message) {
	if (!message) return;

	var now = new Date();
	var nowTime = now.getHours() + "시" + now.getMinutes() + "분" + now.getSeconds() + "초";

	//jQuery("#pb_address_status").html(message);
	alert(message);

	if (jQuery("ul#pb_history_list li").length == 0) {
	   	jQuery("#pb_history_list").html('<li>' + message + '<span class="pb_address_date">' + nowTime + '</span>' + '</li>');
	} else {
		if (jQuery("ul#pb_history_list li").length > 10) jQuery("ul#pb_history_list li").last().remove(); // 10개이상 쌓이면 마지막 요소는 제거
		jQuery("#pb_history_list").prepend('<li>' + message + '<span class="pb_address_date">' + nowTime + '</span>' + '</li>');
	}
}

jQuery(document).ready(function($){
	/**
	 * tipsy 다시호출
	 */
	jQuery('input, a, img, button').filter(function(index){ return !jQuery(this).hasClass('help'); }).tipsy(); 

	/**
	 * pbe_address 창 사이즈구하기
	 */
	pb_address_resize(); 

	/**
	 * 전체보기 리스트 불러오기
	 */
	pb_load_address("1", false); 

	/**
	 * 전체보기창 보여주기
	 */
	pb_address_show();  

	/**
	 * check that already loaded
	 */
	if (pb_already_loaded) return;
	pb_already_loaded = true;

	/**
	 * 개별 삭제
	 */
	jQuery(document).on('click', "#pb_btn_address_delete", function() {
		node_id = jQuery(this).attr('node_id');
		if (!node_id) return false;

		var params = new Array();
		var response_tags = new Array('error','message');

		params['node_id'] = node_id;

		exec_xml('purplebook', 'procPurplebookDeleteNode', params, function(ret_obj){
			// 화면에 업데이트된 리스트 새로고침 
			pb_load_address(null);

			// 전체보기 Status와 History에 글올리기
			pb_set_address_status("삭제가 완료되었습니다. "); 
		}, response_tags);
	});

	/**
	 * 체크박스 설정 
	 */
	jQuery(document).on('click', '#pb_address_list .checkbox', function() {
		jQuery(jQuery(this)).toggleClass("on");
	});

	/**
	 * 리스트 카운트 
	 */
	jQuery(document).on("change", "#pb_list_count", function() {
		list_count = jQuery('#pb_list_count option:selected').val();
		pb_load_address(null, null, list_count);
	});

	/**
	 * 체크된 목록 삭제
	 */
	jQuery(document).on('click', "#pb_address_delete", function() {
		var list = new Array();

		jQuery('span.checkbox.on', '#pb_address_list').each(function(){
			list.push(jQuery(this).attr('node_id'));
		});

		if (list.length == 0) {
			alert('삭제할 명단을 체크하세요.');
			return false;
		}

		jQuery.ajax({
			type : "POST"
			, contentType: "application/json; charset=utf-8"
			, url : "./"
			, data : { 
						module : "purplebook"
						, act : "procPurplebookDelete"
						, node_ids : JSON.stringify(list)
					 }
			, dataType : "json"
			, success : function (data){
				if (data.error == -1) {
					alert(data.message);
				}

				/**
				 * 화면에 업데이트된 리스트 새로고침 
				 */
				pb_load_address(null);

				/**
				 * 전체보기 Status와 History에 글올리기
				 */
				pb_set_address_status("삭제가 완료되었습니다. ");

			}
			, error : function (xhttp, textStatus, errorThrown){ 
				alert(errorThrown + " " + textStatus); 

				/**
				 * 전체보기 Status와 History에 글올리기
				 */
				pb_set_address_status("삭제 실패.");
			}
		});
	});

	/**
	 * 전체보기 검색기능
	 */
	jQuery(document).on('click', '#btn_pb_search_keyword', function() {
		var selected_folders = jQuery('#smsPurplebookTree').jstree('get_selected');

		if (selected_folders.length > 0) {
			jQuery('#pb_address_content').html('');

			var node = jQuery(selected_folders[0]);
			pb_load_address("1", false);
		}		
	});

	/**
	 * view_all.html에서 엑셀로 주소록에 명단 추가
	 */
	jQuery(document).on('click', '#btnAddFullAddressExcel', function () {
		var selected_folders = jQuery('#smsPurplebookTree').jstree('get_selected');
		if (selected_folders.length != 1) {
			alert('선택된 폴더가 없습니다.');
			return;
		}

		var node = jQuery(selected_folders[0]);

		// set data
		jQuery("#excel_parent_node").val(node.attr('node_id'));
		jQuery("#excel_node_id").val(node.attr('node_route'));
		jQuery("#excel_node_name").val(node.attr('node_name'));
		jQuery("#excel_node_type").val('2');

		jQuery("#add_address_excel_form").ajaxSubmit({
			dataType : 'json',
			success : function(data){
				// procPurplebookExcelLoad error 발생시
				if (data.error == -1) {
					alert(data.message);
					return;
				}

				// 화면에 업데이트된 리스트 새로고침 
				pb_load_address(null, false);

				// 전체보기 Status와 History에 글올리기
				pb_set_address_status("엑셀파일로 추가되었습니다. "); 
			},
			error:function(request,status,error){
				// ajaxSubmit 실패시 
				alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error+"\n"+"status:"+status);
			}
		});
		return false;
	});

	/**
	 * 개별등록 
	 */
	jQuery(document).on('click', '#btnAddFullAddress', function() {
		pb_address_append();
		return false;
	});

	/**
	 * 체크박스 전체선택/해제
	 */
	jQuery(document).on('click', '#smsPurplebookToggleListFull', function() {
		if (jQuery(this).hasClass('on')) {
			jQuery(this).removeClass("on");
			jQuery('.checkbox', '#pb_address_list td').removeClass("on");
		} else {
			jQuery(this).addClass("on");
			jQuery('.checkbox', '#pb_address_list td').addClass("on");
			return false;
		}
	});
});
