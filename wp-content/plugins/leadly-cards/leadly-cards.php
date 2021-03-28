<?php
/**
 * @package LeadlyPlugin
 */
/*
Plugin Name: 301 Automatic Redirect
Description: Automatic redirection for each card at the user registration step. Dependencies: 301 Redirect and Ultimate Member plugins.
Version: 1.0
Requires PHP: 7.1
Author: Carabus Alexandru-Manuel
Author URI: https://www.freelancer.com/u/Hawksama
License: GPLv2 or later
Text Domain: leadlyCards
Domain Path: /languages
*/

defined('ABSPATH') or die('Can\'t access this file!');

if( !class_exists('leadlyCards') ) :
    
    class leadlyCards {
        /** @var string The plugin version number */
        var $version = '1.0';
        
        /** @var array The plugin settings array */
        var $settings = array();
        
        /** @var array The plugin data array */
        var $data = array();
        
        /** @var array Storage for class instances */
        var $instances = array();

        private $required_plugins = array('301-redirects', 'ultimate-member');

        function __construct() {
            // vars
            $version  = $this->version;
            $basename = plugin_basename( __FILE__ );
            $path     = plugin_dir_path( __FILE__ );
            $url      = plugin_dir_url( __FILE__ );
            $slug     = dirname($basename);

            $this->settings = array(
                // basic
                'name'				=> __('Leadly Plugin', 'leadly'),
                'version'			=> $version,
                            
                // urls
                'file'				=> __FILE__,
                'basename'			=> $basename,
                'path'				=> $path,
                'url'				=> $url,
                'slug'				=> $slug
            );

            // constants
            $this->define( 'LEADLY', 			true );
            $this->define( 'LEADLY_VERSION', 	$version );
            $this->define( 'LEADLY_PATH', 		$path );  
		
            // Include utility functions.
            include_once( LEADLY_PATH . 'includes/utility-functions.php');

            // Include activate and deactivate functions.
            include_once( LEADLY_PATH . 'includes/activate.php');
            include_once( LEADLY_PATH . 'includes/deactivate.php');

            $this->initialize();
        }

        function initialize() {
            register_activation_hook(__FILE__, array('Activate', 'activate'));
            register_deactivation_hook(__FILE__, array('Deactivate', 'deactivate'));
            add_action('init', array($this, 'init'), 10);
            add_action('init', array('Activate', 'check_flush_rewrite_rules_flag'), 11);
        }

        function init() {
            if (!$this->haveRequiredPlugins()) {
                deactivate_plugins( '/leadly-cards/leadly-cards.php' );
                return;
            }
            load_plugin_textdomain('leadly', false, dirname(plugin_basename(__FILE__)) . '/languages');

            $this->registerActions();
        }
        
        function haveRequiredPlugins() {
            if (empty($this->required_plugins))
                return true;
            $active_plugins = (array) get_option('active_plugins', array());
            if (is_multisite()) {
                $active_plugins = array_merge($active_plugins, get_site_option('active_sitewide_plugins', array()));
            }
            foreach ($this->required_plugins as $key => $required) {
                $required = (!is_numeric($key)) ? "{$key}/{$required}.php" : "{$required}/{$required}.php";
                if (!in_array($required, $active_plugins) && !array_key_exists($required, $active_plugins))
                    return false;
            }
            return true;
        }

        function registerActions() {
            add_action( 'um_registration_set_extra_data', array($this, 'ultimateMemberRegisterCardSave'), 10, 2 );
        }

        function ultimateMemberRegisterCardSave( $user_id, $args ) {
            $customFields = maybe_unserialize($args['custom_fields']);

            if(isset($customFields['nfc-serial'])) {
                $formID = $_POST['form_id'];
                $username = $_POST['user_login-' . $formID];
                $nfcSerial = $_POST['nfc-serial-' . $formID];

                global $wpdb;

                $root = get_bloginfo('url') . '/';

                $from_url = trim(str_replace($root, null, 'card-serial/' . $nfcSerial));
                $to_url = trim($root . 'user/' . $username);

                if (0 !== strpos($from_url, 'http')) {
                    $from_url = '/' . ltrim($from_url, '/');
                }

                if (0 !== strpos($to_url, 'http') && 0 !== strpos($to_url, 'ftp')) {
                    $to_url = '/' . ltrim($to_url, '/');
                }

                $rule = array(
                    'url_from'          => $from_url,
                    'url_to'            => $to_url,
                    'type'              => 301,
                    'query_parameters'  => 'ignore',
                    'case_insensitive'  => 'enabled',
                    'regex'             => 'disabled',
                    'status'            => 'enabled',
                    'position'          => 10,
                    'tags'              => ''
                );

                WF301_functions::save_redirect_rule($rule);
            }

            return $args;
        }

        /**
        *  define
        *
        *  Defines constants.
        *
        *  @param	string $name
        *  @return	boolean
        */
        protected function define( $name, $value = true ) {
            if( !defined($name) ) {
                define( $name, $value );
            }
        }
    }
    
    function leadlyCards() {

        // globals
        global $leadlyCards;
                
        // initializez
        if( !isset($leadlyCards) ) {
            $leadlyCards = new leadlyCards();
        }
    
        // return
        return $leadlyCards;
    }

    // initialize
    leadlyCards();

endif;

