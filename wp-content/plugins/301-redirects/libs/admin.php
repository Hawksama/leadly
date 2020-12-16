<?php

/**
 * 301 Redirects Pro
 * Admin Functions
 * (c) WebFactory Ltd, 2019 - 2021, www.webfactoryltd.com
 */

class WF301_admin extends WF301
{

    /**
     * Enqueue Admin Scripts
     *
     * @since 5.0
     * 
     * @return null
     */
    static function admin_enqueue_scripts($hook)
    {
        global $post, $wf_301_licensing;
        wp_enqueue_script('wf301-tooltipster', WF301_PLUGIN_URL . 'js/tooltipster.bundle.min.js', array('jquery'), self::$version, true);
        wp_enqueue_style('wf301-tooltipster', WF301_PLUGIN_URL . 'css/tooltipster.bundle.min.css', array(), self::$version);
        $license = $wf_301_licensing->get_license();
        $options = WF301_setup::get_options();
        if ('settings_page_301redirects' == $hook) {
            wp_enqueue_style('wp-jquery-ui-dialog');
            wp_enqueue_style('wf301-select2', WF301_PLUGIN_URL . 'css/select2.min.css', array(), self::$version);
            wp_enqueue_style('wf301-admin', WF301_PLUGIN_URL . 'css/301-redirects.css', array(), self::$version);
            wp_enqueue_style('wf301-dataTables', WF301_PLUGIN_URL . 'css/jquery.dataTables.min.css', array(), self::$version);
            wp_enqueue_style('wf301-sweetalert2', WF301_PLUGIN_URL . 'css/sweetalert2.min.css', array(), self::$version);
            wp_enqueue_style('wf301-google-font-roboto', 'https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,300;0,400;0,500;0,700;1,300;1,400;1,500;1,700&display=swap', array(), self::$version);
            wp_enqueue_script('jquery-ui-tabs');
            wp_enqueue_script('jquery-ui-dialog');
            wp_enqueue_script("jquery-effects-core");
            wp_enqueue_script("jquery-effects-blind");

            wp_enqueue_script('wf301-select2', WF301_PLUGIN_URL . 'js/select2.min.js', array(), self::$version, true);
            wp_enqueue_script('wf301-admin', WF301_PLUGIN_URL . 'js/301-redirects.js', array('jquery'), self::$version, true);
            wp_enqueue_script('wf301-dataTables', WF301_PLUGIN_URL . 'js/jquery.dataTables.min.js', array(), self::$version, true);
            wp_enqueue_script('wf301-chart', WF301_PLUGIN_URL . 'js/chart.min.js', array(), self::$version, true);
            wp_enqueue_script('wf301-moment', WF301_PLUGIN_URL . 'js/moment.min.js', array(), self::$version, true);

            wp_enqueue_script('wf301-sweetalert2', WF301_PLUGIN_URL . 'js/sweetalert2.min.js', array('jquery'), self::$version, true);


            wp_enqueue_script('wf301-selectize', WF301_PLUGIN_URL . 'js/selectize.js', array('jquery'), self::$version, true);
            wp_enqueue_style('wf301-selectize', WF301_PLUGIN_URL . 'css/selectize.css', array(), self::$version);

            $current_user = wp_get_current_user();
            $support_text = 'My site details: WP ' . get_bloginfo('version') . ', WP301 v' . self::$version . ', ';
            if (!empty($license['license_key'])) {
                $support_text .= 'license key: ' . $license['license_key'] . '.';
            } else {
                $support_text .= 'no license info.';
            }
            if (strtolower($current_user->display_name) != 'admin' && strtolower($current_user->display_name) != 'administrator') {
                $support_name = $current_user->display_name;
            } else {
                $support_name = '';
            }

            $js_localize = array(
                'undocumented_error' => __('An undocumented error has occurred. Please refresh the page and try again.', '301-redirects'),
                'documented_error' => __('An error has occurred.', '301-redirects'),
                'plugin_name' => __('301 Redirects Pro', '301-redirects'),
                'plugin_url' => WF301_PLUGIN_URL,
                'settings_url' => admin_url('options-general.php?page=301redirects'),
                'icon_url' => WF301_PLUGIN_URL . 'images/301-loader.gif',
                'whitelisted_users_placeholder' => __('Select whitelisted user(s)', '301-redirects'),
                'nonce_submit_support_message' => wp_create_nonce('wf301_submit_support_message'),
                'cancel_button' => __('Cancel', '301-redirects'),
                'ok_button' => __('OK', '301-redirects'),
                'version' => self::$version,
                'support_text' => $support_text,
                'support_name' => $support_name,
                'site' => get_home_url(),
                'nonce_lc_check' => wp_create_nonce('wf301_save_lc'),
                'run_tool_nonce' => wp_create_nonce('wf301_run_tool'),
                'lc_ep' => self::$licensing_servers[0],
                'stats_unavailable' => 'Stats will be available once enough data is collected.' . ($options['nb_redirects'] == 0 ? ' You don\'t have any redirection rules. <a href="#wf301_redirect">Create a redirection rule now.</a>' : ''),
                'stats_unavailable_404' => 'Stats will be available once enough data is collected.',
                'stats_redirect' => WF301_stats::get_stats('redirect'),
                'stats_404' => WF301_stats::get_stats('404'),
                'stats_redirect_devices' => WF301_stats::get_device_stats('redirect'),
                'stats_404_devices' => WF301_stats::get_device_stats('404'),
                'rebranded' => (self::get_rebranding() !== false ? true : false),
                'permalink_change_notices' => WF301_functions::get_changed_permalinks_notices(isset($post->ID) ? $post->ID : false),
                'whitelabel' => WF301_Utility::whitelabel_filter()
            );


            if (self::get_rebranding() !== false) {
                $brand_color = self::get_rebranding('color');
                if (empty($brand_color)) {
                    $brand_color = '#ff6144';
                }
                $js_localize['chart_colors'] = array($brand_color, self::color_luminance($brand_color, 0.2), self::color_luminance($brand_color, 0.4), self::color_luminance($brand_color, 0.6));
            } else {
                $js_localize['chart_colors'] = array('#e33103', '#ff5429', '#ff7d5c', '#ffac97');
            }
        } else {
            $js_localize = array(
                'run_tool_nonce' => wp_create_nonce('wf301_run_tool'),
                'permalink_change_notices' => WF301_functions::get_changed_permalinks_notices(isset($post->ID) ? $post->ID : false),
            );
        }

        wp_enqueue_script('wf301-admin-global', WF301_PLUGIN_URL . 'js/301-redirects-global.js', array('jquery'), self::$version, true);
        wp_localize_script('wf301-admin-global', 'wf301_vars', $js_localize);
        wp_enqueue_style('wf301-font-awesome', 'https://use.fontawesome.com/releases/v5.8.1/css/all.css', array(), WF301::$version);


        if ($hook == 'plugins.php') {
            $rebranding = self::get_rebranding();
            if (false !== $rebranding) {
                wp_localize_script('wf301-admin-global', 'wp301_rebranding', $rebranding);
            }
        }

        $pointers = get_option(WF301_POINTERS_KEY);
        if ($pointers && 'settings_page_301redirects' != $hook) {
            $pointers['_nonce_dismiss_pointer'] = wp_create_nonce('wf301_dismiss_pointer');
            wp_enqueue_script('wp-pointer');
            wp_enqueue_style('wp-pointer');
            wp_localize_script('wp-pointer', 'wf301_pointers', $pointers);
        }
    } // admin_enqueue_scripts


    /**
     * Change luminance of a hex color
     *
     * @since 5.0
     * 
     * @param string hex color
     * @param int percent to adjust color luminance by
     * 
     * @return string new hex color
     */
    static function color_luminance($hex, $percent)
    {
        $hex = preg_replace('/[^0-9a-f]/i', '', $hex);
        $new_hex = '#';

        if (strlen($hex) < 6) {
            $hex = $hex[0] + $hex[0] + $hex[1] + $hex[1] + $hex[2] + $hex[2];
        }

        // convert to decimal and change luminosity
        for ($i = 0; $i < 3; $i++) {
            $dec = hexdec(substr($hex, $i * 2, 2));
            $dec = min(max(0, $dec + $dec * $percent), 255);
            $new_hex .= str_pad(dechex($dec), 2, 0, STR_PAD_LEFT);
        }

        return $new_hex;
    }


    /**
     * Display notices in admin header
     *
     * @since 5.0
     * 
     * @return null
     */
    static function admin_notices()
    {
        global $wp_rewrite, $wf_301_licensing;

        $notices = get_option(WF301_NOTICES_KEY);

        $permalink_changes = WF301_functions::get_changed_permalinks();

        foreach ($permalink_changes as $post_id => $permalink) {
            echo '<div data-permalink="' . $post_id . '" class="notice-warning notice is-dismissible"><p>
            You have changed the permalink for ' . get_the_title($post_id) . ' from ' . $permalink['before'] . ' to ' . $permalink['after'] . '. <a class="wf301-add-redirect-rule" href="#" data-permalink="' . $post_id . '">Click here</a> if you want to add a redirect rule.
            <button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></p></div>';
        }

        if (is_array($notices)) {
            foreach ($notices as $id => $notice) {
                //Don't show some notices like importing rules if license is not activated
                if (!$wf_301_licensing->is_active() && in_array($id, array('redirection_import', 'free_import'))) {
                    continue;
                }
                echo '<div class="notice-' . $notice['type'] . ' notice is-dismissible"><p>' . $notice['text'] . '<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></p></div>';
                if ($notice['once'] == true) {
                    unset($notices[$id]);
                    update_option(WF301_NOTICES_KEY, $notices);
                }
            }
        }

        if (!isset($wp_rewrite->permalink_structure) || empty($wp_rewrite->permalink_structure)) {
            echo '<div class="notice-error notice"><p>WARNING: 301 Redirects Pro requires that a permalink structure is set to a value other than "plain". Please update the <a href="' . admin_url('options-permalink.php') . '" title="Permalinks">Permalink Structure</a></p></div>';
        }
    } // notices

    /**
     * Handle dismiss button for notices
     *
     * @since 5.0
     * 
     * @return null
     */
    static function dismiss_notice($notice = false)
    {
        if (isset($_GET['notice'])) {
            $notice = $_GET['notice'];
        }

        $notices = get_option(WF301_NOTICES_KEY, array());

        unset($notices[$notice]);

        update_option(WF301_NOTICES_KEY, $notices);

        if (!empty($_GET['redirect'])) {
            wp_safe_redirect($_GET['redirect']);
        }
    } // dismiss_notice

    /**
     * Add notice
     *
     * @since 5.0
     * 
     * @return null
     */
    static function add_notice($id = false, $text, $type = 'warning', $show_once = false)
    {
        if ($id) {
            $notices = get_option(WF301_NOTICES_KEY, array());
            $notices[$id] = array('text' => $text, 'type' => $type, 'once' => $show_once);
            update_option(WF301_NOTICES_KEY, $notices);
        }
    }

    /**
     * Add settings link to plugins page
     *
     * @since 5.0
     * 
     * @return null
     */
    static function plugin_action_links($links)
    {
        $plugin_name = self::get_rebranding('name');
        if ($plugin_name === false || empty($plugin_name)) {
            $plugin_name = __('301 Redirects PRO Settings', '301-redirects');
        }

        $settings_link = '<a href="' . admin_url('options-general.php?page=301redirects') . '" title="' . $plugin_name . '">' . __('Settings', '301-redirects') . '</a>';
        array_unshift($links, $settings_link);

        return $links;
    } // plugin_action_links

    /**
     * Add links to plugin's description in plugins table
     *
     * @since 5.0
     * 
     * @return null
     */
    static function plugin_meta_links($links, $file)
    {
        if ($file !== '301-redirects/301-redirects.php') {
            return $links;
        }

        if (self::get_rebranding('url')) {
            unset($links[1]);
            unset($links[2]);

            $links[] = '<a target="_blank" href="' . self::get_rebranding('company_url') . '" title="Get help">' . self::get_rebranding('company_name') . '</a>';
            $links[] = '<a target="_blank" href="' . self::get_rebranding('url') . '" title="Get help">Support</a>';
        } else {
            $support_link = '<a href="https://wp301redirects.com/support/" title="' . __('Get help', '301-redirects') . '">' . __('Support', '301-redirects') . '</a>';
            $links[] = $support_link;
        }

        if (!WF301_Utility::whitelabel_filter()) {
            unset($links[1]);
            unset($links[2]);
            unset($links[3]);
        }

        return $links;
    } // plugin_meta_links

    /**
     * Admin footer text
     *
     * @since 5.0
     * 
     * @return null
     */
    static function admin_footer_text($text)
    {
        if (!self::is_plugin_page() || !WF301_utility::whitelabel_filter()) {
            return $text;
        }

        if (self::get_rebranding()) {
            $text = '<i class="wf301-footer"><a href="' . self::get_rebranding('company_url') . '" title="Visit ' . self::get_rebranding('name') . ' page for more info" target="_blank">' . self::get_rebranding('name') . '</a> v' . self::$version . '. ' . self::get_rebranding('footer_text') . '</i>';
        } else {
            $text = '<i class="wf301-footer"><a href="' . self::generate_web_link('admin_footer') . '" title="' . __('Visit 301 Redirects PRO page for more info', '301-redirects') . '" target="_blank">301 Redirects PRO</a> v' . self::$version . '. Please <a target="_blank" href="https://wordpress.org/support/plugin/eps-301-redirects/reviews/#new-post" title="Rate the plugin">rate the plugin <span>★★★★★</span></a> to help us spread the word. Thank you from the 301 Redirects team!</i>';
        }

        echo '<script type="text/javascript">!function(e,t,n){function a(){var e=t.getElementsByTagName("script")[0],n=t.createElement("script");n.type="text/javascript",n.async=!0,n.src="https://beacon-v2.helpscout.net",e.parentNode.insertBefore(n,e)}if(e.Beacon=n=function(t,n,a){e.Beacon.readyQueue.push({method:t,options:n,data:a})},n.readyQueue=[],"complete"===t.readyState)return a();e.attachEvent?e.attachEvent("onload",a):e.addEventListener("load",a,!1)}(window,document,window.Beacon||function(){});</script>';

        return $text;
    } // admin_footer_text


    /**
     * Helper function for generating UTM tagged links
     *
     * @param string  $placement  Optional. UTM content param.
     * @param string  $page       Optional. Page to link to.
     * @param array   $params     Optional. Extra URL params.
     * @param string  $anchor     Optional. URL anchor part.
     *
     * @return string
     */
    static function generate_web_link($placement = '', $page = '/', $params = array(), $anchor = '')
    {
        $base_url = 'https://wp301redirects.com';

        if ('/' != $page) {
            $page = '/' . trim($page, '/') . '/';
        }
        if ($page == '//') {
            $page = '/';
        }

        $parts = array_merge(array('utm_source' => '301-redirects', 'utm_medium' => 'plugin', 'utm_content' => $placement, 'utm_campaign' => '301-redirects-pro-v' . self::$version), $params);

        if (!empty($anchor)) {
            $anchor = '#' . trim($anchor, '#');
        }

        $out = $base_url . $page . '?' . http_build_query($parts, '', '&amp;') . $anchor;

        return $out;
    } // generate_web_link


    /**
     * Helper function for generating dashboard UTM tagged links
     *
     * @param string  $placement  Optional. UTM content param.
     * @param string  $page       Optional. Page to link to.
     * @param array   $params     Optional. Extra URL params.
     * @param string  $anchor     Optional. URL anchor part.
     *
     * @return string
     */
    static function generate_dashboard_link($placement = '', $page = '/', $params = array(), $anchor = '')
    {
        $base_url = 'https://dashboard.wp301redirects.com';

        if ('/' != $page) {
            $page = '/' . trim($page, '/') . '/';
        }
        if ($page == '//') {
            $page = '/';
        }

        $parts = array_merge(array('utm_source' => '301-redirects-pro', 'utm_medium' => 'plugin', 'utm_content' => $placement, 'utm_campaign' => '301-redirects-pro-v' . self::$version), $params);

        if (!empty($anchor)) {
            $anchor = '#' . trim($anchor, '#');
        }

        $out = $base_url . $page . '?' . http_build_query($parts, '', '&amp;') . $anchor;

        return $out;
    } // generate_dashboard_link


    /**
     * Test if we're on plugin's page
     *
     * @since 5.0
     * 
     * @return null
     */
    static function is_plugin_page()
    {
        $current_screen = get_current_screen();

        if ($current_screen->id == 'settings_page_301redirects') {
            return true;
        } else {
            return false;
        }
    } // is_plugin_page

    /**
     * Admin menu entry
     *
     * @since 5.0
     * 
     * @return null
     */
    static function admin_menu()
    {

        $page_title = self::get_rebranding('name');
        if ($page_title === false || empty($plugin_name)) {
            $page_title = '301 Redirects PRO';
        }

        $menu_title = self::get_rebranding('short_name');
        if ($menu_title === false || empty($menu_title)) {
            $menu_title = '<span style="font-size: 11px;">301 Redirects <span style="color: #ff6144; vertical-align: super; font-size: 9px;">PRO</span></span>';
        }

        add_options_page(
            __($page_title, 'signals'),
            $menu_title,
            'manage_options',
            '301redirects',
            array(__CLASS__, 'main_page')
        );
    } // admin_menu


    /**
     * Admin bar entry
     *
     * @since 5.33
     * 
     * @return null
     */
    static function admin_bar()
    {
        global $wp_admin_bar;

        // only show to admins
        if (false === current_user_can('manage_options')) {
            return;
        }

        $menu_title = self::get_rebranding('short_name');
        if ($menu_title === false || empty($menu_title)) {
            $menu_title = '<span style="font-size: 11px;">301 Redirects <span style="color: #ff6144; vertical-align: super; font-size: 9px;">PRO</span></span>';
        }

        $wp_admin_bar->add_menu(array(
            'parent' => '',
            'id'     => '301redirects',
            'title'  => $menu_title,
            'href'   => admin_url('options-general.php?page=301redirects')
        ));

        $wp_admin_bar->add_node(array(
            'id'    => '301redirects-new-rule',
            'title' => 'Add new redirect rule',
            'parent' => '301redirects',
            'href' => 'options-general.php?page=301redirects&tab=wf301_redirect#add-redirect-rule'
        ));

        $wp_admin_bar->add_node(array(
            'id'    => '301redirects-redirect-rules',
            'title' => 'Redirect Rules',
            'href'  => admin_url('options-general.php?page=301redirects&tab=wf301_redirect'),
            'parent' => '301redirects'
        ));

        $wp_admin_bar->add_node(array(
            'id'    => '301redirects-404-log',
            'title' => '404 Log',
            'href'  => admin_url('options-general.php?page=301redirects&tab=wf301_404_log'),
            'parent' => '301redirects'
        ));

        $wp_admin_bar->add_node(array(
            'id'    => '301redirects-redirect-log',
            'title' => 'Redirect Log',
            'href'  => admin_url('options-general.php?page=301redirects&tab=wf301_redirect_log'),
            'parent' => '301redirects'
        ));

        $wp_admin_bar->add_node(array(
            'id'    => '301redirects-settings',
            'title' => 'Settings',
            'href'  => admin_url('options-general.php?page=301redirects&tab=wf301_settings'),
            'parent' => '301redirects'
        ));
    } // admin_bar

    /**
     * Settings Page HTML
     *
     * @since 5.0
     * 
     * @return null
     */
    static function main_page()
    {
        global $wf_301_licensing;

        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to access this page.');
        }

        if (self::get_rebranding()) {
            echo '<style>';

            echo self::get_rebranding('admin_css_predefined');
            echo self::get_rebranding('admin_css');

            echo '</style>';
        }

        $options = WF301_setup::get_options();

        // auto remove welcome pointer when options are opened
        $pointers = get_option(WF301_POINTERS_KEY);
        if (isset($pointers['welcome'])) {
            unset($pointers['welcome']);
            update_option(WF301_POINTERS_KEY, $pointers);
        }

        $plugin_name = self::get_rebranding('name');
        if ($plugin_name === false || empty($plugin_name)) {
            $plugin_name = '301 Redirects PRO';
        }
        
        echo '<div class="wrap">
        <div class="wf301-header">
            <div class="wf301-logo">';
        if (self::get_rebranding('logo_url') !== false) {
            echo '<img src="' . self::get_rebranding('logo_url') . '" />';
        } else {
            echo '<img src="' . WF301_PLUGIN_URL . '/images/wf301_logo.png" alt="301 Redirects PRO" height="60" title="301 Redirects PRO">';
        }
        echo '</div>';
        $s404_stats_24h = WF301_stats::get_stats('404', 1);
        echo '<div class="wf301-header-stat wf301-header-stat-last">';
        echo '<div class="stat-title">404s<span>in last 24h</span></div>';
        echo '<div class="stat-value" '.($s404_stats_24h['total'] == 0 ? 'style="color:#cfd4da"':'').'>' . $s404_stats_24h['total'] . '</div>';
        echo '</div>';

        $redirect_stats_all = WF301_stats::get_stats('redirect', 360);
        echo '<div class="wf301-header-stat">';
        echo '<div class="stat-title">Redirects<span>since plugin installed</span></div>';
        echo '<div class="stat-value" '.($redirect_stats_all['total'] == 0 ? 'style="color:#cfd4da"':'').'>' . $redirect_stats_all['total'] . '</div>';
        echo '</div>';

        $redirect_stats_24h = WF301_stats::get_stats('redirect', 1);
        echo '<div class="wf301-header-stat">';
        echo '<div class="stat-title">Redirects<span>in last 24h</span></div>';
        echo '<div class="stat-value" '.($redirect_stats_24h['total'] == 0 ? 'style="color:#cfd4da"':'').'>' . $redirect_stats_24h['total'] . '</div>';
        echo '</div>';
        echo '</div>';

        echo '<h1></h1>';

        echo '<form method="post" action="options.php" enctype="multipart/form-data" id="wf301_form">';
        settings_fields(WF301_OPTIONS_KEY);

        $tabs = array();
        if ($wf_301_licensing->is_active()) {
            $tabs[] = array('id' => 'wf301_redirect', 'icon' => 'wf301-icon wf301-redirect', 'class' => '', 'label' => __('Redirect Rules', '301-redirects'), 'callback' => array('WF301_tab_redirect', 'display'));
            $tabs[] = array('id' => 'wf301_redirect_log', 'icon' => 'wf301-icon wf301-log', 'class' => '', 'label' => __('Redirect Log', '301-redirects'), 'callback' => array('WF301_tab_redirect_log', 'display'));
            $tabs[] = array('id' => 'wf301_404_log', 'icon' => 'wf301-icon wf301-404', 'class' => '', 'label' => __('404 Log', '301-redirects'), 'callback' => array('WF301_tab_404_log', 'display'));
            $tabs[] = array('id' => 'wf301_settings', 'icon' => 'wf301-icon wf301-settings', 'class' => '', 'label' => __('Settings', '301-redirects'), 'callback' => array('WF301_tab_settings', 'display'));
            if (WF301_utility::whitelabel_filter()) {
                $tabs[] = array('id' => 'wf301_support', 'icon' => 'wf301-icon wf301-support', 'class' => '', 'label' => __('Support', '301-redirects'), 'callback' => array('WF301_tab_support', 'display'));
                $tabs[] = array('id' => 'wf301_license', 'icon' => 'wf301-icon wf301-check', 'class' => '', 'label' => __('License', '301-redirects'), 'callback' => array('WF301_tab_license', 'display'));
            }
        } else {
            $tabs[] = array('id' => 'wf301_license', 'icon' => 'wf301-icon wf301-check', 'class' => '', 'label' => __('License', '301-redirects'), 'callback' => array('WF301_tab_license', 'display'));
            $tabs[] = array('id' => 'wf301_support', 'icon' => 'wf301-icon wf301-support', 'class' => '', 'label' => __('Support', '301-redirects'), 'callback' => array('WF301_tab_support', 'display'));
        }

        $tabs = apply_filters('wf301_tabs', $tabs);

        echo '<div id="wf301_tabs" class="ui-tabs" style="display: none;">';
        echo '<ul class="wf301-main-tab">';
        foreach ($tabs as $tab) {
            echo '<li><a href="#' . $tab['id'] . '" class="' . $tab['class'] . '"><span class="icon"><i class="' . $tab['icon'] . '"></i></span></span><span class="label">' . $tab['label'] . '</span></a></li>';
        }
        echo '</ul>';

        foreach ($tabs as $tab) {
            if (is_callable($tab['callback'])) {
                echo '<div style="display: none;" id="' . $tab['id'] . '">';
                call_user_func($tab['callback']);
                echo '</div>';
            } else {
                echo $tab['id'] . 'NF';
            }
        } // foreach

        echo '</div>'; // wf301_tabs
        echo '</form>';

        echo '</div>'; // wrap

        // onboarding
        if ($wf_301_licensing->is_active() && $options['onboarding'] == 0) {
            WF301_onboarding::display();
        }
    } // options_page

    /**
     * Settings footer submit button HTML
     *
     * @since 5.0
     * 
     * @return null
     */
    static function footer_save_button()
    {
        echo '<p class="submit">';
        echo '<button class="button button-primary button-large">' . __('Save Changes', '301-redirects') . ' <i class="wf301-icon wf301-checkmark"></i></button>';
        echo '</p>';
    } // footer_save_button

    /**
     * Reset pointers
     *
     * @since 5.0
     * 
     * @return null
     */
    static function reset_pointers()
    {
        $pointers = array();
        $pointers['welcome'] = array('target' => '#menu-settings', 'edge' => 'left', 'align' => 'right', 'content' => 'Thank you for installing the <b style="font-weight: 800; font-variant: small-caps;">301 Redirects Pro</b> plugin! Please open <a href="' . admin_url('options-general.php?page=301redirects') . '">Settings - 301 Redirects Pro</a> to add your redirect rules.');

        update_option(WF301_POINTERS_KEY, $pointers);
    } // reset_pointers


    /**
     * Get rebranding data, return false if not rebranded
     *
     * @since 5.0
     * 
     * @return bool|string requested property or false if not rebranded
     */
    static function get_rebranding($key = false)
    {
        global $wf_301_licensing;
        $license = $wf_301_licensing->get_license();

        if (is_array($license) && array_key_exists('meta', $license) && is_array($license['meta']) && array_key_exists('rebrand', $license['meta']) && !empty($license['meta']['rebrand'])) {
            if (!empty($key)) {
                return $license['meta']['rebrand'][$key];
            }
            return $license['meta']['rebrand'];
        } else {
            return false;
        }
    }
} // class
