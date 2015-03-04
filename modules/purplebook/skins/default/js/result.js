/**
 * @fileoverview 전송결과
 * @requires result.html
 */

/** @define {boolean} permission을 이용해 코드가 중복되지 않도록 한다. */
if (!pb_result_loaded) var pb_result_loaded = false;

/**
 * 리스트 불러오기
 */
function pb_load_result_list(page){
	var params = new Array();
	var response_tags = new Array('error','message','data','list_template');

	params['g_mid'] = g_mid;
	params['page'] = page;

	// page
	if (typeof(page)=='undefined' || !page) page = jQuery('#pb_result_page').val(); 

	// 리스트 카운트
	if (jQuery("#pb_result_count").val()) params['list_count'] = jQuery("#pb_result_count").val();

	// 날짜
	if (jQuery("#pb_result_start_date").val()) params['s_start'] = jQuery("#pb_result_start_date").val();
	if (jQuery("#pb_result_end_date").val()) params['s_end'] = jQuery("#pb_result_end_date").val();

	// 검색어
	if (jQuery("#pb_result_search").val()) params['search_keyword'] = jQuery("#pb_result_search").val();

	// Status 검색
	if (jQuery("#pb_result_status").val()) params['status'] = jQuery("#pb_result_status").val();

	// 처음 로드 할때 현재날짜를 기준으로 검색한다.
	if (pb_result_loaded == false) {
		now = new Date();
		year= now.getFullYear();
		mon = (now.getMonth()+1)>9 ? ''+(now.getMonth()+1) : '0'+(now.getMonth()+1);
		day = now.getDate()>9 ? ''+now.getDate() : '0'+now.getDate();
		current_date = year + '-' + mon + '-' + day;

		jQuery("#pb_result_start_date").val(current_date);
		jQuery("#pb_result_start_date").next().val(current_date);
		jQuery("#pb_result_end_date").val(current_date);
		jQuery("#pb_result_end_date").next().val(current_date);

		params['s_start'] = jQuery("#pb_result_start_date").val();
		params['s_end'] = jQuery("#pb_result_end_date").val();
	}

	exec_xml('purplebook', 'getPurplebookResult', params, function(ret_obj) {
		jQuery('#pb_result_list').html(ret_obj["list_template"]);
	}, response_tags);
}

/**
 * 창 리사이즈할때 마다 갱신
 */
jQuery(window).resize(function () {
	if (jQuery('#pb_result').css('display') == 'block') pb_result_resize();
});
 
/**
 * 스크롤할때마다 위치 갱신
 */
jQuery(window).scroll(function () {
	if (jQuery('#pb_result').css('display') == 'block') pb_result_resize();
});

/**
 * 창 사이즈 구하기 
 */
function pb_result_resize(size_change) {
	var dialHeight = jQuery(document).height();
	var dialWidth = jQuery(window).width();

	if (typeof(size_change) == 'undefined') jQuery('#pb_result').css('width',dialWidth);
	else jQuery('#pb_result').css({'width':dialWidth,'height':dialHeight}); 

	jQuery('#pb_result').css('top', '0');
	jQuery('#pb_result').css('left', '0');
	jQuery('#pb_result').css('position', 'absolute');
}

/**
 * 전송결과 보여주기&숨기기
 */
function pb_result_show() {
	$obj = jQuery("#pb_result");
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
 * 전송결과 닫기
 */
function pb_close_result() {
	jQuery('#pb_result').css('display','none'); // 전송결과 감추기
}

/**
 * 새로고침
 */
function pb_result_reload(){
	pb_load_result_list();
	alert('새로고침했습니다.');
}

jQuery(document).ready(function($){
	/**
	 * tipsy 다시호출
	 */
	jQuery('input, a, img, button','.pb_header').filter(function(index){ return !jQuery(this).hasClass('help'); }).tipsy(); 

	/**
	 * pbe_address 창 사이즈구하기
	 */
	pb_result_resize(); 

	/**
	 * 리스트 불러오기
	 */
	pb_load_result_list("1"); 

	/**
	 * 전체보기창 보여주기
	 */
	pb_result_show();  

	/**
	 * check that already loaded
	 */
	if (pb_result_loaded) return;
	pb_result_loaded = true;

	/**
	 * 체크된 목록 예약취소 
	 */
	jQuery(document).on('click', "#pb_reserve_cancel", function(){
		var list = new Array();

		jQuery('span.checkbox.on', '#pb_result_list').each(function(){
			list.push(jQuery(this).attr('message_id'));
		});

		if (list.length == 0) {
			alert('취소할 명단을 체크하세요.');
			return false;
		}

		jQuery.ajax({
			type : "POST"
			, contentType: "application/json; charset=utf-8"
			, url : "./"
			, data : { 
						module : "purplebook"
						, act : "procPurplebookCancelMsg"
						, message_ids : JSON.stringify(list)
					 }
			, dataType : "json"
			, success : function (data){
				if (data.error == -1) {
					alert(data.message);
				}

				// 화면에 업데이트된 리스트 새로고침 
				pb_load_result_list();

				alert("취소가 완료되었습니다. ");
			}
			, error : function (xhttp, textStatus, errorThrown){ 
				alert(errorThrown + " " + textStatus); 

				alert("취소실패. ");
			}
		});
	});

	/**
	 * 리스트 카운트 
	 */
	jQuery(document).on("change", "#pb_result_count", function(){
		list_count = jQuery('#pb_result_count option:selected').val();
		pb_load_result_list();
	});

	/**
	 * 상태
	 */
	jQuery(document).on("change", "#pb_result_status", function(){
		pb_load_result_list(1);
	});

	/**
	 * 체크박스 전체선택/해제
	 */
	jQuery(document).on('click', '#toggleSendResultList', function(){
		if (jQuery(this).hasClass('on')) {
			jQuery(this).removeClass("on");
			jQuery('.checkbox', '#pb_result_list td').removeClass("on");
		} else { 
			jQuery(this).addClass("on");
			jQuery('.checkbox', '#pb_result_list td').addClass("on");
			return false;
		}
	});

	/**
	 * 체크박스 설정 
	 */
	jQuery(document).on('click', '#pb_result_list .checkbox', function(){
		jQuery(jQuery(this)).toggleClass("on");
	});
});
