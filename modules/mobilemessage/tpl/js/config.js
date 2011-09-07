/* vi:set ts=4 sw=4 expandtab enc=utf8: */
//전화번호 포멧 검사
function isValidPhoneNumber(str) {
    var reg = new RegExp("^01(0|1|6|7|8|9)(-)?[0-9]{3,4}(-)?[0-9]{4}$")
    return reg.test(str)
}

(function($) {
    function check_fields()
    {
        $('#admin_phones').val('');
        $('#inputAdminPhones li .phone').each(function() {
            if ($('#admin_phones').val() == '')
                $('#admin_phones').val($(this).text());
            else
                $('#admin_phones').val($('#admin_phones').val() + '|@|' + $(this).text());
        });
    }

    function detectFields(column_type, keywords, target) {
        $.ajax({
            type: "POST",
            contentType: "application/json; charset=utf-8",
            url: "./",
            data: {module:"mobilemessage", act:"dispMobilemessageDetectFieldName", keywords:keywords, column_type:column_type},
            dataType: "json",
            success:function(data) {
                if (data.field_names.length > 0)
                {
                    var fields='';
                    for (var i = 0; i < data.field_names.length; i++)
                    {
                        $(target).val(data.field_names[i].field_name);
                        fields = fields + data.field_names[i].field_name + '\n';
                    }
                    alert(data.field_names.length + ' 개를 찾았습니다.\n' + fields);
                }
                else
                {
                    alert('자동검색에 실패했습니다.\n가입폼관리에서 필드를 추가하시거나 수동입력하세요.');
                }
            }
            , error : function (xhttp, textStatus, errorThrown) { 
                alert(errorThrown + " " + textStatus); 
            }
        });
    }

    jQuery(function($) {
        $('input:radio[name=callback_number_type]:checked').each(function() {
            if ($(this).val() == 'direct') {
                $('.inputCallbackNumberDirect').css('display', 'inline');
            } else {
                $('.inputCallbackNumberDirect').css('display', 'none');
            }
        });

        $('#fo_config').submit(function() {
            check_fields();
            joinGroupSrls();
            joinChangeGroupSrls();
            return procFilter(this, insert_config);
        });

        $('#btnAutoDetect').click(function() {
            detectFields('tel', 'phone|tel|폰|전화', '#cellphone_fieldname');
            return false;
        });
        $('#btnCleanupPurplebook').click(function() {
            var params = new Array();
            var response_tags = new Array('error','message','update1_count','update2_count','update3_count');
            exec_xml('mobilemessage', 'procMobilemessageAdminUpdateSubnodeCount', params, function(ret_obj) {
                alert('member_srl 매핑 : ' + ret_obj['update1_count'] + "개\n" + 'subnode 카운트 정리 : ' + ret_obj['update2_count'] + "개\n" + 'node_route 정리 : ' + ret_obj['update3_count'] + '개'); 
            }, response_tags);
            return false;
        });

        $('#btnDetectAuthField').click(function() {
            detectFields('text', 'auth|key|code|인증', '#validationcode_fieldname');
            return false;
        });

        $('#btnDetectCountryCodeField').click(function() {
            detectFields('text', 'country|국가', '#countrycode_fieldname');
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
            $('#inputAdminPhones').append('<li><span class="phone">' + phonenum + '</span><button class="btnDelPhone">삭제</button></li>');

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
        });
    });
}) (jQuery);
