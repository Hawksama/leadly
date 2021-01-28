<?php

class ACUI_Helper{
    public function detect_delimiter( $file ) {
        $delimiters = array(
            ';' => 0,
            ',' => 0,
            "\t" => 0,
            "|" => 0
        );
    
        $handle = @fopen($file, "r");
        $firstLine = fgets($handle);
        fclose($handle); 
        foreach ($delimiters as $delimiter => &$count) {
            $count = count(str_getcsv($firstLine, $delimiter));
        }
    
        return array_search(max($delimiters), $delimiters);
    }

    public function user_id_exists( $user_id ){
        if ( get_userdata( $user_id ) === false )
            return false;
        else
            return true;
    }

    public function get_roles_by_user_id( $user_id ){
        $roles = array();
        $user = new WP_User( $user_id );
    
        if ( !empty( $user->roles ) && is_array( $user->roles ) ) {
            foreach ( $user->roles as $role )
                $roles[] = $role;
        }
    
        return $roles;
    }

    public static function get_editable_roles() {
        global $wp_roles;
    
        $all_roles = $wp_roles->roles;
        $editable_roles = apply_filters('editable_roles', $all_roles);
        $list_editable_roles = array();
    
        foreach ($editable_roles as $key => $editable_role)
            $list_editable_roles[$key] = $editable_role["name"];
        
        return $list_editable_roles;
    }

    public static function get_errors_by_row( $errors, $row, $type = 'error' ){
        $errors_found = array();

        foreach( $errors as $error ){
            if( $error['row'] == $row && ( $error['type'] == $type || 'any' == $type ) ){
                $errors_found[] = $error['message'];
            }
        }

        return $errors_found;
    }

    public function string_conversion( $string ){
        if(!preg_match('%(?:
        [\xC2-\xDF][\x80-\xBF]        # non-overlong 2-byte
        |\xE0[\xA0-\xBF][\x80-\xBF]               # excluding overlongs
        |[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}      # straight 3-byte
        |\xED[\x80-\x9F][\x80-\xBF]               # excluding surrogates
        |\xF0[\x90-\xBF][\x80-\xBF]{2}    # planes 1-3
        |[\xF1-\xF3][\x80-\xBF]{3}                  # planes 4-15
        |\xF4[\x80-\x8F][\x80-\xBF]{2}    # plane 16
        )+%xs', $string)){
            return utf8_encode($string);
        }
        else
            return $string;
    }

    public function get_wp_users_fields(){
        return array( "id", "user_email", "user_nicename", "user_url", "display_name", "nickname", "first_name", "last_name", "description", "jabber", "aim", "yim", "user_registered", "password", "user_pass", "locale", "show_admin_bar_front", "user_login" );
    }

    function get_restricted_fields(){
        $wp_users_fields = $this->get_wp_users_fields();
        $wp_min_fields = array( "Username", "Email", "role"  );
        $acui_restricted_fields = array_merge( $wp_users_fields, $wp_min_fields );
        
        return apply_filters( 'acui_restricted_fields', $acui_restricted_fields );
    }

    function get_not_meta_fields(){
        return apply_filters( 'acui_not_meta_fields', array() );
    }

    public function get_random_unique_username( $prefix = '' ){
        do {
            $rnd_str = sprintf("%06d", mt_rand(1, 999999));
        } while( username_exists( $prefix . $rnd_str ) );
        
        return $prefix . $rnd_str;
    }

    function new_error( $row, $message = '', $type = 'error' ){
        return array( 'row' => $row, 'message' => $message, 'type' => $type );
    }

    public function maybe_update_email( $user_id, $email, $password, $update_emails_existing_users ){
        $user_object = get_user_by( 'id', $user_id );

        if( $user_object->user_email == $email )
            return $user_id;

        switch( $update_emails_existing_users ){
            case 'yes':
                $user_id = wp_update_user( array( 'ID' => $user_id, 'user_email' => $email ) );
            break;

            case 'no':
                $user_id = 0;
            break;

            case 'create':
                $user_id = wp_insert_user( array(
                    'user_login'  =>  $this->get_random_unique_username( 'duplicated_username_' ),
                    'user_email'  =>  $email,
                    'user_pass'   =>  $password
                ) );
            break;
           
        }

        return $user_id;
    }

    public static function get_attachment_id_by_url( $url ) {
        $wp_upload_dir = wp_upload_dir();
        // Strip out protocols, so it doesn't fail because searching for http: in https: dir.
        $dir = set_url_scheme( trailingslashit( $wp_upload_dir['baseurl'] ), 'relative' );
    
        // Is URL in uploads directory?
        if ( false !== strpos( $url, $dir ) ) {
    
            $file = basename( $url );
    
            $query_args = array(
                'post_type'   => 'attachment',
                'post_status' => 'inherit',
                'fields'      => 'ids',
                'meta_query'  => array(
                    array(
                        'key'     => '_wp_attachment_metadata',
                        'compare' => 'LIKE',
                        'value'   => $file,
                    ),
                ),
            );
    
            $query = new WP_Query( $query_args );
    
            if ( $query->have_posts() ) {
                foreach ( $query->posts as $attachment_id ) {
                    $meta          = wp_get_attachment_metadata( $attachment_id );
                    $original_file = basename( $meta['file'] );
                    $cropped_files = wp_list_pluck( $meta['sizes'], 'file' );
    
                    if ( $original_file === $file || in_array( $file, $cropped_files ) ) {
                        return (int) $attachment_id;
                    }
                }
            }
        }
    
        return false;
    }

    function print_table_header_footer( $headers ){
        ?>
        <h3><?php echo apply_filters( 'acui_log_inserting_updating_data_title', __( 'Inserting and updating data', 'import-users-from-csv-with-meta' ) ); ?></h3>
        <table id="acui_results">
            <thead>
                <tr>
                    <th><?php _e( 'Row', 'import-users-from-csv-with-meta' ); ?></th>
                    <?php foreach( $headers as $element ): 
                        echo "<th>" . $element . "</th>"; 
                    endforeach; ?>
                    <?php do_action( 'acui_header_table_extra_rows' ); ?>
                </tr>
            </thead>
            <tfoot>
                <tr>
                    <th><?php _e( 'Row', 'import-users-from-csv-with-meta' ); ?></th>
                    <?php foreach( $headers as $element ): 
                        echo "<th>" . $element . "</th>"; 
                    endforeach; ?>
                    <?php do_action( 'acui_header_table_extra_rows' ); ?>
                </tr>
            </tfoot>
            <tbody>
        <?php
    }

    function print_table_end(){
        ?>
            </tbody>
        </table>
        <?php
    }

    function print_row_imported( $row, $data, $errors ){
        $styles = "";
        
        if( !empty( ACUI_Helper::get_errors_by_row( $errors, $row, 'any' ) ) )
            $styles = "background-color:red; color:white;";

        echo "<tr style='$styles' ><td>" . ($row - 1) . "</td>";
        foreach ( $data as $element ){
            if( is_wp_error( $element ) )
                $element = $element->get_error_message();
            elseif( is_array( $element ) )
                $element = implode ( ',' , $element );

            $element = sanitize_textarea_field( $element );
            echo "<td>$element</td>";
        }

        echo "</tr>\n";
    
        flush();
    }

    function print_errors( $errors ){
        if( empty( $errors ) )
            return;
        ?>
        <h3><?php _e( 'Errors, warnings and notices', 'import-users-from-csv-with-meta' ); ?></h3>
        <table id="acui_errors">
            <thead>
                <tr>
                    <th><?php _e( 'Row', 'import-users-from-csv-with-meta' ); ?></th>
                    <th><?php _e( 'Details', 'import-users-from-csv-with-meta' ); ?></th>
                    <th><?php _e( 'Type', 'import-users-from-csv-with-meta' ); ?></th>
                </tr>
            </thead>
            <tfoot>
                <tr>
                    <th><?php _e( 'Row', 'import-users-from-csv-with-meta' ); ?></th>
                    <th><?php _e( 'Details', 'import-users-from-csv-with-meta' ); ?></th>
                    <th><?php _e( 'Type', 'import-users-from-csv-with-meta' ); ?></th>
                </tr>
            </tfoot>
            <tbody>
                <?php foreach( $errors as $error ): ?>
                <tr>
                    <td><?php echo $error['row']; ?></td>
                    <td><?php echo $error['message']; ?></td>
                    <td><?php echo $error['type']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    function execute_datatable(){
        ?>
        <script>
        jQuery( document ).ready( function( $ ){
            $( '#acui_results,#acui_errors' ).DataTable();
        } )
        </script>
        <?php
    }
    
    public function basic_css(){
        ?>
        <style type="text/css">
            .wrap{
                overflow-x:auto!important;
            }

            .wrap table{
                min-width:800px!important;
            }

            .wrap table th,
            .wrap table td{
                width:200px!important;
            }
        </style>
        <?php
    }
}