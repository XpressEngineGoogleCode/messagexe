/**
 * @fileoverview address list에 추가하기 위한 기능들
 * @requires layer_append.html
 */

/** @define {boolean} permission을 이용해 코드가 중복되지 않도록 한다. */
if (!append_javascript_permission) {
	var append_javascript_permission = false;
}

(function($) {
	jQuery(function($) {
		/**
		 * js file이 한번만 로딩되도록
		 */
		if (append_javascript_permission == false) {
		   	append_javascript_permission = true;
		} else {
			return;
		}

		/**
		 * 주소록에 명단 추가 버튼 클릭
		 */
		$(document).on('click', '#btnAddAddress', function() {
			append_address();
			return false;
		});

		/**
		 * 연락쳐 입력시 - 추가
		 */
		$('#inputPurplebookPhone').keyup(function(event) {
			$(this).val(getDashTel($(this).val()));
		});

		/**
		 * 연락쳐 입력 enter 클릭시 추가
		 */
		$('#inputPurplebookPhone').keypress(function(event) {
			if (event.keyCode == 13) {
				append_address();
				return false;
			}
		});

		/**
		 * 연락처저장
		 */
		function append_address() {
			var selected_folders = $('#smsPurplebookTree').jstree('get_selected');
			var node_name = $('#inputPurplebookName').val();
			var phone_num = $('#inputPurplebookPhone').val();
			var memo1 = $('#inputPurplebookMemo1').val();
			var memo2 = $('#inputPurplebookMemo2').val();
			var memo3 = $('#inputPurplebookMemo3').val();

			if (selected_folders.length != 1) {
				alert('선택된 폴더가 없습니다.');
				return;
			}

			var node = $(selected_folders[0]);

			if (node_name.length == 0) {
				alert('이름을 입력하세요.');
				$('#inputPurplebookName').focus();
				return;
			}
			if (phone_num.length == 0) {
				alert('폰번호를 입력하세요.');
				$('#inputPurplebookPhone').focus();
				return;
			}

			if (!checkPhoneFormat(phone_num)) {
				if (!confirm("유효하지 않은 전화번호입니다 (" + phone_num + ")\n계속 진행하시겠습니까?"))
				return false;
			}

			$.ajax({
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
					if (data.error == -1) {
						alert(data.message);
						return;
					}
					add_to_list(data.node_id, node_name, phone_num);

					$('#inputPurplebookPhone').val('');
					$('#inputPurplebookName').val('');
					$('#inputDirectMemo1').val('');
					$('#inputDirectMemo2').val('');
					$('#inputDirectMemo3').val('');
					$('#inputPurplebookName').focus();

					updatePurplebookListCount();
					pb_load_list(null,true);
				}
				, error : function (xhttp, textStatus, errorThrown) { 
					alert(errorThrown + " " + textStatus); 
				}
			});
		}
	});
}) (jQuery);
