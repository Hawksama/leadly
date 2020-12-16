<?php
/*
 * 301 Redirects Pro
 * Logging functions
 * (c) WebFactory Ltd, 2019 - 2021, www.webfactoryltd.com
 */

class WF301_stats extends WF301
{
    static public $stats_cutoff = 1;
    
    /**
     * Get statistics
     * 
     * @since 5.0
     *
     * @param string $type redirect|404
     * @param int $ndays period for statistics
     * @return bool
     */
    static function get_stats($type = "redirect", $ndays = 60)
    {
        global $wpdb;
        $options = WF301_setup::get_options();

        if (!$ndays) {
            if ($type == "redirect") {
                if (substr($options['retention_redirect'], 0, 3) == 'day') {
                    $tmp = explode('-', $options['retention_redirect']);
                    $ndays = (int) $tmp[1];
                } else {
                    $ndays = 60;
                }
            } else {
                if (substr($options['retention_404'], 0, 3) == 'day') {
                    $tmp = explode('-', $options['retention_404']);
                    $ndays = (int) $tmp[1];
                } else {
                    $ndays = 60;
                }
            }
        }
        $days = array();
        for ($i = $ndays; $i >= 0; $i--){
            $days[date("Y-m-d", strtotime('-' . $i . ' days'))] = 0;
        }

        if ($type == 'redirect') {
            $results = $wpdb->get_results("SELECT COUNT(*) as count,DATE_FORMAT(created, '%Y-%m-%d') AS date FROM " . $wpdb->wf301_redirect_logs . " GROUP BY DATE_FORMAT(created, '%Y%m%d')");
        } else {
            $results = $wpdb->get_results("SELECT COUNT(*) as count,DATE_FORMAT(created, '%Y-%m-%d') AS date FROM " . $wpdb->wf301_404_logs . " GROUP BY DATE_FORMAT(created, '%Y%m%d')");
        }

        $total = 0;

        foreach ($results as $day) {
            if(array_key_exists($day->date, $days)){
                $days[$day->date] = $day->count;
                $total += $day->count;
            }
        }
        
        if ($total < self::$stats_cutoff) {
            $stats['days'] = array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20);
            $stats['count'] = array(3, 4, 67, 76, 45, 32, 134, 6, 65, 65, 56, 123, 156, 156, 123, 156, 67, 88, 54, 178);
            $stats['total'] = $total;

            return $stats;
        }

        $stats = array('days' => array(), 'count' => array(), 'total' => 0);
        foreach ($days as $day => $count) {
            $stats['days'][] = $day;
            $stats['count'][] = $count;
            $stats['total'] += $count;
        }
        $stats['period'] = $ndays;
        return $stats;
    } // get_stats

    static function prepare_stats($ndays = 30)
    {
        
        global $wpdb;
        
        WF301_setup::register_custom_tables();

        $stats = array('redirect' => array('count' => array()), '404' => array('count' => array()));
        for ($i = $ndays; $i >= 0; $i--){
            $stats['redirect']['count'][date("Y-m-d", strtotime('-' . $i . ' days'))] = 0;
            $stats['404']['count'][date("Y-m-d", strtotime('-' . $i . ' days'))] = 0;
        }

        $redirects = $wpdb->get_results("SELECT COUNT(*) as count,DATE_FORMAT(created, '%Y-%m-%d') AS date FROM " . $wpdb->wf301_redirect_logs . " GROUP BY DATE_FORMAT(created, '%Y%m%d')");
        foreach ($redirects as $day) {
            if(array_key_exists($day->date, $stats['redirect']['count'])){
                $stats['redirect']['count'][$day->date] = $day->count;
            }
        }

        $r404 = $wpdb->get_results("SELECT COUNT(*) as count,DATE_FORMAT(created, '%Y-%m-%d') AS date FROM " . $wpdb->wf301_404_logs . " GROUP BY DATE_FORMAT(created, '%Y%m%d')");
        foreach ($r404 as $day) {
            if(array_key_exists($day->date, $stats['404']['count'])){
                $stats['404']['count'][$day->date] = $day->count;
            }
        }
        
        $stats['redirect']['countries'] = self::get_top_countries('redirect');
        $stats['redirect']['browsers'] = self::get_top_browsers('redirect');
        $stats['redirect']['devices'] = self::get_top_devices('redirect');
        $stats['redirect']['traffic'] = self::get_top_bots('redirect');
        
        $stats['404']['countries'] = self::get_top_countries('404');
        $stats['404']['browsers'] = self::get_top_browsers('404');
        $stats['404']['devices'] = self::get_top_devices('404');
        $stats['404']['traffic'] = self::get_top_bots('404');
        return $stats;
    } // prepare_stats

    /**
     * Get top countries
     * 
     * @since 5.0
     *
     * @param string $type redirect/404
     * @param int $limit number of countries to return
     * @return array of countries with percent
     */
    static function get_top_countries($type = 'redirect', $limit = 10)
    {
        global $wpdb;

        if ($type == 'redirect') {
            $table = $wpdb->wf301_redirect_logs;
        } else {
            $table = $wpdb->wf301_404_logs;
        }

        $countries_db = $wpdb->get_results("SELECT location,COUNT(*) AS count FROM " . $table . " GROUP BY location ORDER BY COUNT DESC");

        $countries = array();
        $countries_percent = array();
        $other = 0;
        $total = 0;
        foreach ($countries_db as $country) {
            $total += $country->count;
            if (empty($country->location) || $limit == 1) {
                $other += $country->count;
            } else {
                $countries[$country->location] = $country->count;
                $limit--;
            }
        }

        if ($total < self::$stats_cutoff) {
            $countries_percent = array(
                'France' => '15',
                'United States' => '8',
                'China' => '6',
                'Germany' => '3',
                'Russia' => '1'
            );
            return $countries_percent;
        }

        if ($other > 0) {
            $countries['Other'] = $other;
        }

        foreach ($countries as $country => $count) {
            $countries_percent[$country] = round($count / $total * 1000) / 10;
        }

        return $countries_percent;
    } // get_top_countries

    /**
     * Get top browsers
     * 
     * @since 5.0
     *
     * @param string $type redirect/404
     * @param int $limit number of browsers to return
     * @return array of browsers with percent
     */
    static function get_top_browsers($type = 'redirect', $limit = 10)
    {
        global $wpdb;

        if ($type == 'redirect') {
            $table = $wpdb->wf301_redirect_logs;
        } else {
            $table = $wpdb->wf301_404_logs;
        }

        $browsers_db = $wpdb->get_results("SELECT browser,COUNT(*) AS count FROM " . $table . " WHERE device!='bot' GROUP BY browser ORDER BY COUNT DESC");

        $browsers = array();
        $browsers_percent = array();
        $other = 0;
        $total = 0;
        foreach ($browsers_db as $browser) {
            $total += $browser->count;
            if (empty($browser->browser) || $limit == 1) {
                $other += $browser->count;
            } else {
                $browsers[$browser->browser] = $browser->count;
                $limit--;
            }
        }

        if ($total < self::$stats_cutoff) {
            $browsers_percent = array(
                'Chrome' => '35',
                'Internet Explorer' => '34',
                'Firefox' => '24',
                'Safari' => '2',
                'Opera' => '1'
            );
            return $browsers_percent;
        }

        if ($other > 0) {
            $browsers['Other'] = $other;
        }

        foreach ($browsers as $browser => $count) {
            $browsers_percent[$browser] = round($count / $total * 1000) / 10;
        }

        return $browsers_percent;
    } // get_top_browsers

    /**
     * Get top devices
     * 
     * @since 5.0
     *
     * @param string $type redirect/404
     * @param int $limit number of devices to return
     * @return array of devices with percent
     */
    static function get_top_devices($type = 'redirect')
    {
        global $wpdb;

        if ($type == 'redirect') {
            $table = $wpdb->wf301_redirect_logs;
        } else {
            $table = $wpdb->wf301_404_logs;
        }

        $devices_db = $wpdb->get_results("SELECT device,COUNT(*) AS count FROM " . $table . " WHERE device!='bot' GROUP BY device ORDER BY COUNT DESC");
        
        $devices = array();
        $devices_percent = array();
        $other = 0;
        $total = 0;
        foreach ($devices_db as $device) {
            $total += $device->count;
            $devices[$device->device] = $device->count;
        }
        
        if ($total < self::$stats_cutoff) {
            $devices_percent = array(
                'mobile' => 14,
                'tablet' => 26,
                'desktop' => 60
            );
        }

        if ($other > 0) {
            $devices['other'] = $other;
        }
        foreach ($devices as $device => $count) {
            $devices_percent[$device] = round($count / $total, 2) * 100;
        }

        return $devices_percent;
    } // get_top_devices

    /**
     * Get device stats
     * 
     * @since 5.0
     *
     * @param string $type redirect/404
     * @return array of 2 sub-arrays with labels and devices for charts
     */
    static function get_device_stats($type)
    {
        $devices = self::get_top_devices($type);
        $device_stats = array('labels' => array(), 'percent' => array());
        foreach ($devices as $device => $percent) {
            $device_stats['labels'][] = ucfirst($device);
            $device_stats['percent'][] = $percent;
        }

        return $device_stats;
    } // get_device_stats

    /**
     * Get top bots
     * 
     * @since 5.0
     *
     * @param string $type redirect/404
     * @param int $limit number of bots to return
     * @return array of bots with percent
     */
    static function get_top_bots($type = 'redirect', $limit = 10)
    {
        global $wpdb;

        if ($type == 'redirect') {
            $table = $wpdb->wf301_redirect_logs;
        } else {
            $table = $wpdb->wf301_404_logs;
        }

        $bots_db = $wpdb->get_results("SELECT bot,COUNT(*) AS count FROM " . $table . " GROUP BY bot ORDER BY COUNT DESC");

        $bots = array();
        $bots_percent = array();
        $other = 0;
        $human = 0;
        $total = 0;

        foreach ($bots_db as $bot) {
            $total += $bot->count;
            if (empty($bot->bot) || $limit == 1) {
                $human += $bot->count;
            } else if ($limit == 1) {
                $other += $bot->count;
            } else {
                $bots[$bot->bot] = $bot->count;
                $limit--;
            }
        }

        if ($total < self::$stats_cutoff) {
            $bots_percent = array(
                'Human' => '35',
                'Google' => '34',
                'Bing' => '24',
                'Archive' => '2',
                'Other' => '1'
            );
            return $bots_percent;
        }

        if ($human > 0) {
            $bots['Human'] = $human;
        }

        arsort($bots);

        if ($other > 0) {
            $bots['Other'] = $other;
        }

        foreach ($bots as $bot => $count) {
            $bots_percent[$bot] = round($count / $total * 1000) / 10;
        }

        return $bots_percent;
    }

    /**
     * Function to print stats in admin tabs
     * 
     * @since 5.0
     *
     * @param string $type redirect/404
     * @return null
     */
    static function print_stats($type = 'redirect')
    {
        $countries = self::get_top_countries($type);
        echo '<div class="wf301-stats-column">';
        echo '<h3>Top Countries</h3>';
        echo '<table class="wf301-stats-table">';
        foreach ($countries as $country => $count) {
            echo '<tr><td>' . ($country != 'Other' ? '<img src="' . WF301_PLUGIN_URL . 'images/flags/' . strtolower(WF301_utility::country_name_to_code($country)) . '.png" /> ' : '<img src="' . WF301_PLUGIN_URL . 'images/flags/other.png" /> ') . $country . '</td><td>' . $count . '%</td></tr>';
        }
        echo '</table>';
        echo '</div>';

        $browsers = self::get_top_browsers($type);
        echo '<div class="wf301-stats-column">';
        echo '<h3>Top Browsers</h3>';
        echo '<table class="wf301-stats-table">';
        foreach ($browsers as $browser => $count) {
            echo '<tr><td>' . $browser . '</td><td>' . $count . '%</td></tr>';
        }
        echo '</table>';
        echo '</div>';



        echo '<div class="wf301-stats-column">';
        echo '<h3>Top Devices</h3>';
        echo '<div class="wf301-pie-chart-wrapper"><canvas id="wf301_' . $type . '_devices_chart"></canvas></div>';
        echo '</div>';


        $bots = self::get_top_bots($type);
        echo '<div class="wf301-stats-column">';
        echo '<h3>Traffic Type</h3>';
        echo '<table class="wf301-stats-table">';
        foreach ($bots as $bot => $count) {
            echo '<tr><td>' . ($bot == 'Human' ? '<span class="human">Human</span>' : $bot) . '</td><td>' . $count . '%</td></tr>';
        }
        echo '</table>';
        echo '</div>';
    }
} // class
