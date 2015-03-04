/**
 * @fileoverview 대량추가
 * @requires layer_mass.html
 */

/** @define {boolean} permission을 이용해 코드가 중복되지 않도록 한다. */
if (!mass_javascript_permission) {
	var mass_javascript_permission = false;
}

(function($) {
	jQuery(function($) {
		/**
		 * js file이 한번만 로딩되도록
		 */
		if (mass_javascript_permission == false) {
		   	mass_javascript_permission = true;
		} else {
			return;
		}

		/**
		 * 비우기
		 */
		$(document).on('click', '#btnEmptyList', function() {
			$('#smsPurplebookBulkList').val('');
			return false;
		});
	});
}) (jQuery);
