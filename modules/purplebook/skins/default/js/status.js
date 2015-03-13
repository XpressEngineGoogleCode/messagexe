/**
 * @fileoverview 전송상태 관련
 * @requires layer_status.html
 */

/** @define {boolean} permission을 이용해 코드가 중복되지 않도록 한다. */
if (!status_javascript_permission) {
	var status_javascript_permission = false;
}

(function($) {
	jQuery(function($) {
		/**
		 * js file이 한번만 로딩되도록
		 */
		if (status_javascript_permission == false) {
		   	status_javascript_permission = true;
		} else {
			return;
		}

		/**
		 * cancel sending
		 */
		$(document).on('click', '#layer_status #btn_stop', function() {
			if (!sendMessageData.send_status && !sendMessageData.send_timer) {
				alert('일시중지 할 수 없습니다');
				return false;
			}

			/**
			 * sendMessageData.send_timer로 보통발송과 발송간격발송 구분
			 */
			if (sendMessageData.send_status == 'complete') {
				alert('이미 접수가 완료됬습니다.');
				return false;
			}
			
			/**
			 * interval send 정지
			 */
			clearInterval(sendMessageData.send_timer);
			sendMessageData.send_timer = false;
			
			/**
			 * send 정지
			 */
			sendMessageData.send_status = 'pause';

			$('.text','#layer_status').text('일시중지하였습니다.');
			return false;
			
		});

		/**
		 * continue sending
		 */
		$(document).on('click', '#layer_status #btn_continue', function() {
			if (sendMessageData.send_status == 'sending') {
				alert('접수중에 있습니다');
				return false;
			}
			if (sendMessageData.send_status == 'complete') {
				alert('이미 접수가 완료됬습니다.');
				return false;
			}

			$('.text','#layer_status').text('전송을 재개하였습니다.');

			/**
			 * 발송간격설정이 체크되있으면 전송간격 시간을 가져온다
			 */
			if (jQuery("#message_interval_check").is(':checked')) {
				g_send_interval = jQuery("#message_send_interval").val() * 1000 * 60;
			}

			sendMessageData.send_status = 'sending'; 

			/**
			 * sendMessageData.send_timer로 보통발송과 발송간격발송 구분
			 */
			if (jQuery("#message_interval_check").is(':checked')) {
				g_send_interval = jQuery("#message_send_interval").val() * 100 * 60;
				sendMessageData.send_timer = setInterval(function() { sendMessageData();  }, g_send_interval);
			} else {
				sendMessageData();
			}
			return false;
		});

		/**
		 * progressbar options
		 */
		jQuery('.progressBar','#layer_status').progressbar({
			value: 0
		}, 2000);
	});
}) (jQuery);
