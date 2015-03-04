/**
 * @fileoverview 받는사람목록에서 주소록으로 복사 해주는 기능
 * @requires layer_addrbook.html
 */

/** @define {boolean} permission을 이용해 코드가 중복되지 않도록 한다. */
if (!addrbook_javascript_permission) {
	var addrbook_javascript_permission = false;
}

(function($) {
	jQuery(function($) {
		/**
		 *  js file이 한번만 로딩되도록
		 */
		if (addrbook_javascript_permission == false) {
		   	addrbook_javascript_permission = true;
		} else {
			return;
		}
		
		/**
		 * refresh target tree(copy to addrbook)
		 */
		$(document).on('click', '#layer_addrbook .btn_refresh', function() {
			$('#smsPurplebookTargetTreeAddrbook').html('');
			delete init_target_tree.initial;
			init_target_tree('#smsPurplebookTargetTreeAddrbook',init_target_tree.img_base);
			return false;
		});

		/**
		 *  copy to addressbook
		 */
		$(document).on('click', '#layer_addrbook .btn_copy', function() {
			var selected_folders = $('#smsPurplebookTargetTreeAddrbook').jstree('get_selected');
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

			exist_folder = false;
			$('span.checkbox.on', 'ul#smsPurplebookTargetList li').each(function() {
				var node_name = $('.name',$(this).parent()).text();
				var phone_num = $('.number',$(this).parent()).attr('phonenum');

				// check된 항목이 folder라면 return
				if (!phone_num) {
					exist_folder = true;
					return;
				}
					
				list.push({node_name:node_name,phone_num:phone_num});
			});

			if (exist_folder == true) {
				alert('폴더는 복사할수 없습니다.');
				return false;
			}

			if (list.length == 0)
			{
				p_hide_waiting_message();
				alert('복사할 명단을 체크하세요.');
				return false;
			}

			if (!confirm(list.length + '건의 명단을 [' + node_name + ']폴더로 복사하시겠습니까?'))
			{
				p_hide_waiting_message();
				alert('취소했습니다.');
				return false;
			}

			$.ajax({
				type : "POST"
				, contentType: "application/json; charset=utf-8"
				, url : "./"
				, data : { 
							module : "purplebook"
							, act : "procPurplebookAddList"
							, parent_node : node_id
							, data : JSON.stringify(list)
						 }
				, dataType : "json"
				, success : function (data) {
					p_hide_waiting_message();
					pb_load_list(null, true);
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
	});
}) (jQuery);
