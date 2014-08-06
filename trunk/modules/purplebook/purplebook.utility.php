<?php
    /**
     * vi:set sw=4 ts=4 expandtab enc=utf8:
     * @class  CSUtility
     * @author diver(diver@coolsms.co.kr)
     * @brief  CSUtility
     */
    class CSUtility {
        function CSUtility() {
        }

        function dispStatus($mstat) {
            switch ($mstat) {
                case "9":
                    return "대기중";
                case "1":
                    return "전송중";
                case "2":
                    return "전송완료";
            }
        }
        function dispResultCode($rcode) {
            $statset = array("00" => "정상"
                , "10" => "잘못된 번호"
                , "11" => "상위 서비스망 스팸 인식됨"
                , "12" => "이통사 전송불가"
                , "13" => "필드값 누락"
                , "20" => "등록된 계정이 아니거나 패스워드 틀림"
                , "21" => "존재하지 않는 메시지"
                , "30" => "가능한 전송 잔량이 없음"
                , "31" => "전송할 수 없음"
                , "32" => "미가입자"
                , "40" => "전송시간 초과"
                , "41" => "단말기 Busy"
                , "42" => "음영지역"
                , "43" => "단말기 Power off"
                , "44" => "단말기 메시지 저장갯수 초과"
                , "45" => "단말기 일시 서비스 정지"
                , "46" => "기타 단말기 문제"
                , "47" => "착신 거절"
                , "48" => "Unknown error"
                , "49" => "Format Error"
                , "50" => "SMS서비스 불가 단말기"
                , "51" => "착신측의 호불가 상태"
                , "52" => "이통사 서버 운영자 삭제"
                , "53" => "서버 메시지 Que Full"
                , "54" => "스팸인식"
                , "55" => "스팸, nospam.or.kr에 등록된 번호"
                , "56" => "전송실패(무선망단)"
                , "57" => "전송실패(무선망->단말기단)"
                , "58" => "전송경로 없음"
                , "60" => "취소"
                , "70" => "허용되지 않은 IP 주소"
                , "99" => "대기상태"
            );

            if (isset($statset[$rcode]))
                return $statset[$rcode];

            return "Unknown Code";
        }

        /**
         * @brief 긴내용 잘라서 출력
         * @history 2009/11/05 mb_strcut이 오동작해서 abbreviate로 교체(iconv 변환으로 비효율적).
         */
        function dispContent($content) {
            $content = iconv("utf-8", "euc-kr//TRANSLIT", $content);
            if (strlen($content) > 20) {
                $content = $this->abbreviate($content, 20);
            }
            $content = iconv("euc-kr", "utf-8//TRANSLIT", $content);
            return $content;
        }

        function dispFullnumber($country, $phonenum) {
            if (strlen($phonenum) > 0 && substr($phonenum, 0, 1) == '0') $phonenum = substr($phonenum, 1);
            return $country . $phonenum;
        }

        /**
         * @brief - 기호 붙여서 돌려줌.
         **/
        function getDashTel($phonenum) {
            $phonenum = str_replace('-', '', $phonenum);
            switch (strlen($phonenum)) {
                case 10:
                    $initial = substr($phonenum, 0, 3);
                    $medium = substr($phonenum, 3, 3);
                    $final = substr($phonenum, 6, 4);
                    break;
                case 11:
                    $initial = substr($phonenum, 0, 3);
                    $medium = substr($phonenum, 3, 4);
                    $final = substr($phonenum, 7, 4);
                    break;
                default:
                    return $phonenum;
            }
            return $initial . '-' . $medium . '-' . $final;
        }

        /**
         * @brief 한글 깨짐없이 자르기(완성형 한글만 가능)
         **/
        function cutout($msg, $limit) {
            $msg = substr($msg, 0, $limit);
                if (strlen($msg) < $limit)
                $limit = strlen($msg);

            $countdown = 0;
            for ($i = $limit - 1; $i >= 0; $i--) {	 
                if (ord(substr($msg,$i,1)) < 128) break;
                $countdown++;
            }

            $msg = substr($msg, 0, $limit - ($countdown % 2));

            return $msg;
        }

        /**
         * @brief 한글 텍스트를 축약형으로 만듦.
         * @param[in] msg 문자열
         * @param[in] limit 자를 바이트 수
         **/
        function abbreviate($msg, $limit) {
            if ($limit >= strlen($msg))
                return $msg;
            else
                return $this->cutout($msg, $limit) . "..";
        }

        function strcut_utf8($str, $len, $checkmb=false, $tail='') {
            /**
             * UTF-8 Format
             * 0xxxxxxx = ASCII, 110xxxxx 10xxxxxx or 1110xxxx 10xxxxxx 10xxxxxx
             * 라틴 문자, 그리스 문자, 키릴 문자, 콥트 문자, 아르메니아 문자, 히브리 문자, 아랍 문자 는 2바이트
             * BMP(Basic Mulitilingual Plane) 안에 들어 있는 것은 3바이트(한글, 일본어 포함)
             **/
            preg_match_all('/[\xE0-\xFF][\x80-\xFF]{2}|./', $str, $match); // BMP 대상

            $m = $match[0];
            $slen = strlen($str); // length of source string
            $tlen = strlen($tail); // length of tail string
            $mlen = count($m); // length of matched characters

            if ($slen <= $len) return $str;
            if (!$checkmb && $mlen <= $len) return $str;

            $ret = array();
            $count = 0;
            for ($i=0; $i < $len; $i++) {
                $count += ($checkmb && strlen($m[$i]) > 1)?2:1;
                if ($count + $tlen > $len) break;
                $ret[] = $m[$i];
            }

            return join('', $ret).$tail;
        }

        function strlen_utf8($str, $checkmb = false) {
            preg_match_all('/[\xE0-\xFF][\x80-\xFF]{2}|./', $str, $match); // BMP 대상

            $m = $match[0];
            $mlen = count($m); // length of matched characters

            if (!$checkmb) return $mlen;

            $count=0;
            for ($i=0; $i < $mlen; $i++) {
                $count += ($checkmb && strlen($m[$i]) > 1)?2:1;
            }

            return $count;
        }

        // 국가코드 체크 
        function checkCountryCode($country_code)
        {
            if(!$country_code) return -1;

            $country_codes = array(
                "233" // 가나
                , "241" // 가봉
                , "220" // 감비아
                , "264" // 나미비아

                , "234" // 나이지리아
                , "27" // 남아공화국
                , "227" // 니제르
                , "231" // 라이베리아
                , "266" // 레소토
                , "250" // 르완다

                , "261" // 마다가스카르
                , "265" // 말라위
                , "223" // 말리
                , "212" // 모로코
                , "230" // 모리셔스
                , "222" // 모리타니

                , "258" // 모잠비크
                , "267" // 보츠와나
                , "221" // 세네갈
                , "248" // 세이셸
                , "249" // 수단
                , "268" // 스와질란드

                , "232" // 시에라리온
                , "244" // 앙골라
                , "251" // 에티오피아
                , "256" // 우간다
                , "20" // 이집트
                , "260" // 잠비아

                , "263" // 짐바브웨
                , "237" // 카메룬
                , "254" // 케냐
                , "242" // 콩고
                , "255" // 탄자니아
                , "228" // 토고

                , "216" // 튀니지
                , "977" // 네팔
                , "64" // 뉴질랜드
                , "886" // 대만
                , "82" // 대한민국
                , "1809" // 도미니카 공화국

                , "856" // 라오스
                , "596" // 마르티니크
                , "853" // 마카오
                , "60" // 말레이시아
                , "960" // 몰디브
                , "976" // 몽고

                , "95" // 미얀마
                , "678" // 바누아투
                , "880" // 방글라데시
                , "84" // 베트남
                , "975" // 부탄
                , "673" // 브루나이

                , "1670" // 사이판
                , "94" // 스리랑카
                , "65" // 싱가포르
                , "93" // 아프가니스탄
                , "91" // 인도
                , "62" // 인도네시아

                , "81" // 일본
                , "86" // 중국
                , "855" // 캄보디아
                , "269" // 코모로
                , "66" // 태국
                , "993" // 투르크메니스탄

                , "92" // 파키스탄
                , "679" // 피지
                , "63" // 필리핀
                , "852" // 홍콩
                , "1473" // 그레나다
                , "52" // 멕시코

                , "1246" // 바베이도스
                , "1441" // 버뮤다
                , "58" // 베네수엘라
                , "501" // 벨리즈
                , "591" // 볼리비아
                , "55" // 브라질

                , "1758" // 세인트루시아
                , "1784" // 세인트빈센트 그레나딘
                , "297" // 아루바
                , "54" // 아르헨티나
                , "1268" // 앤티가 바부다
                , "503" // 엘살바도르

                , "1876" // 자메이카
                , "56" // 칠레
                , "1345" // 케이맨 제도 
                , "57" // 콜롬비아
                , "53" // 쿠바
                , "1868" // 트리니다드토바고

                , "507" // 파나마
                , "595" // 파라과이
                , "51" // 페루
                , "1" // 푸에르토리코
                , "995" // 그루지아
                , "30" // 그리스

                , "299" // 그린랜드
                , "31" // 네덜란드
                , "599" // 네덜란드령 안틸레스
                , "47" // 노르웨이
                , "45" // 덴마크
                , "49" // 독일

                , "371" // 라트비아
                , "7" // 러시아
                , "262" // 레위니옹
                , "40" // 루마니아
                , "352" // 룩셈부르크
                , "218" // 리비아

                , "370" // 리투아니아
                , "423" // 리히텐슈타인
                , "389" // 마케도니아 공화국
                , "377" // 모나코2
                , "373" // 몰도바
                , "32" // 벨기에

                , "375" // 벨라루스
                , "387" // 보스니아 헤르체고비나
                , "257" // 부룬디
                , "226" // 부르키나파소
                , "359" // 불가리아
                , "39" // 산마리노

                , "381" // 세르비아 몬테그로
                , "46" // 스웨덴
                , "41" // 스위스
                , "34" // 스페인
                , "421" // 슬로바키아
                , "386" // 슬로베니아

                , "374" // 아르메니아
                , "354" // 아이슬란드
                , "353" // 아일랜드
                , "376" // 안도라
                , "355" // 알바니아
                , "213" // 알제리

                , "372" // 에스토니아
                , "44" // 영국
                , "43" // 오스트리아
                , "380" // 우크라이나
                , "39" // 이탈리아
                , "350" // 지브롤터

                , "420" // 체코
                , "385" // 크로아티아
                , "90" // 터키
                , "298" // 페로 제도
                , "351" // 포르투갈
                , "48" // 폴란드

                , "33" // 프랑스
                , "594" // 프랑스령기아나 
                , "689" // 프랑스령폴리네시아
                , "358" // 핀란드
                , "36" // 헝가리
                , "961" // 레바논

                , "973" // 바레인
                , "966" // 사우디아라비아
                , "963" // 시리아
                , "971" // 아랍에미리트연합국
                , "994" // 아제르바이잔
                , "967" // 예맨

                , "968" // 오만
                , "962" // 요르단
                , "964" // 이라크
                , "98" // 이란
                , "972" // 이스라엘
                , "377" // 코소보

                , "965" // 쿠웨이트
                , "970" // 팔레스타인
                , "1" // 미국
                , "509" // 아이티
                , "1" // 캐나다
                , "590" // 과들루프

                , "502" // 과테말라
                , "1671" // 괌
                , "245" // 기니
                , "687" // 뉴칼레도니
                , "505" // 니카라과
                , "691" // 마이크로네시아

                , "356" // 말타
                , "229" // 베닌
                , "597" // 수리남
                , "225" // 아이보리코스트
                , "593" // 에콰도르
                , "1809" // 영국령 버진 제도

                , "61" // 오스트레일리아
                , "504" // 온두라스
                , "598" // 우루과이
                , "998" // 우즈베키스탄
                , "236" // 중앙아프리카공화국
                , "253" // 지부티

                , "235" // 차드
                , "374" // 나고르노카라바흐
                , "238" // 카보베르데
                , "7" // 카자흐스탄
                , "1345" // 케이맨 제도
                , "506" // 코스타리카

                , "243" // 콩고민주공화국
                , "974" // 카타르
                , "996" // 키르기즈스탄
                , "686" // 키리바시
                , "992" // 타지키스탄
                , "676" // 통가

                , "993" // 투르크메니스탄
                , "670" // 모르
                , "689" // 프랑스령 폴리네시아
            );

            if(in_array($country_code, $country_codes)) 
            {
                return $country_code;
            }
            return -1;
        } 

    }
?>
