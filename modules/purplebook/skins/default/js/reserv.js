/**
 * @fileoverview 예약발송 관련
 * @requires layer_reserv.html
 */

/** @define {boolean} permission을 이용해 코드가 중복되지 않도록 한다. */
if (!reserv_javascript_permission) {
	var reserv_javascript_permission = false;
}

(function($) {
	jQuery(function($) {
		/**
		 * js file이 한번만 로딩되도록
		 */
		if (reserv_javascript_permission == false) {
		   	reserv_javascript_permission = true;
		} else {
			return;
		}
		
		/**
		 * 예약전송
		 */
		$(document).on('click', '#btn_reserv_send', function() {
			if (!g_is_logged) {
				alert(getLang('msg_login_required'));
				return false;
			}
			prepare_reservation();
			submit_messages();
			return false;
		});

		/** @define {object} 날짜 클릭시 달력출력을 위한 option */
		var option = {
			yearRange:'-0:+1'
			,mandatory:true
			,onSelect:function(){
				$("#inputReservationDate").val(this.value);
			}
		};
		$.extend(option,$.datepicker.regional['ko']);
		$("#inputReservationDate").datepicker(option);

	});
}) (jQuery);

/**
 * 예약전송시
 */
function prepare_reservation() {
	reservflag = document.getElementById("smsPurplebookReservFlag");
	reservflag.value = "1";
}
