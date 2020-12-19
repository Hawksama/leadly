<?php
/**
 * @package LeadlyPlugin
 */
/*
Plugin Name: Leadly Redirect
Plugin URI: #
Description: Automated redirect for each card
Version: 1.0
Author: Leadly Manuel Alexandru
Author URI: #
License: GPLv2 or later
Text Domain: leadlyCards
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

            load_plugin_textdomain('leadly', false, $slug . '/languages');

            $this->initialize();
        }

        function initialize() {
            register_activation_hook(__FILE__, array('Activate', 'activate'));
            register_deactivation_hook(__FILE__, array('Deactivate', 'deactivate'));
            add_action('init', array($this, 'init'), 10);
            add_action('init', array('Activate', 'check_flush_rewrite_rules_flag'), 11);
        }

        function init() {
            $x = 0;
            // create rest api
            add_action('rest_api_init', array($this, 'register_routes'));
        }
        
        function register_routes() {
            $y = 1;
            register_rest_route('leadly/v1', '/card(?P<id>\d+)', [
                'methods' => 'GET',
                'callback' => [$this,'get_user'],
                'args' => array(
                    'id' => array(
                        'default' => 10,
                        'validate_callback' => function($param, $request, $key) {
                            return is_numeric( $param );
                        }
                    )
                ),
            ]);
        }

        /**
        *  get_user
        *
        *  Return one user based on URL parameter 'id'.
        *
        *  @return	json
        */
        public function get_user() {

            /** @var int Get the user id from the request */
            $userId = $_GET['number'];

            /** @var json Database saved json written by the plugin IF true, else, array from $apiResponse */
            if( false === ($user = get_transient("user_api_$userId"))) {

                /** @var array The API request response  */
                $apiResponse = wp_remote_request(get_option('api_link') . '/' . $userId, array(
                    'ssl_verify' => true
                ));
        
                if(is_wp_error( $apiResponse ) && WP_DEBUG == true){
                    printf(
                        'There was an ERROR in your request.<br />Code: %s<br />Message: %s',
                        $apiResponse->get_error_code(),
                        $apiResponse->get_error_message()
                    );
                }
                
                // Prepare the data
                $user = trim( wp_remote_retrieve_body( $apiResponse ) );
                
                // Double check the Curl response.
                if(strlen($user) == 0) {
                    $apiResponse = wp_remote_request(get_option('api_link') . '/' . $userId);
                    $user = trim( wp_remote_retrieve_body( $apiResponse ) );
                }

                // Convert output to JSON if is not
                if ( strstr( wp_remote_retrieve_header( $apiResponse, 'content-type' ), 'json' ) ){
                    $user = json_decode( $user );
                }

                set_transient("user_api_$userId", $user, DAY_IN_SECONDS);
            }

            return $user;
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