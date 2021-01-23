<?php
/*
 * 301 Redirects Pro
 * Logging functions
 * (c) Web factory Ltd, 2015 - 2019
 */

class WF301_logs extends WF301
{
    static $updated_posts = array();

    /**
     * Format From URL
     *
     * @since 5.0
     * 
     * @return string URL
     * 
     */
    static function format_from_url($string)
    {
        $complete = home_url() . '/' . $string;
        list($uprotocol, $uempty, $uhost, $from) = explode('/', $complete, 4);
        $from = '/' . $from;
        return rtrim($from, '/');
    }

    /**
     * Log Redirect event
     *
     * @since 5.0
     * 
     * @return null
     * 
     */
    static function log_redirect($redirect_id = null, $url = null, $url_to = null, $last_count = 0, $auto = false)
    {
        global $wp, $wpdb;

        if (!$url) {
            $url = $wp->request;
        }

        $agent = WF301_utility::parse_user_agent_array();

        $redirect = array(
            'url' => $url,
            'sent_to' => $url_to,
            'agent' => @$_SERVER['HTTP_USER_AGENT'],
            'referrer' => @$_SERVER['HTTP_REFERER'],
            'redirect_id' => $redirect_id,
            'location' => WF301_utility::getUserCountry(),
            'ip' => WF301_utility::getUserIP(),
            'auto' => $auto?1:0
        );

        $redirect = array_merge($redirect, $agent);

        $wpdb->insert(
            $wpdb->wf301_redirect_logs,
            $redirect
        );

        if($redirect_id){
            $wpdb->update(
                $wpdb->wf301_redirect_rules,
                array('last_count' => ++$last_count, 'last_access' => current_time('mysql')),
                array('id' => $redirect_id)
            );
        }   

        self::send_redirect_log_reports($wpdb->insert_id);
    } // log_404

    /**
     * Log 404 event
     *
     * @since 5.0
     * 
     * @return null
     * 
     */
    static function log_404($url = null)
    {
        global $wp, $wpdb;

        if (!$url) {
            $url = $wp->request;
        }
        
        $agent = WF301_utility::parse_user_agent_array();

        $entry404 = array(
            'url' => $url,
            'agent' => @$_SERVER['HTTP_USER_AGENT'],
            'referrer' => isset($_SERVER['HTTP_REFERER']) ? @$_SERVER['HTTP_REFERER'] : '',
            'location' => WF301_utility::getUserCountry(),
            'ip' => WF301_utility::getUserIP()
        );

        $entry404 = array_merge($entry404, $agent);

        $wpdb->insert(
            $wpdb->wf301_404_logs,
            $entry404
        );

        self::send_404_log_reports($wpdb->insert_id);
    } // log_404

    /**
     * Send 404 report email
     *
     * @since 5.0
     * 
     * @param int $last_id last ID sent it previous report
     * 
     * @return none
     */
    static function send_404_log_reports($last_id)
    {
        global $wpdb;
        $options = WF301_setup::get_options();
        $body = '';

        if (empty($options['404_email_reports']) || (int) $options['404_email_reports'] == 0 || empty($last_id)) {
            return false;
        }

        if (time() - $options['404_last_reported_time'] > (int) $options['404_email_reports']) {

            $events = $wpdb->get_results('SELECT * FROM ' . $wpdb->wf301_404_logs . ' WHERE id > ' . $options['404_last_reported_event'] . ' ORDER BY id DESC');

            if (!$events) {
                return;
            }

            $update['404_last_reported_event'] = $events[0]->id;
            $update['404_last_reported_time'] = time();
            update_option(WF301_OPTIONS_KEY, array_merge($options, $update));

            $headers = array('Content-Type: text/html; charset=UTF-8');
            $body .= '<b>Recent events on ' . get_bloginfo('name') . ':</b> (<a href="' . admin_url('options-general.php?page=301redirects#wf301_404_log') . '">more details are available in WordPress admin</a>)<br>';
            $body .= '<ul>';
            foreach ($events as $event) {
                $body .= '<li>';
                $body .= '404 at ' . $event->url;
                $body .= ' On ' . date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($event->created));
                if (!empty($event->referrer)) {
                    $body .= ' from ' . $event->referrer;
                }
                $body .= '</li>';
            }
            $body .= '</ul>';
            $body .= '<p>301 Redirects Pro - 404 Log email report settings can be adjusted in <a href=' . admin_url('options-general.php?page=301redirects#wf301_404_log') . '>WordPress admin</a>.</p>';

            return wp_mail($options['email_to'], sprintf(__('[%s] 301 Redirects Pro - 404 Log report'), wp_specialchars_decode(get_option('blogname'), ENT_QUOTES)), $body, $headers);
        }
    } // send_404_log_reports

    /**
     * Send Redirect report email
     *
     * @since 5.0
     * 
     * @param int $last_id last ID sent it previous report
     * 
     * @return none
     */
    static function send_redirect_log_reports($last_id)
    {
        global $wpdb;
        $options = WF301_setup::get_options();
        $body = '';

        if (empty($options['redirect_email_reports']) || (int) $options['redirect_email_reports'] == 0 || empty($last_id)) {
            return false;
        }

        if (time() - $options['redirect_last_reported_time'] > (int) $options['redirect_email_reports']) {

            $events = $wpdb->get_results('SELECT * FROM ' . $wpdb->wf301_redirect_logs . ' WHERE id > ' . $options['redirect_last_reported_event'] . ' ORDER BY id DESC');

            if (!$events) {
                return;
            }

            $update['redirect_last_reported_event'] = $events[0]->id;
            $update['redirect_last_reported_time'] = time();
            update_option(WF301_OPTIONS_KEY, array_merge($options, $update));

            $headers = array('Content-Type: text/html; charset=UTF-8');
            $body .= '<b>Recent events on ' . get_bloginfo('name') . ':</b> (<a href="' . admin_url('options-general.php?page=301redirects#wf301_redirect_log') . '">more details are available in WordPress admin</a>)<br>';
            $body .= '<ul>';
            foreach ($events as $event) {
                $body .= '<li>';
                $body .= 'Redirect from ' . $event->url;
                $body .= ' to ' . $event->sent_to;
                $body .= ' On ' . date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($event->created));
                if (!empty($event->referrer)) {
                    $body .= ', referred by ' . $event->referrer;
                }
                $body .= '</li>';
            }
            $body .= '</ul>';
            $body .= '<p>301 Redirects - Redirect Log email report settings can be adjusted in <a href=' . admin_url('options-general.php?page=301redirects#wf301_redirect_log') . '>WordPress admin</a>.</p>';
            return wp_mail($options['email_to'], sprintf(__('[%s] 301 Redirects Pro - Redirect Log report'), wp_specialchars_decode(get_option('blogname'), ENT_QUOTES)), $body, $headers);
        }
    } // send_redirect_log_reports

    /**
     * Trim 404 logs
     * 
     * @since 5.0
     *
     * @param bool $force force trimming, default is random 10% of requests
     * @param int $days period for statistics
     * @return bool
     */
    static function trim_404_log($force = false)
    {
        global $wpdb;
        $options = WF301_setup::get_options();

        if ($force === true) {
            $wpdb->query('TRUNCATE TABLE ' . $wpdb->wf301_404_logs);
        }

        if (rand(0, 100) < 90) {
            return false;
        }

        if (!$options['retention_404'] || $options['retention_404'] == '4-ever') {
            return false;
        } elseif (substr($options['retention_404'], 0, 3) == 'cnt') {
            $tmp = explode('-', $options['retention_404']);
            $tmp = (int) $tmp[1];

            $id = $wpdb->get_var('SELECT id FROM ' . $wpdb->wf301_404_logs . ' ORDER BY id DESC LIMIT ' . $tmp . ', 1');
            if ($id) {
                $wpdb->query('DELETE FROM ' . $wpdb->wf301_404_logs . ' WHERE id < ' . $id);
            }
        } else {
            $tmp = explode('-', $options['retention_404']);
            $tmp = (int) $tmp[1];
            $wpdb->query('DELETE FROM ' . $wpdb->wf301_404_logs . ' WHERE created < DATE_SUB(NOW(), INTERVAL ' . $tmp . ' DAY)');
        }

        return true;
    } // trim_404_log

    /**
     * Trim Redirect logs
     * 
     * @since 5.0
     *
     * @param bool $force force trimming, default is random 10% of requests
     * @param int $days period for statistics
     * @return bool
     */
    static function trim_redirect_log($force = false)
    {
        global $wpdb;
        $options = WF301_setup::get_options();

        if ($force === true) {
            $wpdb->query('TRUNCATE TABLE ' . $wpdb->wf301_redirect_logs);
        }

        if (rand(0, 100) < 90) {
            return false;
        }

        if (!$options['retention_redirect'] || $options['retention_redirect'] == '4-ever') {
            return false;
        } elseif (substr($options['retention_redirect'], 0, 3) == 'cnt') {
            $tmp = explode('-', $options['retention_redirect']);
            $tmp = (int) $tmp[1];

            $id = $wpdb->get_var('SELECT id FROM ' . $wpdb->wf301_redirect_logs . ' ORDER BY id DESC LIMIT ' . $tmp . ', 1');
            if ($id) {
                $wpdb->query('DELETE FROM ' . $wpdb->wf301_redirect_logs . ' WHERE id < ' . $id);
            }
        } else {
            $tmp = explode('-', $options['retention_redirect']);
            $tmp = (int) $tmp[1];
            $wpdb->query('DELETE FROM ' . $wpdb->wf301_redirect_logs . ' WHERE created < DATE_SUB(NOW(), INTERVAL ' . $tmp . ' DAY)');
        }

        return true;
    } // trim_redirect_log

} // class
