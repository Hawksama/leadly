<?php
/**
 * @package LeadlyPlugin
 */

class Deactivate {
    function __construct() {
    
        /* Do nothing here */
        
    }
    
    public static function deactivate() {
        if ( get_option( 'leadly_flush_rewrite_rules_flag' ) ) {
            delete_option( 'leadly_flush_rewrite_rules_flag' );
        } 
        
        flush_rewrite_rules();
    }
}