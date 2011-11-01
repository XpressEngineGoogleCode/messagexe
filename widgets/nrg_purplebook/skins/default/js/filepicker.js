
;(function($) {

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
                .setQuery('module', 'module')
                .setQuery('act', 'dispMobilemessageFilePicker')
                .setQuery('input', this.selected.name)
                .setQuery('filter', filter);

            popopen(url, 'filepicker');
        },

        /**
         * 파일 선택
         */
        selectFile : function(file_url, file_srl){
            alert('purplebook selectFile');
            var target = $(opener.XE.filepicker.selected);
            var target_name = target.attr('name');

            target.val(file_srl);
            var html = _displayMultimedia(file_url, '100%', '100%');
            $('#filepicker_preview_' + target_name, opener.document).html(html).show();
            $('#filepicker_cancel_' + target_name, opener.document).show();
            $('#multi', opener.document).scrollTop(50);
            $('#btnAddPic', opener.document).hide();
            jQuery('#tphoneBtnMMS', opener.document).addClass('on');
            jQuery('#tphoneBtnSMS', opener.document).removeClass('on');

            window.close();
        },

        /**
         * 파일 선택 취소
         */
        cancel : function(name) {
            $('[name=' + name + ']').val('');
            $('#filepicker_preview_' + name).hide().html('');
            /*
            $('#filepicker_cancel_' + name).hide();
            $('#btnAddPic').show();
            */
        },

        /**
         * 파일 삭제
         */
        deleteFile : function(file_srl){
            var params = {
                'file_srl' : file_srl
            };

            $.exec_json('mobilemessage.procMobilemessageFilePickerDelete', params, function() { document.location.reload(); });
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
