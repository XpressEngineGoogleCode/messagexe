/* vi:set ts=4 sw=4 expandtab enc=utf8: */

function makeList()
{
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

/*
function makeGIDList()
{
    var fo_obj = xGetElementById("mobilemessage_fo");
    var group_ids = new Array();

    if(typeof(fo_obj.cart.length)=='undefined') {
        if(fo_obj.cart.checked) group_ids[group_ids.length] = fo_obj.cart.value;
    } else {
        var length = fo_obj.cart.length;
        for(var i=0;i<length;i++) {
            if(fo_obj.cart[i].checked) group_ids[group_ids.length] = fo_obj.cart[i].value;
        }
    }

    return group_ids;
}
*/

/* 일괄 삭제 */
function deleteMobilemessageFiles() {
    var mobilemessage_file_srls = makeList();

    if(mobilemessage_file_srls.length<1) return;

    var params = new Array();
    params["mobilemessage_file_srls"] = mobilemessage_file_srls;

    var response_tags = new Array("error","message", "page");

    exec_xml("mobilemessage", "procMobilemessageAdminDeleteFiles", params, function() { location.reload(); }, response_tags);
}

/* 일괄 삭제 */
function deleteMobilemessageRecentMessages() {
    var keeping_srls = makeList();

    if(keeping_srls.length<1) return;

    var params = new Array();
    params["keeping_srls"] = keeping_srls;

    var response_tags = new Array("error","message", "page");

    exec_xml("mobilemessage", "procMobilemessageAdminDeleteRecentMessages", params, function() { location.reload(); }, response_tags);
}
/* 일괄 삭제 */
function deleteMobilemessageRecentReceivers() {
    var receiver_srls = makeList();

    if(receiver_srls.length<1) return;

    var params = new Array();
    params["receiver_srls"] = receiver_srls;

    var response_tags = new Array("error","message", "page");

    exec_xml("mobilemessage", "procMobilemessageAdminDeleteRecentReceivers", params, function() { location.reload(); }, response_tags);
}
/* 일괄 삭제 */
function deleteMobilemessageLog() {
    var mobilemessage_srl = makeList();

    if(mobilemessage_srl.length<1) return;

    var url = './?module=mobilemessage&act=dispMobilemessageAdminDeleteLog&mobilemessage_srls='+mobilemessage_srl.join(',');
    winopen(url, 'delete_log','scrollbars=no,width=400,height=500,toolbars=no');
}

/* callback func after inserting a prohibition. */
function completeProhibitInsert(ret_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];
    var page = ret_obj['page'];

    alert(message);

    var url = current_url.setQuery('act','dispMobilemessageAdminProhibit');
    if(page) url = url.setQuery('page', page);
    
    location.href = url;
}


/* 일괄 삭제 후 */
function completeDeleteMobilemessageLog(ret_obj) {
    alert(ret_obj['message']);
    opener.location.href = opener.current_url;
    window.close();
}

/* 일괄 삭제(그룹) */
function deleteGroupMessages() {
    var group_ids = makeList();

    if(group_ids.length<1) return;

    var url = './?module=mobilemessage&act=dispMobilemessageAdminDeleteGroup&group_ids='+group_ids.join(',');
    winopen(url, 'delete_log','scrollbars=no,width=400,height=500,toolbars=no');
}

/* 일괄 삭제 후(그룹) */
function completeDeleteGroupMessages(ret_obj) {
    alert(ret_obj['message']);
    opener.location.href = opener.current_url;
    window.close();
}

/* 일괄 삭제(매핑리스트) */
function deleteMappingList() {
    var user_ids = makeList();

    if(user_ids.length < 1) return;

    var url = './?module=mobilemessage&act=dispMobilemessageAdminDeleteMapping&user_ids=' + user_ids.join(',');
    winopen(url, 'delete_log','scrollbars=no,width=400,height=500,toolbars=no');
}
/* 일괄 삭제 후(매핑리스트) */
function completeDeleteMapping(ret_obj) {
    alert(ret_obj['message']);
    opener.location.href = opener.current_url;
    window.close();
}

/* 일괄 삭제(금지리스트) */
function deleteProhibitList() {
    var phonenums = makeList();

    if(phonenums.length < 1) return;

    var url = './?module=mobilemessage&act=dispMobilemessageAdminProhibitDelete&phonenums=' + phonenums.join(',');
    winopen(url, 'delete_log','scrollbars=no,width=400,height=500,toolbars=no');
}
/* 일괄 삭제 후(금지리스트) */
function completeDeleteProhibit(ret_obj) {
    alert(ret_obj['message']);
    opener.location.href = opener.current_url;
    window.close();
}

/* 일괄 취소 */
function cancelMobilemessage() {
    var mobilemessage_srl = makeList();

    if(mobilemessage_srl.length<1) return;

    var url = './?module=mobilemessage&act=dispMobilemessageAdminCancel&mobilemessage_srls='+mobilemessage_srl.join(',');
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

    var url = './?module=mobilemessage&act=dispMobilemessageAdminCancelGroup&group_ids='+group_ids.join(',');
    winopen(url, 'delete_log','scrollbars=no,width=400,height=500,toolbars=no');
}

/* 일괄 취소 후(그룹) */
function completeCancelGroupMessages(ret_obj) {
    alert(ret_obj['message']);
    opener.location.href = opener.current_url;
    window.close();
}

/* */
function prepare_syncdata() {
    var mobilemessage_srl = makeList();

    if (mobilemessage_srl.length == 0) {
        alert("동기화할 목록을 선택하세요.");
        return false;
    }

    obj = document.getElementById('mobilemessage_srls');
    obj.value = mobilemessage_srl.join(',');

    return true;
}

