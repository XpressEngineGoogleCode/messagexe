/**
 * check that already loaded
 */
if (!pb_preview_loaded) var pb_preview_loaded = false;

/**
 * check folder route
 */
var use_preview_route = null;
var use_preview_route_name = null;

/**
 * 리스트 불러오기
 */
function pb_load_preview_list(node_id) {
	var params = new Array();
	var response_tags = new Array('error','message','data','list_template');

	var rcp_list = new Array(); // 받는 사람 정보
	var text = new Array(); // 문자내용
	var node_ids = new Array(); // node_ids 

	/**
	 * 보내는창 갯수
	 */
	p_screen = jQuery('li','#smsPurplebookContentInput');

	/**
	 * 창별로 문자내용 정렬
	 */
	for(var i = 0; i < p_screen.size(); i++){
		context = jQuery('.phonescreen','#smsPurplebookContentInput')[i];
		if (!jQuery(context).val()) return;

		text[i] = jQuery(context).val();

		/**
		 * 받는사람들 
		 */
		list = jQuery('li','#smsPurplebookTargetList');
		for(var p = 0; p < list.size(); p++){
			li = list.eq(p);
			rcp_list[p] = new Object();

			/**
			 * node_id가 있으면
			 */
			if (li.attr('node_id')) {
			   	rcp_list[p]['node_id'] = li.attr('node_id');

				/**
				 * 창이 여러개일때 중복체크
				 */
				if (jQuery.inArray(li.attr('node_id'), node_ids) == -1) node_ids.push(li.attr('node_id'));
			}

			rcp_list[p]['name'] = jQuery(".name", li).text();
			rcp_list[p]['number'] = jQuery(".number", li).text();
		}
	}

	// 직접 건내받은 node_id가있으면 node_route로 만들어 getPurplebookPreview 로 보낸다.
	node_route = null;
	if (node_id && node_id != 'base') {
		node_route = jQuery("#pb_node_id_"+node_id).attr("node_route") + node_id + ".";
		node_name = jQuery("#pb_node_id_"+node_id).attr("node_name");

		// 최상위 폴더일경우 node_id 가 'f'로 넘어오기 때문에 재설정이 필요하다.
		if (node_id == 'f') {
			node_route = 'f.';
			node_name = jQuery("#pb_node_id_"+node_id).attr("node_name");
		}
		use_preview_route = node_route;
		use_preview_route_name = node_name;
	} else if (node_id == 'base') {
		// node_id 가 base면 처음 루트로 돌아가도록 use_preview_route를 null로 바꿈
		use_preview_route = null;
		use_preview_route_name = null;
	}

	// 기존경로가 있으면 그대로 유지해준다
	if (use_preview_route) {
		node_route = use_preview_route;
		node_name = use_preview_route_name;
	}

	// 검색어 설정
	search_keyword = null;
	if (jQuery("#pb_preview_search").val()) search_keyword = jQuery("#pb_preview_search").val();

	jQuery.ajax({
		type : "POST"
        , contentType: "application/json; charset=utf-8"
        , url : "./"
		, data : { 
                    module : "purplebook"
                    , act : "getPurplebookPreview"
                    , g_mid : g_mid
                    , text : JSON.stringify(text)
                    , rcp_list : JSON.stringify(rcp_list)
					, node_ids : JSON.stringify(node_ids)
					, node_route : node_route
					, search_keyword : search_keyword
                 }
        , dataType : "json"
		, success : function (data) {
            if (data.error == -1) {
                alert(data.message);
                return;
            }

			jQuery('#pb_preview_list').html(data.list_template);

			// 경로보여주기
			if (node_route) {
				jQuery("#pb_preview_nav").html('<a href="#" onClick="pb_load_preview_list(\'base\')">기본</a> > <a href="#" onClick="pb_load_preview_list(' + node_route + ')">' + node_name + '</a>');
			}

        }
		, error : function (xhttp, textStatus, errorThrown) { 
            send_json.progress_count += content.length;
            alert(errorThrown + " " + textStatus); 
        }
	});
}

/**
 * 창 리사이즈할때 마다 갱신
 */
jQuery(window).resize(function () {
	if (jQuery('#pb_preview').css('display') == 'block') pb_preview_resize();
});
 
/**
 * 스크롤할때마다 위치 갱신
 */
jQuery(window).scroll(function () {
	if (jQuery('#pb_preview').css('display') == 'block') pb_preview_resize();
});

/**
 * 창 사이즈 구하기 
 */
function pb_preview_resize(size_change) {
	var dialHeight = jQuery(document).height();
	var dialWidth = jQuery(window).width();

	if (typeof(size_change) == 'undefined') jQuery('#pb_preview').css('width',dialWidth);
	else jQuery('#pb_preview').css({'width':dialWidth,'height':dialHeight}); 

	jQuery('#pb_preview').css('top', '0');
	jQuery('#pb_preview').css('left', '0');
	jQuery('#pb_preview').css('position', 'absolute');
}

/**
 * 미리보기 보여주기&숨기기
 */
function pb_preview_show() {
	$obj = jQuery("#pb_preview");
	if ($obj.css('display') == 'block') jQuery($obj.html(''));

	if ($obj.css('display') == 'none') {
		//$obj.css('display','block');
		$obj.fadeIn(400);
	}
	else{ 
		$obj.css('display','none');
	}
	jQuery('body,html').animate({scrollTop: 0}, 300);
}

/**
 * 미리보기 닫기
 */
function pb_close_preview() {
	jQuery('#pb_preview').css('display','none'); // 미리보기 감추기
}

jQuery(document).ready(function($){
	/**
	 * tipsy 다시호출
	 */
	jQuery('input, a, img, button','.pb_header').filter(function(index){ return !jQuery(this).hasClass('help'); }).tipsy(); 

	/**
	 * pbe_address 창 사이즈구하기
	 */
	pb_preview_resize(); 

	/**
	 * 리스트 불러오기
	 */
	pb_load_preview_list(); 

	/**
	 * 전체보기창 보여주기
	 */
	pb_preview_show();  

	/**
	 * check that already loaded
	 */
	if (pb_preview_loaded) return;
	pb_preview_loaded = true;
});
