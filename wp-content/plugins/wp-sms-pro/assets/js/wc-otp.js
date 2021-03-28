jQuery(document).ready(function ($) {
    // Get the current User infos
    var verified = $("input[name=wps_user_verified]");
    var user_meta_filed = $("input[name=wps_user_meta_field]").val();
    var mobile = $("input[name=" + user_meta_filed + "]");
    var intel_input = parseInt(wp_sms_woocommerce_otp.intel_input);
    var intel_class = "";
    if (intel_input === 1) {
        intel_class = " wp-sms-input-mobile-otp";
    }

    if (verified.val() === '1') {
        mobile.attr('readonly', true);
    }

    var dialog = $.dialog({
        columnClass: 'wps-otp-box',
        title: "",
        rtl: parseInt(wp_sms_woocommerce_otp.is_rtl),
        lazyOpen: true,
        content: `
            <div id="wps-otp">
            <div data-otp-alert style="position: relative;height: 50px;display:none;"></div>
            <div class="otp-mobile-verification">
            ` + wp_sms_woocommerce_otp.lang.title + `
            <input type="text" name="wp_sms_otp_number" class="input` + intel_class + `" placeholder="` + wp_sms_woocommerce_otp.lang.number + `" value="` + mobile.val() + `"/>
            <input type="submit" name="otp-submit-number" class="button" value="` + wp_sms_woocommerce_otp.lang.submit + `"/>
            </div>
            <div class="loading_otp">` + wp_sms_woocommerce_otp.lang.wait + `</div>
            `,
        buttons: {},
        closeIcon: true,
    });

    var checkout_form = false;
    $(document).on("click", "[name=woocommerce_checkout_place_order]", function (e) {
        checkout_form = $(this).closest('form');
        var verified = $(checkout_form).find("input[name=wps_user_verified]"),
            mobile_number = $(checkout_form).find("input[name=wps_user_mobile_number]"),
            mobile = $(checkout_form).find("input[name=" + user_meta_filed + "]"),
            whitelist_countries = wp_sms_woocommerce_otp.countries_whitelist;
        
        var billing_country = false;
        var billing_country_select = $(checkout_form).find("select[name=billing_country]");
        var billing_country_input = $(checkout_form).find("input[name=billing_country]");
        if (billing_country_select.length) billing_country = billing_country_select.val();
        if (billing_country_input.length) billing_country = billing_country_input.val();

        if (whitelist_countries) {
            if (!whitelist_countries.includes(billing_country)) {
                return;
            }
        }

        if (verified.val() === '0' || mobile.val() === "" || mobile_number.val() !== mobile.val()) {
            e.preventDefault();

            /* Show Loading Box */
            dialog.open();
            $('input[name="wp_sms_otp_number"]').val(mobile.val());
            var input = document.querySelector(".wp-sms-input-mobile-otp");
            if (input) {
                window.intlTelInput(input, {
                    onlyCountries: wp_sms_intel_tel_input.only_countries,
                    preferredCountries: wp_sms_intel_tel_input.preferred_countries,
                    autoHideDialCode: wp_sms_intel_tel_input.auto_hide,
                    nationalMode: wp_sms_intel_tel_input.national_mode,
                    separateDialCode: wp_sms_intel_tel_input.separate_dial,
                    utilsScript: wp_sms_intel_tel_input.util_js,
                    customContainer: 'intel-otp'
                });
                $(".intel-otp #country-listbox").attr('style', 'position: fixed !important;');
            }

        }
    });

    /* Send User name Login with Mobile Step 1 */
    $(document).on("click", "input[name=otp-submit-number]", function (e) {
        e.preventDefault();

        /* Show Loading */
        $(".loading_otp").show();
        $("[data-otp-alert]").html("");

        var wp_sms_otp_number = $("input[name=wp_sms_otp_number]").val();

        $.ajax({
            url: wp_sms_woocommerce_otp.ajax,
            type: 'GET',
            dataType: "json",
            data: {
                action: 'wp_sms_woocommerce_otp',
                step: 1,
                wp_sms_otp_number: wp_sms_otp_number
            },
            success: function (data) {
                if (data.error === "yes") {
                    $("[data-otp-alert]").html(`<div class="alert-box error"><span>` + wp_sms_woocommerce_otp.lang.error + ` : </span>` + data.text + `</div>`);
                    $("[data-otp-alert]").show();
                } else if (data.error === "yes-limit") {
                    $("[data-otp-alert]").html(`<div class="alert-box error"><span>` + wp_sms_woocommerce_otp.lang.error + ` : </span>` + data.text + `</div>`);
                    $("[data-otp-alert]").show();
                    $(".otp-mobile-verification").html(`
                    <input type="text" name="wp_sms_otp_code" class="input" placeholder="${wp_sms_woocommerce_otp.lang.code}"/>
                    <input type="hidden" name="wp_sms_otp_number" value="` + wp_sms_otp_number + `"/>
                    <input type="submit" name="otp-submit-code" class="button" value="${wp_sms_woocommerce_otp.lang.submit_code}"/>
                    `);
                    $(".otp-mobile-verification").append(`
                    <p class="retry">Didn't receive SMS?</p>
                    <a href="#" name="otp-retry-number">` + wp_sms_woocommerce_otp.lang.retry + `</a>
                    `);
                } else {
                    $("[data-otp-alert]").html(`<div class="alert-box success">` + data.text + `</div>`);
                    $("[data-otp-alert]").show();
                    $(".otp-mobile-verification").html(`
                    <input type="text" name="wp_sms_otp_code" class="input" placeholder="${wp_sms_woocommerce_otp.lang.code}"/>
                    <input type="hidden" name="wp_sms_otp_number" value="` + wp_sms_otp_number + `"/>
                    <input type="submit" name="otp-submit-code" class="button" value="${wp_sms_woocommerce_otp.lang.submit_code}"/>
                    `);
                    $(".otp-mobile-verification").append(`
                    <p class="retry">Didn't receive SMS?</p>
                    <a href="#" name="otp-retry-number">` + wp_sms_woocommerce_otp.lang.retry + `</a>
                    `);
                }
                $(".loading_otp").hide();
            },
            error: function (data) {
                alert(wp_sms_woocommerce_otp.lang.ajax_error);
            }
        });
    });

    /* Check OTP Code step 2 */
    $(document).on("click", "input[name=otp-submit-code]", function (e) {
        e.preventDefault();

        /* Show Loading */
        $(".loading_otp").show();
        $("[data-otp-alert]").html("");

        var verified = $("input[name=wps_user_verified]");
        var wp_sms_otp_number = $("input[name=wp_sms_otp_number]").val();
        var mobile_number = $("input[name=wps_user_mobile_number]");

        $.ajax({
            url: wp_sms_woocommerce_otp.ajax,
            type: 'GET',
            dataType: "json",
            data: {
                action: 'wp_sms_woocommerce_otp',
                step: 2,
                wp_sms_otp_number: wp_sms_otp_number,
                wp_sms_otp_code: $("input[name=wp_sms_otp_code]").val()
            },
            success: function (data) {
                if (data.error === "yes") {
                    $("[data-otp-alert]").html(`<div class="alert-box error"><span>` + wp_sms_woocommerce_otp.lang.error + ` : </span>` + data.text + `</div>`);
                    $("[data-otp-alert]").show();
                } else {
                    $("[data-otp-alert]").html(`<div class="alert-box success">` + data.text + `</div>`);
                    $("[data-otp-alert]").show();
                    mobile.val(wp_sms_otp_number);
                    mobile.attr('readonly', true);
                    verified.val('1');
                    mobile_number.val(wp_sms_otp_number);
                    dialog.close();
                    $(checkout_form).trigger('submit');
                }
                $(".loading_otp").hide();
            },
            error: function () {
                alert(wp_sms_woocommerce_otp.ajax_error);
            }
        });
    });

    /* Retry step 3 - Only resend the SMS */
    $(document).on("click", "a[name=otp-retry-number]", function (e) {
        e.preventDefault();

        /* Show Loading */
        $(".loading_otp").show();
        $("[data-otp-alert]").html("");

        var wp_sms_otp_number = $("input[name=wp_sms_otp_number]").val();


        $.ajax({
            url: wp_sms_woocommerce_otp.ajax,
            type: 'GET',
            dataType: "json",
            data: {
                action: 'wp_sms_woocommerce_otp',
                step: 3,
                wp_sms_otp_number: wp_sms_otp_number
            },
            success: function (data) {
                if (data.error === "yes") {
                    $("[data-otp-alert]").html(`<div class="alert-box error"><span>` + wp_sms_woocommerce_otp.lang.error + ` : </span>` + data.text + `</div>`);
                    $("[data-otp-alert]").show();
                } else {
                    $("[data-otp-alert]").html(`<div class="alert-box success">` + data.text + `</div>`);
                    $("[data-otp-alert]").show();
                }
                $(".loading_otp").hide();
            },
            error: function () {
                alert(wp_sms_woocommerce_otp.ajax_error);
            }
        });
    });


});