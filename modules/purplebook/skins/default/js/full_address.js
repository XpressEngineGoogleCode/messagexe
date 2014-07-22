jQuery(document).ready(function(){
	jQuery('input, a, img, button','.full_header').filter(function(index) { return !jQuery(this).hasClass('help'); }).tipsy(); // tipsy 다시호출

	fullAddressSize(); // fulle_address 창 사이즈구하기

	load_full_address_list("1", false); // 전체보기 리스트 불러오기

	full_address_show();  // 전체보기창 보여주기
});

// html에 리스트 append
function add_to_list_full(node_id, node_name, phone_num, memo1, memo2, memo3)
{
	jQuery('#full_address_list').append('<tr><td node_id="' + node_id + '" class="jstree-draggable"><span class="checkbox"></span></td><td><span class="nodeName" title="' + node_name + '">' + node_name + '</span></td><td><span class="nodePhone">' + getSimpleDashTel(phone_num) + '</span></td><td><span>'+ memo1 +'</span></td><td><span>'+ memo2 +'</span></td><td><span>'+ memo3 +'</span></td></tr>');
}

// 카운터 업데이트
function updatePurplebookListCountFull(total_count)
{
     var total = jQuery('#full_address_list tr').length;

     if (total_count) jQuery('#smsPurplebookListCountFull').text(' (' + total + ' 명 / 총 ' + total_count + ' 명)');
     else jQuery('#smsPurplebookListCountFull').text(' (' + total + ' 명)');
}

// 폴더주소록 보여주기&숨기기
function full_address_show()
{
	$obj = jQuery("#full_address");
	if($obj.css('display') == 'block') jQuery($obj.html(''));

	if ($obj.css('display') == 'none') 
	{
		//$obj.css('display','block');
		$obj.fadeIn(400);
	}
	else 
	{
		$obj.css('display','none');
	}
	jQuery('body,html').animate({scrollTop: 0}, 300);
}

// 폴더주소록 창 사이즈 구하기 
function fullAddressSize(size_change){
	var dialHeight = jQuery(document).height();
	var dialWidth = jQuery(window).width();

	if(typeof(size_change) == 'undefined') jQuery('#full_address').css('width',dialWidth);
	else jQuery('#full_address').css({'width':dialWidth,'height':dialHeight}); 

	jQuery('#full_address').css('top', '0');
	jQuery('#full_address').css('left', '0');
	jQuery('#full_address').css('position', 'absolute');
}

// 전체화면 닫기
function closeFullAddress()
{
	jQuery('#full_address').css('display','none');
	return false;
}


/*
 * 전체보기 추가 폼, 히스토리 감추기/보이기
 */ 
var full_overlap_menu = '';
function showFullAddressMenu(id)
{
	jQuery("#full_add_address_excel").css('display','none');
	jQuery("#full_add_address_direct").css('display','none');
	jQuery("#full_history").css('display','none');

	//jQuery(id).css('display','block');
	
	if(full_overlap_menu == id)
	{
		jQuery(id).css('display','none');;
		full_overlap_menu = '';
	}
	else 
	{
		jQuery(id).slideDown("slow");
		full_overlap_menu = id;
	}
}
/*
 * END
 */


// 숨겨진 메뉴들 닫기(개별추가,엑셀추가 등)
function closeFullMenu(id)
{
	jQuery(id).css('display','none');
	full_overlap_menu = '';
}


// 전체보기 검색기능
jQuery('#btn_full_search_keyword').live('click',function() {
	var selected_folders = jQuery('#smsPurplebookTree').jstree('get_selected');

	if (selected_folders.length > 0) {
		jQuery('#full_address_list').html('');

		var node = jQuery(selected_folders[0]);
		load_full_address_list("1", false);
	}		

});

// 창 리사이즈할때 마다 갱신
jQuery(window).resize(function () {
	if(jQuery('#full_address').css('display') == 'block') fullAddressSize();
});
 
// 스크롤할때마다 위치 갱신
jQuery(window).scroll(function () {
	if(jQuery('#full_address').css('display') == 'block') fullAddressSize(true);
});

// 전체보기 리스트 불러오기
function load_full_address_list(page, full_fix_mode)
{
	// 컨텐츠 SET
	var selected_folders = jQuery('#smsPurplebookTree').jstree('get_selected');

	if (selected_folders.length > 0) {
		var node = jQuery(selected_folders[0]);
	}
	else return;

	// page
	if(typeof(page)=='undefined' || !page) page = jQuery('#full_address_page').val();

    var req_node_id = '';
    if (typeof(node)=='string') {
        req_node_id = node;
        node = jQuery('#'+req_node_id);
    } else {
        req_node_id = node.attr('node_id');
    }

	var params = new Array();
	var response_tags = new Array('error','message','data','list_templete');

	params['g_mid'] = g_mid;
	params['page'] = page;
	params['full_address_view'] = true;
	params['node_id'] = req_node_id;
    params['node_type'] = '2';

	// 수정모드	
	if(full_fix_mode == true)
	{
		params['full_fix_mode'] = full_fix_mode; 
		jQuery("#full_fix_mode_open").css('display','none');
		jQuery("#full_fix_mode_close").css('display','');
	}
	else
	{
		jQuery("#full_fix_mode_open").css('display','');
		jQuery("#full_fix_mode_close").css('display','none');
	}

	search_keyword = jQuery("#full_search_keyword").val();
	if(search_keyword) params['search_keyword'] = search_keyword; // 검색어 설정  

	exec_xml('purplebook', 'getPurplebookList', params, function(ret_obj) {
		jQuery('#full_address_list').html(ret_obj["list_templete"]);
		
		if(ret_obj["data"])
		{
			console.log('hello');
			console.log(ret_obj);	
		}
	}, response_tags);
}

// full_address.html에서 엑셀로 주소록에 명단 추가
jQuery('#btnAddFullAddressExcel').live('click',function (){
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
		success : function(data) {
			// procPurplebookExcelLoad error 발생시
			if(data.error == -1) 
			{
				alert(data.message);
				return;
			}

			// 화면에 업데이트된 리스트 새로고침 
			load_full_address_list(null, false);

			// 전체보기 Status와 History에 글올리기
			set_full_address_status("엑셀파일로 추가되었습니다. "); 
		},
		error:function(request,status,error){
			// ajaxSubmit 실패시 
			alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error+"\n"+"status:"+status);
		}
	});
	return false;
});

// 개별등록 
jQuery('#btnAddFullAddress').live('click',function() {
	append_address_full();
	return false;
});

// 주소록에 명단 추가
function append_address_full()
{
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

	if(!checkPhoneFormat(phone_num)) {
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
		, success : function (data) {
			if (data.error == -1)
			{
				alert(data.message);
				return;
			}

			// 전체보기 리스트 새로고침
			load_full_address_list(null, false);

			// 개별등록 input 값 지우기
			jQuery('#inputFullAddressNumber').val('');
			jQuery('#inputFullAddressName').val('');
			jQuery('#inputFullAddressMemo1').val('');
			jQuery('#inputFullAddressMemo2').val('');
			jQuery('#inputFullAddressMemo3').val('');
			jQuery('#inputFullAddressName').focus();

			// 카운터 올리기
			updatePurplebookListCountFull();

			// 전체보기 Status와 History에 글올리기
			set_full_address_status("개별추가가 완료되었습니다. "); 
		}
		, error : function (xhttp, textStatus, errorThrown) { 
			alert(errorThrown + " " + textStatus); 
		}
	});
}

// 전체보기 Status와 History에 글올리기
function set_full_address_status(message)
{
	if(!message) return;

	var now = new Date();
	var nowTime = now.getFullYear() + "년" + (now.getMonth()+1) + "월" + now.getDate() + "일" + now.getHours() + "시" + now.getMinutes() + "분" + now.getSeconds() + "초";

	jQuery("#full_address_status").html(message);

	if(jQuery("ul#full_address_history li").length == 0) jQuery("#full_address_history").html('<li>' + message + '<span class="full_address_date">' + nowTime + '</span>' + '</li>');
	else 
	{
		if(jQuery("ul#full_address_history li").length > 10) jQuery("ul#full_address_history li").last().remove(); // 10개이상 쌓이면 마지막 요소는 제거
		jQuery("#full_address_history").prepend('<li>' + message + '<span class="full_address_date">' + nowTime + '</span>' + '</li>');
	}
}

// 체크박스 전체선택/해제
jQuery('#smsPurplebookToggleListFull').live('click', function() {
	if(jQuery(this).hasClass('on'))
	{
		jQuery(this).removeClass("on");
		jQuery('.checkbox', '#full_address_list td').removeClass("on");
	}
	else
	{
		jQuery(this).addClass("on");
		jQuery('.checkbox', '#full_address_list td').addClass("on");
		return false;
	}
});
