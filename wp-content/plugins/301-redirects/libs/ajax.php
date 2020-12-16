<?php
/*
 * 301 Redirects Pro
 * AJAX
 * (c) WebFactory Ltd, 2019 - 2021, www.webfactoryltd.com
 */

class WF301_ajax extends WF301
{

    /**
     * Run one tool via AJAX call
     *
     * @return null
     */
    static function ajax_run_tool()
    {
        global $wpdb, $wf_301_licensing;
        check_ajax_referer('wf301_run_tool');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('You are not allowed to run this action.', '301-redirects'));
        }

        $tool = trim(@$_REQUEST['tool']);

        $options = WF301_setup::get_options();
        $update['last_options_edit'] = current_time('mysql', true);
        update_option(WF301_OPTIONS_KEY, array_merge($options, $update));

        if ($tool == '404_logs') {
            self::get_404_logs();
        } else if ($tool == 'redirect_logs') {
            self::get_redirect_logs();
        } else if ($tool == 'redirect_rules') {
            self::get_redirect_rules_ajax();
        } else if ($tool == 'redirect_tags') {
            self::get_redirect_tags_ajax();
        } else if ($tool == 'submit_redirect_rule') {
            $root = get_bloginfo('url') . '/';

            if (isset($_POST['redirect_toggle'])) {
                // only change status
                $wpdb->update(
                    $wpdb->wf301_redirect_rules,
                    array('status' => ($_POST['redirect_toggle'] == 'true' ? 'enabled' : 'disabled')),
                    array('id' => $_POST['redirect_id'])
                );
                $rule['id'] = $_POST['redirect_id'];
                wp_send_json_success(array('id' => $rule['id']));
            }

            $from_url = trim(str_replace($root, null, $_POST['redirect_url_from']));
            $to_url = trim($_POST['redirect_url_to']);

            if (0 !== strpos($from_url, 'http')) {
                $from_url = '/' . ltrim($from_url, '/');
            }

            if (0 !== strpos($to_url, 'http') && 0 !== strpos($to_url, 'ftp')) {
                $to_url = '/' . ltrim($to_url, '/');
            }

            $rule = array(
                'url_from'          => $from_url,
                'url_to'            => $to_url,
                'type'              => trim($_POST['redirect_type']),
                'query_parameters'  => trim($_POST['redirect_query']),
                'case_insensitive'  => ($_POST['redirect_case_insensitive'] == 'true' ? 'enabled' : 'disabled'),
                'regex'             => ($_POST['redirect_regex'] == 'true' ? 'enabled' : 'disabled'),
                'status'            => ($_POST['redirect_enabled'] == 'true' ? 'enabled' : 'disabled'),
                'position'          => (int)$_POST['redirect_position'],
                'tags'              => isset($_POST['redirect_tags']) ? $_POST['redirect_tags'] : '',
            );

            if (isset($_POST['redirect_id']) && (int)$_POST['redirect_id'] > 0) {
                $rule['redirect_id'] = (int)$_POST['redirect_id'];
            }

            $rule['id'] = WF301_functions::save_redirect_rule($rule);
            if (!array_key_exists('created', $rule)) {
                $old_rule = WF301_functions::get_redirect($rule['id']);
                $rule['created'] = $old_rule['created'];
            }

            $update['nb_redirects'] = $wpdb->get_var('SELECT count(*) FROM ' . $wpdb->wf301_redirect_rules);
            update_option(WF301_OPTIONS_KEY, array_merge($options, $update));

            wp_send_json_success(array('id' => $rule['id'], 'row_html' => self::get_rule_row($rule)));
        } else if ($tool == 'delete_redirect_rule') {
            $wpdb->delete(
                $wpdb->wf301_redirect_rules,
                array(
                    'id' => intval($_POST['redirect_id'])
                )
            );

            $update['nb_redirects'] = $wpdb->get_var('SELECT count(*) FROM ' . $wpdb->wf301_redirect_rules);
            update_option(WF301_OPTIONS_KEY, array_merge($options, $update));

            wp_send_json_success(array('id' => $_POST['redirect_id']));
        } else if ($tool == 'delete_redirect_log') {
            $wpdb->delete(
                $wpdb->wf301_redirect_logs,
                array(
                    'id' => intval($_POST['log_id'])
                )
            );
            wp_send_json_success(array('id' => $_POST['log_id']));
        } else if ($tool == 'delete_404_log') {
            $wpdb->delete(
                $wpdb->wf301_404_logs,
                array(
                    'id' => intval($_POST['log_id'])
                )
            );
            wp_send_json_success(array('id' => $_POST['log_id']));
        } else if ($tool == 'dismiss_permalink_change') {
            if(true === WF301_functions::dismiss_permalink_notice((int)$_POST['permalink_id'])){
                wp_send_json_success();
            } else {
                wp_send_json_error();
            }
        } else if ($tool == 'get_permalink_notices') {
            wp_send_json_success(WF301_functions::get_changed_permalinks_notices((int)$_POST['permalink_id']));
        } else if ($tool == 'add_permalink_rule') {
            if ($new_rule_id = WF301_functions::add_permalink_redirect_rule((int)$_POST['permalink_id'])) {
                wp_send_json_success(array('url' => add_query_arg(array('page' => '301redirects', 'edit_rule' => $new_rule_id), admin_url('options-general.php'))));
            } else {
                wp_send_json_error();
            }
        } else if ($tool == 'wf301_dismiss_pointer') {
            delete_option(WF301_POINTERS_KEY);
        } else if ($tool == 'get_group_logs') {
            self::get_logs_group();
        } else if ($tool == 'recreate_tables') {
            $wpdb->query("DROP TABLE IF EXISTS " . $wpdb->wf301_404_logs);
            $wpdb->query("DROP TABLE IF EXISTS " . $wpdb->wf301_redirect_rules);
            $wpdb->query("DROP TABLE IF EXISTS " . $wpdb->wf301_redirect_logs);

            WF301_setup::create_custom_tables();

            WF301_admin::add_notice('recreate_tables', __('Tables have been recreated successfully.', '301-redirects'), 'success', true);
            wp_send_json_success();
        } else if ($tool == 'empty_log_404'){
            $wpdb->query("TRUNCATE TABLE " . $wpdb->wf301_404_logs);
            wp_send_json_success();
        } else if ($tool == 'empty_log_redirect'){
            $wpdb->query("TRUNCATE TABLE " . $wpdb->wf301_redirect_logs);
            wp_send_json_success();
        } else if ($tool == 'save_onboarding_settings') {
            $update['page_404'] = (int)$_POST['page_404'];
            $update['404_email_reports'] = (int)$_POST['email_reports_404'];
            $update['redirect_email_reports'] = (int)$_POST['email_reports_redirect'];
            $update['retention_404'] = sanitize_text_field($_POST['retention_404']);
            $update['retention_redirect'] = sanitize_text_field($_POST['retention_redirect']);
            $update['email_to'] = sanitize_text_field($_POST['email_to']);
            $update['autoredirect_404'] = ($_POST['autoredirect_404'] == 'true' ? 1 : 0);
            $update['logs_upload'] = ($_POST['logs_upload'] == 'true' ? 1 : 0);
            
            if($options['logs_upload'] != $update['logs_upload']){
                $wf_301_licensing->validate();
            }

            $update['onboarding'] = 1;
            update_option(WF301_OPTIONS_KEY, array_merge($options, $update));
        } else {
            wp_send_json_error(__('Unknown tool.', 'wp-reset'));
        }
        die();
    } // ajax_run_tool

    /**
     * Get rule row html
     *
     * @return string row HTML
     * 
     * @param array $data with rule settings
     */
    static function get_rule_row($data = array())
    {

        return '<tr id="' . $data['id'] . '" role="row" ' . ($data['status'] != 'enabled' ? 'class="disabled"' : '') . '>
            <td>
                <div class="toggle-wrapper tooltip" title="' . ($data['status'] == 'enabled' ? 'Disable Rule' : 'Enable Rule') . '">
                    <input class="toggle_redirect_rule" id="toggle_redirect_rule_' . $data['id'] . '" data-redirect="' . $data['id'] . '" type="checkbox" ' . ($data['status'] == 'enabled' ? 'checked' : '') . ' type="checkbox" value="1" name="redirect_enabled">
                    <label for="toggle_redirect_rule_' . $data['id'] . '" class="toggle"><span class="toggle_handler"></span></label>
                </div>
            </td>    
            <td>' . self::get_date_time(strtotime($data['created'])) . '</td>    
            <td class="dt-body-center">' . $data['type'] . '</td>
            <td><a href="' . WF301_functions::get_formatted_full_url(stripslashes($data['url_from'])) . '" target="_blank">' . htmlspecialchars(stripslashes($data['url_from'])) . '</a></td>
            <td><a href="' . WF301_functions::get_formatted_full_url(stripslashes($data['url_to'])) . '" target="_blank">' . htmlspecialchars(stripslashes($data['url_to'])) . '</a></td>
            <td class="dt-body-center">' . $data['position'] . '</td>
            <td class="dt-body-center">' . (isset($data['last_count']) && $data['last_count'] > 0 ? $data['last_count'] : 0) . '</td>
            <td class="dt-body-center">' . self::getTagHtml(explode(',', $data['tags'])) . '</td>
            <td class="dt-body-right">' . self::get_rule_actions_html($data) . '</td>
        </tr>';
    }

    static function getTagHtml($tag_array)
    {
        $tag_html = '';

        foreach ($tag_array as $tag) {
            if (trim($tag) != '')
                $tag_html .= '<button class="wf301-tag" data-tag="' . $tag . '">' . $tag . '</button>';
        }

        return $tag_html;
    }


    /**
     * Get rule row html
     *
     * @return string row HTML
     * 
     * @param array $data with rule settings
     */
    static function get_date_time($timestamp)
    {
        $interval = current_time('timestamp') - $timestamp;
        return '<span class="wf301-dt-small">'.self::humanTiming($interval, true) . '</span><br />' . date('Y/m/d', $timestamp) . ' <span class="wf301-dt-small">' . date('h:i:s A', $timestamp).'</span>';
    }


    /**
     * Get human readable timestamp like 2 hours ago
     *
     * @return int time
     * 
     * @param string timestamp
     */
    static function humanTiming($time)
    {
        $tokens = array(
            31536000 => 'year',
            2592000 => 'month',
            604800 => 'week',
            86400 => 'day',
            3600 => 'hour',
            60 => 'minute',
            1 => 'second'
        );

        if($time < 1){
            return 'just now';
        }
        foreach ($tokens as $unit => $text) {
            if ($time < $unit) continue;
            $numberOfUnits = floor($time / $unit);
            return $numberOfUnits . ' ' . $text . (($numberOfUnits > 1) ? 's' : '') . ' ago';
        }
    }


    /**
     * Get rule action buttons
     *
     * @return string actions HTML
     * 
     * @param array $data with rule settings
     */
    static function get_rule_actions_html($data = array())
    {
        $actions_html = '<div class="tooltip edit_redirect_rule" 
            data-redirect="' . $data['id'] . '" 
            data-redirect-status="' . $data['status'] . '" 
            data-redirect-from="' . stripslashes($data['url_from']) . '" 
            data-redirect-to="' . stripslashes($data['url_to']) . '"
            data-redirect-query="' . (!empty($data['query_parameters']) ? $data['query_parameters'] : 'ignore') . '" 
            data-redirect-case="' . $data['case_insensitive'] . '" 
            data-redirect-regex="' . $data['regex'] . '" 
            data-redirect-type="' . $data['type'] . '"
            data-redirect-position="' . $data['position'] . '"
            data-redirect-tags="' . $data['tags'] . '"
            title="Edit redirect"><i class="wf301-icon wf301-edit"></i> <span>Edit</span>
            </div>';
        $actions_html .= '<div title="Verify Redirect" data-url="' . WF301_functions::get_full_url(stripslashes($data['url_from'])) . '" class="tooltip verify_redirect_rule"><i class="wf301-icon wf301-check"></i></div>';
        $actions_html .= '<div class="tooltip delete_redirect_rule" data-redirect="' . $data['id'] . '" data-msg-success="Redirect deleted" data-btn-confirm="Delete rule" data-title="Are you sure you want to delete this redirect rule?" data-wait-msg="Deleting. Please wait." data-name="" title="Delete this redirect"><i class="wf301-icon wf301-trash"></i></div>';

        return $actions_html;
    }

    /**
     * Fetch 404 logs and output JSON for datatables
     *
     * @return null
     */
    static function get_404_logs()
    {
        global $wpdb;

        $aColumns = array('id', 'created', 'url', 'referrer', 'ip', 'location', 'agent');
        $sIndexColumn = "id";

        // paging
        $sLimit = '';
        if (isset($_GET['iDisplayStart']) && $_GET['iDisplayLength'] != '-1') {
            $sLimit = "LIMIT " . esc_sql($_GET['iDisplayStart']) . ", " .
                esc_sql($_GET['iDisplayLength']);
        } // paging

        // ordering
        $sOrder = '';
        if (isset($_GET['iSortCol_0'])) {
            $sOrder = "ORDER BY  ";
            for ($i = 0; $i < intval($_GET['iSortingCols']); $i++) {
                if ($_GET['bSortable_' . intval($_GET['iSortCol_' . $i])] == "true") {
                    $sOrder .= $aColumns[intval($_GET['iSortCol_' . $i])] . " "
                        .  esc_sql($_GET['sSortDir_' . $i]) . ", ";
                }
            }

            $sOrder = substr_replace($sOrder, '', -2);
            if ($sOrder == "ORDER BY") {
                $sOrder = '';
            }
        } // ordering

        // filtering
        $sWhere = '';
        if (isset($_GET['sSearch']) && $_GET['sSearch'] != '') {
            $sWhere = "WHERE (";
            for ($i = 0; $i < count($aColumns); $i++) {
                $sWhere .= $aColumns[$i] . " LIKE '%" . esc_sql($_GET['sSearch']) . "%' OR ";
            }
            $sWhere  = substr_replace($sWhere, '', -3);
            $sWhere .= ')';
        } // filtering

        // individual column filtering
        for ($i = 0; $i < count($aColumns); $i++) {
            if (isset($_GET['bSearchable_' . $i]) && $_GET['bSearchable_' . $i] == "true" && $_GET['sSearch_' . $i] != '') {
                if ($sWhere == '') {
                    $sWhere = "WHERE ";
                } else {
                    $sWhere .= " AND ";
                }
                $sWhere .= $aColumns[$i] . " LIKE '%" . esc_sql($_GET['sSearch_' . $i]) . "%' ";
            }
        } // individual columns

        // build query
        $sQuery = "SELECT SQL_CALC_FOUND_ROWS " . str_replace(" , ", " ", implode(", ", $aColumns)) .
            " FROM " . $wpdb->wf301_404_logs . " $sWhere $sOrder $sLimit";

        $rResult = $wpdb->get_results($sQuery);

        // data set length after filtering
        $sQuery = "SELECT FOUND_ROWS()";
        $iFilteredTotal = $wpdb->get_var($sQuery);

        // total data set length
        $sQuery = "SELECT COUNT(" . $sIndexColumn . ") FROM " . $wpdb->wf301_404_logs;
        $iTotal = $wpdb->get_var($sQuery);

        // construct output
        $output = array(
            "sEcho" => intval(@$_GET['sEcho']),
            "iTotalRecords" => $iTotal,
            "iTotalDisplayRecords" => $iFilteredTotal,
            "aaData" => array()
        );

        foreach ($rResult as $aRow) {
            $row = array();
            $row['DT_RowId'] = $aRow->id;

            for ($i = 0; $i < count($aColumns); $i++) {
                if ($aColumns[$i] == 'created') {
                    $row[] = self::get_date_time(strtotime($aRow->{$aColumns[$i]}));
                } elseif ($aColumns[$i] == 'url') {
                    $row[] = '<a href="' . $aRow->{$aColumns[$i]} . '" target="_blank">' . htmlspecialchars($aRow->{$aColumns[$i]}) . '</a>';
                } elseif ($aColumns[$i] == 'created') {
                    $time = strtotime($aRow->{$aColumns[$i]});
                    $row[] = date(get_option('date_format') . ' ' . get_option('time_format'), $time);
                } elseif ($aColumns[$i] == 'referrer') {
                    if (empty($aRow->{$aColumns[$i]})) {
                        $row[] = '<span>n/a</span>';
                    } else {
                        $row[] = '<a href="' . $aRow->{$aColumns[$i]} . '" target="_blank">' . rtrim(str_replace(array('http://', 'https://'), '', htmlspecialchars($aRow->{$aColumns[$i]})), '/') . '</a>';
                    }
                } elseif ($aColumns[$i] == 'agent') {
                    $row[] = WF301_utility::parse_user_agent($aRow->{$aColumns[$i]});
                } else if ($aColumns[$i] == 'location') {
                    if (!empty(trim($aRow->{$aColumns[$i]}))) {
                        $location = $aRow->{$aColumns[$i]};
                    } else {
                        $location = WF301_utility::getUserCountry($aRow->{$aColumns[$i]});
                    }
                    if (empty($location)) $location = 'unknown';
                    $location .= '<br /><a href="https://redirect.li/map/?ip=' . $aRow->ip . '" target="_blank">' . $aRow->ip . '</a>';

                    $row[] = $location;
                } else if (!in_array($aColumns[$i], array(' ', 'ip', 'id'))) {
                    $row[] = $aRow->{$aColumns[$i]};
                }
            }
            $row[] = '<div data-url="' . $aRow->url . '" class="tooltip create_redirect_rule_404" title="Create redirect rule"><i class="wf301-icon wf301-redirect"></i></div><div data-log-id="' . $aRow->id . '" class="tooltip delete_404_entry" title="Delete 404 log entry" data-msg-success="404 log entry deleted" data-btn-confirm="Delete 404 log entry" data-title="Delete 404 log entry" data-wait-msg="Deleting. Please wait." data-name="" title="Delete this 404 log entry"><i class="wf301-icon wf301-trash"></i></div>';
            $output['aaData'][] = $row;
        } // foreach row

        // json encoded output
        ob_end_clean();
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        echo json_encode($output);
        die();
    }

    /**
     * Fetch redirect logs and output JSON for datatables
     *
     * @return null
     */
    static function get_redirect_logs()
    {
        global $wpdb;

        $aColumns = array('id', 'auto', 'created', 'url', 'sent_to', 'referrer', 'ip', 'location', 'agent');
        $sIndexColumn = "id";

        // paging
        $sLimit = '';
        if (isset($_GET['iDisplayStart']) && $_GET['iDisplayLength'] != '-1') {
            $sLimit = "LIMIT " . esc_sql($_GET['iDisplayStart']) . ", " .
                esc_sql($_GET['iDisplayLength']);
        } // paging

        // ordering
        $sOrder = '';
        if (isset($_GET['iSortCol_0'])) {
            $sOrder = "ORDER BY  ";
            for ($i = 0; $i < intval($_GET['iSortingCols']); $i++) {
                if ($_GET['bSortable_' . intval($_GET['iSortCol_' . $i])] == "true") {
                    $sOrder .= $aColumns[intval($_GET['iSortCol_' . $i])] . " "
                        .  esc_sql($_GET['sSortDir_' . $i]) . ", ";
                }
            }

            $sOrder = substr_replace($sOrder, '', -2);
            if ($sOrder == "ORDER BY") {
                $sOrder = '';
            }
        } // ordering

        // filtering
        $sWhere = '';
        if (isset($_GET['sSearch']) && $_GET['sSearch'] != '') {
            $sWhere = "WHERE (";
            for ($i = 0; $i < count($aColumns); $i++) {
                $sWhere .= $aColumns[$i] . " LIKE '%" . esc_sql($_GET['sSearch']) . "%' OR ";
            }
            $sWhere  = substr_replace($sWhere, '', -3);
            $sWhere .= ')';
        } // filtering

        // individual column filtering
        for ($i = 0; $i < count($aColumns); $i++) {
            if (isset($_GET['bSearchable_' . $i]) && $_GET['bSearchable_' . $i] == "true" && $_GET['sSearch_' . $i] != '') {
                if ($sWhere == '') {
                    $sWhere = "WHERE ";
                } else {
                    $sWhere .= " AND ";
                }
                $sWhere .= $aColumns[$i] . " LIKE '%" . esc_sql($_GET['sSearch_' . $i]) . "%' ";
            }
        } // individual columns

        // build query
        $sQuery = "SELECT SQL_CALC_FOUND_ROWS " . str_replace(" , ", " ", implode(", ", $aColumns)) .
            " FROM " . $wpdb->wf301_redirect_logs . " $sWhere $sOrder $sLimit";

        $rResult = $wpdb->get_results($sQuery);

        // data set length after filtering
        $sQuery = "SELECT FOUND_ROWS()";
        $iFilteredTotal = $wpdb->get_var($sQuery);

        // total data set length
        $sQuery = "SELECT COUNT(" . $sIndexColumn . ") FROM " . $wpdb->wf301_redirect_logs;

        $iTotal = $wpdb->get_var($sQuery);

        // construct output
        $output = array(
            "sEcho" => intval(@$_GET['sEcho']),
            "iTotalRecords" => $iTotal,
            "iTotalDisplayRecords" => $iFilteredTotal,
            "aaData" => array()
        );

        foreach ($rResult as $aRow) {
            $row = array();
            $row['DT_RowId'] = $aRow->id;

            for ($i = 0; $i < count($aColumns); $i++) {
                if ($aColumns[$i] == 'created') {
                    $row[] = self::get_date_time(strtotime($aRow->{$aColumns[$i]}));
                } else if ($aColumns[$i] == 'url') {
                    $row[] = ($aRow->auto == 1 ? '<span class="tooltip" title="Autoredirected"><i class="fas fa-robot"></i></span> ' : '') . 'From: <a href="' . WF301_functions::get_full_url($aRow->{$aColumns[$i]}) . '" target="_blank">' . htmlspecialchars($aRow->{$aColumns[$i]}) . '</a><br />Sent to: <a href="' . $aRow->sent_to . '" target="_blank">' . $aRow->sent_to . '</a>';
                } elseif ($aColumns[$i] == 'created') {
                    $time = strtotime($aRow->{$aColumns[$i]});
                    $row[] = date(get_option('date_format') . ' ' . get_option('time_format'), $time);
                } elseif ($aColumns[$i] == 'referrer') {
                    if (empty($aRow->{$aColumns[$i]})) {
                        $row[] = '<span>n/a</span>';
                    } else {
                        $row[] = '<a href="' . $aRow->{$aColumns[$i]} . '" target="_blank">' . rtrim(str_replace(array('http://', 'https://'), '', htmlspecialchars($aRow->{$aColumns[$i]})), '/') . '</a>';
                    }
                } else if ($aColumns[$i] == 'location') {
                    if (!empty(trim($aRow->{$aColumns[$i]}))) {
                        $location = $aRow->{$aColumns[$i]};
                    } else {
                        $location = WF301_utility::getUserCountry($aRow->{$aColumns[$i]});
                    }
                    if (empty($location)) $location = 'unknown';
                    $location .= '<br /><a href="https://redirect.li/map/?ip=' . $aRow->ip . '" target="_blank">' . $aRow->ip . '</a>';
                    $row[] = $location;
                } elseif ($aColumns[$i] == 'agent') {
                    $row[] = WF301_utility::parse_user_agent($aRow->{$aColumns[$i]});
                } else if (!in_array($aColumns[$i], array(' ', 'id', 'sent_to', 'ip', 'auto'))) {
                    $row[] = $aRow->{$aColumns[$i]};
                }
            }
            $row[] = '<div data-log-id="' . $aRow->id . '" class="tooltip delete_redirect_entry" title="Delete redirect log entry" data-msg-success="Redirect log entry deleted" data-btn-confirm="Delete redirect log entry" data-title="Delete redirect log entry" data-wait-msg="Deleting. Please wait." data-name="" title="Delete this redirect log entry"><i class="wf301-icon wf301-trash"></i></div>';
            $output['aaData'][] = $row;
        } // foreach row

        // json encoded output
        ob_end_clean();
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        echo json_encode($output);
        die();
    }

    /**
     * Fetch redirect rules and output JSON for datatables
     *
     * @return null
     */
    static function get_redirect_tags_ajax()
    {
        global $wpdb;

        $query = "SELECT tags FROM " . $wpdb->wf301_redirect_rules . " WHERE tags IS NOT NULL";

        $results = $wpdb->get_results($query);

        $tags = [];

        foreach ($results as $row) {
            $tagsArray = explode(",", $row->tags);

            foreach ($tagsArray as $tag) {
                $tags[] = $tag;
            }
        }

        $unique_tags = array_unique($tags);

        asort($unique_tags);

        $options = [];

        foreach ($unique_tags as $tag) {
            $options[] = array(
                'text' => $tag,
                'value' => $tag
            );
        }

        // json encoded output
        ob_end_clean();
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        echo json_encode($options);
        die();
    }

    /**
     * Fetch redirect rules and output JSON for datatables
     *
     * @return null
     */
    static function get_redirect_rules_ajax()
    {
        global $wpdb;

        $aColumns = array('status', 'created', 'type', 'url_from', 'url_to', 'position', 'last_count', 'tags', 'id', 'query_parameters', 'case_insensitive', 'regex');
        $sIndexColumn = "id";

        // paging
        $sLimit = '';
        if (isset($_GET['iDisplayStart']) && $_GET['iDisplayLength'] != '-1') {
            $sLimit = "LIMIT " . esc_sql($_GET['iDisplayStart']) . ", " .
                esc_sql($_GET['iDisplayLength']);
        } // paging

        // ordering
        $sOrder = '';
        if (isset($_GET['iSortCol_0']) && $_GET['iSortCol_0'] == 0) {
            $sOrder = 'ORDER BY id DESC';
        } else if (isset($_GET['iSortCol_0'])) {
            $sOrder = "ORDER BY  ";
            for ($i = 0; $i < intval($_GET['iSortingCols']); $i++) {
                if ($_GET['bSortable_' . intval($_GET['iSortCol_' . $i])] == "true") {
                    $sOrder .= $aColumns[intval($_GET['iSortCol_' . $i])] . " "
                        .  esc_sql($_GET['sSortDir_' . $i]) . ", ";
                }
            }

            $sOrder = substr_replace($sOrder, '', -2);
            if ($sOrder == "ORDER BY") {
                $sOrder = '';
            }
        } // ordering

        // filtering
        $sWhere = '';
        if (isset($_GET['sSearch']) && $_GET['sSearch'] != '') {
            $sWhere = "WHERE (";
            for ($i = 0; $i < count($aColumns); $i++) {
                if ($aColumns[$i] != 'tags') {
                    $sWhere .= $aColumns[$i] . " LIKE '%" . esc_sql($_GET['sSearch']) . "%' OR ";
                }
            }
            $sWhere  = substr_replace($sWhere, '', -3);
            $sWhere .= ')';
        } // filtering

        // individual column filtering
        for ($i = 0; $i < count($aColumns); $i++) {
            if (isset($_GET['bSearchable_' . $i]) && $_GET['bSearchable_' . $i] == "true" && $_GET['sSearch_' . $i] != '') {
                if ($sWhere == '') {
                    $sWhere = "WHERE ";
                } else {
                    $sWhere .= " AND ";
                }

                if ($aColumns[$i] != 'tags') {
                    $sWhere .= $aColumns[$i] . " LIKE '%" . esc_sql($_GET['sSearch_' . $i]) . "%' ";
                } else {
                    $searchTagValueArray = explode(',', $_GET['sSearch_' . $i]);

                    foreach ($searchTagValueArray as $tagValue) {
                        $sWhere .= "FIND_IN_SET('" . esc_sql($tagValue) . "', " . $aColumns[$i] . ") AND ";
                    }

                    $sWhere  = substr_replace($sWhere, '', -4);
                }
            }
        } // individual columns

        // build query
        $sQuery = "SELECT SQL_CALC_FOUND_ROWS " . str_replace(" , ", " ", implode(", ", $aColumns)) .
            " FROM " . $wpdb->wf301_redirect_rules . " $sWhere $sOrder $sLimit";

        $rResult = $wpdb->get_results($sQuery);

        $t = $sQuery;

        // data set length after filtering
        $sQuery = "SELECT FOUND_ROWS()";
        $iFilteredTotal = $wpdb->get_var($sQuery);

        // total data set length
        $sQuery = "SELECT COUNT(" . $sIndexColumn . ") FROM " . $wpdb->wf301_redirect_rules;
        $iTotal = $wpdb->get_var($sQuery);

        // construct output
        $output = array(
            "sEcho" => intval(@$_GET['sEcho']),
            "iTotalRecords" => $iTotal,
            "iTotalDisplayRecords" => $iFilteredTotal,
            "aaData" => array(),
            "sQuery" => $t
        );

        foreach ($rResult as $aRow) {
            $row = array();
            $row['DT_RowId'] = $aRow->id;
            
            for ($i = 0; $i < count($aColumns); $i++) {
                if ($aColumns[$i] == 'created') {
                    $row[] = self::get_date_time(strtotime($aRow->{$aColumns[$i]}));
                } else if ($aColumns[$i] == 'status') {
                    if($aRow->{$aColumns[$i]} != 'enabled'){
                        $row['DT_RowClass'] = 'disabled';
                    }
                    $row[] = '<div class="toggle-wrapper tooltip" title="' . ($aRow->{$aColumns[$i]} == 'enabled' ? 'Disable Rule' : 'Enable Rule') . '"><input class="toggle_redirect_rule" id="toggle_redirect_rule_' . $aRow->id . '" data-redirect="' . $aRow->id . '" type="checkbox" ' . ($aRow->{$aColumns[$i]} == 'enabled' ? 'checked' : '') . ' type="checkbox" value="1" name="redirect_enabled"><label for="toggle_redirect_rule_' . $aRow->id . '" class="toggle"><span class="toggle_handler"></span></label></div>';
                } else if ($aColumns[$i] == 'tags') {
                    $row[] = self::getTagHtml(explode(',', $aRow->{$aColumns[$i]}));
                } else if (in_array($aColumns[$i], array('url_from', 'url_to'))) {
                    $row[] = '<a href="' . WF301_functions::get_formatted_full_url(stripslashes($aRow->{$aColumns[$i]})) . '" target="_blank">' . htmlspecialchars(stripslashes($aRow->{$aColumns[$i]})) . '</a>';
                } else if (!in_array($aColumns[$i], array('id', 'query_parameters', 'case_insensitive', 'regex'))) {
                    $row[] = $aRow->{$aColumns[$i]};
                }
            }
            $row[] = self::get_rule_actions_html((array)$aRow);
            $output['aaData'][] = $row;
        } // foreach row

        // json encoded output
        ob_end_clean();
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        echo json_encode($output);
        die();
    }


    /**
     * Fetch redirect logs group and output JSON for datatables
     *
     * @return null
     */
    static function get_logs_group()
    {
        global $wpdb;

        $log = $_GET['logs'];

        if ($log == 'redirect') {
            $table = $wpdb->wf301_redirect_logs;
        } else {
            $table = $wpdb->wf301_404_logs;
        }

        $group = $_GET['group'];

        $aColumns = array($group, 'count');

        // paging
        $sLimit = '';
        if (isset($_GET['iDisplayStart']) && $_GET['iDisplayLength'] != '-1') {
            $sLimit = "LIMIT " . esc_sql($_GET['iDisplayStart']) . ", " .
                esc_sql($_GET['iDisplayLength']);
        } // paging

        // ordering
        $sOrder = '';
        if (isset($_GET['iSortCol_0'])) {
            $_GET['iSortCol_0'] = --$_GET['iSortCol_0'];
            $sOrder = "ORDER BY  ";
            for ($i = 0; $i < intval($_GET['iSortingCols']); $i++) {
                if ($_GET['bSortable_' . intval($_GET['iSortCol_' . $i])] == "true") {
                    $sOrder .= $aColumns[intval($_GET['iSortCol_' . $i])] . " "
                        .  esc_sql($_GET['sSortDir_' . $i]) . ", ";
                }
            }

            $sOrder = substr_replace($sOrder, '', -2);
            if ($sOrder == "ORDER BY") {
                $sOrder = '';
            }
        } // ordering
        // filtering
        $sWhere = '';
        if (isset($_GET['sSearch']) && $_GET['sSearch'] != '') {
            $sWhere = "WHERE (";
            for ($i = 0; $i < count($aColumns); $i++) {
                if ($aColumns[$i] == 'count') continue;
                $sWhere .= $aColumns[$i] . " LIKE '%" . esc_sql($_GET['sSearch']) . "%' OR ";
            }
            $sWhere  = substr_replace($sWhere, '', -3);
            $sWhere .= ')';
        } // filtering

        // individual column filtering
        for ($i = 0; $i < count($aColumns); $i++) {
            if (isset($_GET['bSearchable_' . $i]) && $_GET['bSearchable_' . $i] == "true" && $_GET['sSearch_' . $i] != '') {
                if ($sWhere == '') {
                    $sWhere = "WHERE ";
                } else {
                    $sWhere .= " AND ";
                }
                $sWhere .= $aColumns[$i] . " LIKE '%" . esc_sql($_GET['sSearch_' . $i]) . "%' ";
            }
        } // individual columns

        // build query
        $sQuery = "SELECT $group, count(*) as count FROM " . $table . " $sWhere GROUP BY $group $sOrder $sLimit";
        $rResult = $wpdb->get_results($sQuery);

        // data set length after filtering
        $sQuery = "SELECT FOUND_ROWS()";

        // total data set length
        $sQuery = "SELECT COUNT(*) FROM " . $table . " GROUP BY " . $group;

        $iTotal = count($wpdb->get_results($sQuery));

        // construct output
        $output = array(
            "sEcho" => intval(@$_GET['sEcho']),
            "iTotalRecords" => $iTotal,
            "iTotalDisplayRecords" => $iTotal,
            "aaData" => array()
        );

        foreach ($rResult as $aRow) {
            $row = array();
            $row[] = $wpdb->get_var('SELECT CONCAT(MIN(created), " to<br /> ", MAX(created)) AS date_range FROM ' . $table . ' WHERE ' . ($group == 'ip' ? 'ip = "' . $aRow->ip . '"' : 'url = "' . $aRow->url . '"'));

            for ($i = 0; $i < count($aColumns); $i++) {
                if (!in_array($aColumns[$i], array(' '))) {
                    $row[] = $aRow->{$aColumns[$i]};
                }
            }

            $groupedvals = $wpdb->get_results('SELECT ' . ($group == 'ip' ? 'url' : 'ip') . ' as val FROM ( SELECT ip,url,COUNT(*) AS count FROM ' . $table . ' WHERE ' . ($group == 'ip' ? 'ip = "' . $aRow->ip . '"' : 'url = "' . $aRow->url . '"') . ' GROUP BY ' . ($group == 'ip' ? 'url' : 'ip') . ' ORDER BY COUNT DESC LIMIT 3) AS subq');
            $groupedvals_array = array();
            foreach ($groupedvals as $groupedval) {
                $groupedvals_array[] = $groupedval->val;
            }
            $row[] = implode('<br />', $groupedvals_array);

            $agents = $wpdb->get_results('SELECT agent FROM ( SELECT ip,url,agent,COUNT(*) AS count FROM ' . $table . ' WHERE ' . ($group == 'ip' ? 'ip = "' . $aRow->ip . '"' : 'url = "' . $aRow->url . '"') . ' GROUP BY agent ORDER BY COUNT DESC LIMIT 3) AS subq');
            $agents_array = array();
            foreach ($agents as $agent) {
                $agents_array[] = WF301_utility::parse_user_agent($agent->agent);
            }
            $row[] = implode('<br />', $agents_array);
            $row[] = '<div class="delete_log_group" data-redirect="" data-msg-success="Group deleted" data-btn-confirm="Delete group" data-title="Delete this group" data-wait-msg="Deleting. Please wait." data-name="" title="Delete this group"><i class="wf301-icon wf301-trash"></i></div>';
            $output['aaData'][] = $row;
        } // foreach row

        // json encoded output
        ob_end_clean();
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        echo json_encode($output);
        die();
    }
} // class
