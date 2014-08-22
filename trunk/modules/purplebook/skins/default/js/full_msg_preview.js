// check that already loaded
if(!msg_preview_loaded) var msg_preview_loaded = false;


// 리스트 불러오기
function load_full_preview_list(page){
	var params = new Array();
	var response_tags = new Array('error','message','data','list_templete');

	var rcp_list = new Array(); // 받는 사람 정보
	var text = new Array(); // 문자내용
	var node_ids = new Array(); // node_ids 

	// 보내는창 갯수
	p_screen = jQuery('li','#smsPurplebookContentInput');

	// 창별로 문자내용 정렬
	for(var i = 0; i < p_screen.size(); i++){
		context = jQuery('.phonescreen','#smsPurplebookContentInput')[i];
		if(!jQuery(context).val()) return;

		text[i] = jQuery(context).val();

		// 받는사람들 
		list = jQuery('li','#smsPurplebookTargetList');
		for(var p = 0; p < list.size(); p++){
			li = list.eq(p);

			// number, name, no_id set
			rcp_list[p] = new Object();

			// node_id가 있으면
			if(li.attr('node_id')){
			   	rcp_list[p]['node_id'] = li.attr('node_id');

				// 창이 여러개일때 중복체크
				if(jQuery.inArray(li.attr('node_id'), node_ids) == -1) node_ids.push(li.attr('node_id'));
			}

			rcp_list[p]['name'] = jQuery(".name", li).text();
			rcp_list[p]['number'] = jQuery(".number", li).text();
		}
	}

	console.log('wp-1');
	console.log(text);
	console.log(rcp_list);

	jQuery.ajax({
		type : "POST"
        , contentType: "application/json; charset=utf-8"
        , url : "./"
		, data : { 
                    module : "purplebook"
                    , act : "getPurplebookMsgPreview"
                    , g_mid : g_mid
                    , text : JSON.stringify(text)
                    , rcp_list : JSON.stringify(rcp_list)
					, node_ids : JSON.stringify(node_ids)
                 }
        , dataType : "json"
		, success : function (data) {
            if (data.error == -1)
            {
                alert(data.message);
                return;
            }

			console.log('w');
			console.log(data);

			jQuery('#full_preview_list').html(data.list_templete);
        }
		, error : function (xhttp, textStatus, errorThrown) { 
            send_json.progress_count += content.length;
            alert(errorThrown + " " + textStatus); 
        }
	});
}

// 창 리사이즈할때 마다 갱신
jQuery(window).resize(function () {
	if(jQuery('#full_msg_preview').css('display') == 'block') fullMsgPreviewSize();
});
 
// 스크롤할때마다 위치 갱신
jQuery(window).scroll(function () {
	if(jQuery('#full_msg_preview').css('display') == 'block') fullMsgPreviewSize();
});

//  창 사이즈 구하기 
function fullMsgPreviewSize(size_change){
	var dialHeight = jQuery(document).height();
	var dialWidth = jQuery(window).width();

	if(typeof(size_change) == 'undefined') jQuery('#full_msg_preview').css('width',dialWidth);
	else jQuery('#full_msg_preview').css({'width':dialWidth,'height':dialHeight}); 

	jQuery('#full_msg_preview').css('top', '0');
	jQuery('#full_msg_preview').css('left', '0');
	jQuery('#full_msg_preview').css('position', 'absolute');
}

// 미리보기 보여주기&숨기기
function fullMsgPreviewShow(){
	$obj = jQuery("#full_msg_preview");
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

// 미리보기 닫기
function closeFullMsgPreview(){
	jQuery('#full_msg_preview').css('display','none'); // 미리보기 감추기
}

jQuery(document).ready(function($){

	// tipsy 다시호출
	jQuery('input, a, img, button','.full_header').filter(function(index){ return !jQuery(this).hasClass('help'); }).tipsy(); 

	// fulle_address 창 사이즈구하기
	fullMsgPreviewSize(); 

	// 리스트 불러오기
	load_full_preview_list("1"); 

	// 전체보기창 보여주기
	fullMsgPreviewShow();  

	// check that already loaded
	if(msg_preview_loaded) return;
	msg_preview_loaded = true;

});
