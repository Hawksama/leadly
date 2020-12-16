<?php
/*
 * 301 Redirects Pro
 * Export - Import
 * (c) WebFactory Ltd, 2019 - 2021, www.webfactoryltd.com
 */

class WF301_ei extends WF301
{

    /**
     * Generate export CSV with all rules
     *
     * @since 5.0
     * 
     * @return null
     * 
     */
    static function generate_export_file()
    {
        $filename = str_replace(array('http://', 'https://'), '', home_url());
        $filename = str_replace(array('/', '\\', '.'), '-', $filename);
        $filename .= '-' . date('Y-m-d') . '-301-redirects-pro.txt';

        $out = '';
        $rules = WF301_functions::get_redirects();

        foreach ($rules as $rule) {
            $csv = array(
                $rule->status,
                $rule->url_from,
                $rule->url_to,
                $rule->type,
                $rule->query_parameters,
                $rule->regex,
                $rule->position,
                $rule->case_insensitive,
                $rule->regex
            );
            $out .= implode(',', $csv);
            $out .= "\n";
        }

        header('Content-type: text/csv');
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . strlen($out));

        @ob_end_clean();
        flush();

        echo $out;
        exit;
    } // generate_export_file


    /**
     * Import rules from uploaded CSV
     *
     * @since 5.0
     * 
     * @return null
     * 
     */
    static function import_csv()
    {
        $new_redirects = array();

        $counter = array(
            'new' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0,
            'total' => 0
        );

        $mimes = array(
            'text/csv',
            'text/tsv',
            'text/plain',
            'application/csv',
            'text/comma-separated-values',
            'application/excel',
            'application/vnd.ms-excel',
            'application/vnd.msexcel',
            'text/anytext',
            'application/octet-stream',
            'application/txt'
        );
        ini_set('auto_detect_line_endings', true);

        if (!in_array($_FILES['wf301_import_file']['type'], $mimes)) {

            WF301_utility::display_notice(
                sprintf(
                    "WARNING: Not a valid CSV file - the Mime Type '%s' is wrong! No new redirects have been added.",
                    $_FILES['wf301_import_file']['type']
                ),
                "error"
            );
            return false;
        }

        // open the file.
        if (($handle = fopen($_FILES['wf301_import_file']['tmp_name'], "r")) !== false) {
            $counter['total'] = 1;
            while (($redirect = fgetcsv($handle, 0, ",")) !== false) {
                $redirect = array_filter($redirect);

                if (empty($redirect)) continue;

                $args = count($redirect);

                if ($args > 8 || $args < 2) {
                    // Bad line. Too many/few arguments.
                    WF301_utility::display_notice(
                        sprintf(
                            "WARNING: Encountered a badly formed entry in your CSV file on line %d and it was skipped.",
                            $counter['total']
                        ),
                        "error"
                    );
                    $counter['errors']++;
                    continue;
                }

                $status           = (isset($redirect[0])) ? $redirect[0] : false;
                $url_from         = (isset($redirect[1])) ? $redirect[1] : false;
                $url_to           = (isset($redirect[2])) ? $redirect[2] : false;
                $type             = (isset($redirect[3])) ? $redirect[3] : false;
                $query_parameters = (isset($redirect[4])) ? $redirect[4] : false;
                $position         = (isset($redirect[6])) ? $redirect[6] : false;
                $case_insensitive = (isset($redirect[7])) ? $redirect[7] : false;
                $regex            = (isset($redirect[8])) ? $redirect[8] : false;

                if ($status == 'status') {
                    continue;
                }

                // new redirect!
                $new_redirect = array(
                    'id'               => false, // new
                    'status'           => $status,
                    'url_from'         => $url_from,
                    'url_to'           => $url_to,
                    'type'             => $type,
                    'query_parameters' => $query_parameters,
                    'regex'            => $regex,
                    'position'         => $position,
                    'case_insensitive' => $case_insensitive
                );

                array_push($new_redirects, $new_redirect);
                $counter['total']++;
            }
            fclose($handle); // close file.
        }

        if ($new_redirects) {
            $save_redirects = array();
            foreach ($new_redirects as $redirect) {
                // Decide how to handle duplicates:
                switch (strtolower($_POST['wf301_import_method'])) {
                    case 'skip':
                        if (!WF301_functions::redirect_exists($redirect)) {
                            $save_redirects[] = $redirect;
                            $counter['new']++;
                        } else {
                            $counter['skipped']++;
                        }
                        break;
                    case 'update':
                        if ($entry = WF301_functions::redirect_exists($redirect)) {
                            $redirect['id'] = $entry->id;
                            $counter['updated']++;
                            $save_redirects[] = $redirect;
                        } else {
                            $save_redirects[] = $redirect;
                            $counter['new']++;
                        }
                        break;
                    default:
                        $save_redirects[] = $redirect;
                        $counter['new']++;
                        break;
                }
            }

            if (!empty($save_redirects)) {
                foreach ($save_redirects as $redirect) {
                    WF301_functions::save_redirect_rule($redirect);
                }
            }

            WF301_utility::display_notice(sprintf(
                "SUCCCESS:<br /> %d New Redirects<br /> %d Updated<br /> %d Skipped<br /> %d Errors<br /> (Attempted to import %d redirects).",
                $counter['new'],
                $counter['updated'],
                $counter['skipped'],
                $counter['errors'],
                $counter['total']
            ), "updated");
        } else {
            WF301_utility::display_notice("WARNING: Something went wrong. No new redirects were added, please review your CSV file.", "error");
        }
        ini_set('auto_detect_line_endings', false);
    } // import_csv

    /**
     * Check if redirects from 301 Redirects free exist and create notice
     *
     * @since 5.0
     * 
     * @return null
     * 
     */
    static function check_301redirects_free_db()
    {
        global $wpdb;
        $eps_table_name = $wpdb->prefix . 'redirects';
        if ($wpdb->get_var('SHOW TABLES LIKE \'' . $eps_table_name . '\'') == $eps_table_name) {
            $free_rules = $wpdb->get_results('SELECT * FROM ' . $eps_table_name);
            if (isset($free_rules[0]->url_from)) {
                WF301_admin::add_notice('free_import', 'There are ' . count($free_rules) . ' rules that can be imported from 301 Redirects Free. 
                <br />
                <a style="margin-right:30px;" href="' . add_query_arg(array('action' => 'wf301_import_301redirects_free_db', 'doimport' => 'true'), admin_url('admin.php')) . '">Click here if you want to import them</a>
                <a href="' . add_query_arg(array('action' => 'wf301_import_301redirects_free_db'), admin_url('admin.php')) . '">No, don\'t ask again</a>
                ', 'success');
            }
        }
    }

    /**
     * Import redirects from 301 Redirect Free or dismiss notice
     *
     * @since 5.0
     * 
     * @return null
     * 
     */
    static function import_301redirects_free_db()
    {
        global $wpdb;

        if (!isset($_GET['doimport'])) {
            WF301_admin::dismiss_notice('free_import');
        } else {
            $eps_table_name = $wpdb->prefix . 'redirects';
            $root = get_bloginfo('url') . '/';
            $imported_rules = 0;

            if ($wpdb->get_var('SHOW TABLES LIKE \'' . $eps_table_name . '\'') == $eps_table_name) {
                $free_rules = $wpdb->get_results('SELECT * FROM ' . $eps_table_name);
                foreach ($free_rules as $free_rule) {
                    $url_to = $free_rule->url_to;

                    if (is_numeric($url_to)) {
                        $url_to = get_permalink($url_to);
                    }

                    $rule = array(
                        'url_from'         => trim(rtrim(ltrim(str_replace($root, null, $free_rule->url_from), '/'), '/')),
                        'url_to'           => trim(rtrim(ltrim(str_replace($root, null, $url_to), '/'), '/')),
                        'type'             => $free_rule->status,
                        'query_parameters' => 'ignore',
                        'case_insensitive' => 'enabled',
                        'regex'            => 'disabled',
                        'status'           => 'enabled'
                    );

                    if(WF301_functions::save_redirect_rule($rule) > 0){
                        $imported_rules++;
                    }
                }

                $options = WF301_setup::get_options();
                $update['nb_redirects'] = $wpdb->get_var('SELECT count(*) FROM ' . $wpdb->wf301_redirect_rules);
                update_option(WF301_OPTIONS_KEY, array_merge($options, $update));

                WF301_admin::dismiss_notice('free_import');
                WF301_admin::add_notice('free_import_done', $imported_rules . ' Redirect rules from 301 Redirects Free have been imported', 'success', true);
            }
        }
        wp_safe_redirect(admin_url('options-general.php?page=301redirects&tab=wf301_redirect'));
        die();
    }

    /**
     * Check if redirects from redirection exist and create notice
     *
     * @since 5.34
     * 
     * @return null
     * 
     */
    static function check_redirection_db()
    {
        global $wpdb;
        $redirection_table_name = $wpdb->prefix . 'redirection_items';
        if ($wpdb->get_var('SHOW TABLES LIKE \'' . $redirection_table_name . '\'') == $redirection_table_name) {
            $redirection_rules = $wpdb->get_results('SELECT * FROM ' . $redirection_table_name);
            if (isset($redirection_rules[0]->url)) {
                WF301_admin::add_notice('redirection_import', 'There are ' . count($redirection_rules) . ' rules that can be imported from Redirection. 
                <br />
                <a style="margin-right:30px;" href="' . add_query_arg(array('action' => 'wf301_import_redirection_db', 'doimport' => 'true'), admin_url('admin.php')) . '">Click here if you want to import them</a>
                <a href="' . add_query_arg(array('action' => 'wf301_import_redirection_db'), admin_url('admin.php')) . '">No, don\'t ask again</a>
                ', 'success');
            }
        }
    }

    /**
     * Import redirects from 3redirection or dismiss notice
     *
     * @since 5.34
     * 
     * @return null
     * 
     */
    static function import_redirection_db()
    {
        global $wpdb;

        if (!isset($_GET['doimport'])) {
            WF301_admin::dismiss_notice('redirection_import');
        } else {

            $redirection_table_name = $wpdb->prefix . 'redirection_items';
            $root = get_bloginfo('url') . '/';

            if ($wpdb->get_var('SHOW TABLES LIKE \'' . $redirection_table_name . '\'') == $redirection_table_name) {
                $redirection_rules = $wpdb->get_results('SELECT * FROM ' . $redirection_table_name);
                foreach ($redirection_rules as $redirection_rule) {
                    $url_from = $redirection_rule->url;
                    $url_to = $redirection_rule->action_data;

                    $regex = $redirection_rule->action_data;

                    if ($regex != '1') {
                        $url_from = trim(rtrim(ltrim(str_replace($root, null, $url_from), '/'), '/'));
                    }

                    if (is_numeric($url_to)) {
                        $url_to = get_permalink($url_to);
                    }

                    $rule = array(
                        'url_from'         => $url_from,
                        'url_to'           => trim(rtrim(ltrim(str_replace($root, null, $url_to), '/'), '/')),
                        'type'             => $redirection_rule->action_code,
                        'query_parameters' => 'ignore',
                        'case_insensitive' => 'enabled',
                        'regex'            => 'disabled',
                        'status'           => 'enabled'
                    );

                    WF301_functions::save_redirect_rule($rule);
                }

                $options = WF301_setup::get_options();
                $update['nb_redirects'] = $wpdb->get_var('SELECT count(*) FROM ' . $wpdb->wf301_redirect_rules);
                update_option(WF301_OPTIONS_KEY, array_merge($options, $update));

                WF301_admin::dismiss_notice('redirection_import');
                WF301_admin::add_notice('redirection_import_done', 'Redirect rules from Redirection have been imported', 'success', true);
            }
        }
        
        wp_safe_redirect(admin_url('options-general.php?page=301redirects&tab=wf301_redirect'));
        die();
    }
} // class
