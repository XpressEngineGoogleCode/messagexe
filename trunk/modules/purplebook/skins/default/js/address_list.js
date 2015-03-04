/**
 * @fileoverview address individual list
 * @requires address.html > #smsPurplebook > .left_select 
 */

(function($) {
	jQuery(function($) {
		/**
		 * selected address delete
		 */
		$('.left_select .btn_delete','#smsPurplebook').click(function() {
			p_show_waiting_message();

			var list = new Array();

			$('span.checkbox.on', 'ul#smsPurplebookList li').each(function() {
				list.push($(this).parent().attr('node_id'));
			});

			if (list.length == 0) {
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

		/**
		 * list checkbox toggle event
		 */
		$(document).on('click', 'ul#smsPurplebookList li', function() {
			$('.checkbox', $(this)).toggleClass("on");
		});
	
		// 주소록 리스트 전체선택/해제 (지금은 사용하지 않음)
		/*
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
		*/

		/**
		 * modify name by double click
		 */
		$(document).on('dblclick', '.nodeName', function() {
			pb_modify_name(this);
		});

		/**
		 * modify phone by double click
		 */
		$(document).on('dblclick', '.nodePhone', function() {
			pb_modify_phone(this);
		});

		/** @define {array} 마우스 왼쪽버튼 눌렀을 때 메뉴 이름 및 기능*/
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

		/**
		 * list 마우스 왼쪽버튼 클릭시 이벤트 설정
		 */
		$('#smsPurplebookList').delegate('li','mousedown', function() { $(this).contextMenu(menu1,{theme:'vista',offsetX:1,offsetY:1}); });

		/**
		 * search for address
		 */
		$('#btn_search','#smsPurplebook').click(function() {
			var search_word = $(this).prev('input').val();
			$('#search_word','#smsPurplebook').select().focus();
			pb_search_list(search_word);
			return false;
		});

		/**
		 *  주소록 검색창 엔터시 작동하게
		 */
		$('#search_word','#smsPurplebook').keypress(function(event) {
			if (event.keyCode == 13) {
				$(this).select();
				pb_search_list($(this).val());
				return false;
			}
		});
	});
}) (jQuery);

/**
 * selected li name change
 * @param {obj.object} li 객체
 */
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

/**
 * selected li phone num change
 * @param {obj.object} li 객체
 */
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

/**
 * show infomation
 * @param {obj.object} li 객체
 */
function pb_view_properties(obj) {
	var $node = jQuery(obj);
	var params = new Array();
	params['node_id'] = $node.attr('node_id');
	var response_tags = new Array('error','message','data');
	exec_xml('purplebook', 'getPurplebookProperties', params, function(ret_obj,response_tags) { completeGetProperties(obj,ret_obj,response_tags); }, response_tags);
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

/**
 * 리스트 카운트 업데이트 및 100개 더보기 버튼 추가 
 * @param {total_count.int}
 */
function updatePurplebookListCount(total_count) {
	 var list_count = jQuery('li', '#smsPurplebookList').size();
	 if (total_count) {
		 jQuery('#smsPurplebookListCount').text(list_count + ' / ' + total_count);
	 } else {
		 jQuery('#smsPurplebookListCount').text(list_count);
	 }
	 
	 if (list_count < total_count) {
		jQuery('<li id="pb_show_more" style="text-align:center; cursor:pointer;">100개 더보기</li>').appendTo('#smsPurplebookList').click(function() {
			pb_load_list.page++;
			pb_load_list();
			jQuery(this).remove();
		});
	 }
}

/**
 * search for address
 * @param {search_word.string}
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

function completePurplebookSearch(ret_obj, response_tags) {
	$list = jQuery('#smsPurplebookList','#smsPurplebook');
	if (ret_obj['data']) {
		var data = ret_obj['data']['item'];
		if (!jQuery.isArray(data)) {
			data = new Array(data);
		}
		for (var i = 0; i < data.length; i++) {
			$list.append('<li node_id="' + data[i].node_id + '" class="jstree-draggable"><span class="checkbox"></span><span class="nodeName" title="' + data[i].node_name + '">' + data[i].node_name + '</span><span class="nodePhone">' + getDashTel(data[i].phone_num) + '</span></li>');
		}
		updatePurplebookListCount(ret_obj['total_count']);
	}
}

/**
 * 주소록 리스트에 새로운 address 추가
 * @param {node_id.string} node 고유의 number
 * @param {node_name.string} node 이름
 * @param {phone_number.int} 폰번호
 */
function add_to_list(node_id, node_name, phone_num) {
	/** @define {boolean} country code check */
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
		jQuery('#smsPurplebookList').append('<li node_id="' + node_id + '" class="jstree-draggable" style="color:red;" original-title="잘못된 국가번호입니다."><span class="checkbox"></span><span class="nodeName" title="' + node_name + '">' + node_name + '</span><span class="nodePhone">' + getDashTel(phone_num) + '</span></li>');

		jQuery('li','#smsPurplebookList').filter(function(index) { return !jQuery(this).hasClass('help'); }).tipsy();

	}else {
		jQuery('#smsPurplebookList').append('<li node_id="' + node_id + '" class="jstree-draggable"><span class="checkbox"></span><span class="nodeName" title="' + node_name + '">' + node_name + '</span><span class="nodePhone">' + getDashTel(phone_num) + '</span></li>');
	}
}

/**
 * 선택된 폴더의 연락처 목록을 가져와서 선택 목록 영역에 출력한다.
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
