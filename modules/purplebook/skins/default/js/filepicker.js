/**
 * @fileoverview image file handling
 */

(function($) {
    var defaults = {
    };

    var filepicker = {
        selected : null,
        /**
         * 파일 박스 창 팝업
         */
        open : function(input_obj, filter) {
            this.selected = input_obj;

            var url = request_uri
                .setQuery('module', 'purplebook')
                .setQuery('act', 'dispPurplebookFilePicker')
                .setQuery('input', this.selected.name)
                .setQuery('filter', filter);

            popopen(url, 'filepicker');
        },

        /**
         * 파일 선택
         */
        selectFile : function(filename, file_url, file_srl){
            var target = $(parent.XE.filepicker.selected);
            var target_name = target.attr('name');

            target.val(filename);
            var html = _displayMultimedia(file_url, '100%', '100%');
            $('#filepicker_preview_' + target_name, parent.document).html(html).show();
            if (parent.filepicker_selected) {
                parent.filepicker_selected();
                window.close();
                return;
            }

            $('#filepicker_cancel_' + target_name, parent.document).show();
            $('#multi', parent.document).scrollTop(50);
            $('#btnAddPic', parent.document).hide();
            jQuery('#tphoneBtnMMS', parent.document).addClass('on');
            jQuery('#tphoneBtnSMS', parent.document).removeClass('on');
            //window.close();
        },

        /**
         * 파일 선택 취소
         */
        cancel : function(name) {
            $('[name=' + name + ']').val('');
            $('#filepicker_preview_' + name).hide().html('');
            $('#filepicker_cancel_' + name).hide();
            $('#btnAddPic').show();
        },

        /**
         * 파일 삭제
         */
        deleteFile : function(file_srl){
            var params = {
                'file_srl' : file_srl
            };

            $.exec_json('purplebook.procPurplebookFilePickerDelete', params, function() { document.location.reload(); });
        },

        /**
         * 초기화
         */
        init : function(name) {
            var file;

            if(opener && opener.selectedWidget && opener.selectedWidget.getAttribute("widget")) {
                file = opener.selectedWidget.getAttribute(name);
            } else if($('[name=' + name + ']').val()) {
                file = $('[name=' + name + ']').val();
            }

            if(file) {
                var html = _displayMultimedia(file, '100%', '100%');
                $('#filepicker_preview_' + name).html(html).show();
                $('#filepicker_cancel_' + name).show();
            }
        }
    };

    // XE에 담기
    $.extend(window.XE, {'filepicker' : filepicker});
}) (jQuery);

/**
 * 파일업로드 창에서 파일 선택시
 */
function filepicker_selected() {
	jQuery('.text_area','#smsMessage').scrollTop(60);
	jQuery('#btn_attach_pic_box').hide();
	jQuery('#btn_delete_pic_box').show();
	jQuery('#mmsSend','#smsMessage').attr('checked','checked');
	update_screen();

	$obj = jQuery('#layer_upload');
	show_and_hide($obj);
}
