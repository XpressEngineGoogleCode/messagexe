/**
 * @fileoverview 받는사람 목록 관련
 * @requires address.html #purplebook_right
 */

(function($) {
	jQuery(function($) {
		/**
		 * 명단을 발송대상에 추가
		 */
		$('#addRecipients').click(function() {
			add_addrs_to_recipient();
			return false; // bacause of a(anchor) tag
		});

		/**
		 * 폴더를 발송대상에 추가
		 */
		$('#addFolder').click(function() {
			add_folder_to_recipient();
			return false; // bacause of a(anchor) tag
		});

		/**
		 * 받는사람 직접추가
		 */
		function addDirectNumber() {
			var new_num = $('#inputDirectNumber').val();
			var ref_name = $('#inputDirectName').val();
			if (new_num == "") { 
				alert("전화번호를 입력하세요");
				$('#inputDirectNumber').focus();
				return false;
			}
			addNum(new_num, ref_name);
			updateExceptListCount();
			scrollBottomTargetList();
			updateTargetListCount();
			display_cost();

			var params = new Array();
			params['ref_name'] = ref_name;
			params['phone_num'] = new_num;
			var response_tags = new Array('error','message');
			exec_xml('purplebook', 'procPurplebookSaveReceiverNumber', params, function() { }, response_tags);

			$('#inputDirectName').val('');
			$('#inputDirectNumber').val('');
			$('#inputDirectName').focus();
		}

		/**
		 * 대량추가
		 */
		function addRecipient(text) {
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

			for (var i = 0; i < lengthList; i++) {
				var strLine = '';
				row = pattern.exec(arrayList[i]);
				if (!row) continue;

				rePhone = row[1].replace(/[-\(\) ]/g, "");
				reName = row[2];

				if (!addNum(rePhone, reName)) countList++;
			}
			updateExceptListCount();
			scrollBottomTargetList();
			updateTargetListCount();

			$('#smsPurplebookBulkList').val('');
			$('#layer_mass').css('display', 'none');
			$('span.total', '#layer_mass').text('총 0 명');

			return countList;
		}

		/**
		 * 주소목록에 선택된 폴더를 받는사람 목록으로 추가한다.
		 */
		function add_folder(node_route, node_id, f_name) {
			$except_list = jQuery('#smsPurplebookExceptList');

			var params = new Array();
			var response_tags = new Array('error','message','data');

			params['node_route'] = node_route + node_id + ".";
			
			/**
			 * 최상위 폴더가 들어오면 node_id에 node_route가 들어오기 때문에 처리를 해줘야한다.
			 */
			if(node_id == 'f') params['node_route'] = node_id + ".";

			exec_xml('purplebook', 'getPurplebookListCount', params, function(ret_obj) {
				/**
				 * 이상이 없을 경우 추가 (개별선택, 삭제 이벤트 포함)
				 */
				$('#smsPurplebookTargetList').append('<li class="pb_folder_address" id="folder_' + node_id + '" ' + 'node_id=' + node_id + ' count=' + ret_obj["data"] + ' node_route=' + params['node_route'] + '><span class="checkbox"></span><span class="name">' + f_name + '</span><span class="number">(' + ret_obj["data"] + '명)' + '</span><span class="delete" title="삭제">삭제</span><span class="statusBox"></span></li>');
			}, response_tags);
	   
			return 0;
		}

		/**
		 * 폴더의 node_id로 명단을 가져와서 받는사람 목록에 추가한다.
		 */
		function add_folder_to_recipient() {
			var t = $('#smsPurplebookTree').jstree("get_selected");
			if (t.length == 0)
			{
				alert('체크된 폴더가 없습니다.\n왼쪽 폴더목록에서 체크박스에 체크하세요.');
				return;
			}

			node_id = t.attr('node_id')

			/**
			 * 최상위 폴더가 들어오면 node_id에 node_route가 들어오기 때문에 처리를 해줘야한다.
			 */
			if (node_id == 'f.') {
				node_id = 'f';
			}

			/**
			 * 이미 존재하는 폴더인지 검사
			 */
			if ($('#folder_' + node_id).length > 0)
			{
				if ($('#f_dup_' + node_id).length > 0) {
					var $count = $('.count', $('#f_dup_' + node_id).parent());
					var countVal = $count.text();

					countVal = parseInt(countVal) + 1;
					$count.text(countVal);
				} else {
					overlap_count = $('#pb_overlap_count').text();
					overlap_val = parseInt(overlap_count) + 1;

					$("#pb_overlap_count").html(overlap_val);

					$except_list.append('<li><span class="name">' + t.attr('node_name') + '</span><span id="f_dup_' + node_id + '" class="number">' + t.attr('count') + '</span><span class="count">1</span></li>');
				}

				/**
				 * pop_message 호출
				 */
				call_pb_pop_message(".pop_overlap", "중복번호에 추가되었습니다");

				return 1;
			}
			p_show_waiting_message();

			/**
			 * 폴더 추가
			 */
			add_folder(t.attr('node_route'), node_id, t.attr('node_name'));

			/**
			 * 이렇게 안하면 처음에 카운트를 제대로 세지 못한다.
			 */
			setTimeout(function() {
				//updateExceptListCount();
				scrollBottomTargetList();
				updateTargetListCount();
				display_cost();
				p_hide_waiting_message();
			}, 500);
		}

		/**
		 * 주소목록에 선택된 명단을 받는사람 목록으로 추가한다.
		 */
		function add_addrs_to_recipient() {
			p_show_waiting_message();

			/**
			 * 컨텐츠 SET
			 */
			var selected_folders = jQuery('#smsPurplebookTree').jstree('get_selected');
			if (selected_folders.length > 0) {
				var node = jQuery(selected_folders[0]);
			}

			/**
			 * 0.5초 뒤 실행, 이거 왜 이렇게 하는걸까? -_-
			 */
			setTimeout(function() {
				var succ_count=0;
				var list = new Array();
				$('span.checkbox.on', '#smsPurplebookList li').each(function() {
					list.push($(this));
				});

				/**
				 * 선택 항목이 없다면
				 */
				if (list.length == 0) { 
					alert('체크된 항목이 없습니다.\n왼쪽 목록에서 선택하세요.');
					p_hide_waiting_message();
					return;
				}
				for (var i = 0; i < list.length; i++) {
					var obj = list[i];
					var phonenum = $('.nodePhone', $(obj).parent()).text(); // 폰번호
					var name = $('.nodeName', $(obj).parent()).text(); // 이름
					var node_id = $(obj).parent().attr('node_id'); // node_id
					if (phonenum.length <= 0) continue;
					if (!addNum(phonenum, name, node_id)) succ_count++; // 실컷 카운팅하지만 뒤에서 안써먹는다-_-
				}

				// 중복번호, 스크롤내리고, 카운트 출력갱신하고, 소요비용 재계산해서 다시 출력하는 함수들을 호출하고 있는데 복잡하다. 개선이 필요한 듯.
				updateExceptListCount();
				scrollBottomTargetList();
				updateTargetListCount();
				display_cost();

				p_hide_waiting_message();
			}, 500);
		}

		/**
		 * 대량추가::점검하기
		 */
		$(document).on('click', '#btnVerifyList', function() {
			var obj = cellphone_generalize($('#smsPurplebookBulkList').val());
			$('#smsPurplebookBulkList').val(obj.text);
			$('span.total', '#layer_mass').text('총 ' + obj.count + ' 명');
			return false;
		});

		/**
		 * 대량추가 추가 버튼 클릭시
		 */
		$(document).on('click', '#btnAddList', function() {
			alert(addRecipient($('#smsPurplebookBulkList').val()) + " 명을 추가했습니다.");
			update_screen();
			return false;
		});

		/**
		 * 대량추가 textarea 클릭시
		 */
		$(document).on('click', '#smsPurplebookBulkList', function() {
			if (!$(this).attr('firstclick')) {
				$(this).val('');
				$(this).attr('firstclick', true);
			}
		});

		/**
		 * 수신목록 선택전환
		 */
		$('#smsPurplebookToggleTarget').toggle(
			function () { 
				$(this).addClass("on");
				$('span.checkbox', '#smsPurplebookTargetList li').addClass("on");
			},
			function () { 
				$(this).removeClass("on");
				$('span.checkbox', '#smsPurplebookTargetList li').removeClass("on");
			}
		);

		/**
		 * 받는사람::li 클릭
		 */
		$(document).on('click', '#smsPurplebookTargetList li', function() {
			$('.checkbox', $(this)).toggleClass("on");
		});

		/**
		 * 받는사람::삭제 버튼 클릭
		 */
		$(document).on('click', 'ul#smsPurplebookTargetList li span.delete', function() {
			$(this).parent().remove();
			updateTargetListCount();
		});

		/**
		 * 받는사람::선택된 받는사람 삭제
		 */
		$('#minusRecipients').click(function () {
			removeSelectedRecipients();
			display_cost();
			return false;
		});

		/**
		 * 받는사람 전화번호 직접추가 버튼 클릭시 
		 */
		$('#btnDirectAdd','#smsPurplebook').click(function() {
			addDirectNumber();
			return false;
		});

		/**
		 * 직접추가 전화번호 입력창keyup
		 */
		$('#inputDirectNumber','#smsPurplebook').keyup(function() {
			$(this).val(getDashTel($(this).val()));
		});

		/**
		 * 직접추가 전화번호 입력창  keypress
		 */
		$('#inputDirectNumber','#smsPurplebook').keypress(function(event) {
			if (event.keyCode == 13) {
				addDirectNumber();
				return false;
			}
		});

		/**
		 * 중복번호 set
		 */
		pb_load_overlap();
	});
}) (jQuery);

/**
 * 받는사람수 업데이트
 */
function updateTargetListCount(total_count) {
	total = list_counting();

	if (total_count) {
	   jQuery('#smsPurplebookTargetListCount').text(' (' + total + ' 명 / 총 ' + total_count + ' 명)');
	} else {
	   jQuery('#smsPurplebookTargetListCount').text(' (' + total + ' 명)');
	}

	return total;
}

/**
 * 제외번호 카운트
 */
function updateExceptListCount() {
	var size = jQuery('#smsPurplebookExceptList li').size();
	jQuery('#smsPurplebookExceptNum').text(size);
	jQuery('.pop_overlap .number','#smsPurplebook').text(size);
}

function scrollBottomTargetList() {
	var $list = jQuery('#smsPurplebookTargetList');
	$list.attr({scrollTop: $list.attr('scrollHeight')}, 1000);
}

/**
 * 폴더를 포함한 받는사람수를 카운팅한다.
 */
function list_counting() {
	li_size = jQuery('li', '#smsPurplebookTargetList').size();

	total = 0;
	for (i=0; i<li_size; i++) {
		target_li = jQuery('li', '#smsPurplebookTargetList')[i];
		folder_id = "folder_" + target_li.getAttribute('node_id');

		if (folder_id == target_li.getAttribute('id')) {
			total = total + parseInt(target_li.getAttribute('count'));
		} else {
			total++;
		}
	}
	return total;
}

/**
 * 선택된 받는 사람 명단을 삭제
 */
function removeSelectedRecipients() {
	p_show_waiting_message();
	var $chkLi = jQuery('span.checkbox.on', '#smsPurplebookTargetList li').parent();
	if (!$chkLi.size()) {
		alert('삭제할 대상을 선택하세요');
		return false;
	}
	$chkLi.remove();
	updateTargetListCount();
	alert($chkLi.size() + ' 건을 삭제하였습니다');
	p_hide_waiting_message();
}

/**
 * 중복번호창 불러오기
 */
function pb_load_overlap() {
	var params = new Array();
	var response_tags = new Array('error','message','data');

	params['g_mid'] = g_mid;
	params['layer_name'] = 'layer_overlap';

	layer_id = '#layer_overlap';

	exec_xml('purplebook', 'getPopupLayer', params, function(ret_obj) {
		if (ret_obj["data"]) {
			jQuery(layer_id).html(ret_obj["data"]);
			if (jQuery(layer_id).css('display') == 'block') jQuery(layer_id).html('');
		}
	}, response_tags);
}

/**
 * 0: ok
 * 1: already exist number
 **/
function addNum(newNum, rName, node_id) {
	newNum = newNum.replace(/-/g,'');
	$except_list = jQuery('#smsPurplebookExceptList');

	/**
	 * 국가코드 +로 시작할 경우 아이디를 찾지 못하기때문에  idNum을 따로 만들어준다
	 */
	idNum = newNum;
	if (newNum.charAt(0) == '+') idNum = newNum.substring(1, newNum.length)

	/**
	 * 이미 존재하는 번호인지 검사
	 */
	if (jQuery('#tel'+idNum).length > 0) {
		if (jQuery('#dup'+idNum).length > 0) {
			var $count = jQuery('.count', jQuery('#dup'+idNum).parent());
			var countVal = $count.text();

			countVal = parseInt(countVal) + 1;
			$count.text(countVal);
		} else {
			overlap_count = jQuery('#pb_overlap_count').text();
			overlap_val = parseInt(overlap_count) + 1;

			jQuery("#pb_overlap_count").html(overlap_val);

			$except_list.append('<li><span class="name">' + rName + '</span><span id="dup' + idNum + '" class="number">' + newNum + '</span><span class="count">1</span></li>');
		}

		/**
		 * pop_message 호출
		 */
		call_pb_pop_message(".pop_overlap", "중복번호에 추가되었습니다");

		return 1;
	}

	if (!node_id) node_id = '';

	/**
	 * 이상이 없을 경우 추가 (개별선택, 삭제 이벤트 포함)
	 */
	jQuery('#smsPurplebookTargetList').append('<li id="tel' + idNum + '" ' + 'node_id=' + node_id + '><span class="checkbox"></span><span class="name">' + rName + '</span><span class="number" phonenum="' + newNum + '">'+ newNum +'</span><span class="delete" title="삭제">삭제</span><span class="statusBox"></span></li>');

	return 0;
}

function cellphone_generalize(text) {
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
	var prefix = new RegExp("^0[1-9](0|1|6|7|8|9)([-\)])?[0-9]{3,4}(-)?[0-9]{4}$")
	for (var i = 0; i < lengthList; i++) {
		var strLine = '';
		row = pattern.exec(arrayList[i]);
		if (!row) continue;

		if (prefix.test(row[1])) {
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
