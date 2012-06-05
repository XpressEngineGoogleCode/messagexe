<?php
    /**
     * vi:set sw=4 ts=4 expandtab enc=utf8:
     * @class  mobilemessageAdminModel
     * @author diver(diver@coolsms.co.kr)
     * @brief  mobilemessageAdminModel
     */
    class mobilemessageAdminModel extends mobilemessage {
        /**
         * @brief 맵핑정보 리스트
         **/
        function getMappingList($args) {
            $args->page = Context::get('page');
            $args->list_count = 40;
            $args->page_count = 10;
            $query_id = 'mobilemessage.getMappingList';
            return executeQueryArray($query_id, $args);
        }

        /**
         * @brief getting prohibitted phone nubmer list.
         **/
        function getProhibitList($args) {
            $args->page = Context::get('page');
            $args->list_count = 40;
            $args->page_count = 10;
            $query_id = 'mobilemessage.getProhibitList';
            return executeQueryArray($query_id, $args);
        }

    }
?>
