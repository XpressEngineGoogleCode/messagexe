<?php
    /**
     * vi:set sw=4 ts=4 expandtab enc=utf8:
     * @class  mobilemessageAPI
     * @author diver(diver@coolsms.co.kr)
     * @brief  mobilemessageAPI
     **/

    class mobilemessageAPI extends mobilemessage {
        function dispMobilemessageGroupList(&$oModule) {
            $oModule->add('group_list', Context::get('group_list'));
        }

        // 그룹에 속하는 멤버정보 가져오기(페이징) - Content-Type: JSON
        function dispMobilemessageMembersInGroupPaging(&$oModule) {
            $oModule->add('total_count', Context::get('total_count'));
            $oModule->add('total_page', Context::get('total_page'));
            $oModule->add('page', Context::get('page'));
            $oModule->add('member_list', Context::get('member_list'));
            $oModule->add('base_url', Context::get('base_url'));
        }

        // 그룹에 속하는 모든 멤버정보 가져오기 - Content-Type: JSON
        function dispMobilemessageMembersInGroup(&$oModule) {
            $oModule->add('member_list', Context::get('member_list'));
            $oModule->add('base_url', Context::get('base_url'));
        }

        // 전화번호 형식 필드명 가져오기 - Content-Type: JSON
        function dispMobilemessageDetectFieldName(&$oModule) {
            $oModule->add('field_names', Context::get('field_names'));
        }

        function dispMobilemessageJSON(&$oModule) {
            $oModule->add('success_count', Context::get('success_count'));
            $oModule->add('failure_count', Context::get('failure_count'));
            $oModule->add('alert_message', Context::get('alert_message'));
            $oModule->add('data', Context::get('return_data'));
        }

        function dispMobilemessageGetCashInfo(&$oModule) {
            $oModule->add('cash', Context::get('cash'));
            $oModule->add('point', Context::get('point'));
            $oModule->add('mdrop', Context::get('mdrop'));
        }

        function dispMobilemessageGetPointInfo(&$oModule) {
            $oModule->add('point', Context::get('point'));
            $oModule->add('msg_not_enough', Context::get('msg_not_enough'));
        }

        function dispMobilemessageMemberMessageGroupList(&$oModule) {
            $oModule->add('total_count', Context::get('total_count'));
            $oModule->add('total_page', Context::get('total_page'));
            $oModule->add('page', Context::get('page'));
            $oModule->add('message_list', Context::get('message_list'));
            $oModule->add('base_url', Context::get('base_url'));
        }
        function dispMobilemessageMemberMessageList(&$oModule) {
            $oModule->add('total_count', Context::get('total_count'));
            $oModule->add('total_page', Context::get('total_page'));
            $oModule->add('page', Context::get('page'));
            $oModule->add('data', Context::get('data'));
            $oModule->add('base_url', Context::get('base_url'));
        }
/*
        function dispMobilemessageMessageInfo(&$oModule) {
            $oModule->add('data', Context::get('data'));
        }
 */
        function dispMobilemessageCancelGroupMessages(&$oModule) {
            $oModule->add('resultcode', '0');
        }
        function dispMobilemessageCancelMessages(&$oModule) {
            $oModule->add('resultcode', '0');
        }
        function dispMobilemessagePurplebookAddNode(&$oModule) {
            $oModule->add('node_id', Context::get('node_id'));
            $oModule->add('node_route', Context::get('node_route'));
        }
        function dispMobilemessagePurplebookRenameNode(&$oModule) {
        }
        function dispMobilemessagePurplebookList(&$oModule) {
            $oModule->add('total_count', Context::get('total_count'));
            if (!is_array(Context::get('data')))
                $oModule->add('purplebook_data', array());
            else
                $oModule->add('purplebook_data', Context::get('data'));
            $oModule->add('base_url', Context::get('base_url'));
        }
        function dispMobilemessagePurplebookListPaging(&$oModule) {
            $oModule->add('total_count', Context::get('total_count'));
            $oModule->add('total_page', Context::get('total_page'));
            $oModule->add('page', Context::get('page'));
            if (!is_array(Context::get('data')))
                $oModule->add('purplebook_data', array());
            else
                $oModule->add('purplebook_data', Context::get('data'));
            $oModule->add('base_url', Context::get('base_url'));
        }
        function dispMobilemessagePurplebookCopy(&$oModule) {
            $oModule->add('resultcode', '0');
        }
        function dispMobilemessagePurplebookRegister(&$oModule) {
            $oModule->add('data', Context::get('return_data'));
        }
        function dispMobilemessageSendAuthCode(&$oModule) {
        }
        function dispMobilemessageSendUserID(&$oModule) {
        }
        function dispMobilemessageChangePassword(&$oModule) {
        }
        function dispMobilemessageLatestNumbers(&$oModule) {
            $oModule->add('latest_numbers', Context::get('latest_numbers'));
        }
        function dispMobilemessageLatestMessages(&$oModule) {
            $oModule->add('latest_messages', Context::get('latest_messages'));
        }
    }
?>
