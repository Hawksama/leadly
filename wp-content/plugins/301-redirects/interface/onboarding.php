<?php
/**
 * 301 Redirects Pro
 * https://wp301redirects.com/
 * (c) WebFactory Ltd, 2019 - 2021, www.webfactoryltd.com
 */

class WF301_onboarding extends WF301
{
    static function display()
    {
        global $wpdb, $wp_rewrite;
        echo '<div id="wf301-onboarding-tabs-wrapper" style="display:none;">';

        echo '<div class="wrap">
            <h1 class="wf301-logo">';
        if (WF301_admin::get_rebranding('logo_url') !== false) {
            echo '<img src="' . WF301_admin::get_rebranding('logo_url') . '" />';
        } else {
            echo '<img src="' . WF301_PLUGIN_URL . '/images/wf301_logo.png" alt="301 Redirects PRO" height="60" title="301 Redirects PRO">';
        }
        echo '</h1>';

        echo '<h2 align="center">Setup Wizard</h2>';

        echo '<form method="post" action="options.php" enctype="multipart/form-data" id="wf301_form">';
        settings_fields(WF301_OPTIONS_KEY);
        echo '<div id="wf301-onboarding-tabs" class="ui-tabs">';

        echo '<ul class="wf301-onboarding-tab">';
        echo '<li><a href="#wf301_onboarding_step1"><span class="wf301-onboarding-step-number">1</span><span class="label">Welcome</span></a></li>';
        echo '<li><a href="#wf301_onboarding_step2"><span class="wf301-onboarding-step-number">2</span><span class="label">Settings</span></a></li>';
        echo '<li><a href="#wf301_onboarding_step3"><span class="wf301-onboarding-step-number">3</span><span class="label">Logging</span></a></li>';
        echo '<li><a href="#wf301_onboarding_step4"><span class="wf301-onboarding-step-number">4</span><span class="label">Finish</span></a></li>';
        echo '<li class="wf301-icon wf301-onboarding-step-link"></li>';
        echo '</ul>';

        echo '<div style="display: none;" id="wf301_onboarding_step1" data-tab="0">';

        echo '<table class="form-table wf301-onboarding-checks"><tbody>';
        echo '<tr valign="top"><td scope="row"><i class="fas fa-check fa-2x"></i></td><td>WP Version ' . get_bloginfo('version') . '</td></tr>';
        echo '<tr valign="top"><td scope="row"><i class="fas fa-check fa-2x"></i></td><td>PHP Version ' . phpversion() . '</td></tr>';

        if (
            $wpdb->get_var('SHOW TABLES LIKE "' . $wpdb->wf301_404_logs . '"') == $wpdb->wf301_404_logs &&
            $wpdb->get_var('SHOW TABLES LIKE "' . $wpdb->wf301_redirect_rules . '"') == $wpdb->wf301_redirect_rules &&
            $wpdb->get_var('SHOW TABLES LIKE "' . $wpdb->wf301_redirect_logs . '"') == $wpdb->wf301_redirect_logs
        ) {
            echo '<tr valign="top"><td scope="row"><i class="fas fa-check fa-2x"></i></td><td>WF 301 Tables</td></tr>';
        } else {
            echo '<tr valign="top"><td scope="row"><i class="fas fa-times fa-2x"></i></td><td>WF 301 Tables not found<br /><div class="wf301_recreate_tables button button-primary" data-msg-wait="Please wait" data-btn-confirm="Recreate tables" data-text="This will delete and recreate the tables used by 301 Redirects Pro. All redirect rules and logs will be deleted but no settings will be changed. This action is not reversible!" data-title="Recreate/reset 301 Redirects Pro tables" data-msg-success="Tables successfully recreated" href="' . add_query_arg(array('action' => 'wf301_recreate_tables'), admin_url('admin.php')) . '"><i class="fas fa-exclamation-triangle"></i> Recreate/Reset 301 Redirect Pro Table</div></td></tr>';
        }

        if (!isset($wp_rewrite->permalink_structure) || empty($wp_rewrite->permalink_structure)) {
            echo '<tr valign="top"><td scope="row"><i class="fas fa-times fa-2x"></i></td><td>301 Redirects Pro requires that a permalink structure is set to a value other than "plain". Please update the <a href="' . admin_url('options-permalink.php') . '" title="Permalinks">Permalink Structure</a></td></tr>';
        } else {
            echo '<tr valign="top"><td scope="row"><i class="fas fa-check fa-2x"></i></td><td>Permalinks enabled</td></tr>';
        }


        echo '<tr valign="top"><td scope="row"></td><td></td></tr>';
        echo '</table>';

        echo '<div class="wf301-onboarding-tabs-nav">';
        echo '<div class="button button-secondary wf301-onboarding-tab-skip">Skip</div>';
        echo '<div class="button button-primary wf301-onboarding-tab-next">Next</div>';
        echo '</div>';
        echo '</div>';

        echo '<div style="display: none;" id="wf301_onboarding_step2" data-tab="1">';
        $options = WF301_setup::get_options();

        echo '<table class="form-table"><tbody>';

        echo '<tr valign="top">
                                <th scope="row"><label for="ob_autoredirect_404">Autoredirect 404</label></th>
                                <td>';
        WF301_utility::create_toogle_switch('ob_autoredirect_404', array('saved_value' => $options['autoredirect_404'], 'option_key' => WF301_OPTIONS_KEY . '[autoredirect_404]'));
        echo '<br /><span>Autoredirect visitors if a page with a similar slug exists. For example, if it would have resulted in a 404 not found error, "/sample-pag" will be redirected to "/sample-page"</span>';
        echo '</td></tr>';

        echo '<tr valign="top">
                        <th scope="row"><label for="ob_page_404">404 Page</label></th>
                        <td><p id="page_404_dummy">';
        $dropdown_pages = wp_dropdown_pages(array(
            'id'   => 'ob_page_404',
            'name' => '' . WF301_OPTIONS_KEY . '[page_404]',
            'echo' => 0,
            'show_option_none' => 'Default 404.php file',
            'option_none_value' => '0',
            'selected' => $options['page_404']
        ));
        if ($dropdown_pages) {
            echo $dropdown_pages;
        } else {
            echo '<select id="ob_page_404" name="' . WF301_OPTIONS_KEY . '[page_404]"><option value="0">Default 404.php file</option></select>';
        }
        echo ' or <a href="post-new.php?post_type=page" title="Add a new page">add a new page</a><br />';
        echo 'The 404 "document not found" page for your site. It has to be published and public.</p></td></tr>';
        echo '</tbody></table>';
        echo '<div class="wf301-onboarding-tabs-nav">';
        echo '<div class="button button-secondary wf301-onboarding-tab-skip">Skip</div>';
        echo '<div class="button button-primary wf301-onboarding-tab-previous">Previous</div>';
        echo '<div class="button button-primary wf301-onboarding-tab-next">Next</div>';
        echo '</div>';
        echo '</div>';

        echo '<div style="display: none;" id="wf301_onboarding_step3" data-tab="2">';
        echo '<table class="form-table"><tbody>';

        $retention_settings =   array();
        $retention_settings[] = array('val' => 'cnt-100', 'label' => 'Keep a maximum of 100 logged events');
        $retention_settings[] = array('val' => 'cnt-200', 'label' => 'Keep a maximum of 200 logged events');
        $retention_settings[] = array('val' => 'cnt-500', 'label' => 'Keep a maximum of 500 logged events');
        $retention_settings[] = array('val' => 'cnt-1000', 'label' => 'Keep a maximum of 1,000 logged events');
        $retention_settings[] = array('val' => 'cnt-5000', 'label' => 'Keep a maximum of 5,000 logged events');
        $retention_settings[] = array('val' => 'cnt-10000', 'label' => 'Keep a maximum of 10,000 logged events');
        $retention_settings[] = array('val' => 'cnt-20000', 'label' => 'Keep a maximum of 20,000 logged events');
        $retention_settings[] = array('val' => 'cnt-50000', 'label' => 'Keep a maximum of 50,000 logged events');
        $retention_settings[] = array('val' => 'day-7', 'label' => 'Keep event logs for up to 7 days');
        $retention_settings[] = array('val' => 'day-15', 'label' => 'Keep event logs for up to 15 days');
        $retention_settings[] = array('val' => 'day-30', 'label' => 'Keep event logs for up to 30 days');
        $retention_settings[] = array('val' => 'day-45', 'label' => 'Keep event logs for up to 45 days');
        $retention_settings[] = array('val' => 'day-60', 'label' => 'Keep event logs for up to 60 days');
        $retention_settings[] = array('val' => '4-ever', 'label' => 'Keep all event logs');

        $email_reports_settings = array();
        $email_reports_settings[] = array('val' => '0', 'label' => 'Do not email any reports');
        $email_reports_settings[] = array('val' => '86400', 'label' => 'Send one email every 24 hours');
        $email_reports_settings[] = array('val' => '604800', 'label' => 'Send one email every week');

        echo '<tr valign="top">
                            <th scope="row"><label for="ob_email_to">Email Address for Reports</label></th>
                            <td><input type="text" class="regular-text" id="ob_email_to" name="' . WF301_OPTIONS_KEY . '[email_to]" value="' . $options['email_to'] . '" />';
        echo '<br><span>Email address of the person (usually the site admin) who\'ll receive the email reports. Default: WP admin email.</span>';
        echo '</td></tr>';

        echo '<tr valign="top">
                            <th scope="row"><label for="ob_404_email_reports">404 Email Reports</label></th>
                            <td><select id="ob_404_email_reports" name="' . WF301_OPTIONS_KEY . '[404_email_reports]">';
        WF301_utility::create_select_options($email_reports_settings, $options['404_email_reports']);
        echo '</select>';
        echo '<br /><span>Email reports with a specified number of latest events can be automatically emailed to alert the admin of any suspicious events.
                                <br />Default: do not email any reports.</span>';
        if ($options['404_email_reports'] > 0) {
            echo '<br />Last sent: ' . ($options['404_last_reported_time'] > 0 ? date('Y-m-d h:i', $options['404_last_reported_time']) : 'never');
        }
        echo '</td></tr>';

        echo '<tr valign="top">
                            <th scope="row"><label for="ob_retention_404">404 Log Retention Policy</label></th>
                            <td><select id="ob_retention_404" name="' . WF301_OPTIONS_KEY . '[retention_404]">';
        WF301_utility::create_select_options($retention_settings, $options['retention_404']);
        echo '</select>';
        echo '<br /><span>In order to preserve disk space logs are automatically deleted based on this option. Default: keep logs for 30 days.</span>';
        echo '</td></tr>';

        echo '<tr valign="top">
                            <th scope="row"><label for="ob_redirect_email_reports">Redirects Email Reports</label></th>
                            <td><select id="ob_redirect_email_reports" name="' . WF301_OPTIONS_KEY . '[redirect_email_reports]">';
        WF301_utility::create_select_options($email_reports_settings, $options['redirect_email_reports']);
        echo '</select>';
        echo '<br /><span>Email reports with a specified number of latest events can be automatically emailed to alert the admin of any suspicious events.
                                <br />Default: do not email any reports.</span>';
        if ($options['redirect_email_reports'] > 0) {
            echo '<br />Last sent: ' . ($options['redirect_last_reported_time'] > 0 ? date('Y-m-d h:i', $options['redirect_last_reported_time']) : 'never');
        }
        echo '</td></tr>';

        echo '<tr valign="top">
                            <th scope="row"><label for="ob_retention_redirect">Redirect Log Retention Policy</label></th>
                            <td><select id="ob_retention_redirect" name="' . WF301_OPTIONS_KEY . '[retention_redirect]">';
        WF301_utility::create_select_options($retention_settings, $options['retention_redirect']);
        echo '</select>';
        echo '<br /><span>In order to preserve disk space logs are automatically deleted based on this option. Default: keep logs for 30 days.</span>';
        echo '</td></tr>';

        echo '<tr valign="top">
        <th scope="row"><label for="ob_logs_upload">Upload Logs to your 301 Redirects Dashboard</label></th>
        <td>';
        WF301_utility::create_toogle_switch('ob_logs_upload', array('saved_value' => $options['logs_upload'], 'option_key' => WF301_OPTIONS_KEY . '[logs_upload]'));
        echo '<br /><span>Enable this option to upload 404 and Redirect logs to your 301 Redirects Dashboard. Due to privacy concerns logs are off by default.</span>';
        echo '</td></tr>';

        echo '</tbody></table>';

        echo '<div class="wf301-onboarding-tabs-nav">';
        echo '<div class="button button-secondary wf301-onboarding-tab-skip">Skip</div>';
        echo '<div class="button button-primary wf301-onboarding-tab-previous">Previous</div>';
        echo '<div class="button button-primary wf301-onboarding-tab-next">Next</div>';
        echo '</div>';
        echo '</div>';

        echo '<div style="display: none;" id="wf301_onboarding_step4" data-tab="3">';
        echo '<h3>Setup completed!</h3><br />';

        echo '<div class="wf301-onboarding-tabs-nav">';
        echo '<div class="button button-primary wf301-onboarding-tab-skip">Finish</div>';
        echo '</div>';
        echo '</div>';

        echo '</div>';

        echo '</form>';

        echo '</div>';
    } // display
} // class WF301_tab_404_log
