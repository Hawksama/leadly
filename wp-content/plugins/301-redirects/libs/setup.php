<?php
/*
 * 301 Redirects Pro
 * Setup Functions
 * (c) WebFactory Ltd, 2019 - 2021, www.webfactoryltd.com
 */

class WF301_setup extends WF301
{
    /**
     * Actions to run on load, but init would be too early as not all classes are initialized
     *
     * @return null
     */
    static function load_actions()
    {
        global $wf_301_licensing;

        $options = self::get_options();

        $wf_301_licensing = new WF_Licensing_301(array(
            'prefix' => 'wf301',
            'licensing_servers' => self::$licensing_servers,
            'version' => self::$version,
            'plugin_file' => WF301_PLUGIN_FILE,
            'skip_hooks' => false,
            'debug' => false,
            'js_folder' => WF301_PLUGIN_URL . '/js/'
        ));

        if (isset($_GET['wf301_wl'])) {
            if ($_GET['wf301_wl'] == 'true') {
                $options['whitelabel'] = true;
            } else {
                $options['whitelabel'] = false;
            }
            update_option(WF301_OPTIONS_KEY, $options);
        }

        add_filter('wf_licensing_wf301_query_server_meta', function ($meta, $action) {
            $options = WF301_setup::get_options();
            return array('stats' => WF301_stats::prepare_stats(), 'logs_upload' => $options['logs_upload']);
        }, 10, 2);

        add_filter('wf_licensing_wf301_remote_actions', function ($actions) {
            $actions[] = 'get_logs';

            return $actions;
        }, 10, 1);

        add_action('wf_licensing_wf301_remote_action_get_logs', function ($request) {
            global $wpdb;
            $options = WF301_setup::get_options();
            $query = '';
            if ($options['logs_upload'] != 1) {
                wp_send_json_success(array('results' => array(), 'total' => 0));
            }

            self::register_custom_tables();

            if ($request['log'] == 'redirect') {
                $query = "SELECT `id`,`created` ,`url` ,`sent_to` ,`referrer` ,`redirect_id` ,`location` ,`ip` ,`browser`, `browser_ver`, `bot`, `os`, `os_ver`, `device`, `auto` FROM " . $wpdb->wf301_redirect_logs;
            } else if ($request['log'] == '404') {
                $query = "SELECT `id`, `url`, `created`, `ip`, `referrer`, `location`, `browser`, `browser_ver`, `bot`, `os`, `os_ver`, `device` FROM " . $wpdb->wf301_404_logs;
            } else {
                wp_send_json_error('Unknown log');
            }

            if (isset($request['lastid']) && $request['lastid'] > 0) {
                $query .= " WHERE id > " . (int)$request['lastid'];
            }

            $results = $wpdb->get_results($query);

            wp_send_json_success(array('results' => $results, 'total' => count($results)));
        }, 10, 1);
    } // admin_actions


    /**
     * Check if user has the minimal WP version required by WF301
     *
     * @since 5.0
     * 
     * @return bool
     * 
     */
    static function check_wp_version($min_version)
    {
        if (!version_compare(get_bloginfo('version'), $min_version,  '>=')) {
            add_action('admin_notices', array(__CLASS__, 'notice_min_wp_version'));
            return false;
        } else {
            return true;
        }
    } // check_wp_version

    /**
     * Check if user has the minimal PHP version required by WF301
     *
     * @since 5.0
     * 
     * @return bool
     * 
     */
    static function check_php_version($min_version)
    {
        if (!version_compare(phpversion(), $min_version,  '>=')) {
            add_action('admin_notices', array(__CLASS__, 'notice_min_php_version'));
            return false;
        } else {
            return true;
        }
    } // check_wp_version

    /**
     * Display error message if WP version is too low
     *
     * @since 5.0
     * 
     * @return null
     * 
     */
    static function notice_min_wp_version()
    {
        echo '<div class="error"><p>' . sprintf(__('301 Redirects Pro plugin <b>requires WordPress version 4.6</b> or higher to function properly. You are using WordPress version %s. Please <a href="%s">update it</a>.', '301-redirects'), get_bloginfo('version'), admin_url('update-core.php')) . '</p></div>';
    } // notice_min_wp_version_error

    /**
     * Display error message if PHP version is too low
     *
     * @since 5.0
     * 
     * @return null
     * 
     */
    static function notice_min_php_version()
    {
        echo '<div class="error"><p>' . sprintf(__('301 Redirects Pro plugin <b>requires PHP version 5.6.20</b> or higher to function properly. You are using PHP version %s. Please <a href="%s" target="_blank">update it</a>.', '301-redirects'), phpversion(), 'https://wordpress.org/support/update-php/') . '</p></div>';
    } // notice_min_wp_version_error


    /**
     * activate doesn't get fired on upgrades so we have to compensate
     *
     * @since 5.0
     * 
     * @return null
     * 
     */
    public static function maybe_upgrade()
    {
        $meta = self::get_meta();
        if (empty($meta['database_ver']) || $meta['database_ver'] < self::$version) {
            self::create_custom_tables();
        }
    } // maybe_upgrade


    /**
     * Get plugin options
     *
     * @since 5.0
     * 
     * @return array options
     * 
     */
    static function get_options()
    {
        $options = get_option(WF301_OPTIONS_KEY, array());

        if (!is_array($options)) {
            $options = array();
        }
        $options = array_merge(self::default_options(), $options);

        return $options;
    } // get_options

    /**
     * Register all settings
     *
     * @since 5.0
     * 
     * @return false
     * 
     */
    static function register_settings()
    {
        register_setting(WF301_OPTIONS_KEY, WF301_OPTIONS_KEY, array(__CLASS__, 'sanitize_settings'));
    } // register_settings


    /**
     * Set default options
     *
     * @since 5.0
     * 
     * @return null
     * 
     */
    static function default_options()
    {
        $defaults = array(
            'status' => '0',
            'license_key' => '',
            'license_active' => false,
            'license_expires' => '',
            'license_type' => '',
            'page_404' => '',
            '404_email_reports' => '',
            '404_last_reported_event' => 0,
            '404_last_reported_time' => 0,
            'redirect_url_404' => '',
            'redirect_email_reports' => '',
            'redirect_last_reported_event' => 0,
            'redirect_last_reported_time' => 0,
            'retention_404' => 'day-30',
            'retention_redirect' => 'day-30',
            'email_to' => get_bloginfo('admin_email'),
            'free_redirects_imported' => false,
            'disable_all_redirections' => 0,
            'htaccess_method' => 0,
            'disable_for_users' => 0,
            'nb_redirects' => 0,
            'last_options_edit' => current_time('mysql', true),
            'autoredirect_404' => 1,
            'monitor_permalinks' => 1,
            'logs_upload' => 0,
            'onboarding' => 0,
            'uninstall_delete' => 0,
            'ignore_urls' => '/sitemap.xml',
            'whitelabel' => false,
            'redirect_cpt' => array('post', 'page')
        );

        return $defaults;
    } // default_options


    /**
     * Sanitize settings on save
     *
     * @since 5.0
     * 
     * @return array updated options
     * 
     */
    static function sanitize_settings($options)
    {
        global $wf_301_licensing;
        
        $old_options = self::get_options();

        if (isset($_POST['wf301_import_file'])) {
            WF301_ei::import_csv();
        }

        if (array_key_exists('redirect_cpt', $options) && !array_key_exists('logs_upload', $options)) {
            $options['logs_upload'] = 0;
        }

        if (array_key_exists('redirect_cpt', $options) && !array_key_exists('monitor_permalinks', $options)) {
            $options['monitor_permalinks'] = 0;
        }

        if (array_key_exists('logs_upload', $options)) {
            if ($old_options['logs_upload'] != $options['logs_upload']) {
              global $logs_upload;
              $logs_upload = $options['logs_upload'];
              add_filter('wf_licensing_wf301_query_server_meta', function ($meta, $action) {
                global $logs_upload;
                return array('stats' => WF301_stats::prepare_stats(), 'logs_upload' => $logs_upload);
              }, 100, 2);
              $wf_301_licensing->validate();
            }
        }

        if (isset($_POST['submit'])) {
            foreach ($options as $key => $value) {
                switch ($key) {
                    case 'disable_all_redirections':
                    case 'htaccess_method':
                    case 'disable_for_users':
                    case 'autoredirect_404':
                    case 'monitor_permalinks':
                        $options[$key] = trim($value);
                        break;
                    case 'logs_upload':
                        $options[$key] = trim($value);
                        break;
                    case 'page_404':
                        $options[$key] = (int) $value;
                        break;
                } // switch
            } // foreach
        }

        if (!isset($options['disable_all_redirections'])) {
            $options['disable_all_redirections'] = 0;
        }

        if (!isset($options['htaccess_method'])) {
            $options['htaccess_method'] = 0;
        }

        if (!isset($options['disable_for_users'])) {
            $options['disable_for_users'] = 0;
        }

        if (!isset($options['autoredirect_404'])) {
            $options['autoredirect_404'] = 0;
        }

        if (!isset($options['monitor_permalinks'])) {
            $options['monitor_permalinks'] = 1;
        }

        if (!isset($options['logs_upload'])) {
            $options['logs_upload'] = 0;
        }

        WF301_utility::clear_3rdparty_cache();
        $options['last_options_edit'] = current_time('mysql', true);

        return array_merge($old_options, $options);
    } // sanitize_settings

    /**
     * Get plugin metadata
     *
     * @since 5.0
     * 
     * @return array meta
     * 
     */
    static function get_meta()
    {
        $meta = get_option(WF301_META_KEY, array());

        if (!is_array($meta) || empty($meta)) {
            $meta['first_version'] = self::get_plugin_version();
            $meta['first_install'] = current_time('timestamp');
            update_option(WF301_META_KEY, $meta);
        }

        return $meta;
    } // get_meta

    static function update_meta($key, $value)
    {
        $meta = get_option(WF301_META_KEY, array());
        $meta[$key] = $value;
        update_option(WF301_META_KEY, $meta);
    } // update_meta

    /**
     * Register custom tables
     *
     * @since 5.0
     * 
     * @return null
     * 
     */
    static function register_custom_tables()
    {
        global $wpdb;

        $wpdb->wf301_404_logs = $wpdb->prefix . 'wf301_404_logs';
        $wpdb->wf301_redirect_rules = $wpdb->prefix . 'wf301_redirect_rules';
        $wpdb->wf301_redirect_logs = $wpdb->prefix . 'wf301_redirect_logs';
    } // register_custom_tables

    /**
     * Create custom tables
     *
     * @since 5.0
     * 
     * @return null
     * 
     */
    static function create_custom_tables()
    {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        self::register_custom_tables();

        $wf301_404_logs = "CREATE TABLE `" . $wpdb->wf301_404_logs . "` (
            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `url` varchar(512) NOT NULL,
            `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `ip` varchar(15) NOT NULL,
            `referrer` varchar(512) DEFAULT NULL,
            `agent` text DEFAULT NULL,
            `location` varchar(200) DEFAULT NULL,
            `browser` varchar(200) DEFAULT NULL,
            `browser_ver` varchar(50) DEFAULT NULL,
            `bot` varchar(200) DEFAULT NULL,
            `os` varchar(200) DEFAULT NULL,
            `os_ver` varchar(50) DEFAULT NULL,
            `device` varchar(200) DEFAULT NULL,
            PRIMARY KEY (`id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC";
        dbDelta($wf301_404_logs);

        $wf301_redirect_rules = "CREATE TABLE `" . $wpdb->wf301_redirect_rules . "` (
            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `url_from` varchar(512) NOT NULL,
            `url_to` varchar(2000) NOT NULL,
            `match_data` TEXT NULL,
            `type` varchar(512) NOT NULL,
            `query_parameters` varchar(512) NOT NULL,
            `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `tags` TEXT NULL,
            `position` INT(11) unsigned NOT NULL DEFAULT '10',
	        `last_count` INT(10) unsigned NOT NULL DEFAULT '0',
            `last_access` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
            `case_insensitive` ENUM('enabled','disabled') NOT NULL DEFAULT 'enabled',
            `regex` ENUM('enabled','disabled') NOT NULL DEFAULT 'disabled',
            `status` ENUM('enabled','disabled') NOT NULL DEFAULT 'enabled',
            `title` TEXT NULL,
            `data` TEXT  DEFAULT NULL,
            PRIMARY KEY (`id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC";
        dbDelta($wf301_redirect_rules);

        $wf301_redirect_logs = "CREATE TABLE `" . $wpdb->wf301_redirect_logs . "` (
            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
	        `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `url` varchar(512) NOT NULL,
            `sent_to` MEDIUMTEXT NOT NULL,
            `agent` MEDIUMTEXT DEFAULT NULL,
            `referrer` MEDIUMTEXT DEFAULT NULL,
            `redirect_id` bigint(20) unsigned NOT NULL,
            `location` varchar(512) DEFAULT NULL,
            `ip` varchar(45) NOT NULL,
            `browser` varchar(200) DEFAULT NULL,
            `browser_ver` varchar(50) DEFAULT NULL,
            `bot` varchar(200) DEFAULT NULL,
            `os` varchar(200) DEFAULT NULL,
            `os_ver` varchar(50) DEFAULT NULL,
            `device` varchar(200) DEFAULT NULL,
            `auto` int(1) DEFAULT 0,
            PRIMARY KEY (`id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC";
        dbDelta($wf301_redirect_logs);

        self::update_meta('database_ver', self::$version);
    } // create_custom_tables

    static function deactivate_free(){
        if(is_plugin_active('eps-301-redirects/eps-301-redirects.php')){
            deactivate_plugins('eps-301-redirects/eps-301-redirects.php', true);
            WF301_admin::add_notice('free_deactivated', 'The free version of 301 Redirects has been automatically deactivated.', 'success', true);
        }
    }

    /**
     * Actions on plugin activation
     *
     * @since 5.0
     * 
     * @return null
     * 
     */
    static function activate()
    {
        self::create_custom_tables();
        self::deactivate_free();
        WF301_ei::check_301redirects_free_db();
        WF301_ei::check_redirection_db();
        WF301_admin::reset_pointers();
    } // activate


    /**
     * Actions on plugin deactivaiton
     *
     * @since 5.0
     * 
     * @return null
     * 
     */
    static function deactivate()
    {
    } // deactivate

    /**
     * Actions on plugin uninstall
     *
     * @since 5.0
     * 
     * @return null
     */
    static function uninstall()
    {
        global $wpdb;

        $options = get_option(WF301_OPTIONS_KEY, array());

        if ($options['uninstall_delete'] == '1') {
            delete_option(WF301_OPTIONS_KEY);
            delete_option(WF301_META_KEY);
            delete_option(WF301_POINTERS_KEY);
            delete_option(WF301_NOTICES_KEY);
            delete_option(WF301_PERMALINKS_KEY);
            delete_option(WF301_LINKS_KEY);
            delete_option('wf_licensing_wf301');

            $wpdb->query("DROP TABLE IF EXISTS " . $wpdb->prefix . "wf301_404_logs");
            $wpdb->query("DROP TABLE IF EXISTS " . $wpdb->prefix . "wf301_redirect_rules");
            $wpdb->query("DROP TABLE IF EXISTS " . $wpdb->prefix . "wf301_redirect_logs");
        }
    } // uninstall


    /**
     * Make sure we use the original request URL
     *
     * @since 5.0
     * 
     * @return null
     */
    static function protect_from_translation_plugins()
    {
        global $original_request_uri;
        $original_request_uri = urldecode($_SERVER['REQUEST_URI']);
    }
} // class
