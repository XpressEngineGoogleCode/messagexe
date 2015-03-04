/*
 * @fileoverview address folder list
 * @requires address.html > #smsPurplebook > .left_list
 */

(function($) {
	jQuery(function($) {
		/**
		 * create folder
		 */
		$('#btn_create_folder', '#smsPurplebook').click(function() {
			$('#smsPurplebookTree').jstree('create');
		});

		/**
		 * download for excel
		 */
		$('#btn_excel_download','#smsPurplebook').click(function() {
			var selected_folders = jQuery('#smsPurplebookTree').jstree('get_selected');
			if (selected_folders.length > 0) {
				node = jQuery(selected_folders[0]);
			}
			if (!node) {
				alert('폴더를 선택하세요.');
				return;
			}
			pb_excel_download(node);
		});

		/**
		 * refresh list
		 */
		$('#smsPurplebookRefreshTree').click(function() {
			$('#smsPurplebookTree').html('');
			$('#smsPurplebookList').html('');
			$('#smsPurplebookListCount').html('');

			delete init_purplebook_tree.initial;
			init_purplebook_tree(init_purplebook_tree.img_base);
			return false; // because of a(anchor) tag
		});
	});
}) (jQuery);

/**
 * download for excel function
 * @param {li.Object} object for selected address folder list
 */
function pb_excel_download(obj) {
	var $node = jQuery(obj);
	window.open(current_url.setQuery('module','purplebook').setQuery('act','procPurplebookPurplebookDownload').setQuery('node_type','2').setQuery('node_id', $node.attr('node_id')), '_excel_download');
}
