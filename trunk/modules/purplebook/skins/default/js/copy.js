/**
 * @fileoverview 연락처 복사기능 담당
 * @requires layer_copy.html
 */

/** @define {boolean} permission을 이용해 코드가 중복되지 않도록 한다. */
if (!copy_javascript_permission) {
	var copy_javascript_permission = false;
}

(function($) {
	jQuery(function($) {
		/**
		 * js file이 한번만 로딩되도록
		 */
		if (copy_javascript_permission == false) {
		   	copy_javascript_permission = true;
		} else {
			return;
		}

		/**
		 * 폴더 복사
		 */
		$(document).on('click', '#smsPurplebookDoCopy', function() {
			var selected_folders = $('#smsPurplebookTargetTreeCopy').jstree('get_selected');
			if (selected_folders.length != 1) {
				alert('명단을 추가할 폴더를 한개만 선택하세요.');
				return false;
			}

			var $node = $(selected_folders[0]);
			if (!$node) {
				alert('복사할 폴더를 선택하세요.');
				return;
			}
			node_route = $node.attr('node_route') + $node.attr('node_id') + '.';
			node_name = $node.attr('node_name');
			var node_id = $node.attr('node_id');

			p_show_waiting_message();

			var list = new Array();

			$('span.checkbox.on', 'ul#smsPurplebookList li').each(function() {
				list.push($(this).parent().attr('node_id'));
			});

			if (list.length == 0)
			{
				p_hide_waiting_message();
				alert('복사할 명단을 체크하세요.');
				return;
			}

			if (!confirm(list.length + '건의 명단을 [' + node_name + ']폴더로 복사하시겠습니까?'))
			{
				p_hide_waiting_message();
				alert('취소했습니다.');
				return;
			}

			$.ajax({
				type : "POST"
				, contentType: "application/json; charset=utf-8"
				, url : "./"
				, data : { 
							module : "purplebook"
							, act : "procPurplebookCopy"
							, node_list : JSON.stringify(list)
							, node_id : node_id
						 }
				, dataType : "json"
				, success : function (data) {
					p_hide_waiting_message();
					pb_load_list(null,true);
					if (data.error == -1)
						alert(data.message);
				}
				, error : function (xhttp, textStatus, errorThrown) { 
					p_hide_waiting_message();
					alert(errorThrown + " " + textStatus); 
				}
			});
			return false;
		});

		/**
		 * refresh target tree(copy)
		 */
		$(document).on('click', '#layer_copy .btn_refresh', function() {
			$('#smsPurplebookTargetTreeCopy').html('');
			delete init_target_tree.initial;
			init_target_tree('#smsPurplebookTargetTreeCopy',init_target_tree.img_base);
			return false;
		});
	});
}) (jQuery);
