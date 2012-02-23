jQuery(function($) {
	$('a.modalAnchor.deleteConfig').bind('before-open.mw', function(event){
		var notification_srl = $(this).attr('data-notification-srl');
		if (!notification_srl) return;

		exec_xml(
			'notification',
			'getNotificationAdminDelete',
			{notification_srl:notification_srl},
			function(ret){
				var tpl = ret.tpl.replace(/<enter>/g, '\n');
				$('#deleteForm').html(tpl);
			},
			['error','message','tpl']
		);

	});
});
