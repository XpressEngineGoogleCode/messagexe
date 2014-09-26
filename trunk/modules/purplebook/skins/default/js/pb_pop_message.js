jQuery(document).ready(function ($){
	$('<div id="pb_pop_message" tabindex="0" />').appendTo(document.body);
	$("#pb_pop_message").css({
		"position":"absolute",
		"display":"none",
		"z-index":"9999",
	});
});

function call_pb_pop_message(id, text){
	message_location = jQuery(id).offset();

	jQuery("#pb_pop_message").html(text);
	jQuery("#pb_pop_message").css({
		"top":message_location.top + 15,
		"left":message_location.left + 75,
	});
	jQuery("#pb_pop_message").fadeIn("fast");

	setTimeout(function() { jQuery("#pb_pop_message").fadeOut("slow") }, 1000);
}
