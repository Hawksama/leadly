<?php
/*
  Plugin Name: 301 Redirects Pro
  Plugin URI: https://wp301redirects.com/
  Description: Manage redirects and 404 errors.
  Version: 5.62
  Author: WebFactory Ltd
  Author URI: https://www.webfactoryltd.com/
  Text Domain: 301-redirects

  Copyright 2019 - 2021  Web factory Ltd  (email: support@webfactoryltd.com)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// include only file
if (!defined('ABSPATH')) {
    wp_die(__('Do not open this file directly.', '301-redirects'));
}

define('WF301_PLUGIN_FILE', __FILE__);
define('WF301_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WF301_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WF301_OPTIONS_KEY', 'wf301_options');
define('WF301_META_KEY', 'wf301_meta');
define('WF301_POINTERS_KEY', 'wf301_pointers');
define('WF301_NOTICES_KEY', 'wf301_notices');
define('WF301_PERMALINKS_KEY', 'wf301_permalinks');
define('WF301_LINKS_KEY', 'wf301_links');


require_once WF301_PLUGIN_DIR . 'interface/tab_404_log.php';
require_once WF301_PLUGIN_DIR . 'interface/tab_license.php';
require_once WF301_PLUGIN_DIR . 'interface/tab_redirect.php';
require_once WF301_PLUGIN_DIR . 'interface/tab_redirect_log.php';
require_once WF301_PLUGIN_DIR . 'interface/tab_support.php';
require_once WF301_PLUGIN_DIR . 'interface/tab_settings.php';
require_once WF301_PLUGIN_DIR . 'interface/onboarding.php';

require_once WF301_PLUGIN_DIR . 'vendor/autoload.php';

require_once WF301_PLUGIN_DIR . 'libs/admin.php';
require_once WF301_PLUGIN_DIR . 'libs/setup.php';
require_once WF301_PLUGIN_DIR . 'libs/ajax.php';
require_once WF301_PLUGIN_DIR . 'libs/utility.php';
require_once WF301_PLUGIN_DIR . 'libs/export_import.php';
require_once WF301_PLUGIN_DIR . 'libs/functions.php';
require_once WF301_PLUGIN_DIR . 'libs/logs.php';
require_once WF301_PLUGIN_DIR . 'libs/stats.php';
require_once WF301_PLUGIN_DIR . 'libs/geoip/autoload.php';

require_once WF301_PLUGIN_DIR . 'wf-licensing.php';

// main plugin class
class WF301
{
    static $version = 0;
    static $pro = true;
    static $licensing_servers = array('https://dashboard.wp301redirects.com/api/');
    
    static $type;

    /**
     * Setup Hooks
     * 
     * @since 5.0
     *
     * @return null
     */
    static function init()
    {
        // check if minimal required WP version is present
        if (false === WF301_setup::check_wp_version(4.6) || false === WF301_setup::check_php_version('5.6.20')) {
            return false;
        }


        WF301_setup::register_custom_tables();

        if (is_admin()) {
            // upgrade/install DB if needed
            WF301_setup::maybe_upgrade();

            // add WF301 menu to admin tools menu group
            add_action('admin_menu', array('WF301_admin', 'admin_menu'));

            // add WF301 menu to admin bar
            add_action('wp_before_admin_bar_render', array('WF301_admin', 'admin_bar'));

            // add WF301 add new rule dialog to all admin area
            add_action('admin_footer', array('WF301_tab_redirect', 'dialogs'));

            // settings registration
            add_action('admin_init', array('WF301_setup', 'register_settings'));

            // aditional links in plugin description and footer
            add_filter('plugin_action_links_' . plugin_basename(__FILE__), array('WF301_admin', 'plugin_action_links'));
            add_filter('plugin_row_meta', array('WF301_admin', 'plugin_meta_links'), 10, 2);
            add_filter('admin_footer_text', array('WF301_admin', 'admin_footer_text'));

            // manages admin header notifications
            add_action('admin_notices', array('WF301_admin', 'admin_notices'));
            add_action('admin_action_wf301_dismiss_notice', array('WF301_admin', 'dismiss_notice'));

            // enqueue admin scripts
            add_action('admin_enqueue_scripts', array('WF301_admin', 'admin_enqueue_scripts'));

            // AJAX endpoints
            add_action('wp_ajax_wf301_run_tool', array('WF301_ajax', 'ajax_run_tool'));
            add_action('wp_ajax_wf301_submit_support_message', array('WF301_utility', 'submit_support_message_ajax'));
            add_action('wp_ajax_wf301_dismiss_pointer', array('WF301_utility', 'dismiss_pointer_ajax'));

            // admin actions
            add_action('admin_action_wf301_export_rules', array('WF301_ei', 'generate_export_file'));
            add_action('admin_action_wf301_add_permalink_redirect_rule', array('WF301_functions', 'add_permalink_redirect_rule'));
            add_action('admin_action_wf301_dismiss_permalink_notice', array('WF301_functions', 'dismiss_permalink_notice'));
            add_action('admin_action_wf301_import_301redirects_free_db', array('WF301_ei', 'import_301redirects_free_db'));
            add_action('admin_action_wf301_import_redirection_db', array('WF301_ei', 'import_redirection_db'));

            // admin actions to happen after saving options
            add_action('update_option_' . WF301_OPTIONS_KEY, array('WF301_functions', 'options_updated_callback'), 10, 2);
        } else {
            add_action('template_redirect', array('WF301_functions', 'redirect'), 1);
        } // if not admin

        // permalink change - not working in is_admin() with gutenberg
        add_action('pre_post_update', array('WF301_functions', 'pre_post_update'), 10, 2);
        add_action('post_updated', array('WF301_functions', 'post_updated'), 11, 3);


        WF301_logs::trim_404_log(false);
        WF301_logs::trim_redirect_log(false);
    } // init

    /**
     * Get plugin version
     *
     * @since 5.0
     * 
     * @return int plugin version
     * 
     */
    static function get_plugin_version()
    {
        $plugin_data = get_file_data(__FILE__, array('version' => 'Version'), 'plugin');
        self::$version = $plugin_data['version'];

        return $plugin_data['version'];
    } // get_plugin_version

    /**
     * Set plugin version and texdomain
     *
     * @since 5.0
     * 
     * @return null
     */
    static function plugins_loaded()
    {
        self::get_plugin_version();
        load_plugin_textdomain('301-redirects');
    } // plugins_loaded

    static function run()
    {
        self::plugins_loaded();
        WF301_setup::load_actions();
        WF301_setup::protect_from_translation_plugins();
    }
} // class WF301


/**
 * Setup Hooks
 */
register_activation_hook(__FILE__, array('WF301_setup', 'activate'));
register_deactivation_hook(__FILE__, array('WF301_setup', 'deactivate'));
register_uninstall_hook(__FILE__, array('WF301_setup', 'uninstall'));
add_action('plugins_loaded', array('WF301', 'run'), -9999);
add_action('init', array('WF301', 'init'), -1);
