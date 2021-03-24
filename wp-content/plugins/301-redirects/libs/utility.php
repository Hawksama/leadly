<?php
/*
 * 301 Redirects Pro
 * Utility & Helper functions
 * (c) WebFactory Ltd, 2019 - 2021, www.webfactoryltd.com
 */

use MaxMind\Db\Reader;

class WF301_utility extends WF301
{
    /**
     * Display settings notice
     *
     * @param $redirect
     * @return bool
     */
    static function display_notice($message, $type = 'error', $code = 'wf301')
    {
        global $wp_settings_errors;

        $wp_settings_errors[] = array(
            'setting' => WF301_OPTIONS_KEY,
            'code'    => $code,
            'message' => $message,
            'type'    => $type
        );
        set_transient('settings_errors', $wp_settings_errors);
    } // display_notice


    /**
     * Whitelabel filter
     *
     * @return bool display contents if whitelabel is not enabled or not hidden
     */
    static function whitelabel_filter()
    {
        global $wf_301_licensing;
        $options = WF301_setup::get_options();

        if (!$wf_301_licensing->is_active('white_label')) {
            return true;
        }

        if (!$options['whitelabel']) {
            return true;
        }

        return false;
    } // whitelabel_filter
    

    /**
     * Empty cache in various 3rd party plugins
     *
     * @since 5.0
     * 
     * @return null
     * 
     */
    static function clear_3rdparty_cache()
    {
        if (function_exists('w3tc_pgcache_flush')) {
            w3tc_pgcache_flush();
        }
        if (function_exists('wp_cache_clean_cache')) {
            global $file_prefix;
            wp_cache_clean_cache($file_prefix);
        }
        if (function_exists('wp_cache_clear_cache')) {
            wp_cache_clear_cache();
        }
        if (class_exists('Endurance_Page_Cache')) {
            $epc = new Endurance_Page_Cache;
            $epc->purge_all();
        }
        if (method_exists('SG_CachePress_Supercacher', 'purge_cache')) {
            SG_CachePress_Supercacher::purge_cache(true);
        }

        if (class_exists('SiteGround_Optimizer\Supercacher\Supercacher')) {
            SiteGround_Optimizer\Supercacher\Supercacher::purge_cache();
        }
    } // empty_3rdparty_cache


    /**
     * Dismiss pointer
     *
     * @since 5.0
     * 
     * @return null
     * 
     */
    static function dismiss_pointer_ajax(){
        delete_option(WF301_POINTERS_KEY);
    }


    /**
     * Send support message
     *
     * @since 5.0
     * 
     * @return null
     * 
     */
    static function submit_support_message_ajax()
    {
        global $wf_301_licensing;
        
        check_ajax_referer('wf301_submit_support_message');

        $options = WF301_setup::get_options();

        $email = sanitize_text_field($_POST['support_email']);
        if (!is_email($email)) {
            wp_send_json_error('Please double-check your email address.');
        }

        $message = stripslashes(sanitize_text_field($_POST['support_message']));
        $subject = 'WF301 Support';
        $body = $message;
        $theme = wp_get_theme();
        $body .= "\r\n\r\nSite details:\r\n";
        $body .= '  WordPress version: ' . get_bloginfo('version') . "\r\n";
        $body .= '  301 Redirects Pro version: ' . parent::get_plugin_version() . "\r\n";
        $body .= '  Site URL: ' . get_bloginfo('url') . "\r\n";
        $body .= '  WordPress URL: ' . get_bloginfo('wpurl') . "\r\n";
        $body .= '  Theme: ' . $theme->get('Name') . ' v' . $theme->get('Version') . "\r\n";
        if ($wf_301_licensing->is_active()) {
            $body .= '  License key: ' . $options['license_key'] . "\r\n";;
            $body .= '  License details: ' . $options['license_type'] . ', expires on ' . $options['license_expires'] . "\r\n";;
        } else {
            $body .= '  License key: ' . (empty($options['license_key']) ? 'n/a' : $options['license_key']) . "\r\n";
        }
        $headers = 'From: ' . $email . "\r\n" . 'Reply-To: ' . $email;

        if (true === wp_mail('support@webfactoryltd.com', $subject, $body, $headers)) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Something is not right with your wp_mail() function. Please email as directly at support@webfactoryltd.com.');
        }
    } // submit_support_message

    /**
     * checkbox helper function
     *
     * @since 5.0
     * 
     * @return string checked HTML
     * 
     */
    static function checked($value, $current, $echo = false)
    {
        $out = '';

        if (!is_array($current)) {
            $current = (array) $current;
        }

        if (in_array($value, $current)) {
            $out = ' checked="checked" ';
        }

        if ($echo) {
            echo $out;
        } else {
            return $out;
        }
    } // checked

    /**
     * Create toggle switch
     *
     * @since 5.0
     * 
     * @return string Switch HTML
     * 
     */
    static function create_toogle_switch($name, $options = array(), $output = true)
    {
        $default_options = array('value' => '1', 'saved_value' => '', 'option_key' => $name);
        $options = array_merge($default_options, $options);

        $out = "\n";
        $out .= '<div class="toggle-wrapper">';
        $out .= '<input type="checkbox" id="' . $name . '" ' . self::checked($options['value'], $options['saved_value']) . ' type="checkbox" value="' . $options['value'] . '" name="' . $options['option_key'] . '">';
        $out .= '<label for="' . $name . '" class="toggle"><span class="toggle_handler"></span></label>';
        $out .= '</div>';

        if ($output) {
            echo $out;
        } else {
            return $out;
        }
    } // create_toggle_switch

    /**
     * Get user country
     *
     * @since 5.0
     * 
     * @return string country
     * 
     */
    static function getUserCountry($ip = false)
    {
        if (!$ip) {
            $ip = self::getUserIP();
        }
        $reader = new Reader(WF301_PLUGIN_DIR . '/misc/geo-country.mmdb');
        $ip_data = $reader->get($ip);
        $country = isset($ip_data) ? $ip_data['country']['names']['en'] : '';
        $reader->close();

        return $country;
    }

    /**
     * Get user IP
     *
     * @since 5.0
     * 
     * @return string userip
     * 
     */
    static function getUserIP()
    {
        if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            if (strpos($_SERVER['HTTP_X_FORWARDED_FOR'], ',') > 0) {
                $addr = explode(",", $_SERVER['HTTP_X_FORWARDED_FOR']);
                return trim($addr[0]);
            } else {
                return $_SERVER['HTTP_X_FORWARDED_FOR'];
            }
        } else if (!empty($_SERVER['REMOTE_ADDR'])) {
            return $_SERVER['REMOTE_ADDR'];
        } else {
            return 'unknown.ip';
        }
    } // getUserIP

    
    /**
     * Create select options for select
     *
     * @since 5.0
     * 
     * @param array $options options
     * @param string $selected selected value
     * @param bool $output echo, if false return html as string
     * @return string html with options
     */
    static function create_select_options($options, $selected = null, $output = true)
    {
        $out = "\n";

        foreach ($options as $tmp) {
            if ((is_array($selected) && in_array($tmp['val'], $selected)) || $selected == $tmp['val']) {
                $out .= "<option selected=\"selected\" value=\"{$tmp['val']}\">{$tmp['label']}&nbsp;</option>\n";
            } else {
                $out .= "<option value=\"{$tmp['val']}\">{$tmp['label']}&nbsp;</option>\n";
            }
        }

        if ($output) {
            echo $out;
        } else {
            return $out;
        }
    } //  create_select_options

    /**
     * Parse user agent to add device icon and clean text
     *
     * @since 5.0
     * 
     * @param string $user_agent
     * @return string $user_agent
     */
    static function parse_user_agent($user_agent = false)
    {
        if (!$user_agent) {
            $user_agent = array();
            foreach ($_SERVER as $name => $value)
            {
                if (substr($name, 0, 5) == 'HTTP_')
                {
                    $user_agent[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                }
            }
        }

        $user_agent = new WhichBrowser\Parser($user_agent);

        $user_agent_string = '';
        if ($user_agent->isType('mobile')) {
            $user_agent_string .= '<i class="tooltip fas fa-mobile-alt" title="Phone"></i>';
        } else if ($user_agent->isType('tablet')) {
            $user_agent_string .= '<i class="tooltip fas fa-tablet-alt" title="Table"></i>';
        } else if ($user_agent->isType('desktop')) {
            $user_agent_string .= '<i class="tooltip fas fa-desktop" title="Desktop"></i>';
        } else {
            $user_agent_string .= '<i class="tooltip fas fa-robot" title="Bot"></i>';
        }

        if($user_agent->browser->version){
            $browser_version = explode('.', $user_agent->browser->version->toString());
        } else {
            $browser_version = array('unknown');
        }

        if($user_agent->os){
            $os = $user_agent->os->toString();
        } else {
            $os = 'unknown';
        }

        $user_agent_string .= ' ' . $user_agent->browser->name . ' ' . $browser_version[0] . ' on ' . $os;


        return $user_agent_string;
    } // parse_user_agent

    /**
     * Parse user agent to return an array with info
     *
     * @since 5.0
     * 
     * @param string $user_agent
     * @return array user agent data
     */
    static function parse_user_agent_array($user_agent = false)
    {
        if (!$user_agent) {
            $user_agent = array();
            foreach ($_SERVER as $name => $value)
            {
                if (substr($name, 0, 5) == 'HTTP_')
                {
                    $user_agent[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                }
            }
        }

        $user_agent = new WhichBrowser\Parser($user_agent);

        if (!is_null($user_agent)) {

            $agent['device'] = '';

            if ($user_agent->isType('mobile')) {
                $agent['device'] = 'mobile';
            } else if ($user_agent->isType('tablet')) {
                $agent['device'] = 'tablet';
            } else if ($user_agent->isType('desktop')) {
                $agent['device'] = 'desktop';
            } else {
                $agent['device'] = 'bot';
            }


            $agent['browser'] = $user_agent->browser->name;
            if ($agent['device'] != 'bot') {
                $version = explode('.', $user_agent->browser->version->value);
                $agent['browser_ver'] = $version[0];
                $agent['os'] = $user_agent->os->name;
                if (!empty($user_agent->os->version->nickname)) {
                    $agent['os_ver'] = $user_agent->os->version->nickname;
                } else if (!empty($user_agent->os->version->alias)) {
                    $agent['os_ver'] = $user_agent->os->version->alias;
                } else {
                    $agent['os_ver'] = $user_agent->os->version->value;
                }
            } else {
                $agent['bot'] = $agent['browser'];
                $agent['browser_ver'] = '';
                $agent['os'] = '';
                $agent['browser'] = '';
            }

            return $agent;
        } else {
            return array('browser' => '', 'device' => '', 'browser_ver' => '', 'os' => '', 'bot' => '');
        }
    } // parse_user_agent_array

    /**
     * Convert country code to country name
     *
     * @since 5.0
     * 
     * @param string country code
     * @return string country name
     */
    static function country_name_to_code($country)
    {
        if($country == 'Sweeden'){
            $country = 'Sweden';
        }

        $countrycodes = array(
            'other' => 'Other',
            'AF' => 'Afghanistan',
            'AX' => 'Åland Islands',
            'AL' => 'Albania',
            'DZ' => 'Algeria',
            'AS' => 'American Samoa',
            'AD' => 'Andorra',
            'AO' => 'Angola',
            'AI' => 'Anguilla',
            'AQ' => 'Antarctica',
            'AG' => 'Antigua and Barbuda',
            'AR' => 'Argentina',
            'AU' => 'Australia',
            'AT' => 'Austria',
            'AZ' => 'Azerbaijan',
            'BS' => 'Bahamas',
            'BH' => 'Bahrain',
            'BD' => 'Bangladesh',
            'BB' => 'Barbados',
            'BY' => 'Belarus',
            'BE' => 'Belgium',
            'BZ' => 'Belize',
            'BJ' => 'Benin',
            'BM' => 'Bermuda',
            'BT' => 'Bhutan',
            'BO' => 'Bolivia',
            'BA' => 'Bosnia and Herzegovina',
            'BW' => 'Botswana',
            'BV' => 'Bouvet Island',
            'BR' => 'Brazil',
            'IO' => 'British Indian Ocean Territory',
            'BN' => 'Brunei Darussalam',
            'BG' => 'Bulgaria',
            'BF' => 'Burkina Faso',
            'BI' => 'Burundi',
            'KH' => 'Cambodia',
            'CM' => 'Cameroon',
            'CAN' => 'Canada',
            'CV' => 'Cape Verde',
            'KY' => 'Cayman Islands',
            'CF' => 'Central African Republic',
            'TD' => 'Chad',
            'CL' => 'Chile',
            'CN' => 'China',
            'CX' => 'Christmas Island',
            'CC' => 'Cocos (Keeling) Islands',
            'CO' => 'Colombia',
            'KM' => 'Comoros',
            'CG' => 'Congo',
            'CD' => 'Zaire',
            'CK' => 'Cook Islands',
            'CR' => 'Costa Rica',
            'CI' => 'Côte D\'Ivoire',
            'HR' => 'Croatia',
            'CU' => 'Cuba',
            'CY' => 'Cyprus',
            'CZ' => 'Czech Republic',
            'DK' => 'Denmark',
            'DJ' => 'Djibouti',
            'DM' => 'Dominica',
            'DO' => 'Dominican Republic',
            'EC' => 'Ecuador',
            'EG' => 'Egypt',
            'SV' => 'El Salvador',
            'GQ' => 'Equatorial Guinea',
            'ER' => 'Eritrea',
            'EE' => 'Estonia',
            'ET' => 'Ethiopia',
            'FK' => 'Falkland Islands (Malvinas)',
            'FO' => 'Faroe Islands',
            'FJ' => 'Fiji',
            'FI' => 'Finland',
            'FR' => 'France',
            'GF' => 'French Guiana',
            'PF' => 'French Polynesia',
            'TF' => 'French Southern Territories',
            'GA' => 'Gabon',
            'GM' => 'Gambia',
            'GE' => 'Georgia',
            'DE' => 'Germany',
            'GH' => 'Ghana',
            'GI' => 'Gibraltar',
            'GR' => 'Greece',
            'GL' => 'Greenland',
            'GD' => 'Grenada',
            'GP' => 'Guadeloupe',
            'GU' => 'Guam',
            'GT' => 'Guatemala',
            'GG' => 'Guernsey',
            'GN' => 'Guinea',
            'GW' => 'Guinea-Bissau',
            'GY' => 'Guyana',
            'HT' => 'Haiti',
            'HM' => 'Heard Island and Mcdonald Islands',
            'VA' => 'Vatican City State',
            'HN' => 'Honduras',
            'HK' => 'Hong Kong',
            'HU' => 'Hungary',
            'IS' => 'Iceland',
            'IN' => 'India',
            'ID' => 'Indonesia',
            'IR' => 'Iran',
            'IQ' => 'Iraq',
            'IE' => 'Ireland',
            'IM' => 'Isle of Man',
            'IL' => 'Israel',
            'IT' => 'Italy',
            'JM' => 'Jamaica',
            'JP' => 'Japan',
            'JE' => 'Jersey',
            'JO' => 'Jordan',
            'KZ' => 'Kazakhstan',
            'KE' => 'Kenya',
            'KI' => 'Kiribati',
            'KP' => 'Korea, Democratic People\'s Republic of',
            'KR' => 'Korea, Republic of',
            'KW' => 'Kuwait',
            'KG' => 'Kyrgyzstan',
            'LA' => 'Lao People\'s Democratic Republic',
            'LV' => 'Latvia',
            'LB' => 'Lebanon',
            'LS' => 'Lesotho',
            'LR' => 'Liberia',
            'LY' => 'Libyan Arab Jamahiriya',
            'LI' => 'Liechtenstein',
            'LT' => 'Lithuania',
            'LU' => 'Luxembourg',
            'MO' => 'Macao',
            'MK' => 'Macedonia, the Former Yugoslav Republic of',
            'MG' => 'Madagascar',
            'MW' => 'Malawi',
            'MY' => 'Malaysia',
            'MV' => 'Maldives',
            'ML' => 'Mali',
            'MT' => 'Malta',
            'MH' => 'Marshall Islands',
            'MQ' => 'Martinique',
            'MR' => 'Mauritania',
            'MU' => 'Mauritius',
            'YT' => 'Mayotte',
            'MX' => 'Mexico',
            'FM' => 'Micronesia, Federated States of',
            'MD' => 'Moldova, Republic of',
            'MC' => 'Monaco',
            'MN' => 'Mongolia',
            'ME' => 'Montenegro',
            'MS' => 'Montserrat',
            'MA' => 'Morocco',
            'MZ' => 'Mozambique',
            'MM' => 'Myanmar',
            'NA' => 'Namibia',
            'NR' => 'Nauru',
            'NP' => 'Nepal',
            'NL' => 'Netherlands',
            'AN' => 'Netherlands Antilles',
            'NC' => 'New Caledonia',
            'NZ' => 'New Zealand',
            'NI' => 'Nicaragua',
            'NE' => 'Niger',
            'NG' => 'Nigeria',
            'NU' => 'Niue',
            'NF' => 'Norfolk Island',
            'MP' => 'Northern Mariana Islands',
            'NO' => 'Norway',
            'OM' => 'Oman',
            'PK' => 'Pakistan',
            'PW' => 'Palau',
            'PS' => 'Palestine',
            'PA' => 'Panama',
            'PG' => 'Papua New Guinea',
            'PY' => 'Paraguay',
            'PE' => 'Peru',
            'PH' => 'Philippines',
            'PN' => 'Pitcairn',
            'PL' => 'Poland',
            'PT' => 'Portugal',
            'PR' => 'Puerto Rico',
            'QA' => 'Qatar',
            'RE' => 'Réunion',
            'RO' => 'Romania',
            'RU' => 'Russia',
            'RW' => 'Rwanda',
            'SH' => 'Saint Helena',
            'KN' => 'Saint Kitts and Nevis',
            'LC' => 'Saint Lucia',
            'PM' => 'Saint Pierre and Miquelon',
            'VC' => 'Saint Vincent and the Grenadines',
            'WS' => 'Samoa',
            'SM' => 'San Marino',
            'ST' => 'Sao Tome and Principe',
            'SA' => 'Saudi Arabia',
            'SN' => 'Senegal',
            'RS' => 'Serbia',
            'SC' => 'Seychelles',
            'SL' => 'Sierra Leone',
            'SG' => 'Singapore',
            'SK' => 'Slovakia',
            'SI' => 'Slovenia',
            'SB' => 'Solomon Islands',
            'SO' => 'Somalia',
            'ZA' => 'South Africa',
            'GS' => 'South Georgia and the South Sandwich Islands',
            'ES' => 'Spain',
            'LK' => 'Sri Lanka',
            'SD' => 'Sudan',
            'SR' => 'Suriname',
            'SJ' => 'Svalbard and Jan Mayen',
            'SZ' => 'Swaziland',
            'SE' => 'Sweden',
            'CH' => 'Switzerland',
            'SY' => 'Syrian Arab Republic',
            'TW' => 'Taiwan, Province of China',
            'TJ' => 'Tajikistan',
            'TZ' => 'Tanzania, United Republic of',
            'TH' => 'Thailand',
            'TL' => 'Timor-Leste',
            'TG' => 'Togo',
            'TK' => 'Tokelau',
            'TO' => 'Tonga',
            'TT' => 'Trinidad and Tobago',
            'TN' => 'Tunisia',
            'TR' => 'Turkey',
            'TM' => 'Turkmenistan',
            'TC' => 'Turks and Caicos Islands',
            'TV' => 'Tuvalu',
            'UG' => 'Uganda',
            'UA' => 'Ukraine',
            'AE' => 'United Arab Emirates',
            'GB' => 'United Kingdom',
            'US' => 'United States',
            'UM' => 'United States Minor Outlying Islands',
            'UY' => 'Uruguay',
            'UZ' => 'Uzbekistan',
            'VU' => 'Vanuatu',
            'VE' => 'Venezuela',
            'VN' => 'Vietnam',
            'VG' => 'Virgin Islands, British',
            'VI' => 'Virgin Islands, U.S.',
            'WF' => 'Wallis and Futuna',
            'EH' => 'Western Sahara',
            'YE' => 'Yemen',
            'ZM' => 'Zambia',
            'ZW' => 'Zimbabwe',
        );

        $code = array_search($country, $countrycodes);
        
        if(false === $code){
            return 'other';
        } 
        
        return $code;
    } // country_name_to_code


} // class
