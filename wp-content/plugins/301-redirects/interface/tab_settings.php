<?php
/**
 * 301 Redirects Pro
 * https://wp301redirects.com/
 * (c) WebFactory Ltd, 2019 - 2021, www.webfactoryltd.com
 */

class WF301_tab_settings extends WF301
{
    static function display()
    {
        $tabs[] = array('id' => 'tab_settings_general', 'class' => 'tab-content', 'label' => __('General', '301-redirects'), 'callback' => array(__CLASS__, 'tab_general'));
        $tabs[] = array('id' => 'tab_settings_advanced', 'class' => 'tab-content', 'label' => __('Advanced', '301-redirects'), 'callback' => array(__CLASS__, 'tab_advanced'));
        $tabs[] = array('id' => 'tab_settings_tools', 'class' => 'tab-content', 'label' => __('Tools', '301-redirects'), 'callback' => array(__CLASS__, 'tab_tools'));

        echo '<div id="tabs_settings" class="ui-tabs wf301-tabs-2nd-level">';
        echo '<ul>';
        foreach ($tabs as $tab) {
            echo '<li><a href="#' . $tab['id'] . '">' . $tab['label'] . '</a></li>';
        }
        echo '</ul>';

        foreach ($tabs as $tab) {
            if (is_callable($tab['callback'])) {
                echo '<div style="display: none;" id="' . $tab['id'] . '" class="' . $tab['class'] . '">';
                call_user_func($tab['callback']);
                echo '</div>';
            }
        } // foreach
        
        echo '</div>'; // second level of tabs
    } // display

    static function tab_general()
    {
        $options = WF301_setup::get_options();

        echo '<table class="form-table"><tbody>';

        echo '<tr valign="top">
        <th scope="row"><label for="autoredirect_404">Autoredirect 404</label></th>
        <td>';
        WF301_utility::create_toogle_switch('autoredirect_404', array('saved_value' => $options['autoredirect_404'], 'option_key' => WF301_OPTIONS_KEY . '[autoredirect_404]'));
        echo '<br /><span>Autoredirect visitors if a page with a similar slug exists. For example, if it would have resulted in a 404 not found error, "/sample-pag" will be redirected to "/sample-page"</span>';
        echo '</td></tr>';


        echo '<tr valign="top">
        <th scope="row"><label for="monitor_permalinks">Monitor permalink changes</label></th>
        <td>';
        WF301_utility::create_toogle_switch('monitor_permalinks', array('saved_value' => $options['monitor_permalinks'], 'option_key' => WF301_OPTIONS_KEY . '[monitor_permalinks]'));
        echo '<br /><span>Monitor permalink changes and show a notice allowing you to create a redirect rule in order to avoid 404 errors.</span>';
        echo '</td></tr>';

        $get_cpt_args = array(
            'public'   => true,
            '_builtin' => true
        );
        $post_types = get_post_types( [], 'object' );

        $redirect_cpt =   array();

        if ( $post_types ) {
            foreach ( $post_types as $cpt_key => $cpt_val ) {
                if(!$cpt_val->public){
                    continue;
                }
                $redirect_cpt[] = array('val' => $cpt_key, 'label' => $cpt_val->label);
            }
        }

        echo '<tr valign="top">
        <th scope="row"><label for="redirect_cpt">Post types used when autoredirecting</label></th>
        <td><select id="redirect_cpt" name="' . WF301_OPTIONS_KEY . '[redirect_cpt][]" multiple>';
        WF301_utility::create_select_options($redirect_cpt, $options['redirect_cpt']);
        echo '</select>';
        echo '<br /><span>Use control-click (Windows) or command-click (Mac) to select more than one.</span>';
        echo '<br /><span>Select post types that the Autoredirect function considers when looking for a similar URL</span>';
        echo '</td></tr>';

        echo '<tr valign="top">
        <th scope="row"><label for="page_404">404 Page</label></th>
        <td><p id="page_404_dummy">';
        
        $dropdown_pages = wp_dropdown_pages(array(
            'id'   => 'page_404',
            'name' => '' . WF301_OPTIONS_KEY . '[page_404]',
            'echo' => 0,
            'show_option_none' => 'Default 404.php file',
            'option_none_value' => '0',
            'selected' => $options['page_404']
        ));

        if ($dropdown_pages) {
            echo $dropdown_pages;
        } else {
            echo '<select id="page_404" name="' . WF301_OPTIONS_KEY . '[page_404]"><option value="0">Default 404.php file</option></select>';
        }
        echo '<a href="#" id="edit_404_page" title="Edit selected page"> or <a href="post-new.php?post_type=page" title="Add a new page">add a new page</a><br />';
        echo 'The 404 "document not found" page for your site. It has to be published and public.</p></td></tr>';

        echo '<tr valign="top">
        <th scope="row"><label for="redirect_url_404">Redirect to URL on 404</label></th>
        <td><input type="text" class="regular-text" id="redirect_url_404" name="' . WF301_OPTIONS_KEY . '[redirect_url_404]" value="' . $options['redirect_url_404'] . '" />';
        echo '<br><span>If a 404 occurs, redirect visitor to this URL instead of displaying a 404 page.</span>';
        echo '</td></tr>';

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
        <th scope="row"><label for="email_to">Email Address for Reports</label></th>
        <td><input type="text" class="regular-text" id="email_to" name="' . WF301_OPTIONS_KEY . '[email_to]" value="' . $options['email_to'] . '" />';
        echo '<br><span>Email address of the person (usually the site admin) who\'ll receive the email reports. Default: WP admin email.</span>';
        echo '</td></tr>';

        echo '<tr valign="top">
        <th scope="row"><label for="email_reports">404 Email Reports</label></th>
        <td><select id="email_reports" name="' . WF301_OPTIONS_KEY . '[404_email_reports]">';
        WF301_utility::create_select_options($email_reports_settings, $options['404_email_reports']);
        echo '</select>';
        echo '<br /><span>Email reports with a specified number of latest events can be automatically emailed to alert the admin of any suspicious events.
              <br />Default: do not email any reports.</span>';
        if ($options['404_email_reports'] > 0) {
            echo '<br />Last sent: ' . ($options['404_last_reported_time'] > 0 ? date('Y-m-d h:i', $options['404_last_reported_time']) : 'never');
        }
        echo '</td></tr>';

        echo '<tr valign="top">
        <th scope="row"><label for="retention_404">404 Log Retention Policy</label></th>
        <td><select id="retention_404" name="' . WF301_OPTIONS_KEY . '[retention_404]">';
        WF301_utility::create_select_options($retention_settings, $options['retention_404']);
        echo '</select>';
        
        echo '<div class="wf301_empty_log_404 button button-primary button-delete button-small" data-msg-wait="Please wait" data-btn-confirm="Empty 404 log" data-text="This will empty the 404 log. This action is not reversible!" data-title="Empty 404 log" data-msg-success="404 log successfully emptied" href="#"><i class="wf301-icon wf301-trash "></i> Empty 404 log</div>';
        
        echo '<br /><span>In order to preserve disk space, logs are automatically deleted based on this option. Default: keep logs for 30 days.</span>';
        echo '</td></tr>';

        echo '<tr valign="top">
        <th scope="row"><label for="redirect_email_reports">Redirects Email Reports</label></th>
        <td><select id="redirect_email_reports" name="' . WF301_OPTIONS_KEY . '[redirect_email_reports]">';
        WF301_utility::create_select_options($email_reports_settings, $options['redirect_email_reports']);
        echo '</select>';
        echo '<br /><span>Email reports with a specified number of latest events can be automatically emailed to alert the admin of any suspicious events.
              <br />Default: do not email any reports.</span>';
        if ($options['redirect_email_reports'] > 0) {
            echo '<br />Last sent: ' . ($options['redirect_last_reported_time'] > 0 ? date('Y-m-d h:i', $options['redirect_last_reported_time']) : 'never');
        }
        echo '</td></tr>';

        echo '<tr valign="top">
        <th scope="row"><label for="retention_redirect">Redirect Log Retention Policy</label></th>
        <td><select id="retention_redirect" name="' . WF301_OPTIONS_KEY . '[retention_redirect]">';
        WF301_utility::create_select_options($retention_settings, $options['retention_redirect']);
        echo '</select>';

        echo '<div class="wf301_empty_log_redirect button button-primary button-delete button-small" data-msg-wait="Please wait" data-btn-confirm="Empty Redirect log" data-text="This will empty the redirect log. This action is not reversible!" data-title="Empty redirect log" data-msg-success="Redirect log successfully emptied" href="#"><i class="wf301-icon wf301-trash "></i> Empty redirect log</div>';
        
        echo '<br /><span>In order to preserve disk space, logs are automatically deleted based on this option. Default: keep logs for 30 days.</span>';
        echo '</td></tr>';

        echo '<tr valign="top">
        <th scope="row"><label for="logs_upload">Upload Logs to your 301 Redirects Dashboard</label></th>
        <td>';
        WF301_utility::create_toogle_switch('logs_upload', array('saved_value' => $options['logs_upload'], 'option_key' => WF301_OPTIONS_KEY . '[logs_upload]'));
        echo '<br /><span>Enable this option to upload 404 and Redirect logs to your 301 Redirects Dashboard. Due to privacy concerns logs are off by default.</span>';
        echo '</td></tr>';

        echo '<tr><td></td><td>';
        WF301_admin::footer_save_button();
        echo '</td></tr>';
        echo '</tbody></table>';

        
    }

    static function tab_advanced()
    {
        $options = WF301_setup::get_options();

        echo '<table class="form-table"><tbody>';

        echo '<tr valign="top">
        <th scope="row"><label for="disable_all_redirections">Disable all redirections</label></th>
        <td>';
        WF301_utility::create_toogle_switch('disable_all_redirections', array('saved_value' => $options['disable_all_redirections'], 'option_key' => WF301_OPTIONS_KEY . '[disable_all_redirections]'));
        echo '<br /><span>If enabled, all rules will be disabled.</span>';
        echo '</td></tr>';

        echo '<tr valign="top">
        <th scope="row"><label for="disable_for_users">Disable for logged in users</label></th>
        <td>';
        WF301_utility::create_toogle_switch('disable_for_users', array('saved_value' => $options['disable_for_users'], 'option_key' => WF301_OPTIONS_KEY . '[disable_for_users]'));
        echo '<br /><span>If enabled, logged in users will not be redirected by any redirect rules.</span>';
        echo '</td></tr>';

        echo '<tr valign="top">
        <th scope="row"><label for="uninstall_delete">Delete all tables and options when plugin is deleted</label></th>
        <td>';
        WF301_utility::create_toogle_switch('uninstall_delete', array('saved_value' => $options['uninstall_delete'], 'option_key' => WF301_OPTIONS_KEY . '[uninstall_delete]'));
        echo '<br /><span>If enabled, 301 Redirects Pro options, rules and all log tables will be deleted when the plugin is deleted.</span>';
        echo '</td></tr>';

        echo '<tr valign="top">
        <th scope="row"><label for="wf301_recreate_tables">Recreate/Reset Tables</label></th>
        <td>';
        echo '<div class="wf301_recreate_tables button button-primary button-delete" data-msg-wait="Please wait" data-btn-confirm="Recreate tables" data-text="This will delete and recreate the tables used by 301 Redirects. All redirect rules and logs will be deleted but no settings will be changed. This action is not reversible!" data-title="Recreate/reset 301 Redirects Pro tables" data-msg-success="Tables successfully recreated" href="' . add_query_arg(array('action' => 'wf301_recreate_tables'), admin_url('admin.php')) . '">Recreate/Reset 301 Redirect Pro Tables <i class="wf301-icon wf301-alert-triangle"></i></div>';
        echo '<span class="description">This will delete and recreate the tables used by 301 Redirects Pro. All redirect rules and logs will be deleted but no settings will be changed. This action cannot be undone!</span>';
        echo '</td></tr>';

        /*echo '<tr valign="top">
        <th scope="row"><label for="htaccess_method">Add all rules to .htaccess</label></th>
        <td>';
        WF301_utility::create_toogle_switch('htaccess_method', array('saved_value' => $options['htaccess_method'], 'option_key' => WF301_OPTIONS_KEY . '[htaccess_method]'));
        echo '<br /><span>If enabled, rules will be created into .htaccess file.</span>';
        echo '</td></tr>';*/
        
        echo '<tr valign="top">
        <th scope="row"><label for="ignore_urls">Ignore URLs</label></th>
        <td><textarea name="' . WF301_OPTIONS_KEY . '[ignore_urls]" style="width:400px; height:160px;">';
        echo isset($options['ignore_urls'])?$options['ignore_urls']:'';
        echo '</textarea>';
        echo '<br /><span>URL paths that should be ignored by 301 Redirects. Enter relative paths, one per line, for example /sitemap.xml</span>';
        echo '</td></tr>';


        echo '<tr><td></td><td>';
        WF301_admin::footer_save_button();
        echo '</td></tr>';
        
        echo '</tbody></table>';
    }

    static function tab_tools()
    {
        echo '<table class="form-table">
        <tr>
        <th><label>Import</label></th>
        <td>
        <input accept="csv" type="file" name="wf301_import_file" value="">
        <input type="submit" name="wf301_import_file" id="submit" class="button button-secondary" value="Upload" />
        <p>
            <input type="radio" name="wf301_import_method" value="skip" checked="checked"> Skip Duplicates
           &nbsp;&nbsp;&nbsp;<input type="radio" name="wf301_import_method" value="update"> Update Duplicates
        </p>
    
        <br><small class="eps-grey-text">Supply Columns: <strong>Status</strong> (301,302,inactive), <strong>Request URL</strong>, <strong>Redirect
        To</strong> (ID or URL). <a href="' . WF301_PLUGIN_URL . 'misc/example.csv" target="_blank">Download Example CSV</a></small>
        </td>
        </tr>

        <tr>
        <th><label>Export</label></th>
        <td>
        <a class="button button-secondary" href="' . add_query_arg(array('action' => 'wf301_export_rules'), admin_url('admin.php')) . '">Download Export File</a>
        <br><small class="eps-grey-text">Export a backup copy of your redirects.</small>
        </td>
        </tr>
        </table>';
    }
} // class WF301_tab_settings
