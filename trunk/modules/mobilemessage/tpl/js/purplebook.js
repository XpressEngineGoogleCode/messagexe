/**
 * vi:set ts=4 sw=4 expandtab enc=utf8: 
 **/

var DDD = new Array("02", "031", "033", "032", "042", "043", "041", "053", "054", "055", "052", "051", "063", "061", "062", "064", "011", "012", "013", "014", "015", "016", "017", "018", "019", "010", "070");

function getDashTel(tel)
{
    tel = tel.replace(/-/g,'');

    if (tel == null || tel.length < 4)
        return tel;

    if (tel.indexOf("-") != -1)
        return tel;

    for (var i = 0; DDD.length > i; i++) 
    {
        if (tel.substring(0, DDD[i].length) == DDD[i] ) 
        {
            if(tel.length < 9)
                return tel.substring(0, DDD[i].length) + "-"+ tel.substring(DDD[i].length, tel.length);
            else
                return tel.substring(0, DDD[i].length) + "-"+ tel.substring(DDD[i].length, tel.length - 4) + "-" + tel.substring(tel.length - 4, tel.length);
        }
    }
    return tel;
}

function getSimpleDashTel(tel)
{
    tel = tel.replace(/-/g,'');
    switch(tel.length)
    {
        case 10:
            initial = tel.substring(0, 3);
            medium = tel.substring(3, 6);
            _final = tel.substring(6, 10);
            break;
        case 11:
            initial = tel.substring(0, 3);
            medium = tel.substring(3, 7);
            _final = tel.substring(7, 11);
            break;
        default:
            return tel;
    }
    return initial + '-' + medium + '-' + _final;
}

(function($) {
    function load_purplebook_list(page)
    {
        var node = $.tree.reference('smsPurplebookTree').selected;
        if (!node)
            return;

        if (page == undefined)
            page = 1;

        node_route = node.attr('node_route') + node.attr('node_id') + '.';
        $('#smsPurplebookSelectedFolderName').text(extract_node_text(node));

        $('#progressModal').modal({
            containerCss: {
                backgroundColor:"#eee"
                , height:40
                , width:200
            }
        });

        $.ajax({
            type : "POST"
            , contentType: "application/json; charset=utf-8"
            , url : "./"
            , data : { 
                        module : "mobilemessage"
                        , act : "dispMobilemessagePurplebookListPaging"
                        , node_route : node_route
                        , node_type : '2'
                        , page : page
                     }
            , dataType : "json"
            , success : function (data) {
                if (data.error == -1)
                {
                   alert(data.message);
                   return -1;
                }
                $('#smsPurplebookList').html('');
                for (i = 0; i < data.purplebook_data.length; i++)
                {
                    node_id = data.purplebook_data[i].attributes.node_id;
                    node_name = data.purplebook_data[i].attributes.node_name;
                    phone_num = data.purplebook_data[i].attributes.phone_num;
                    $('#smsPurplebookList').append('<li node_id="' + node_id + '"><span class="chkPurplebookMembers"></span><span class="nodeName">' + node_name + '</span><span class="nodePhone">' + getSimpleDashTel(phone_num) + '</span></li>');
                }
                $('#smsPurplebookListPages').html('');
                for (var i = 1; i <= data.total_page; i++)
                {
                    if (data.page == i)
                        $('#smsPurplebookListPages').append('<li class="smsPurplebookListPage on">' + i + '</li>');
                    else
                        $('#smsPurplebookListPages').append('<li class="smsPurplebookListPage">' + i + '</li>');
                }
                $('#btnPurplebookExcelDownload').attr('href', data.base_url + '?module=mobilemessage&act=dispMobilemessagePurplebookDownload&node_type=2&node_route=' + node_route);

                if (data.total_page > 1)
                    updatePurplebookListCount(data.total_count);
                else
                    updatePurplebookListCount();
                $.modal.close();
            }
            , error : function (xhttp, textStatus, errorThrown) { 
                $.modal.close();
                alert(errorThrown + " " + textStatus); 
            }
        });
   }

    function create_node(node, ref_node, type, tree_obj)
    {
        _parent = tree_obj.selected;
        var node_id = $(_parent).attr('node_id');
        var node_name = extract_node_text($(node));
        var node_route = $(_parent).attr('node_route');

        if (!node_id)
            node_id = '';
        if (!node_route)
            node_route = '';

        $.ajax({
            type : "POST"
            , contentType: "application/json; charset=utf-8"
            , url : "./"
            , data : { 
                        module : "mobilemessage"
                        , act : "dispMobilemessagePurplebookAddNode"
                        , parent_node : node_id
                        , parent_route : node_route
                        , node_name : node_name
                        , node_type : '1'
                     }
            , dataType : "json"
            , success : function (data) {
                if (data.error == -1)
                {
                   $(node).remove();
                   alert(data.message);
                   return -1;
                }
                $(node).attr('node_id', data.node_id);
                $(node).attr('node_route', data.node_route);
            }
            , error : function (xhttp, textStatus, errorThrown) { 
                alert(errorThrown + " " + textStatus); 
            }
        });
    }

    function rename_node(node, tree_obj)
    {
        var node_id = $(node).attr('node_id');
        var node_name = extract_node_text($(node));

        $.ajax({
            type : "POST"
            , contentType: "application/json; charset=utf-8"
            , url : "./"
            , data : { 
                        module : "mobilemessage"
                        , act : "dispMobilemessagePurplebookRenameNode"
                        , node_id : node_id
                        , node_name : node_name
                     }
            , dataType : "json"
            , success : function (data) {
                if (data.error == -1)
                    alert(data.message);
            }
            , error : function (xhttp, textStatus, errorThrown) { 
                alert(errorThrown + " " + textStatus); 
            }
        });
    }
    
    function delete_node(node, tree_obj)
    {
        var node_id = $(node).attr('node_id');
        var node_route = $(node).attr('node_route');

        $.ajax({
            type : "POST"
            , contentType: "application/json; charset=utf-8"
            , url : "./"
            , data : { 
                        module : "mobilemessage"
                        , act : "dispMobilemessagePurplebookDeleteNode"
                        , node_id : node_id
                        , node_route : node_route
                        , recursive : true
                     }
            , dataType : "json"
            , success : function (data) {
                if (data.error == -1)
                    alert(data.message);
            }
            , error : function (xhttp, textStatus, errorThrown) { 
                alert(errorThrown + " " + textStatus); 
            }
        });

    }

    function assemble_query(node)
    {
        var node_route = $(node).attr('node_route');
        var node_id = $(node).attr('node_id');

        if (!node_route)
            node_route = '';
        if (!node_id)
            node_id = '';

        return 'module=mobilemessage&act=dispMobilemessagePurplebookList&node_type=1&node_route=' + node_route + node_id + '.'; 
    }

    function init_target_tree()
    {
        $("#smsPurplebookTargetTree").tree({
            data : {
                type : 'json'
                , async : true
                , opts : {
                    url : './'
                    , method : 'POST'
                }
            }
            , ui : {
                theme_name : 'classic'
            }
            , plugins : {
                checkbox : { }
                , hotkeys : { }
            }
            , lang : {
                loading : "읽는 중.."
            }
            , callback : {
                beforedata : function(NODE, TREE_OBJ) { return assemble_query(NODE); }
                , ondata : function(DATA, TREE_OBJ) { if (DATA.purplebook_data) { return DATA.purplebook_data; } else { return DATA; } }
            }
        });
    }

    function init_purplebook_tree()
    {
        $("#smsPurplebookTree").tree({
            data : {
                type : 'json'
                , async : true
                , opts : {
                    url : './'
                    , method : 'POST'
                }
            }
            , types : {
                "default" : {
                    draggable : false  
                }
            }
            , ui : {
                theme_name : 'checkbox'
            }
            , plugins : {
                checkbox : { }
                , hotkeys : { }
            }
            , lang : {
                new_node : "새 폴더"
                , loading : "읽는 중.."
            }
            , callback : {
                onselect : function() { load_purplebook_list(); }
                , oncreate : function(NODE, REF_NODE, TYPE, TREE_OBJ, RB) { create_node(NODE, REF_NODE, TYPE, TREE_OBJ);  }
                , onrename : function(NODE, TREE_OBJ, RB) { rename_node(NODE, TREE_OBJ); }
                , ondelete : function(NODE, TREE_OBJ, RB) { delete_node(NODE, TREE_OBJ); }
                , beforedata : function(NODE, TREE_OBJ) { return assemble_query(NODE); }
                , ondata : function(DATA, TREE_OBJ) { if (DATA.purplebook_data) { return DATA.purplebook_data; } else { return DATA; } }
            }
        });
    }

    function extract_node_text(node)
    {
        htmlstr = $(':first', node).html();
        htmlstr = htmlstr.replace(/<INS>&nbsp;<\/INS>/g, '');
        htmlstr = htmlstr.replace(/&nbsp;/g, '');
        return $.trim($('<a>' + htmlstr + '</a>').text());
    }

    function append_address()
    {
            var node = $.tree.reference('smsPurplebookTree').selected;
            var node_name = $('#inputPurplebookName').val();
            var phone_num = $('#inputPurplebookPhone').val();

            if (!node)
            {
                alert('선택된 폴더가 없습니다.');
                return;
            }

            if (node_name.length == 0)
            {
                alert('이름을 입력하세요.');
                $('#inputPurplebookName').focus();
                return;
            }
            if (phone_num.length == 0)
            {
                alert('폰번호를 입력하세요.');
                $('#inputPurplebookPhone').focus();
                return;
            }

            if(!checkPhoneFormat(phone_num)) 
            {
                if (!confirm("유효하지 않은 전화번호입니다 (" + phone_num + ")\n계속 진행하시겠습니까?"))
			    return false;
		    }


            $.ajax({
                type : "POST"
                , contentType: "application/json; charset=utf-8"
                , url : "./"
                , data : { 
                            module : "mobilemessage"
                            , act : "dispMobilemessagePurplebookAddNode"
                            , parent_node : node.attr('node_id')
                            , parent_route : node.attr('node_route')
                            , node_name : node_name
                            , node_type : '2'
                            , phone_num : phone_num
                         }
                , dataType : "json"
                , success : function (data) {
                    if (data.error == -1)
                    {
                        alert(data.message);
                        return;
                    }
                    $('#smsPurplebookList').append('<li node_id="' + data.node_id + '"><span class="chkPurplebookMembers"></span><span class="nodeName">' + node_name + '</span><span class="nodePhone">' + phone_num + '</span></li>');

                    $('#inputPurplebookPhone').val('');
                    $('#inputPurplebookName').val('');
                    $('#inputPurplebookName').focus();
                    updatePurplebookListCount();
                }
                , error : function (xhttp, textStatus, errorThrown) { 
                    alert(errorThrown + " " + textStatus); 
                }
            });
    }

    function updatePurplebookListCount(total_count)
    {
         var total = $('li', '#smsPurplebookList').size();

         if (total_count)
            $('#smsPurplebookListCount').text(' (' + total + ' 명 / 총 ' + total_count + ' 명)');
         else
            $('#smsPurplebookListCount').text(' (' + total + ' 명)');
    }

    jQuery(function($) {
        // sarissa bugfix with MSIE & jquery 1.4.1
        if (typeof Sarissa != 'undefined') {
            jQuery.ajaxSetup({
                xhr: function() {
                        if (Sarissa.originalXMLHttpRequest) {
                                return new 
Sarissa.originalXMLHttpRequest();
                        } else if (typeof ActiveXObject != 
'undefined') {
                                return new 
ActiveXObject("Microsoft.XMLHTTP");
                        } else {
                                return new XMLHttpRequest(); 
                        }
                } 
            });
        }

        init_target_tree();
        init_purplebook_tree();

        $('#btnPurplebookAdd').click(function() {
            var t = $.tree.reference('smsPurplebookTree');
            if (t.selected)
            {
                t.create();
            }
            else
            {
                $.tree.reference('smsPurplebookTree').create({data: "새 폴더"}, -1, "after");
            }
        });

        $('#btnPurplebookRename').click(function() {
            var t = $.tree.reference('smsPurplebookTree');
            if (t.selected)
                t.rename();
            else
                alert('수정할 폴더를 먼저 선택하세요.');
        });

        $('#btnPurplebookDelete').click(function() {
            var t = $.tree.reference('smsPurplebookTree');
            if (t.selected)
            {
                if (confirm('[' + extract_node_text(t.selected) + '] 하위 모든 폴더 및 정보가 삭제됩니다.\n정말 삭제하시겠습니까?'))
                    t.remove();
            }
            else
            {
                alert('삭제할 폴더를 먼저 선택하세요.');
            }
        });

        $('#btnPurplebookDeselect').click(function() {
            var t = $.tree.reference('smsPurplebookTree');
            if (t.selected)
            {
                t.deselect_branch(t.selected);
                $('#smsPurplebookSelectedFolderName').html('&nbsp;');
                $('#smsPurplebookList').html('');
                $('#smsPurplebookListCount').html('');
                $('#smsPurplebookListPages').html('');
            }
        });

        $('#btnAddAddress').click(function() {
            append_address();
        });

        $('#btnPurplebookMemberAdd').click(function() {
            var node = $.tree.reference('smsPurplebookTree').selected;
            if (!node)
            {
                alert('명단을 추가할 폴더를 선택하세요.');
                return false;
            }

            if ($('#smsPurplebookAppendPane').css('display') == 'none')
                $('#smsPurplebookAppendPane').css('display', 'block');
            else
                $('#smsPurplebookAppendPane').css('display', 'none');

            $('#smsPurplebookTargetTreePane').css('display', 'none');
            $('#inputPurplebookName').focus();
        });

        $('#btnPurplebookMemberMove').click(function() {
            $('#paneTitle').text('이동할 폴더를 선택하세요.');
            $('#smsPurplebookTargetTreePaneFooterButtons').html('');
            $('#smsPurplebookTargetTreePaneFooterButtons').append('<button id="btnPurplebookMemberDoMove" class="btnPurplebookMemberDoMove">이동</button>');
            if ($('#smsPurplebookTargetTreePane').css('display') == 'none')
                $('#smsPurplebookTargetTreePane').css('display', 'block');
            else
                $('#smsPurplebookTargetTreePane').css('display', 'none');
        });
        $('#btnPurplebookMemberCopy').click(function() {
            $('#paneTitle').text('복사할 폴더를 선택하세요.');
            $('#smsPurplebookTargetTreePaneFooterButtons').html('');
            $('#smsPurplebookTargetTreePaneFooterButtons').append('<button id="btnPurplebookMemberDoCopy" class="btnPurplebookMemberDoCopy">복사</button>');
            if ($('#smsPurplebookTargetTreePane').css('display') == 'none')
                $('#smsPurplebookTargetTreePane').css('display', 'block');
            else
                $('#smsPurplebookTargetTreePane').css('display', 'none');
        });

        $('#btnPurplebookMemberDoMove').live('click', function() {
            var node = $.tree.reference('smsPurplebookTargetTree').selected;
            if (!node)
            {
                alert('이동할 폴더를 선택하세요.');
                return;
            }
            node_route = node.attr('node_route') + node.attr('node_id') + '.';
            node_name = extract_node_text(node);

            $('#progressModal').modal({
                containerCss: {
                    backgroundColor:"#eee"
                    , height:40
                    , width:200
                }
            });

            var list = new Array();

            $('span.chkPurplebookMembers.on', '.smsPurplebookListPaneBody ul li').each(function() {
                list.push($(this).parent().attr('node_id'));
            });

            if (list.length == 0)
            {
                $.modal.close();
                alert('이동할 명단을 체크하세요.');
                return;
            }

            if (!confirm(list.length + '건의 명단을 [' + node_name + ']폴더로 옮기겠습니까?'))
            {
                $.modal.close();
                alert('취소했습니다.');
                return;
            }

            $.ajax({
                type : "POST"
                , contentType: "application/json; charset=utf-8"
                , url : "./"
                , data : { 
                            module : "mobilemessage"
                            , act : "dispMobilemessagePurplebookUpdateRoute"
                            , json : JSON.stringify(list)
                            , node_route : node_route
                         }
                , dataType : "json"
                , success : function (data) {
                    $.modal.close();
                    load_purplebook_list();
                    if (data.error == -1)
                        alert(data.message);
                }
                , error : function (xhttp, textStatus, errorThrown) { 
                    $.modal.close();
                    alert(errorThrown + " " + textStatus); 
                }
            });
        });

        $('#btnPurplebookMemberDoCopy').live('click', function() {
            var node = $.tree.reference('smsPurplebookTargetTree').selected;
            if (!node)
            {
                alert('복사할 폴더를 선택하세요.');
                return;
            }
            node_route = node.attr('node_route') + node.attr('node_id') + '.';
            node_name = extract_node_text(node);

            $('#progressModal').modal({
                containerCss: {
                    backgroundColor:"#eee"
                    , height:40
                    , width:200
                }
            });

            var list = new Array();

            $('span.chkPurplebookMembers.on', '.smsPurplebookListPaneBody ul li').each(function() {
                list.push($(this).parent().attr('node_id'));
            });

            if (list.length == 0)
            {
                $.modal.close();
                alert('복사할 명단을 체크하세요.');
                return;
            }

            if (!confirm(list.length + '건의 명단을 [' + node_name + ']폴더로 복사하시겠습니까?'))
            {
                $.modal.close();
                alert('취소했습니다.');
                return;
            }

            $.ajax({
                type : "POST"
                , contentType: "application/json; charset=utf-8"
                , url : "./"
                , data : { 
                            module : "mobilemessage"
                            , act : "dispMobilemessagePurplebookCopy"
                            , json : JSON.stringify(list)
                            , node_route : node_route
                         }
                , dataType : "json"
                , success : function (data) {
                    $.modal.close();
                    load_purplebook_list();
                    if (data.error == -1)
                        alert(data.message);
                }
                , error : function (xhttp, textStatus, errorThrown) { 
                    $.modal.close();
                    alert(errorThrown + " " + textStatus); 
                }
            });
        });


        $('#btnPurplebookMemberDelete').live('click', function() {
            $('#progressModal').modal({
                containerCss: {
                    backgroundColor:"#eee"
                    , height:40
                    , width:200
                }
            });

            var list = new Array();

            $('span.chkPurplebookMembers.on', '.smsPurplebookListPaneBody ul li').each(function() {
                list.push($(this).parent().attr('node_id'));
            });

            if (list.length == 0)
            {
                $.modal.close();
                alert('삭제할 명단을 체크하세요.');
                return;
            }

            if (!confirm(list.length + '건의 명단을 삭제하시겠습니까?'))
            {
                $.modal.close();
                alert('취소했습니다.');
                return;
            }

            $.ajax({
                type : "POST"
                , contentType: "application/json; charset=utf-8"
                , url : "./"
                , data : { 
                            module : "mobilemessage"
                            , act : "dispMobilemessagePurplebookDeleteList"
                            , node_list : JSON.stringify(list)
                         }
                , dataType : "json"
                , success : function (data) {
                    $.modal.close();
                    load_purplebook_list();
                    if (data.error == -1)
                        alert(data.message);
                }
                , error : function (xhttp, textStatus, errorThrown) { 
                    $.modal.close();
                    alert(errorThrown + " " + textStatus); 
                }
            });
        });

        $('.smsPurplebookListPaneBody ul li').live('click', function() {
            $('.chkPurplebookMembers', $(this)).toggleClass("on");
        });
        $('#chkPurplebookMembers').toggle(
            function () { 
                $('.chkPurplebookMembers').addClass("on");
                return false;
            },
            function () { 
                $('.chkPurplebookMembers').removeClass("on");
            }
        );
        /*
        $('#chkPurplebookFolders').toggle(
            function() {
                $('.chkPurplebookFolders').addClass('on');
                t = $.tree.reference('smsPurplebookTree');
                console.log(t.get_node(t));
                return false;
            },
            function() {
                $('.chkPurplebookFolders').removeClass('on');
                $.tree.plugins.checkbox.uncheck('smsPurplebookTree');
            }
        );
        */

        $('#btnPurplebookRefresh').click(function() {
            $('#smsPurplebookTree').html('');
            $('#smsPurplebookSelectedFolderName').html('&nbsp;');
            $('#smsPurplebookList').html('');
            $('#smsPurplebookListCount').html('');
            $('#smsPurplebookListPages').html('');

            init_purplebook_tree();
        });

        $('#btnPurplebookMemberRefresh').click(function() {
            $('#smsPurplebookTargetTree').html('');
            init_target_tree();
        });

        $('#btnPurplebookTargetTreePaneClose').click(function() {
            $('#smsPurplebookTargetTreePane').css('display', 'none');
        });

        $('#btnPurplebookAppendPaneClose').click(function() {
            $('#smsPurplebookAppendPane').css('display', 'none');
        });

        $('input').live('keypress', function(event) {
            if (event.keyCode == 13)
                return false;
        });

        $('#inputPurplebookPhone').keyup(function(event) {
            $(this).val(getDashTel($(this).val()));
        });
        $('#inputPurplebookPhone').keypress(function(event) {
            if (event.keyCode == 13)
            {
                append_address();
                return false;
            }
        });

        $('.smsPurplebookListPage').live('click', function() {
            load_purplebook_list($(this).text());
        });
    });
}) (jQuery);
