function addReplaceVar(varStr) {
    jQuery('textarea[name=content]').val(jQuery('textarea[name=content]').val() + varStr);
    jQuery('textarea[name=content]').focus();
}
(function($) {
	jQuery(function($) {
		// replace var
        $('.notiReplaceVar').click(function() {
            addReplaceVar('%' + $(this).attr('var') + '%');
            return false;
        });
	});
}) (jQuery);
