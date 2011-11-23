<?php
	/**
	 * @class  textmessageAdminView
	 * @author wiley (wiley@nurigo.net)
	 * @brief  textmessage view class of textmessage module
	 **/

	class textmessageAdminView extends textmessage {
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
			$oTextmessageModel = &getModel('textmessage');
			$config = $oTextmessageModel->getConfig();
			if (!$config) Context::set('isSetupCompleted', false);
			else Context::set('isSetupCompleted', true);
			Context::set('config',$config);

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

        /**
         * 기본설정
         */
        function dispTextmessageAdminConfig() {
			$oTextmessageModel = &getModel('textmessage');
			$config = $oTextmessageModel->getConfig();

			$callback_url = Context::getDefaultUrl();
			$callback_url_style = "";
			if ($config->callback_url) $callback_url = $config->callback_url;
			else $callback_url_style = 'style="color:red;"';

			Context::set('callback_url', $callback_url);
			Context::set('callback_url_style', $callback_url_style);
			Context::set('config', $config);

            // 템플릿 파일 지정
            $this->setTemplateFile('config');
		}

        function dispTextmessageAdminLogView() {
            $oTextmessageModel = &getModel('textmessage');
            $config = $oTextmessageModel->getModuleConfig();

            if (Context::get('group_id'))
            {
                $args->group_id = Context::get('group_id');
                $output = $oTextmessageModel->getMessagesInGroup($args);
                $this->setTemplateFile('message_list');
            }
            else
            {
                $args = new StdClass();
                $output = $oTextmessageModel->getMessageGroups($args);
                $this->setTemplateFile('message_grouping');
            }


            // 템플릿에 쓰기 위해서 context::set
            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('message_list', $output->data);
            Context::set('page_navigation', $output->page_navigation);

            require_once('textmessage.utility.php');
            $csutil = new CSUtility();
            Context::set('csutil', $csutil);
            Context::set('config', $config);

        }
	}
