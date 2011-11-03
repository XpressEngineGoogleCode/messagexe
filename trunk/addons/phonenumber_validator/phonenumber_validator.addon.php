<?php
    /**
     * vi:set ts=4 sw=4 expandtab enc=utf8:
     * @file phonenumber_validator.addon.php
     * @author diver (diver@coolsms.co.kr)
     * @brief 핸드폰 번호 인증 애드온
     *
     * MessageXE 핸드폰문자 모듈의 핸드폰인증 기능을 사용한 애드온입니다.
     * 모듈이 설치되어 있어야 합니다.
     **/

    if (!defined('__ZBXE__')) exit();

    /**
     * @brief 핸드폰번호인증 버튼 출력
     *
     * 애드온 작동 액션: dispMemberSignUpForm(회원가입폼 출력)
     * 애드온 작동 시점: before_display_content
     */
    if (in_array(Context::get('act'), array('dispMemberSignUpForm', 'dispMemberModifyInfo')) && $called_position == 'before_display_content') {
        // loading lang-file
        Context::loadLang('./addons/phonenumber_validator/lang');
        $lang = Context::get('lang');

        // set filter
        $filter = 'signup';
        if (Context::get('act') == 'dispMemberSignUpForm') $filter = 'signup';
        if (Context::get('act') == 'dispMemberModifyInfo') $filter = 'modify_info';

        // Class
        require_once($addon_path.'phonenumber_validator.class.php');
        $validator = new PhonenumberValidator($addon_info);

        $current_version = $validator->getXEVerInt();
        $v115 = $validator->getVerInt('1.1.5');

        // default settings.
        if (!$addon_info->hide_checkbox) $addon_info->hide_checkbox = 'Y';
        if (!$addon_info->display_authcode) $addon_info->display_authcode = 'Y';
        if (!$addon_info->mandatory) $addon_info->mandatory = 'Y';

        // Model
        $oMobilemessageModel = &getModel('mobilemessage');
        $config = $oMobilemessageModel->getModuleConfig();

        // for international messaging
        if ($addon_info->global=='Y') {

            // get country code
            require_once($addon_path.'geoip.inc.php');
            $gi = geoip_open($addon_path."GeoIP.dat",GEOIP_STANDARD);
            $geocode = geoip_country_code_by_addr($gi, $_SERVER["REMOTE_ADDR"]);
            geoip_close($gi);

            $country_code = $validator->getCountryByGeocode($geocode);


            Context::addJsFile('./addons/phonenumber_validator/tpl/js/data.js');
            Context::addHtmlHeader("
<script language=javascript>
    (function($) {
        jQuery(function($) {
            // check form  existence
            var fo_obj = $('#fo_insert_member');
            if (!fo_obj.attr('id')) return;

            var country = $('input[name={$config->countrycode_fieldname}]');
            var detected_country = '{$country_code}';
            if (!country.attr('name')) 
                alert('[핸드폰인증 애드온] 제어판 > 부가 기능 설정 > 핸드폰 문자 > 설정 > 국가번호 필드 를 설정해 주세요.');
            else {
                country.parents('tr').css('display', 'none');
                countrycode = country.val();
                if (!countrycode) {
                    country.val(detected_country);
                    countrycode = detected_country;
                }
            }

            // display country
            var flag = document.createElement('select');
            flag.id = 'phoneNumberValidatorFlag';
            flag.onchange = function() { this.form.{$config->countrycode_fieldname}.value = flag.options[flag.options.selectedIndex].value; }
            for (i = 0; i < prefix_data.length; i++) {
                objOpt = document.createElement('option');
                var item = prefix_data[i];
                objOpt.text = item[1] + '(' + item[0] + ')';
                objOpt.value = item[0];
                if (item[0] == countrycode) objOpt.selected = true;
                flag.options.add(objOpt);
            }
            $(flag).css('margin-right', 4);
            var phonenum = $('input[name^={$config->cellphone_fieldname}]:first');
            phonenum.before($(flag));


        });
    }) (jQuery);
</script>
            ");
        } // global = 'Y'


        $url = getSiteUrl().'?module=mobilemessage&act=dispMobilemessageValidation';
        $buttonUrl = getSiteUrl().'addons/phonenumber_validator/tpl/img/button.gif';
        Context::addHtmlHeader("
<script language=javascript>
    (function($) {
        jQuery(function($) {
            // check form  existence
            var fo_obj = $('#fo_insert_member');
            if (!fo_obj.attr('id')) return;

            var phonenum = $('input[name^={$config->cellphone_fieldname}]:last');
            var authcode = $('input[name={$config->validationcode_fieldname}]');

            if (!phonenum.attr('name')) {
                alert('[핸드폰인증 애드온] 제어판 > 부가 기능 설정 > 핸드폰 문자 > 설정 > 폰번호 필드를 설정해 주세요.');
                return;
            }
            if (!authcode.attr('name')) {
                alert('[핸드폰인증 애드온] 제어판 > 부가 기능 설정 > 핸드폰 문자 > 설정 > 인증번호 필드를 설정해 주세요.');
                return;
            }

            // show button
            var button = document.createElement('img');
            button.src = '{$buttonUrl}';
            $(button).attr('align', 'absmiddle');
            $(button).click(function() { popopen('{$url}', 'phonenumber_validation'); return false; });
            $(button).css({'position':'relative', 'top':'-2px', 'margin-left': 2, 'cursor':'pointer'});
            phonenum.after($(button));

            // check fields & submit
            if ('{$addon_info->mandatory}' == 'Y') {
                $('#fo_insert_member').unbind('submit');
                $('#fo_insert_member').submit(function(e) {
                    if (!$('input[name={$config->validationcode_fieldname}]').val().length) {
                        alert('핸드폰인증 버튼을 클릭하여 인증을 받으세요');
                        e.stopImmediatePropagation();
                        return false;
                    }
                    return procFilter(this, {$filter});
                });
            }

            // hide checkbox
            if ('{$addon_info->hide_checkbox}' == 'Y') {
                $('#open_{$config->cellphone_fieldname}').parent().css('display', 'none');
                $('#open_{$config->validationcode_fieldname}').parent().css('display', 'none');
            }

            // display authcode field
            if ('{$addon_info->display_authcode}' == 'N') {
                authcode.parents('tr').css('display', 'none');
            }

            // max length
            var phonenums = $('input[name^={$config->cellphone_fieldname}]');
            phonenums.attr('maxlength', '4');
        });
    }) (jQuery);
</script>
            ");
    }

    /**
     * @brief mobilemessage 모듈의 dispMobilemessageValidation 기능을 옮겨옴.
     *
     * 애드온 작동 액션: dispMobilemessageValidation
     * 애드온 작동 시점: before_display_content
     */
    if (Context::get('act') == 'dispMobilemessageValidation' && $called_position == 'after_module_proc') {
        // 언어파일 로딩
        Context::loadLang('./addons/phonenumber_validator/lang');
        $lang = Context::get('lang');

        // 템플릿 PATH
        $this->setTemplatePath('addons/phonenumber_validator/tpl');

        // Class
        require_once($addon_path.'phonenumber_validator.class.php');
        $validator = new PhonenumberValidator($addon_info);

        // Model
        $oMobilemessageModel = &getModel('mobilemessage');
        $config = $oMobilemessageModel->getModuleConfig();



        // Setup Validation
        if (!$config->cellphone_fieldname)
            Context::set('alert_message', $lang->alert_no_cellphone_fieldname_input);
        if (!$config->validationcode_fieldname)
            Context::set('alert_message', $lang->alert_no_validation_fieldname_input);


        // 핸드폰필드명, 인증번호필드명 지정
        Context::set('countrycode_fieldname', $config->countrycode_fieldname);
        Context::set('phonenumber_fieldname', $config->cellphone_fieldname);
        Context::set('validationcode_fieldname', $config->validationcode_fieldname);

        Context::set('global', $addon_info->global);

        $this->setLayoutPath('addons/phonenumber_validator/tpl');
        $this->setLayoutFile('popup_layout');

        // default country code
        if ($this->addon_info->country_code) 
            $default_country = $this->addon_info->default_country;
        else
            $default_country = $config->default_country;

        // step3
        if (Context::get('validation_code')) {
            $numbers = Context::gets('cellphone_number1', 'cellphone_number2', 'cellphone_number3');
            $country_code = Context::get("country_code");
            if (!$country_code) $country_code = $default_country;
            $args->callno = $numbers->cellphone_number1 . $numbers->cellphone_number2 . $numbers->cellphone_number3;
            $args->country = $country_code;
            $args->valcode = Context::get('validation_code');
            if ($validator->validateValCode($args)) {
                Context::set('cellphone_number', "{$numbers->cellphone_number1}-{$numbers->cellphone_number2}-{$numbers->cellphone_number3}");
                $this->setTemplateFile('phonenumber_validation3');
            } else {
                Context::set('alert_message', $lang->alert_no_validation_code_match);
                $this->setTemplateFile('phonenumber_validation2');
            }
            return;
        }

        // step2
        if (Context::get('cellphone_number1')) {
            // 템플릿 설정
            $this->setTemplateFile('phonenumber_validation2');

            // 폰번호 조합
            $numbers = Context::gets('cellphone_number1', 'cellphone_number2', 'cellphone_number3');
            $args->callno = $numbers->cellphone_number1 . $numbers->cellphone_number2 . $numbers->cellphone_number3;
            $args->country = Context::get('country_code');
            if (!$args->country) $args->country = $default_country;

            // international number
            $international_number = Context::get('country_code') . ($args->callno[0] == '0' ? substr($args->callno, 1) : $args->callno);
            Context::set('international_number', $international_number);

            // 금지번호 체크
            if ($oMobilemessageModel->isProhibitedNumber($args->callno, $args->country)) {
                Context::set('alert_message', $lang->msg_prohibited_number);
                $this->setTemplateFile('phonenumber_validation'); // step1로 돌아감.
                return;
            }

            // 폰번호 중복 방지
            if ($addon_info->unique && $addon_info->unique == 'Y') {
                $userids = $oMobilemessageModel->getUserIDsByPhoneNumber($args->callno, $args->country);
                if ($userids && count($userids) > 0) {
                    $logged_info = Context::get('logged_info');
                    if (!$logged_info || ($logged_info && !in_array($logged_info->user_id, $userids))) {
                        Context::set('alert_message', $lang->msg_already_registered);
                        $this->setTemplateFile('phonenumber_validation'); // step1로 돌아감.
                        return;
                    }
                }
            }

            // 재발송 제한시간 얻어오기
            if (!$addon_info->timeover)
                $timeover = 30;
            else
                $timeover = intval($addon_info->timeover);

            // 재발송 제한시간 검사
            $res = $validator->getValCode($args->callno, $args->country);
            if (isset($res->data->regdate)) {
                if ((time() - ztime($res->data->regdate)) <= $timeover) {
                    Context::set("alert_message", "{$timeover}{$lang->msg_wait_for_resend}");
                    return;
                }
            }

            // check system auth-code
            $authcode = Context::get('authcode');
            if (!$authcode) {
                Context::set('alert_message', '시스템 인증코드가 입력되지 않았습니다.');
                return;
            }
            if ($authcode != $_SESSION['phonenumber_validation_authcode']) {
                Context::set('alert_message', '시스템 인증코드가 일치하지 않습니다.');
                return;
            }

            // 같은 세션에서 5회 이상 전송 금지
            if (!isset($_SESSION['phonenumber_validation_count']))
                $_SESSION['phonenumber_validation_count'] = 1;
            else
                $_SESSION['phonenumber_validation_count'] += 1;

            if ($_SESSION['phonenumber_validation_count'] > 5) {
                Context::set('alert_message', '인증번호가 5회 이상 전송되었습니다. 열려있는 브라우저를 모두 닫은 다음 처음부터 다시 시작하세요.');
                return;
            }

            // 인증번호 발송
            $res = $validator->sendValCode($args);
            if (!$res->toBool()) {
                Context::set('alert_message',  $res->getMessage(). " 창을 닫고 처음부터 다시 시도하세요.");
                return;
            }
        } else {
            // step1
            $authcode = time();
            $_SESSION['phonenumber_validation_authcode'] = $authcode;

            Context::set('authcode', $authcode);
            $this->setTemplateFile('phonenumber_validation');
        }
    }

    /**
     * @brief DB저장 전에 핸드폰번호와 인증번호를 비교대조.
     *
     * 애드온 작동 액션: procMemberInsert(회원가입 DB저장)
     * 애드온 작동 시점: before_module_proc
     */
    if (in_array(Context::get('act'), array('procMemberInsert', 'procMemberModifyInfo')) && $called_position == 'before_module_proc') {
        // 언어파일 로딩
        Context::loadLang('./addons/phonenumber_validator/lang');
        $lang = Context::get('lang');

        // Class
        require_once($addon_path.'phonenumber_validator.class.php');
        $validator = new PhonenumberValidator($addon_info);
        
        $oMobilemessageController = &getController('mobilemessage');
        $oMobilemessageModel = &getModel('mobilemessage');

        // 기본모듈 미설치 오류
        if (!$oMobilemessageController || !$oMobilemessageModel) {
            $this->stop($lang->alert_module_not_installed);
            // 모듈실행을 더이상 진행하지 않도록 한다.
            Context::set('act', '');
            $this->act = '';
            return;
        }

        $config = $oMobilemessageModel->getModuleConfig();

        // 기본모듈 설정 확인
        if (!$config->cellphone_fieldname || !$config->validationcode_fieldname) {
            $this->stop($lang->alert_check_field_names);
            // 모듈실행을 더이상 진행하지 않도록 한다.
            Context::set('act', '');
            $this->act = '';
            return;
        }

        // default country code
        if ($this->addon_info->country_code) 
            $default_country = $this->addon_info->default_country;
        else
            $default_country = $config->default_country;

        // 가입자가 입력한 폰번호 및 인증번호 확보
        $numbers = Context::get($config->cellphone_fieldname);
        if (!is_array($numbers)) $numbers = explode('|@|', $numbers);
        $phonenumber = join($numbers);
        $valcode = Context::get($config->validationcode_fieldname);	
        $country = $default_country;
        if ($config->countrycode_fieldname) $country = Context::get($config->countrycode_fieldname);
        if (!$country) $country = $default_country;

        // 인증번호 확인
        $args = new StdClass();
        $args->callno = $phonenumber;
        $args->valcode = $valcode;
        $args->country = $country;

        /*
        $pass = true;
        if ($addon_info->mandatory == 'Y' || Context::get('act') == 'procMemberModifyInfo') {
            if (!$validator->validateValCode($args)) $pass = false;
        } else {  // mandatory == 'N' && procMemberInsert
            if ($valcode && !$validator->validateValCode($args)) $pass = false;
        }
         */
        $pass = false;
        if (Context::get('act') == 'procMemberInsert' && $addon_info->mandatory == 'N' && !$valcode) {
            $pass = true;
        } else {
            if ($validator->validateValCode($args)) $pass = true;
        }

        if (!$pass) {
            $this->stop($lang->alert_no_match_phone_valcode);
            // 모듈실행을 더이상 진행하지 않도록 한다.
            Context::set('act', '');
            $this->act = '';
            return;
        }

        // 회원정보수정이면 user_id, phone_num도 변경
        if (Context::get('act') == 'procMemberModifyInfo') {
            $logged_info = Context::get('logged_info');
            unset($args);
            $args = new StdClass();
            $args->user_id = $logged_info->user_id;
            $args->phone_num = $phonenumber;
            $args->country = $country;
            $oMobilemessageController->insertMapping($args);
        }

        // 금지번호 체크
        if ($phonenumber && $oMobilemessageModel->isProhibitedNumber($phonenumber, $country)) {
            $this->stop('msg_prohibited_number');
            // 모듈실행을 더이상 진행하지 않도록 한다.
            Context::get('act', '');
            $this->act = '';
            return;
        }

        // 폰번호 중복 방지
        if ($phonenumber && $addon_info->unique && $addon_info->unique == 'Y') {
            $userids = $oMobilemessageModel->getUserIDsByPhoneNumber($phonenumber, $country);
            $is_unique = true;
            if (Context::get('act') == 'procMemberModifyInfo') {
                $logged_info = Context::get('logged_info');
                if ($userids && !in_array($logged_info->user_id, $userids)) $is_unique = false;
            } else {
                if ($userids && count($userids) > 0) $is_unique = false;
            }
            if (!$is_unique) {
                    $this->stop('msg_already_registered');
                    // 모듈실행을 더이상 진행하지 않도록 한다.
                    Context::get('act', '');
                    $this->act = '';
            }
        }
    }
?>
