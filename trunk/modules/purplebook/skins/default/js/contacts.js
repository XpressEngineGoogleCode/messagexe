/**
 * return selected folder in the folder tree
 */
function get_selected_folder() {
	var selected_folders = jQuery('#smsPurplebookTree').jstree('get_selected');
	if (selected_folders.length != 1) {
		alert('폴더를 한개만 선택하세요.');
		return false;
	}
	var node = jQuery(selected_folders[0]);
	if (!node) {
		alert('폴더를 선택하세요.');
		return false;
	}
	return node;
}

/**
 * initialize advanced menus
 */
function pb_initialize_advanced_menu() {
	/*
	var pos = jQuery('.left_list').position();
	var height = jQuery('.left_list').height();
	var top_str = ''+(pos.top+height)+'px';
	jQuery('.advanced_menu').css({top:top_str});
	*/
	jQuery('#btn_advanced_menu').toggle(function() {
		jQuery('.advanced_menu').show();
	}, function() {
		jQuery('.advanced_menu').hide();
	});
	jQuery('#btn_rename').click(function() {
		jQuery('#smsPurplebookTree').jstree('rename');
		return false;
	});
	jQuery('#btn_delete').click(function() {
		jQuery('#smsPurplebookTree').jstree('remove');
		return false;
	});
	jQuery('#btn_share').click(function() {
		var selected_folder = get_selected_folder();
		if (selected_folder) pb_share_folder(selected_folder);
		return false;
	});
	jQuery('#btn_property').click(function() {
		var selected_folder = get_selected_folder();
		if (selected_folder) pb_view_properties(selected_folder);
		return false;
	});
	jQuery('#btn_cleanup').click(function() {
		clearTrash();
		return false;
	});
}

/**
 * entry point
 */
(function($) {
	jQuery(function($) {
		pb_initialize_advanced_menu();
	});
}) (jQuery);
