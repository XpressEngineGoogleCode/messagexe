/**
 * @fileoverview 저장된 입력내용 (머지기능)
 * @requires layer_messages.html
 */

/** @define {boolean} permission을 이용해 코드가 중복되지 않도록 한다. */
if (!messages_javascript_permission) {
	var messages_javascript_permission = false;
}

(function($) {
	jQuery(function($) {
		/**
		 * js file이 한번만 로딩되도록
		 */
		if (messages_javascript_permission == false) {
		   	messages_javascript_permission = true;
		} else {
			return;
		}

		/**
		 * 해당 메시지 지우기
		 */
		$(document).on('click', '#layer_messages .delete', function() {
			var message_srl = $(this).attr('message_srl');
			var params = new Array();
			params['message_srl'] = message_srl;
			var response_tags = new Array('error','message');
			exec_xml('purplebook', 'procPurplebookDeleteMessage', params, function(ret_obj,response_tags) { pb_load_saved_messages(); }, response_tags);
			return false;
		});

		/**
		 * li 클릭시 해당 내용 screen에 입력
		 */
		$(document).on('click', '#layer_messages li', function() {
			var content = $(this).attr('title');
			$current = get_active_textarea();
			$current.val(content);
			$layer = $('#layer_messages');
			show_and_hide($layer);
		});
	});
}) (jQuery);
