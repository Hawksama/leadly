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

        if($(".um-editing")[0]) {
            $.each($(".um-row-heading"), function (indexInArray) {
                $(this).click(function() {
                    if($(this).siblings('.dropdown-container')[indexInArray]) {
                        $(this).toggleClass('opened');
                        $($(this).siblings('.dropdown-container')[indexInArray]).slideToggle("fast");
                    }
                });
            });

            $(".dropdown-container").hide();
        }

        $(".um-button-wrapper-icon").on( "click", function(event) {
            $(this).closest( "form" ).submit();
        });


        if($(".um-viewing")[0]) {
            if($(".um-field-phone_number")[0]) {
                $(".um-field-phone_number").on( "click", function(event) {
                    window.location = "tel:" + $(this).find('.um-field-value').text();
                });
            }
        }

        if($(".um-field-url")[0]) {
            if($(".um-profile.um-viewing")[0]) {
                $(".um-field-url").find('.um-field-value').find('a').text('Website');
            }
        }

    });

})(jQuery);