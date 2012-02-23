<?php
	/**
	 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
	 * @class  notification
	 * @author diver(diver@coolsms.co.kr)
	 * @brief  notification
	 */
	class notification extends ModuleObject {
		/**
		 * @brief Object를 텍스트의 %...% 와 치환.
		 **/
		function mergeKeywords($text, &$obj) {
			if (!is_object($obj)) return $text;

			foreach ($obj as $key => $val)
			{
				if (is_array($val)) $val = join($val);
				if (is_string($key) && is_string($val)) {
					if (substr($key,0,10)=='extra_vars') $val = str_replace('|@|', '-', $val);
					$text = preg_replace("/%" . preg_quote($key) . "%/", $val, $text);
				}
			}
			return $text;
		}

		function getJSON($name) {
			// 1.1.2 이전 버젼은 무조건 stripslashes 되어 넘어온다.
			// 1.1.2 버젼부터는 get_magic_quotes_gpc에 따라서 On이면 addslashes된 상태이고 Off이면 raw상태로 넘어온다.
			$oModel = &getModel('notification');
			$config = $oModel->getModuleConfig();
			if ($config->force_strip=='Y') {
				$json_string = stripslashes(Context::get($name));
			} else {
				if (get_magic_quotes_gpc()) {
					$json_string = stripslashes(Context::get($name));
				} else {
					$json_string = Context::get($name);
				}
			}

			require_once('JSON.php');
			$json = new Services_JSON();
			$decoded = $json->decode($json_string);

			return $decoded;
		}

		/**
		 * @brief 모듈 설치 실행
		 **/
		function moduleInstall() {
			$oModuleController = &getController('module');
			$oModuleModel = &getModel('module');

			/*
			// Document Registration Trigger
			$oModuleController->insertTrigger('document.insertDocument', 'notification', 'controller', 'triggerInsertDocument', 'after');
			 */

			// Comment Registration Trigger
			$oModuleController->insertTrigger('comment.insertComment', 'notification', 'controller', 'triggerInsertComment', 'after');
		}

		/**
		 * @brief 설치가 이상없는지 체크
		 **/
		function checkUpdate() {
			$oDB = &DB::getInstance();
			$oModuleModel = &getModel('module');
			$oModuleController = &getController('module');

			/*
			// Document Registration Trigger
			if (!$oModuleModel->getTrigger('document.insertDocument', 'notification', 'controller', 'triggerInsertDocument', 'after'))
				return true;
			 */

			// Comment Registration Trigger
			if (!$oModuleModel->getTrigger('comment.insertComment', 'notification', 'controller', 'triggerInsertComment', 'after'))
				return true;

		   return false;
		}

		/**
		 * @brief 업데이트(업그레이드)
		 **/
		function moduleUpdate() {
			$oDB = &DB::getInstance();
			$oModuleModel = &getModel('module');
			$oModuleController = &getController('module');

			/*
			// Document Registration Trigger
			if (!$oModuleModel->getTrigger('document.insertDocument', 'notification', 'controller', 'triggerInsertDocument', 'after'))
				$oModuleController->insertTrigger('document.insertDocument', 'notification', 'controller', 'triggerInsertDocument', 'after');
			 */

			// Comment Registration Trigger
			if (!$oModuleModel->getTrigger('comment.insertComment', 'notification', 'controller', 'triggerInsertComment', 'after'))
				$oModuleController->insertTrigger('comment.insertComment', 'notification', 'controller', 'triggerInsertComment', 'after');
		}

		/**
		 * @brief 캐시파일 재생성
		 **/
		function recompileCache() {
		}
	}
?>
