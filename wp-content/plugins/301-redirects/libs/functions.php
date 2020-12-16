<?php
/*
 * 301 Redirects Pro
 * Main plugin functions
 * (c) WebFactory Ltd, 2019 - 2021, www.webfactoryltd.com
 */

class WF301_functions extends WF301
{
    static $updated_posts = array();

    /**
     * Redirect if any matching rules found, otherwise process 404 rules
     *
     * @since 5.0
     * 
     * @return null
     *     
     * 
     */
    static function redirect()
    {
        global $wp;
        $options = WF301_setup::get_options();

        if (($options['disable_for_users'] == '1' && is_user_logged_in()) || $options['disable_all_redirections'] == '1' || is_admin()) return false;

        $redirects = self::get_redirects(true); // True for only active redirects.

        // Get current url
        $url_request = self::get_url();

        if(self::is_url_ignored($url_request)){
            return false;
        }

        $query_string = explode('?', $url_request);
        $url_request_nq = $query_string[0];
        $query_string = (isset($query_string[1])) ? $query_string[1] : false;

        foreach ($redirects as $redirect) {
            $from = urldecode($redirect->url_from);
            $to = urldecode($redirect->url_to);
            if ($redirect->case_insensitive == 'enabled') {
                $formatted_from_url = strtolower(self::format_from_url(trim($from)));
                $formatted_to_url = strtolower(self::format_to_url(trim($to)));
            } else {
                $formatted_from_url = self::format_from_url(trim($from));
                $formatted_to_url = self::format_to_url(trim($to));
            }
            
            if ($redirect->query_parameters == 'exact' || $redirect->regex == 'enabled') {
                $compare_url_request = $url_request;
            } else {
                $compare_url_request = $url_request_nq;
            }

            if($redirect->regex == 'enabled'){
                $regex_matches = null;
            } else {
                $regex_matches = false;
            }

            if ($redirect->status != 'disabled' && self::wild_compare(stripslashes($formatted_from_url), rtrim(trim($compare_url_request), '/'), $redirect->case_insensitive == 'enabled', $regex_matches)) {

                if ($redirect->query_parameters == 'utm' && strlen($query_string) > 0) {
                  $query_string_array = explode('&', $url_request);
                  $query_string_new = [];

                  foreach($query_string_array as $value){
                    $exp_key = explode('_', $value);
                    if($exp_key[0] == 'utm'){
                        $query_string_new[] = $value;
                    }
                  }
                  
                  if (count($query_string_new) > 0) {
                    $query_string = implode('&', $query_string_new);
                  }
                }

                WF301_logs::log_redirect($redirect->id, $wp->request, $to, $redirect->last_count, false);
                header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
                header("Cache-Control: post-check=0, pre-check=0", false);
                header("Pragma: no-cache");

                if ($redirect->type == 'cloaking') {
                    echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
                    <html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
                    <head><meta http-equiv="Content-Type" content="application/xhtml+xml; charset=UTF-8"/>
                    <title>' . get_bloginfo('name') . '</title>
                    <style type="text/css">
                        html, body { padding: 0; margin: 0; border: 0; height: 100%; overflow: hidden; }
                        iframe { width: 100%; height: 100%; border: 0; margin: 0; padding: 0; }
                    </style></head>
                    <body><iframe src="' . $to . '"></iframe></body></html>';
                    exit();
                } else {
                    switch($redirect->type){
                        case 301: $status_text = 'Moved Permanently'; break;
                        case 302: $status_text = 'Moved Temporarily'; break;
                        case 303: $status_text = 'See Other'; break;
                        case 304: $status_text = 'Not Modified'; break;
                        case 307: $status_text = 'Temporary Redirect'; break;
                        case 308: $status_text = 'Permanent Redirect'; break;
                    }
                    header('HTTP/1.1 ' . $redirect->type . ' ' . $status_text);
                }

                $to = (strlen($query_string) > 0 && $redirect->query_parameters != 'ignore') ? $to . "?" . $query_string : $to;

	            if (isset($regex_matches) && $regex_matches !== false) {
		            foreach ($regex_matches as $key => $value) {
						$to = str_replace("[$key]", trim ($value, ' /'), $to);
		            }
	            }

                header('Location: ' . $to, true, (int) $redirect->status);
                exit();
            }
        }

        if (is_404()) {
            $options = WF301_setup::get_options();

            $http_response_code = http_response_code();

            // if we have a 302 here is because this is already a redirect so let's bypass this thing and move on to
            // what really we are here for. No more loop redirect. It's useless.
            if ($options['autoredirect_404'] == '1' && $http_response_code !== "302") {
                self::autoredirect_404($url_request);
            }

            WF301_logs::log_404(null, false);

            if (!empty($options['redirect_url_404'])){
                header('HTTP/1.1 ' . '302 Moved Temporarily');
                header('Location: ' . $options['redirect_url_404'], true, 302);
                exit();
            }
            
            if ($options['page_404'] && get_posts(array('post_type' => 'page', 'status' => 'publish', 'page_id' => $options['page_404']))) {
                global $wp_query, $post;
                $post = get_post($options['page_404']);
                header('HTTP/1.1 ' . '404');
                $wp_query = new WP_Query(array('page_id' => $options['page_404'] ));
                $wp_query->is_page = true;
            }
        } // is_404
    } // redirect

    static function is_url_ignored($url){
        $options = WF301_setup::get_options();
        if(empty($options['ignore_urls'])){
            return false;
        }

        $ignored_urls = explode("\n", $options['ignore_urls']);

        if(in_array($url, $ignored_urls)){
            return true;
        }

        return false;
    }

    static function autoredirect_404($url_request)
    {
        $agent = WF301_utility::parse_user_agent_array();
        
        if ($agent['device'] == 'bot' && 
            strpos($agent['bot'], 'Google') === false && 
            strpos($agent['bot'], 'Bing') === false && 
            strpos($agent['bot'], 'Yahoo') === false && 
            strpos($agent['bot'], 'Yandex') === false && 
            strpos($agent['bot'], 'Baiduspider') === false && 
            strpos($agent['bot'], 'Wolframalpha') === false && 
            strpos($agent['bot'], 'Duck') === false) {
            return;
        }


        $indexed_slugs = get_transient('wf301_indexed_slugs');
        if (false === $indexed_slugs) {
            $indexed_slugs = self::refresh_indexed_slugs();
        }


        $url_request = rtrim($url_request,'/');
        $url_parts = parse_url(rtrim($url_request,'/'));

        $url_parts['path'] = str_replace('//', '/', $url_parts['path']);
        if (substr($url_parts['path'], -5, 1) == '.' || substr($url_parts['path'], -4, 1) == '.' || substr($url_parts['path'], -3, 1) == '.' || substr($url_parts['path'], -2, 1) == '.') {
            //echo '<span style="color:#F00">'. htmlentities($url_request) . ' is file! </span><br />';
        } else if (strpos($url_parts['path'], 'wp-content') > 0 || strpos($url_parts['path'], 'wp-admin') > 0 || strpos($url_parts['path'], 'wp-inlcudes') > 0) {
            //echo '<span style="color:#007cff">'. htmlentities($url_request) . ' is WP URL! </span><br />';
        } else {
            $scores = array();
            
            $url_request = basename($url_request);
            foreach ($indexed_slugs as $id => $url) {
                $perc = 0;
                similar_text($url_request, $url, $perc);
                $scores[$id] = $perc;
            }

            arsort($scores);

            if (reset($scores) > 65) {
                WF301_logs::log_redirect(0, $url_request, get_permalink(key($scores)), 0, true);
                header('Location: ' . get_permalink(key($scores)), true, 302);
                exit();
            }
        }
    }

    /**
     * Compare URLs with wild characters
     *
     * @since 5.0
     * 
     * @return bool URLs match
     * 
     */
    static function wild_compare($pattern, $path, $ignoreCase = false, &$matches = null)
    {
    	if (fnmatch($pattern, $path)) {
    		return true;
        }
        
        if($matches === false){
            return false;
        }

        $matches = false;

	    $pattern = str_replace('.*', '*', $pattern);

        $expr = preg_replace_callback('/[\\\\^$.[\\]|()?*+{}\\-\\/]/', function($matches) {
          switch ($matches[0]) {
            case '*':
              return '.*';
            case '/':
              return '\/';
            default:
              return $matches[0];
          }
        }, $pattern);

        $expr = str_replace('\/^', '^', $expr);
        $expr = str_replace('\\\'', '\'', $expr);

	    $expr = '/' . $expr . '$/';
	    if ($ignoreCase) {
		    $expr .= 'i';
        };
        
	    return (bool) preg_match($expr, $path, $matches);
    } // wild_compare

    /**
     * Format From URL
     *
     * @since 5.0
     * 
     * @return string URL
     * 
     */
    static function format_from_url($url)
    {
        $complete = home_url() . '/' . $url;
        list($uprotocol, $uempty, $uhost, $from) = explode('/', $complete, 4);
        $from = '/' . $from;
        return str_replace('//', '/', rtrim($from, '/'));
    }

    /**
     * Format To URL
     *
     * @since 5.0
     * 
     * @return string URL
     * 
     */
    static function format_to_url($url)
    {
        if (strpos($url, 'http') === 0 || strpos($url, 'ftp') === 0) {
            return $url;
        }

        return rtrim(home_url(), '/') . '/' . ltrim($url, '/');
    }

    /**
     * Get current URL
     *
     * @since 5.0
     * 
     * @return string URL
     * 
     */
    public static function get_url()
    {
	    global $original_request_uri;

	    return $original_request_uri;
    }

    /**
     * Get formatted Full URL
     *
     * @since 5.0
     * 
     * @return string URL
     * 
     */
    public static function get_formatted_full_url($url)
    {
        if (strpos($url, 'http') === 0 || strpos($url, 'ftp') === 0) {
            return $url;
        }

        return str_replace('*', '', rtrim(home_url(), '/') . '/' . ltrim($url, '/'));
    }

    /**
     * Get redirects array
     *
     * @since 5.0
     * 
     * @return array of redirects
     * 
     */
    public static function get_redirects($active_only = false)
    {
        global $wpdb;

        $orderby = (isset($_GET['orderby']))  ?  esc_sql($_GET['orderby']) : 'id';
        $order = (isset($_GET['order']))    ? esc_sql($_GET['order']) : 'desc';
        $orderby = (in_array(strtolower($orderby), array('id', 'url_from', 'url_to', 'count'))) ? $orderby : 'id';
        $order = (in_array(strtolower($order), array('asc', 'desc'))) ? $order : 'desc';

        $query = "SELECT *
            FROM $wpdb->wf301_redirect_rules
            WHERE 1 " . (($active_only) ? "AND status != 'disabled'" : null) . "
            ORDER BY $orderby $order";
        
        $results = $wpdb->get_results($query);

        return $results;
    }

    /**
     * Get single redirects
     *
     * @since 5.0
     * 
     * @return array of redirect data
     * 
     */
    static function get_redirect($id = false)
    {
        global $wpdb;
        $query = "SELECT * FROM $wpdb->wf301_redirect_rules WHERE id='" . $id . "' ";
        $redirect = $wpdb->get_row($query);
        return (array) $redirect;
    }

    /**
     * Checks if a redirect exists for a given url_from
     *
     * @param $redirect
     * @return bool
     */
    static function redirect_exists($redirect)
    {
        global $wpdb;
        $result = $wpdb->get_row("SELECT id FROM $wpdb->wf301_redirect_rules WHERE url_from = '" . $redirect['url_from'] . "'");
        return ($result) ? true : false;
    } // redirect_exists

    /**
     * Save redirect rule
     *
     * @param $redirect
     * @return bool
     */
    static function save_redirect_rule($rule = array())
    {
        global $wpdb;

        if (array_key_exists('redirect_id', $rule) && (int) $rule['redirect_id'] > 0) {
            $redirect_id = (int) $rule['redirect_id'];
            unset($rule['redirect_id']);
            // edit redirect rule
            $wpdb->update(
                $wpdb->wf301_redirect_rules,
                $rule,
                array('id' => $redirect_id)
            );

            return $redirect_id;
        } else {
            // insert new rule
            $wpdb->insert(
                $wpdb->wf301_redirect_rules,
                $rule
            );
            return $wpdb->insert_id;
        }
    } // save_redirect_rule

    /**
     * Get site path
     *
     * @return string site path
     */
    static function get_site_path()
    {
        $path = wp_parse_url(get_site_url(), PHP_URL_PATH);

        if ($path) {
            return rtrim($path, '/') . '/';
        }

        return '/';
    } // get_site_path

    /**
     * Get full URL of redirect path
     *
     * @param string $url
     * 
     * @return string url
     */
    static function get_full_url($url)
    {
        $root = get_bloginfo('url') . '/';

        if (strpos($url, 'http') === false) {
            $url = $root . trim(ltrim($url, '/'));
        }

        return $url;
    }

    /**
     * Store post permalink before being updated so we can compare to new data
     *
     * @param int $post_id 
     * @param object $post 
     * @param object $post_before 
     * 
     * @return none
     */
    static function pre_post_update($post_id, $data)
    {
        self::$updated_posts[$post_id] = get_permalink($post_id);
    } // pre_post_update

    /**
     * Check for modified permalinks
     *
     * @param int $post_id 
     * @param object $post 
     * @param object $post_before 
     * 
     * @return none
     */
    static function post_updated($post_id, $post, $post_before)
    {
        $options = WF301_setup::get_options();

        if ($options['autoredirect_404'] == '1') {
            self::refresh_indexed_slugs();
        }

        if (isset(self::$updated_posts[$post_id]) && self::should_check_post_permalink($post, $post_before)) {
            self::check_post_permalink_changed($post_id, self::$updated_posts[$post_id]);
        }
    } // post_updated

    /**
     * Settings updated, refresh indexed slugs
     * 
     * @return none
     */
    static function options_updated_callback($old_value, $value) {
        if (is_array($old_value['redirect_cpt']) && is_array($value['redirect_cpt']) && $old_value['redirect_cpt'] != $value['redirect_cpt']){
            self::refresh_indexed_slugs();
        }
    }

    /**
     * Refreshed indexed slugs
     * 
     * @return none
     */
    static function refresh_indexed_slugs()
    {
        global $wpdb;

        $options = WF301_setup::get_options();

        $post_types = '';

        if (count($options['redirect_cpt']) > 0) {
            $post_types = implode("','", $options['redirect_cpt']);
        }

        $results_posts = $wpdb->get_results('SELECT ID,post_name FROM ' . $wpdb->posts . ' WHERE post_status = \'publish\' AND post_type in (\'' . $post_types . '\')');
        $slugs_available = array();

        foreach ($results_posts as $url) {
            $slugs_available[$url->ID] = $url->post_name;
        }

        set_transient('wf301_indexed_slugs', $slugs_available, DAY_IN_SECONDS);

        return $slugs_available;
    } // refresh_indexed_slugs

    /**
     * Check if we should verify permalink change for this post
     *
     * @param object $post 
     * @param object $post_before 
     * 
     * @return bool
     */
    static function should_check_post_permalink($post, $post_before)
    {

        if (!isset($post->ID) || !isset(self::$updated_posts[$post->ID])) {
            return false;
        }

        if ($post->post_status !== 'publish' || $post_before->post_status !== 'publish') {
            return false;
        }

        return true;
    } // should_check_post_permalink    

    /**
     * Check if permalink changed
     *
     * @param int $post_id 
     * @param object $post_before 
     * 
     * @return bool
     */
    static function has_permalink_changed($before, $after)
    {
        // Check it's not redirecting from the root
        if (self::get_site_path() === $before || $before === '/') {
            return false;
        }

        // Are the URLs the same?
        if ($before === $after) {
            return false;
        }

        return true;
    } // has_permalink_changed

    /**
     * Check if we should verify permalink change for this post
     *
     * @param int $post_id 
     * @param object $post_before 
     * 
     * @return bool
     */
    static function check_post_permalink_changed($post_id, $before)
    {
        $permalinks = self::get_changed_permalinks();

        $after  = wp_parse_url(get_permalink($post_id), PHP_URL_PATH);
        $before = wp_parse_url(esc_url($before), PHP_URL_PATH);

        // Add notice if permalink changed
        if (self::has_permalink_changed($before, $after)) {
            $permalinks[$post_id] = array('before' => $before, 'after' => $after);
            self::update_changed_permalinks($permalinks);
        }
    } // check_post_permalink_changed

    /**
     * Get changed permalinks
     *
     * @param object $post_before 
     * 
     * @return array permalinks
     */
    static function get_changed_permalinks()
    {
        $permalinks = get_option(WF301_PERMALINKS_KEY);
        return $permalinks ? $permalinks : array();
    } //get_changed_permalinks

    /**
     * Get changed permalinks
     *
     * @param array $permalinks 
     * 
     * @return none
     */
    static function update_changed_permalinks($permalinks)
    {
        update_option(WF301_PERMALINKS_KEY, $permalinks);
    } // update_changed_permalinks

    /**
     * Add rule for changed permalink
     *
     * @param array $permalinks 
     * 
     * @return none
     */
    static function add_permalink_redirect_rule($permalink_id = false)
    {
        $permalinks = self::get_changed_permalinks();
        $root = get_bloginfo('url') . '/';
        if (array_key_exists($permalink_id, $permalinks)) {
            $rule = array(
                'url_from'         => trim(rtrim(ltrim(str_replace($root, null, $permalinks[$permalink_id]['before']), '/'), '/')),
                'url_to'           => trim(rtrim(ltrim(str_replace($root, null, $permalinks[$permalink_id]['after']), '/'), '/')),
                'type'             => 301,
                'query_parameters' => 'ignore',
                'case_insensitive' => 'enabled',
                'regex'            => 'disabled',
                'status'           => 'enabled'
            );
            $rule_id = self::save_redirect_rule($rule);
            unset($permalinks[$permalink_id]);
            self::update_changed_permalinks($permalinks);
            return $rule_id;
        }
    } // add_permalink_redirect_rule

    /**
     * Dismiss permalink change notice
     *
     * @param int $post_id 
     * 
     * @return none
     */
    static function dismiss_permalink_notice($post_id)
    {
        $permalinks = self::get_changed_permalinks();
        if (array_key_exists($post_id, $permalinks)) {
            unset($permalinks[$post_id]);
            self::update_changed_permalinks($permalinks);
            return true;
        } else {
            return false;
        }
    } //get_changed_permalinks

    /**
     * Get changed permalinks notices
     *
     * @param int $pid ID of post optional if we want to only display notices for current post
     * 
     * @return array notices
     */
    static function get_changed_permalinks_notices($pid = false)
    {
        $permalinks = get_option(WF301_PERMALINKS_KEY);
        $notices = array();
        if ($permalinks) {
            foreach ($permalinks as $post_id => $permalink) {
                if ($pid === false || $pid == $post_id) {
                    $notices[] = array(
                        'text' => 'You have changed the permalink for ' . get_the_title($post_id) . ' from ' . $permalink['before'] . ' to ' . $permalink['after'] . '.',
                        'create_rule' => add_query_arg(array('action' => 'wf301_add_permalink_redirect_rule', 'permalink_id' => $post_id), admin_url('admin.php')),
                        'dismiss_notice' => add_query_arg(array('action' => 'wf301_dismiss_permalink_notice', 'permalink_id' => $post_id), admin_url('admin.php'))
                    );
                }
            }
        }

        return count($notices) ? $notices : array();
    } // get_changed_permalinks


} // class
