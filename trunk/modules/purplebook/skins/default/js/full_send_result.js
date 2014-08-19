// check that already loaded
if(!send_result_loaded) var send_result_loaded = false;


// 리스트 불러오기
function load_full_result_list(page){
	var params = new Array();
	var response_tags = new Array('error','message','data','list_templete');

	params['g_mid'] = g_mid;
	params['page'] = page;

	// page
	if(typeof(page)=='undefined' || !page) page = jQuery('#full_send_result_page').val(); 

	// 리스트 카운트
	if(jQuery("#full_send_result_count").val()) params['list_count'] = jQuery("#full_send_result_count").val();

	// 날짜
	if(jQuery("#send_result_start_date").val()) params['s_start'] = jQuery("#send_result_start_date").val();
	if(jQuery("#send_result_end_date").val()) params['s_end'] = jQuery("#send_result_end_date").val();

	// 검색어
	if(jQuery("#send_result_search").val()) params['search_keyword'] = jQuery("#send_result_search").val();

	// Status 검색
	if(jQuery("#full_send_result_status").val()) params['status'] = jQuery("#full_send_result_status").val();
	
	exec_xml('purplebook', 'getPurplebookSendResult', params, function(ret_obj) {
		jQuery('#full_result_list').html(ret_obj["list_templete"]);
	}, response_tags);
}

// 창 리사이즈할때 마다 갱신
jQuery(window).resize(function () {
	if(jQuery('#full_send_result').css('display') == 'block') fullSendResultSize();
});
 
// 스크롤할때마다 위치 갱신
jQuery(window).scroll(function () {
	if(jQuery('#full_send_result').css('display') == 'block') fullSendResultSize();
});

// 폴더주소록 창 사이즈 구하기 
function fullSendResultSize(size_change){
	var dialHeight = jQuery(document).height();
	var dialWidth = jQuery(window).width();

	if(typeof(size_change) == 'undefined') jQuery('#full_send_result').css('width',dialWidth);
	else jQuery('#full_send_result').css({'width':dialWidth,'height':dialHeight}); 

	jQuery('#full_send_result').css('top', '0');
	jQuery('#full_send_result').css('left', '0');
	jQuery('#full_send_result').css('position', 'absolute');
}

// 폴더주소록 보여주기&숨기기
function fullSendResultShow(){
	$obj = jQuery("#full_send_result");
	if($obj.css('display') == 'block') jQuery($obj.html(''));

	if ($obj.css('display') == 'none'){
		//$obj.css('display','block');
		$obj.fadeIn(400);
	}
	else{ 
		$obj.css('display','none');
	}
	jQuery('body,html').animate({scrollTop: 0}, 300);
}

// 전체화면 닫기
function closeFullSendResult(){
	jQuery('#full_send_result').css('display','none'); // 전체보기 감추기
}

function send_result_reload(){
	load_full_result_list();
	alert('새로고침했습니다.');
}

jQuery(document).ready(function($){

	// tipsy 다시호출
	jQuery('input, a, img, button','.full_header').filter(function(index){ return !jQuery(this).hasClass('help'); }).tipsy(); 

	// fulle_address 창 사이즈구하기
	fullSendResultSize(); 

	// 리스트 불러오기
	load_full_result_list("1"); 

	// 전체보기창 보여주기
	fullSendResultShow();  

	// check that already loaded
	if(send_result_loaded) return;
	send_result_loaded = true;

	// 체크된 목록 예약취소 
	jQuery("#full_reserve_cancel").live('click', function(){
		var list = new Array();

		jQuery('span.checkbox.on', '#full_send_result_list').each(function(){
			list.push(jQuery(this).attr('message_id'));
		});

		if (list.length == 0){
			alert('취소할 명단을 체크하세요.');
			return false;
		}

		jQuery.ajax({
			type : "POST"
			, contentType: "application/json; charset=utf-8"
			, url : "./"
			, data : { 
						module : "purplebook"
						, act : "procPurplebookCancelMessages"
						, message_ids : JSON.stringify(list)
					 }
			, dataType : "json"
			, success : function (data){
				if(data.error == -1){
					alert(data.message);
				}

				// 화면에 업데이트된 리스트 새로고침 
				load_full_result_list();

				alert("취소가 완료되었습니다. ");
			}
			, error : function (xhttp, textStatus, errorThrown){ 
				alert(errorThrown + " " + textStatus); 

				alert("취소실패. ");
			}
		});
	});

	// 리스트 카운트 
	jQuery("#full_send_result_count").live("change", function(){
		list_count = jQuery('#full_send_result_count option:selected').val();
		load_full_result_list();
	});

	// 상태
	jQuery("#full_send_result_status").live("change", function(){
		load_full_result_list(1);
	});

	// 체크박스 전체선택/해제
	jQuery('#toggleSendResultList').live('click', function(){
		if(jQuery(this).hasClass('on')){
			jQuery(this).removeClass("on");
			jQuery('.checkbox', '#full_send_result_list td').removeClass("on");
		}
		else{
			jQuery(this).addClass("on");
			jQuery('.checkbox', '#full_send_result_list td').addClass("on");
			return false;
		}
	});

	// 체크박스 설정 
	jQuery('#full_send_result_list .checkbox').live('click', function(){
		jQuery(jQuery(this)).toggleClass("on");
	});
});
