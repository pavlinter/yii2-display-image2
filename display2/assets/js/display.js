(function($){
    $(".display img").on('load', function(){
        var $el = $(this);
        $el.closest(".display").removeAttr('style').find('.display-loading').hide();
    });
})(jQuery);