<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  purplebook
 * @author wiley(wiley@nurigo.net)
 * @brief  purplebook
 */
class purplebook extends ModuleObject
{
    var $version;

    /**
     * @brief contructor
     */
    function purplebook()
    {
        $oModuleModel = &getModel('module');
        $this->module_info = $oModuleModel->getModuleInfoXml($this->module);
        $this->version = $this->module_info->version;
    }

    /**
     * @brief 모듈 설치 실행
     */
    function moduleInstall()
    {
        $oModuleController = &getController('module');
        $oModuleModel = &getModel('module');
    }

    /**
     * @brief 설치가 이상없는지 체크
     */
    function checkUpdate()
    {
        $oDB = &DB::getInstance();
        $oModuleModel = &getModel('module');
        $oModuleController = &getController('module');
        return false;
    }

    /**
     * @brief 업데이트(업그레이드)
     **/
    function moduleUpdate()
    {
        $oDB = &DB::getInstance();
        $oModuleModel = &getModel('module');
        $oModuleController = &getController('module');
    }

    /**
     * @brief 캐시파일 재생성
     **/
    function recompileCache()
    {
    }


    /**
     * @brief Object를 텍스트의 %...% 와 치환.
     */
    function mergeKeywords($text, &$obj)
    {
        if(!is_object($obj)) return $text;

        foreach($obj as $key => $val)
        {
            if(is_array($val)) $val = join($val);
            if(is_string($key) && is_string($val))
            {
                if (substr($key,0,10)=='extra_vars') $val = str_replace('|@|', '-', $val);
                $text = preg_replace("/%" . preg_quote($key) . "%/", $val, $text);
            }
        }
        return $text;
    }

    /**
     * @return post node
     */
    function getPostNode($node_route)
    {
        $route_arr = preg_split('/\./', trim($node_route, '.'));
        $last = count($route_arr) - 1;
        if($last < 0) return;
        return $route_arr[$last];
    }

    function getJSON($name)
    {
        // 1.1.2 버젼부터는 get_magic_quotes_gpc에 따라서 On이면 addslashes된 상태이고 Off이면 raw상태로 넘어온다.
        if(get_magic_quotes_gpc())
        {
            $json_string = stripslashes(Context::get($name));
        }
        else
        {
            $json_string = Context::get($name);
        }
        require_once('JSON.php');
        $json = new Services_JSON();
        $decoded = $json->decode($json_string);

        return $decoded;
    }
}
/* End of file purplebook.class.php */
/* Location: ./modules/purplebook/purplebook.class.php */
