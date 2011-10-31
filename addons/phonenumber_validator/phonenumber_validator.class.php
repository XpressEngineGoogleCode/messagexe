<?php
/**
 * vi:set ts=4 sw=4 expandtab enc=utf8:
 * @file phonenumber_validator.class.php
 * @author diver (diver@coolsms.co.kr)
 * @brief class
 **/
class PhonenumberValidator {
    var $addon_info;

    function PhonenumberValidator($addon_info) {
        $this->addon_info = $addon_info;
    }

    function getCountryByGeocode($domain) {
        $DOMAIN_TO_TELECODE = array(
            "AF" => "93"
            , "AL" => "355"
            , "DZ" => "213"
            , "AS" => "1"
            , "AD" => "376"
            , "AO" => "244"
            , "AI" => "1"
            , "AQ" => "672"
            , "AG" => "1"
            , "AR" => "54"
            , "AM" => "374"
            , "AW" => "297"
            , "AU" => "61"
            , "AT" => "43"
            , "AZ" => "994"
            , "BS" => "1"
            , "BH" => "973"
            , "BD" => "880"
            , "BB" => "1"
            , "BY" => "375"
            , "BE" => "32"
            , "BZ" => "501"
            , "BJ" => "229"
            , "BM" => "1"
            , "BT" => "975"
            , "BO" => "591"
            , "BA" => "387"
            , "BW" => "267"
            , "BR" => "55"
            , "IO" => ""
            , "VG" => "1"
            , "BN" => "673"
            , "BG" => "359"
            , "BF" => "226"
            , "MM" => "95"
            , "BI" => "257"
            , "KH" => "855"
            , "CM" => "237"
            , "CA" => "1"
            , "CV" => "238"
            , "KY" => "1"
            , "CF" => "236"
            , "TD" => "235"
            , "CL" => "56"
            , "CN" => "86"
            , "CX" => "61"
            , "CC" => "61"
            , "CO" => "57"
            , "KM" => "269"
            , "CK" => "682"
            , "CR" => "506"
            , "HR" => "385"
            , "CU" => "53"
            , "CY" => "357"
            , "CZ" => "420"
            , "CD" => "243"
            , "DK" => "45"
            , "DJ" => "253"
            , "DM" => "1"
            , "DO" => "1"
            , "EC" => "593"
            , "EG" => "20"
            , "SV" => "503"
            , "GQ" => "240"
            , "ER" => "291"
            , "EE" => "372"
            , "ET" => "251"
            , "FK" => "500"
            , "FO" => "298"
            , "FJ" => "679"
            , "FI" => "358"
            , "FR" => "33"
            , "PF" => "689"
            , "GA" => "241"
            , "GM" => "220"
            , "GE" => "995"
            , "DE" => "49"
            , "GH" => "233"
            , "GI" => "350"
            , "GR" => "30"
            , "GL" => "299"
            , "GD" => "1"
            , "GU" => "1"
            , "GT" => "502"
            , "GN" => "224"
            , "GW" => "245"
            , "GY" => "592"
            , "HT" => "509"
            , "VA" => "39"
            , "HN" => "504"
            , "HK" => "852"
            , "HU" => "36"
            , "IS" => "354"
            , "IN" => "91"
            , "ID" => "62"
            , "IR" => "98"
            , "IQ" => "964"
            , "IE" => "353"
            , "IM" => "44"
            , "IL" => "972"
            , "IT" => "39"
            , "CI" => "225"
            , "JM" => "1"
            , "JP" => "81"
            , "JE" => ""
            , "JO" => "962"
            , "KZ" => "7"
            , "KE" => "254"
            , "KI" => "686"
            , "KW" => "965"
            , "KG" => "996"
            , "LA" => "856"
            , "LV" => "371"
            , "LB" => "961"
            , "LS" => "266"
            , "LR" => "231"
            , "LY" => "218"
            , "LI" => "423"
            , "LT" => "370"
            , "LU" => "352"
            , "MO" => "853"
            , "MK" => "389"
            , "MG" => "261"
            , "MW" => "265"
            , "MY" => "60"
            , "MV" => "960"
            , "ML" => "223"
            , "MT" => "356"
            , "MH" => "692"
            , "MR" => "222"
            , "MU" => "230"
            , "YT" => "262"
            , "MX" => "52"
            , "FM" => "691"
            , "MD" => "373"
            , "MC" => "377"
            , "MN" => "976"
            , "ME" => "382"
            , "MS" => "1"
            , "MA" => "212"
            , "MZ" => "258"
            , "NA" => "264"
            , "NR" => "674"
            , "NP" => "977"
            , "NL" => "31"
            , "AN" => "599"
            , "NC" => "687"
            , "NZ" => "64"
            , "NI" => "505"
            , "NE" => "227"
            , "NG" => "234"
            , "NU" => "683"
            , "KP" => "850"
            , "MP" => "1"
            , "NO" => "47"
            , "OM" => "968"
            , "PK" => "92"
            , "PW" => "680"
            , "PA" => "507"
            , "PG" => "675"
            , "PY" => "595"
            , "PE" => "51"
            , "PH" => "63"
            , "PN" => "870"
            , "PL" => "48"
            , "PT" => "351"
            , "PR" => "1"
            , "QA" => "974"
            , "CG" => "242"
            , "RO" => "40"
            , "RU" => "7"
            , "RW" => "250"
            , "BL" => "590"
            , "SH" => "290"
            , "KN" => "1"
            , "LC" => "1"
            , "MF" => "1"
            , "PM" => "508"
            , "VC" => "1"
            , "WS" => "685"
            , "SM" => "378"
            , "ST" => "239"
            , "SA" => "966"
            , "SN" => "221"
            , "RS" => "381"
            , "SC" => "248"
            , "SL" => "232"
            , "SG" => "65"
            , "SK" => "421"
            , "SI" => "386"
            , "SB" => "677"
            , "SO" => "252"
            , "ZA" => "27"
            , "KR" => "82"
            , "ES" => "34"
            , "LK" => "94"
            , "SD" => "249"
            , "SR" => "597"
            , "SJ" => ""
            , "SZ" => "268"
            , "SE" => "46"
            , "CH" => "41"
            , "SY" => "963"
            , "TW" => "886"
            , "TJ" => "992"
            , "TZ" => "255"
            , "TH" => "66"
            , "TL" => "670"
            , "TG" => "228"
            , "TK" => "690"
            , "TO" => "676"
            , "TT" => "1"
            , "TN" => "216"
            , "TR" => "90"
            , "TM" => "993"
            , "TC" => "1"
            , "TV" => "688"
            , "VI" => "1"
            , "UG" => "256"
            , "UA" => "380"
            , "AE" => "971"
            , "GB" => "44"
            , "US" => "1"
            , "UY" => "598"
            , "UZ" => "998"
            , "VU" => "678"
            , "VE" => "58"
            , "VN" => "84"
            , "WF" => "681"
            , "EH" => ""
            , "YE" => "967"
            , "ZM" => "260"
            , "ZW" => "263"
        );

        return $DOMAIN_TO_TELECODE[$domain];
    }

    /**
     * @brief 인증번호 전송
     */
    function sendValCode($args) {
        $oMobilemessageModel = &getModel('mobilemessage');
        $config = &$oMobilemessageModel->getModuleConfig();
        $phonenumber = $args->callno;
        $country = $args->country;
        $callback = $this->addon_info->callback;

        // default country code
        if ($this->addon_info->country_code) 
            $default_country = $this->addon_info->default_country;
        else
            $default_country = $config->default_country;

        // encode_utf16 flag
        $encode_utf16 = false;
        switch ($this->addon_info->encode_utf16) {
            default:
            case 'A':
                if ($config->encode_utf16=='Y')
                    $encode_utf16 = true;
                else
                    $encode_utf16 = false;
                break;
            case 'Y':
                $encode_utf16 = true;
                break;
            case 'N':
                $encode_utf16 = false;
                break;
        }

        $key = rand(1, 99999);
        $keystr = sprintf("%05d", $key);
        if ($country == $default_country) {
            $msg = $this->addon_info->content;
            if (!$msg) $msg = "[핸드폰인증]\n%validation_code% ☜ 인증번호를 정확히 입력해 주세요.";
        } else {
            $msg = $this->addon_info->content_eng;
            if (!$msg) $msg = "[Authentication Code]\n%validation_code% Please input the five digits correctly.";
        }

        $msg = preg_replace("/%new_line%/", "\n", $msg);
        $msg = preg_replace("/%validation_code%/", $keystr, $msg);

        // delete
        $query_id = 'mobilemessage.deleteValCode';
        unset($args);
        $args = new StdClass();
        $args->callno = $phonenumber;
        $args->country = $country;
        executeQuery($query_id, $args);

        // insert
        $query_id = 'mobilemessage.insertValCode';
        unset($args);
        $args = new StdClass();
        $args->callno = $phonenumber;
        $args->country = $country;
        $args->valcode = $keystr;
        executeQuery($query_id, $args);

        unset($args);
        $args = new StdClass();
        $args->country = $country;
        $args->recipient = $phonenumber;
        if ($callback)
            $args->callback = $callback;
        else
            $args->callback = $config->s_callback;
        $args->message = $msg;
        $args->encode_utf16 = $encode_utf16;

        $controller = &getController('mobilemessage');
        $output = $controller->sendMessage($args);
        if (!$output->toBool())
            return $output;

        return new Object();
    }

    /**
     * @brief 인증번호 인증
     **/
    function validateValCode($args) {

        $oMobilemessageModel = &getModel('mobilemessage');
        $output = $oMobilemessageModel->getValCode($args->callno, $args->country);
        if (!$output->toBool() || !$output->data) return false;

        // comparison
        if ($output->data->valcode == $args->valcode) return true;

        return false;
    }

    /**
     * @brief 인증번호 가져오기
     **/
    function getValCode($callno, $country) {
        $oMobilemessageModel = &getModel('mobilemessage');
        $output = $oMobilemessageModel->getValCode($callno, $country);
        return $output;
    }

    /**
     * @brief 버젼 비교를 위해 major.minor.build 문자열버젼을 Integer로 변환
     **/
    function getVerInt($version_str) {
        $version = split("\.", $version_str);
        $major = intval($version[0]) * 10000;
        $minor = intval($version[1]) * 100;
        $build = intval($version[2]);

        // 1.2.4 버젼의 integer값은 10204가 된다.
        $version_int = $major + $minor + $build;

        return $version_int;
    }

    /**
     * @brief 설치된 XE 버젼 Integer값 구하기
     **/
    function getXEVerInt() {
        return $this->getVerInt(__ZBXE_VERSION__);
    }
}
?>
