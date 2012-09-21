/**
 * @brief number display
 **/
(function($){
	$('*[href=#popup_menu_area]').each(function() {
        // 객체의 className값을 구함
		var cls = $(this).attr('class'), match;
		if(cls) match = cls.match(new RegExp('(?:^| )((document|comment|member)_([1-9]\\d*))(?: |$)',''));
		if(!match) return;

		var params = {
			target_srl : match[3]
		};
		var response_tags = 'error message number'.split(' ');

		var _this = $(this);
		_this.parent().append('<span class="cellphoneNumber"><span class="loading">&nbsp;</span></span>');
		exec_xml('authentication', 'getAuthenticationAdminNumber', params, function(ret_obj) { $('.cellphoneNumber',_this.parent()).html(ret_obj['number']); }, response_tags, params);
	});
})(jQuery);
