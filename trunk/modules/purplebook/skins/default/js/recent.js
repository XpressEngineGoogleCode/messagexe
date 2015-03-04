/**
 * @fileoverview 최근보낸 번호목록 관련 js
 * @requires layer_recent.html
 */

/** @define {boolean} permission을 이용해 코드가 중복되지 않도록 한다. */
if (!recent_javascript_permission) {
	var recent_javascript_permission = false;
}

(function($) {
	jQuery(function($) {
		/**
		 * js file이 한번만 로딩되도록
		 */
		if (recent_javascript_permission == false) {
		   	recent_javascript_permission = true;
		} else {
			return;
		}

		/**
		 * 최근목록에서 li 클릭시 받는사람 목록으로 추가
		 */
		$(document).on('click', '#layer_recent li', function() {
			var name = $('.name',$(this)).text();
			var phonenum = $('.phonenum',$(this)).text();
			addNum(phonenum,name);
			updateExceptListCount();
			display_cost();
		});

		/**
		 * delete recent number
		 */
		$(document).on('click', '#layer_recent .delete', function() {
			var receiver_srl = $(this).attr('receiver_srl');
			var params = new Array();
			params['receiver_srl'] = receiver_srl;
			var response_tags = new Array('error','message');
			exec_xml('purplebook', 'procPurplebookDeleteReceiverNumber', params, function(ret_obj,response_tags) { pb_load_recent_numbers(); }, response_tags);
		});
	});
}) (jQuery);
