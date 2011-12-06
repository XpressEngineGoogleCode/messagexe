
function makeList() {
    var fo_obj = xGetElementById("mobilemessage_fo");
    var mobilemessage_srl = new Array();

    if(typeof(fo_obj.cart.length)=='undefined') {
        if(fo_obj.cart.checked) mobilemessage_srl[mobilemessage_srl.length] = fo_obj.cart.value;
    } else {
        var length = fo_obj.cart.length;
        for(var i=0;i<length;i++) {
            if(fo_obj.cart[i].checked) mobilemessage_srl[mobilemessage_srl.length] = fo_obj.cart[i].value;
        }
    }

    return mobilemessage_srl;
}

/* 일괄 취소 */
function cancelMobilemessage() {
    var message_id = makeList();

    if(message_id.length<1) return;

    var url = './?module=textmessage&act=dispTextmessageAdminCancelReserv&message_id='+message_id.join(',');
    winopen(url, 'delete_log','scrollbars=no,width=400,height=500,toolbars=no');
}

/* 일괄 취소 후 */
function completeMobilemessageCancel(ret_obj) {
    alert(ret_obj['message']);
    opener.location.href = opener.current_url;
    window.close();
}

/* 일괄 취소(그룹) */
function cancelGroupMessages() {
    var group_ids = makeList();

    if(group_ids.length<1) return;

    var url = './?module=textmessage&act=dispTextmessageAdminCancelGroup&group_ids='+group_ids.join(',');
    winopen(url, 'delete_log','scrollbars=no,width=400,height=500,toolbars=no');
}

/* 일괄 취소 후(그룹) */
function completeCancelGroupMessages(ret_obj) {
    alert(ret_obj['message']);
    opener.location.href = opener.current_url;
    window.close();
}

