<?php
    /**
     * @class  mobilemessageex
     * @author NHN (developers@xpressengine.com)
     * @brief  mobilemessageex module의 high class
     **/
    require_once(_XE_PATH_.'modules/textmessage/textmessage.class.php');
    class textmessagex extends ModuleObject {

        /**
         * @brief constructor
         **/
        function textmessagex() {
            if(!Context::isInstalled()) return;
        }

        /**
         * @brief 설치시 추가 작업이 필요할시 구현
         **/
        function moduleInstall() {
            $oModuleController = &getController('module');

            // extension
            $oModuleController->insertModuleExtend('textmessage','textmessagex','model','');
            $oModuleController->insertModuleExtend('textmessage','textmessagex','controller','');
            $oModuleController->insertModuleExtend('textmessage','textmessagex','view','');

            return new Object();
        }

        /**
         * @brief 설치가 이상이 없는지 체크하는 method
         **/
        function checkUpdate() {
            $oDB = &DB::getInstance();
            $oModuleModel = &getModel('module');

            // extension
            if (!$oModuleModel->getModuleExtend('textmessage','model','')) return true;
            if (!$oModuleModel->getModuleExtend('textmessage','controller','')) return true;
            if (!$oModuleModel->getModuleExtend('textmessage','view','')) return true;

            return false;
        }

        /**
         * @brief 업데이트 실행
         **/
        function moduleUpdate() {
            $oDB = &DB::getInstance();
            $oModuleController = &getController('module');
            $oModuleModel = &getModel('module');

            // extension
            if (!$oModuleModel->getModuleExtend('textmessage','model','')) $oModuleController->insertModuleExtend('textmessage','textmessagex','model','');
            if (!$oModuleModel->getModuleExtend('textmessage','controller','')) $oModuleController->insertModuleExtend('textmessage','textmessagex','controller','');
            if (!$oModuleModel->getModuleExtend('textmessage','view','')) $oModuleController->insertModuleExtend('textmessage','textmessagex','view','');

            return new Object(0, 'success_updated');
        }

        /**
         * @brief 캐시 파일 재생성
         **/
        function recompileCache() {
        }
    }

