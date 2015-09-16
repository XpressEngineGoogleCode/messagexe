# Introduction #

TextmessageModule을 사용하면 손쉽게 문자를 발송할 수 있습니다.


# sendMessage #

```
 $args->type = 'SMS' or 'LMS' or 'MMS' // default = 'SMS'
 $args->recipient_no = '수신번호'
 $args->sender_no = '발신번호'
 $args->content = '메시지 내용'
 $args->reservdate = 'YYYYMMDDHHMISS'
 $args->subject = 'LMS제목'
 $args->country_code = '국가번호'
 $args->country_iso_code = '국가ISO코드'
 $args->attachment = 첨부파일
 $args->encode_utf16 = true or false
 sendMessage($args);
```