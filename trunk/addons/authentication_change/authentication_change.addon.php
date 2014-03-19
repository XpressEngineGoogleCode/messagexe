<?php
    /**
     * vi:set ts=4 sw=4 expandtab enc=utf8:
     * @file authentication_change.addon.php
     * @author hosy (hosy@nurigo.net)
     * @brief 인증번호 변경 애드온
     *
     * MessageXE 핸드폰문자 모듈의 핸드폰인증 기능을 사용한 애드온입니다.
     * Auhthentication 모듈이 설치되어 있어야 합니다.
     **/

    if (!defined('__XE__')) exit();

    /**
     * @brief 핸드폰번호인증 버튼 출력
     *
     * 애드온 작동 액션: dispMemberSignUpForm(회원가입폼 출력)
     * 애드온 작동 시점: before_display_content
     */
if (in_array(Context::get('act'), array('dispMemberSignUpForm', 'dispMemberModifyInfo')) && $called_position == 'before_display_content' && $_SESSION['authentication_pass'] == 'Y') {

        $oAuthenticationModel = &getModel('authentication');

        $authentication_config = $oAuthenticationModel->getModuleConfig();
        $authentication_info = $oAuthenticationModel->getAuthenticationInfo($_SESSION['authentication_srl']);

        $oMemberModel = &getModel('member');
        $memberConfig = $oMemberModel->getMemberConfig();
        $signupForm = $memberConfig->signupForm;
        
        if($authentication_config->cellphone_fieldname){
            $field_name = $authentication_config->cellphone_fieldname;

            foreach($signupForm as $k => $v)
            {
                if($v->name == $authentication_config->cellphone_fieldname)
                {
                    $field_type = $v->type;
                }
            }

            if($field_type == 'tel'){
                if(strlen($authentication_info->clue) > 10){
                    $phone[0] = substr($authentication_info->clue,0,3);
                    $phone[1] = substr($authentication_info->clue,3,4);
                    $phone[2] = substr($authentication_info->clue,-4,4);
                }else{
                    $phone[0] = substr($authentication_info->clue,0,3);
                    $phone[1] = substr($authentication_info->clue,3,3);
                    $phone[2] = substr($authentication_info->clue,-4,4);
                }
                Context::addHtmlHeader("
                    <script>
                        jQuery(document).ready(function (){
                            var phone_nums = [
                                {num:'{$phone[0]}'},
                                {num:'{$phone[1]}'},
                                {num:'{$phone[2]}'}
                            ];

                            var inp = document.getElementsByName('{$field_name}[]');
                            for(i=0;i<inp.length; i++) {
                                inp[i].value = phone_nums[i].num;
                            }

                            jQuery('input[name=\'{$field_name}[]\']').attr('readonly',true);
                        });
                    </script>
               ");
            }else if($field_type == 'text'){
                Context::addHtmlHeader("
                    <script>
                        jQuery(document).ready(function (){
                            jQuery('#{$field_name}').val('{$authentication_info->clue}');

                            jQuery('#{$field_name}').attr('readonly',true);
                        });
                    </script>
               ");
            }
        }
    }
?>
