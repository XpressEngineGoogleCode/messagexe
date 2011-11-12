<?php
	/**
	 * @class  textmessageAdminView
	 * @author NHN (developers@xpressengine.com)
	 * @brief  textmessage view class of textmessage module
	 **/

	class textmessageAdminView extends textmessage {

		var $layout_list;
		var $easyinstallCheckFile = './files/env/easyinstall_last';

		/**
		 * @brief Initilization
		 * @return none
		 **/
		function init() {
			// 템플릿 경로 지정 (board의 경우 tpl에 관리자용 템플릿 모아놓음)
			$template_path = sprintf("%stpl/",$this->module_path);
			$this->setTemplatePath($template_path);
		}

		/**
		 * @brief Display Super Admin Dashboard
		 * @return none
		 **/
		function dispTextmessageAdminIndex() {
			Context::set('isSetupCompleted', false);

			//Retrieve recent news and set them into context
			$newest_news_url = sprintf("http://news.xpressengine.com/%s/news.php?version=%s&package=%s", _XE_LOCATION_, __ZBXE_VERSION__, _XE_PACKAGE_);
			$cache_file = sprintf("%sfiles/cache/newest_news.%s.cache.php", _XE_PATH_, _XE_LOCATION_);
			if(!file_exists($cache_file) || filemtime($cache_file)+ 60*60 < time()) {
				// Considering if data cannot be retrieved due to network problem, modify filemtime to prevent trying to reload again when refreshing textmessageistration page
				// Ensure to access the textmessageistration page even though news cannot be displayed
				FileHandler::writeFile($cache_file,'');
				FileHandler::getRemoteFile($newest_news_url, $cache_file, null, 1, 'GET', 'text/html', array('REQUESTURL'=>getFullUrl('')));
			}

			if(file_exists($cache_file)) {
				$oXml = new XmlParser();
				$buff = $oXml->parse(FileHandler::readFile($cache_file));

				$item = $buff->zbxe_news->item;
				if($item) {
					if(!is_array($item)) $item = array($item);

					foreach($item as $key => $val) {
						$obj = null;
						$obj->title = $val->body;
						$obj->date = $val->attrs->date;
						$obj->url = $val->attrs->url;
						$news[] = $obj;
					}
					Context::set('news', $news);
				}
				Context::set('released_version', $buff->zbxe_news->attrs->released_version);
				Context::set('download_link', $buff->zbxe_news->attrs->download_link);
			}

			$this->setTemplateFile('index');
		}
	}
