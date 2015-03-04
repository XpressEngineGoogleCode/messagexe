/**
 * @fileoverview folder 공유관련 js
 * @requires layer_share.html
 */

/** @define {boolean} permission을 이용해 코드가 중복되지 않도록 한다. */
if (!share_javascript_permission) {
	var share_javascript_permission = false;
}

(function($) {
	jQuery(function($) {
		/**
		 * js file이 한번만 로딩되도록
		 */
		if (share_javascript_permission == false) {
		   	share_javascript_permission = true;
		} else {
			return;
		}

		/**
		 * 폴더공유
		 */
		jQuery(document).on('click', '#layer_share #btn_append_id', function() {
			var user_id = jQuery('#input_user_id','#layer_share').val();
			var params = new Array();
			params['user_id'] = user_id;
			params['node_id'] = pb_share_folder.node_id;
			var response_tags = new Array('error','message','node_id','member_srl','user_id','nick_name','shared_count');
			exec_xml('purplebook', 'procPurplebookShareNode', params, completeShareNode, response_tags);
		});

		function completeShareNode(ret_obj, response_tags) {
			var node_id = ret_obj['node_id'];
			var member_srl = ret_obj['member_srl'];
			var user_id = ret_obj['user_id'];
			var nick_name = ret_obj['nick_name'];
			var shared_count = ret_obj['shared_count'];
			var list_size = jQuery('li','#layer_share #share_list').size();

			if (ret_obj['error']==-1) {
				alert(ret_obj['message']);
				return false;
			}

			/**
			 * append
			 */
			var $list = jQuery('#share_list','#layer_share');
			$list.append('<li id="sn_' + member_srl + '" node_id="' + node_id + '" member_srl="' + member_srl + '"><span class="user_id">' + user_id + '</span><span class="nick_name">' + nick_name + '</span><span class="delete" title="삭제">삭제</span></li>');

			/**
			 * refresh parent node
			 */
			if (list_size == 0) {
				var node = document.getElementById('node_'+node_id);
				var p = jQuery.jstree._reference(node)._get_parent(node);
				jQuery('#smsPurplebookTree').jstree('refresh',p);
			}
		}

		/**
		 * 폴더공유해제 
		 */
		jQuery(document).on('click', '#layer_share #share_list .delete', function() {
			var node_id = jQuery(this).parent().attr('node_id');
			var member_srl = jQuery(this).parent().attr('member_srl');
			var params = new Array();
			params['node_id'] = node_id;
			params['member_srl'] = member_srl;
			var response_tags = new Array('error','message','member_srl','shared_count');
			exec_xml('purplebook', 'procPurplebookUnshareNode', params, function(ret_obj,response_tags) { completeUnshareNode(node_id,ret_obj,response_tags) }, response_tags);
		});
	});
}) (jQuery);

function completeUnshareNode(node_id, ret_obj, response_tags) {
	var member_srl = ret_obj['member_srl'];
	var shared_count = ret_obj['shared_count'];

	/**
	 * remove
	 */
	jQuery('#sn_'+member_srl,'#layer_share #share_list').remove();

	/**
	 * refresh
	 */
	if (shared_count == 0) {
		var node = document.getElementById('node_'+node_id);
		var p = jQuery.jstree._reference(node)._get_parent(node);
		jQuery('#smsPurplebookTree').jstree('refresh',p);
	}

	alert('공유해제했습니다');
}
