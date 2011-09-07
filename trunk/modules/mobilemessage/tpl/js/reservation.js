(function($){
    jQuery(function($) {
        var option = {
            yearRange:'-0:+1'
            ,mandatory:true
            ,onSelect:function(){
                $("#inputReservationDate").val(this.value);
            }
        };

        $.extend(option,$.datepicker.regional['ko']);
        $("#smsReservationInputDate").datepicker(option);
    });
})(jQuery);
