/* vi:set ts=4 sw=4 expandtab enc=utf8: */
function replaceAll(str,s,d){
    var i=0;

    while(i > -1)
    {
        i = str.indexOf(s);
        str = str.substr(0,i) + d + str.substr(i+1,str.length);
    }
    return str;
}

//전화번호 포멧 검사
function isValidNumber(str) {
    var reg = new RegExp("^01(0|1|6|7|8|9)(-)?[0-9]{3,4}(-)?[0-9]{4}$")
    return reg.test(str)
}

function check_input_fields()
{
    numb1 = document.getElementById("cellphone_number1");
    numb2 = document.getElementById("cellphone_number2");
    numb3 = document.getElementById("cellphone_number3");

    if (isValidNumber(numb1.value + numb2.value + numb3.value))
        return true;

    alert("핸드폰번호를 올바르게 입력하세요.");
    numb1.focus();
    return false;
}

function check_input_fields2()
{
    valcode = document.getElementById("validation_code");

    if (valcode.value.length == 5)
        return true;

    alert("다섯자리의 인증번호를 올바르게 입력하세요.");
    valcode.focus();
    return false;
}

(function($) {
    jQuery(function($) {
        $('#fo_phonenum_valid').submit(function() {
            if ($('#country_code').val() == '82') {
                return check_input_fields();
            }
        });
    });
}) (jQuery);
