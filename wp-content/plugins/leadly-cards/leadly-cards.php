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
            if (!$this->haveRequiredPlugins())
                return;
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
            // remove_action('um_after_profile_fields', 'um_add_submit_button_to_profile', 1000);
            // add_action('um_after_profile_fields', array($this, 'um_add_submit_button_to_profile'), 1000);

            // add_action( 'um_after_form_fields', array($this, 'ultimateMemberRegisterCardSave'), 10, 1 );
            add_action( 'um_submit_form_errors_hook__registration', array($this, 'my_submit_form_errors_registration'), 10, 1 );
        }

        function my_submit_form_errors_registration( $args ) {
            $x = 0;
        }

        function ultimateMemberRegisterCardSave( $args ) {
            if(isset($args['custom_fields']['nfc-serial'])) {
                ?>
                <script type="text/javascript">
                    (function ($) {
                        $( document ).ready(function() {
                            $("#um-submit-btn").on("click", function (e) {
                                debugger;
                                setTimeout(() => $(this).submit(), 1000);

                                $(this).addClass("loading");
                                $.post(
                                    '/wp-admin/admin-ajax.php', {
                                        action: "wf301_run_tool",
                                        _ajax_nonce: wf301_vars.run_tool_nonce,
                                        tool: "submit_redirect_rule",
                                        redirect_id: "",
                                        redirect_enabled: true,
                                        redirect_url_from: $("#redirect_url_from").val(),
                                        redirect_url_to: $("#redirect_url_to").val(),
                                        redirect_query: $("#redirect_query").children("option:selected").val(),
                                        redirect_case_insensitive: $("#redirect_case_insensitive").is(
                                            ":checked"
                                        ),
                                        redirect_regex: $("#redirect_regex").is(
                                            ":checked"
                                        ),
                                        redirect_type: $("#redirect_type").children("option:selected").val(),
                                        redirect_position: $("#redirect_position").val(),
                                        redirect_tags: $("#redirect_tags").val(),
                                    },
                                    function (response) {
                                        if (response.success) {
                                            wf301_swal.close();
                                            $(".dataTables_empty").closest("tr").remove();
                                            if ($("#wf301-redirects-table tr#" + response.data.id).length == 1) {
                                                $("#wf301-redirects-table tr#" + response.data.id).replaceWith(
                                                    response.data.row_html
                                                );
                                            } else {
                                                $("#wf301-redirects-table").prepend(response.data.row_html);
                                                $("#wf301-redirects-table tbody")
                                                    .find("tr")
                                                    .first()
                                                    .hide()
                                                    .show(500);
                                            }
                                        } else {
                                            alert(response.data);
                                        }
                                        $("#submit_redirect_rule").removeClass("loading");
                                    }
                                ).fail(function () {
                                    alert("Undocumented error. Please reload the page and try again.");
                                });
                            });
                        });    
                    })(jQuery);
                </script>
                <?php
            }
        }

        function um_add_submit_button_to_profile( $args ) {
            // DO NOT add when reviewing user's details
            if ( UM()->user()->preview == true && is_admin() ) {
                return;
            }
        
            // only when editing
            if ( UM()->fields()->editing == false ) {
                return;
            }
        
            if ( ! isset( $args['primary_btn_word'] ) || $args['primary_btn_word'] == '' ){
                $args['primary_btn_word'] = UM()->options()->get( 'profile_primary_btn_word' );
            }
            if ( ! isset( $args['secondary_btn_word'] ) || $args['secondary_btn_word'] == '' ){
                $args['secondary_btn_word'] = UM()->options()->get( 'profile_secondary_btn_word' );
            } ?>
        
            <div class="um-col-alt LEADLYYYYYYYYYY">
        
                <div class="um-col-1">
                    <div id="um_field_300_birth_date_11"
                        class="um-field um-field-date  um-field-birth_date_11 um-field-date um-field-type_date"
                        data-key="birth_date_11">

                        <div class="um-field-label"><label for="phone_number-300">Phone Number</label>
                            <div class="um-clear"></div>
                        </div>

                        <div class="um-field-area">
                            <div class="um-field-icon">
                                <i class="um-faicon-phone"></i>
                            </div>
                            <input autocomplete="off"
                                class="um-form-field valid not-required um-iconed " type="text" name="phone_number-300"
                                id="phone_number-300" value="+04237672712" placeholder="" data-validate="0"
                                data-key="phone_number">
                        </div>
                    </div>
                </div>

                <?php if ( isset( $args['secondary_btn'] ) && $args['secondary_btn'] != 0 ) { ?>
        
                    <div class="um-left um-half MANUEL">
                        <input type="submit" value="<?php esc_attr_e( wp_unslash( $args['primary_btn_word'] ), 'ultimate-member' ); ?>" class="um-button" />
                    </div>
                    <div class="um-right um-half CARABUS">
                        <a href="<?php echo esc_url( um_edit_my_profile_cancel_uri() ); ?>" class="um-button um-alt">
                            <?php _e( wp_unslash( $args['secondary_btn_word'] ), 'ultimate-member' ); ?>
                        </a>
                    </div>
        
                <?php } else { ?>
        
                    <div class="um-center">
                        <input type="submit" value="<?php esc_attr_e( wp_unslash( $args['primary_btn_word'] ), 'ultimate-member' ); ?>" class="um-button" />
                    </div>
        
                <?php } ?>
        
                <div class="um-clear"></div>
        
            </div>
        
            <?php
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