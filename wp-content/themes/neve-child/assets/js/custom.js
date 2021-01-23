'use strict';

/*global
    jQuery
 */

/**
 * custom.js
 *
 * Theme enhancements for a better user experience.
 */

(function($){
    function copyToClipboard() {
        var $temp = $("<input>");
        $("body").append($temp);
        $temp.val(window.location.href).select();
        document.execCommand("copy");
        $temp.remove();
    }

    $(document).ready(function(){
        $('#share-link').on( "click", function(event) {
            event.preventDefault();
            
            if(!$(this).hasClass('show-tooltip')) {
                $( '<div class="tooltip">Copied to clipboard</div>' ).appendTo($(this));

                $(this).addClass("show-tooltip");
                setTimeout(function () { 
                    $('.show-tooltip .tooltip').remove();
                    $('.show-tooltip').removeClass("show-tooltip");
                }, 2000);
            }
            
            copyToClipboard();
        });
    });

})(jQuery);