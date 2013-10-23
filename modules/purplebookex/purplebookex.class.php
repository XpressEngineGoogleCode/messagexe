<?php
    /**
     * @class  mobilemessageex
     * @author NHN (developers@xpressengine.com)
     * @brief  mobilemessageex module의 high class
     **/
    require_once(_XE_PATH_.'modules/purplebook/purplebook.class.php');
    class purplebookex extends ModuleObject {

        /**
         * @brief constructor
         **/
        function purplebookex() {
            if(!Context::isInstalled()) return;
        }

        /**
         * @brief 설치시 추가 작업이 필요할시 구현
         **/
        function moduleInstall() {
            $oModuleController = &getController('module');

            // extension
            $oModuleController->insertModuleExtend('purplebook','purplebookex','model','');
            $oModuleController->insertModuleExtend('purplebook','purplebookex','controller','');
            $oModuleController->insertModuleExtend('purplebook','purplebookex','view','');

            return new Object();
        }

        /**
         * @brief 설치가 이상이 없는지 체크하는 method
         **/
        function checkUpdate() {
            $oDB = &DB::getInstance();
            $oModuleModel = &getModel('module');

            // extension
            if (!$oModuleModel->getModuleExtend('purplebook','model','')) return true;
            if (!$oModuleModel->getModuleExtend('purplebook','controller','')) return true;
            if (!$oModuleModel->getModuleExtend('purplebook','view','')) return true;

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
            if (!$oModuleModel->getModuleExtend('purplebook','model','')) $oModuleController->insertModuleExtend('purplebook','purplebookex','model','');
            if (!$oModuleModel->getModuleExtend('purplebook','controller','')) $oModuleController->insertModuleExtend('purplebook','purplebookex','controller','');
            if (!$oModuleModel->getModuleExtend('purplebook','view','')) $oModuleController->insertModuleExtend('purplebook','purplebookex','view','');

            return new Object(0, 'success_updated');
        }

        /**
         * @brief 캐시 파일 재생성
         **/
        function recompileCache() {
        }
    }

