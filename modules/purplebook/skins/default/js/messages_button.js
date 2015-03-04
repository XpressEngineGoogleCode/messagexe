/**
 * @fileoverview 보내기, 전송결과, 미리보기 등 전송 관련 버튼
 * @requires address.html
 */

(function($) {
	jQuery(function($) {
		/**
		 * 보내기 버튼 클릭시
		 */
		$('#btnSimplePhoneSend').click(function() {
			if (!g_is_logged) {
				alert(getLang('msg_login_required'));
				return false;
			}
			prepare_direct();
			submit_messages();
			return false;
		});

		/**
		 * 발송간격설정
		 */
		$("#message_interval_check").change( function(){
			if ($(this).is(':checked'))
			{
				$("#message_send_limit").attr('readonly', false);
				$("#message_send_interval").attr('readonly', false);

				$("#message_send_limit").css('background', 'white');
				$("#message_send_interval").css('background', 'white');
			}
			else 
			{
				$("#message_send_limit").attr('readonly', true);
				$("#message_send_interval").attr('readonly', true);

				$("#message_send_limit").css('background', '#f0f0f0');
				$("#message_send_interval").css('background', '#f0f0f0');
			}
		});
		
		/**
		 * 현재잔액 set
		 */
		set_balance();

	});
}) (jQuery);

/**
 * 바로보내기시 예약발송 flag를 0 으로
 */
function prepare_direct() {
	reservflag = document.getElementById("smsPurplebookReservFlag");
	reservflag.value = "0";
}

/**
 * 현재잔액 표시
 */
function set_balance() {
	var params = new Array();
	var response_tags = new Array('error','message','cash','point','sms_price','lms_price','mms_price','deferred_payment');
	exec_xml('purplebook', 'getPurplebookCashInfo', params, function (ret_obj){
		cash = parseInt(ret_obj['cash']);
		point = parseInt(ret_obj['point']);

		cash = add_num_comma(cash);
		point = add_num_comma(point);

		jQuery("#pb_balance").html("현재잔액 : " + cash + "(캐쉬) " + point + "(포인트)");

		if (ret_obj['deferred_payment'] == 'Y') {
			jQuery("#pb_balance").html("후불제 회원입니다.");
		} 
	
	}, response_tags);
}
