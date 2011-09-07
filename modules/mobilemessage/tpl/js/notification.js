/**
 * vi:set ts=4 sw=4 expandtab enc=utf8: 
 * MessageXE
 * http://message.xpressengine.net/
 **/

function completeNotiDocAction(ret_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];

    alert(message);

    var url = current_url.setQuery('act','dispMobilemessageAdminNotiDocList');
    location.href = url;
}

function completeNotiComAction(ret_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];

    alert(message);

    var url = current_url.setQuery('act','dispMobilemessageAdminNotiComList');
    location.href = url;
}


function addReplaceVar(varStr) {
    jQuery('textarea[name=content]').val(jQuery('textarea[name=content]').val() + varStr);
    jQuery('textarea[name=content]').focus();
}

(function($) {
    function isValidPhoneNumber(str) {
        var reg = new RegExp("^01(0|1|6|7|8|9)(-)?[0-9]{3,4}(-)?[0-9]{4}$")
        return reg.test(str)
    }


    function check_fields()
    {
        $('#direct_numbers').val('');
        $('#inputDirectNumbers li .phone').each(function() {
            if ($('#direct_numbers').val() == '')
                $('#direct_numbers').val($(this).text());
            else
                $('#direct_numbers').val($('#direct_numbers').val() + '|@|' + $(this).text());
        });
    }

    jQuery(function($) {
        $('#fo_notidoc_append').submit(function() {
            check_fields();
            joinGroupSrls();
            return procFilter(this, notidoc_append);
        });

        $('#fo_noticom_append').submit(function() {
            check_fields();
            joinGroupSrls();
            return procFilter(this, noticom_append);
        });

        $('input:radio[name=callback_number_type]:checked').each(function() {
            if ($(this).val() == 'direct') {
                $('.inputCallbackNumberDirect').css('display', 'inline');
            } else {
                $('.inputCallbackNumberDirect').css('display', 'none');
            }
        });

        // replace var
        $('.notiReplaceVar').click(function() {
            addReplaceVar('%' + $(this).attr('var') + '%');
            return false;
        });


        $('#btnAddPhone').click(function() {
            // assemble phonenum fields.
            var phonenum = $('#admin_phone_1').val() + '-' + $('#admin_phone_2').val() + '-' + $('#admin_phone_3').val();

            if (!isValidPhoneNumber(phonenum))
            {
                alert('번호를 올바르게 입력하세요.');
                $('#admin_phone_1').focus();
                return false;
            }

            // append to phone list.
            $('#inputDirectNumbers').append('<li><span class="phone">' + phonenum + '</span><button class="btnDelPhone">삭제</button></li>');

            // clear phonenum input fields.
            $('#admin_phone_1').val('');
            $('#admin_phone_2').val('');
            $('#admin_phone_3').val('');

            return false;
        });
        $('.btnDelPhone').live('click', function() {
            $(this).parent().remove();
        });

        $('input:radio[name=callback_number_type]').click(function() {
            if ($(this).val() == 'direct') {
                $('.inputCallbackNumberDirect').css('display', 'inline');
                $('input:text[name=callback_number_direct]:first').focus();
            } else {
                $('.inputCallbackNumberDirect').css('display', 'none');
            }
            if ($(this).val() == 'writer') $('#notidocNonmemberIndex').css('display', 'block');
            else $('#notidocNonmemberIndex').css('display', 'none');
        });

        $('#message_link, #reverse_notify').click(function() {
            if ($(this).attr('checked')) {
                if ($(this).attr('id') == 'message_link') {
                    $('#reverse_notify').attr('checked', false);
                } else {
                    $('#message_link').attr('checked', false);
                }
            }
        });
    });
}) (jQuery);
