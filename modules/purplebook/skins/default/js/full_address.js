jQuery(function($) {
	$(document).ready(function()
	{
		$('input, a, img, button','.full_header').filter(function(index) { return !$(this).hasClass('help'); }).tipsy(); // tipsy 다시호출
		$('#tab_add_address').tabs(); 
	});
});

