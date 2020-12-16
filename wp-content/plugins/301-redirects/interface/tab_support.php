<?php
/**
 * 301 Redirects Pro
 * https://wp301redirects.com/
 * (c) WebFactory Ltd, 2019 - 2021, www.webfactoryltd.com
 */

class WF301_tab_support extends WF301
{
    static function display()
    {
        echo '<div class="wf301-tab-title"><i class="wf301-icon wf301-support"></i> Support</div>';

        if (WF301_admin::get_rebranding()) {
            echo WF301_admin::get_rebranding('support_content');
            return;
        }
        echo '<p class="wf301-tab-description">Something is not working the way it\'s supposed to? Having problems with the license or activating 301 Redirects PRO? Have a look at our detailed <a target="_blank" href="https://wp301redirects.com/documentation/">documentation</a>. If that doesn\'t help <a href="#" class="open-beacon">contact our friendly support</a>, they\'ll respond ASAP!</p>';

        echo '<div class="wf301-tab-box">';
        echo '<i class="wf301-icon wf301-log"></i>';
        echo '<h3>Is there any documentation available?</h3>';
        echo '<p>Sure! Detailed is available on our site. If it doesn\'t help <a href="#" class="open-beacon">contact our friendly support</a>.</p>';
        echo '<p class="small">Quick Reminder: You should avoid creating pages that have very close slug structure like <b>contact</b> and <b>contact2</b> since it is not a good practice and the redirect will go to the first similar one alphabetically.</p>';
        echo '<a class="button button-primary button-gray" href="https://wp301redirects.com/documentation/" target="_blank">Documentation</a>';
        echo '</div>';

        echo '<div class="wf301-tab-box">';
        echo '<i class="wf301-icon wf301-heart"></i>';
        echo '<h3>Please rate the plugin to help us spread the word.</h3>';
        echo '<p>Thank you from 301 Redirects PRO Team!</p>';
        echo '<a class="button button-primary button-gray" href="https://wordpress.org/support/plugin/eps-301-redirects/reviews/#new-post" target="_blank">Rate the plugin</a>';
        echo '</div>';

    } // display
} // class WF301_tab_support
