<?php

class ACUI_Import{
    public function show(){
        if ( !current_user_can( apply_filters( 'acui_capability', 'create_users' ) ) ) {
            wp_die( __( 'You are not allowed to see this content.', 'import-users-from-csv-with-meta' ));
        }
    
        $tab = ( isset ( $_GET['tab'] ) ) ? $_GET['tab'] : 'homepage';
    
        if( isset( $_POST ) && !empty( $_POST ) ):
            if ( !wp_verify_nonce( $_POST['security'], 'codection-security' ) ) {
                wp_die( __( 'Nonce check failed', 'import-users-from-csv-with-meta' ) ); 
            }
    
            switch ( $tab ){
                  case 'homepage':
                      update_option( 'acui_last_roles_used', ( empty( $_POST['role'] ) ? '' : array_map( 'sanitize_text_field', $_POST['role'] ) ) );
                      $this->fileupload_process( $_POST, false );
                      return;
                  break;
    
                  case 'frontend':
                      do_action( 'acui_frontend_save_settings', $_POST );
                  break;
    
                case 'columns':
                    do_action( 'acui_columns_save_settings', $_POST );
                  break;
    
                case 'mail-options':
                    do_action( 'acui_mail_options_save_settings', $_POST );
                  break;
    
                  case 'cron':
                      do_action( 'acui_cron_save_settings', $_POST );
                  break;
              }
        endif;
        
        ACUI_Import::admin_tabs( $tab );
        
          switch ( $tab ){
              case 'homepage' :
                ACUI_Homepage::admin_gui();	
            break;
    
            case 'export' :
                ACUI_Exporter::admin_gui();	
            break;
    
            case 'frontend':
                ACUI_Frontend::admin_gui();	
            break;
    
            case 'columns':
                ACUI_Columns::admin_gui();
            break;
    
            case 'meta-keys':
                ACUI_MetaKeys::admin_gui();
            break;
    
            case 'doc':
                ACUI_Doc::message();
            break;
    
            case 'mail-options':
                ACUI_Email_Options::admin_gui();
            break;
    
            case 'cron':
                ACUI_Cron::admin_gui();
            break;
    
            case 'donate':
                ACUI_Donate::message();
            break;
    
            case 'help':
                ACUI_Help::message();
            break;
    
            case 'new_features':
                ACUI_NewFeatures::message();
            break;
    
            default:
                do_action( 'acui_tab_action_' . $tab );
            break;
        }
    }

    public static function admin_tabs( $current = 'homepage' ) {
        $tabs = array( 
                'homepage' => __( 'Import', 'import-users-from-csv-with-meta' ),
                'export' => __( 'Export', 'import-users-from-csv-with-meta' ),
                'frontend' => __( 'Frontend', 'import-users-from-csv-with-meta' ), 
                'cron' => __( 'Cron import', 'import-users-from-csv-with-meta' ), 
                'columns' => __( 'Extra profile fields', 'import-users-from-csv-with-meta' ), 
                'meta-keys' => __( 'Meta keys', 'import-users-from-csv-with-meta' ), 
                'mail-options' => __( 'Mail options', 'import-users-from-csv-with-meta' ), 
                'doc' => __( 'Documentation', 'import-users-from-csv-with-meta' ), 
                'donate' => __( 'Donate/Patreon', 'import-users-from-csv-with-meta' ), 
                'shop' => __( 'Shop', 'import-users-from-csv-with-meta' ), 
                'help' => __( 'Hire an expert', 'import-users-from-csv-with-meta' ),
               // 'new_features' => __( 'New features', 'import-users-from-csv-with-meta' )
        );
    
        $tabs = apply_filters( 'acui_tabs', $tabs );
    
        echo '<div id="icon-themes" class="icon32"><br></div>';
        echo '<h2 class="nav-tab-wrapper">';
        foreach( $tabs as $tab => $name ){
               $class = ( $tab == $current ) ? ' nav-tab-active' : '';
    
            if( $tab == "shop"  ){
                $href = "https://codection.com/tienda/";	
                $target = "_blank";
            }
            else{
                $href = "?page=acui&tab=$tab";
                $target = "_self";
            }
    
            echo "<a class='nav-tab$class' href='$href' target='$target'>$name</a>";
    
        }
        echo '</h2>';
    }

    public function fileupload_process( $form_data, $is_cron = false, $is_frontend  = false ) {
        if ( !defined( 'DOING_CRON' ) && ( !isset( $form_data['security'] ) || !wp_verify_nonce( $form_data['security'], 'codection-security' ) ) ){
            wp_die( __( 'Nonce check failed', 'import-users-from-csv-with-meta' ) ); 
        }
    
        if( empty( $_FILES['uploadfile']['name'] ) || $is_frontend ):
              $path_to_file = wp_normalize_path( $form_data["path_to_file"] );
              
            if( validate_file( $path_to_file ) !== 0 ){
                wp_die( __( 'Error, path to file is not well written', 'import-users-from-csv-with-meta' ) . ": $path_to_file" );
            } 
    
            if( !file_exists ( $path_to_file ) ){
                wp_die( __( 'Error, we cannot find the file', 'import-users-from-csv-with-meta' ) . ": $path_to_file" );
            }
    
            $this->import_users( $path_to_file, $form_data, 0, $is_cron, $is_frontend );
        else:
              $uploadfile = wp_handle_upload( $_FILES['uploadfile'], array( 'test_form' => false, 'mimes' => array('csv' => 'text/csv') ) );
    
            if ( !$uploadfile || isset( $uploadfile['error'] ) ) {
                wp_die( __( 'Problem uploading file to import. Error details: ' . var_export( $uploadfile['error'], true ), 'import-users-from-csv-with-meta' ));
            } else {
                $this->import_users( $uploadfile['file'], $form_data, ACUI_Helper::get_attachment_id_by_url( $uploadfile['url'] ), $is_cron, $is_frontend );
            }
        endif;
    }

    public function import_users( $file, $form_data, $attach_id = 0, $is_cron = false, $is_frontend = false ){
        if ( ! function_exists( 'get_editable_roles' ) ) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
        }
        ?>
        <div class="wrap">
            <h2><?php echo apply_filters( 'acui_log_main_title', __('Importing users','import-users-from-csv-with-meta') ); ?></h2>
            <?php
                set_time_limit(0);
                
                do_action( 'before_acui_import_users' );
    
                $acui_helper = new ACUI_Helper();
                $restricted_fields = $acui_helper->get_restricted_fields();
                $all_roles = array_keys( wp_roles()->roles );
                $editable_roles = array_keys( get_editable_roles() );
    
                $users_registered = array();
                $headers = array();
                $headers_filtered = array();
                $is_backend = !$is_frontend && !$is_cron;			
                $update_existing_users = isset( $form_data["update_existing_users"] ) ? sanitize_text_field( $form_data["update_existing_users"] ) : '';
    
                $role_default = isset( $form_data["role"] ) ? $form_data["role"] : array( '' );
                if( !is_array( $role_default ) )
                    $role_default = array( $role_default );
                array_walk( $role_default, 'sanitize_text_field' );
                
                $update_emails_existing_users = isset( $form_data["update_emails_existing_users"] ) ? sanitize_text_field( $form_data["update_emails_existing_users"] ) : 'yes';
                $update_roles_existing_users = isset( $form_data["update_roles_existing_users"] ) ? sanitize_text_field( $form_data["update_roles_existing_users"] ) : '';
                $update_allow_update_passwords = isset( $form_data["update_allow_update_passwords"] ) ? sanitize_text_field( $form_data["update_allow_update_passwords"] ) : 'yes';
                $empty_cell_action = isset( $form_data["empty_cell_action"] ) ? sanitize_text_field( $form_data["empty_cell_action"] ) : '';
                $delete_users = isset( $form_data["delete_users"] ) ? sanitize_text_field( $form_data["delete_users"] ) : '';
                $delete_users_assign_posts = isset( $form_data["delete_users_assign_posts"] ) ? sanitize_text_field( $form_data["delete_users_assign_posts"] ) : '';
                $delete_users_only_specified_role = isset( $form_data["delete_users_only_specified_role"] ) ? sanitize_text_field( $form_data["delete_users_only_specified_role"] ) : false;			
    
                $change_role_not_present = isset( $form_data["change_role_not_present"] ) ? sanitize_text_field( $form_data["change_role_not_present"] ) : '';
                $change_role_not_present_role = isset( $form_data["change_role_not_present_role"] ) ? sanitize_text_field( $form_data["change_role_not_present_role"] ) : '';
                
                if( $is_cron ){
                    $allow_multiple_accounts = ( get_option( "acui_cron_allow_multiple_accounts" ) == "allowed" ) ? "allowed" : "not_allowed";
                }
                else {
                    $allow_multiple_accounts = ( empty( $form_data["allow_multiple_accounts"] ) ) ? "not_allowed" : sanitize_text_field( $form_data["allow_multiple_accounts"] );
                }
        
                update_option( "acui_manually_send_mail", isset( $form_data["sends_email"] ) && $form_data["sends_email"] == 'yes' );
                 update_option( "acui_manually_send_mail_updated", isset( $form_data["send_email_updated"] ) && $form_data["send_email_updated"] == 'yes' );
    
                // disable WordPress default emails if this must be disabled
                if( !get_option('acui_automatic_wordpress_email') ){
                    add_filter( 'send_email_change_email', function() { return false; }, 999 );
                    add_filter( 'send_password_change_email', function() { return false; }, 999 );
                }
    
                // action
                echo apply_filters( "acui_log_header", "<h3>" . __('Ready to registers','import-users-from-csv-with-meta') . "</h3>" );
                echo apply_filters( "acui_log_header_first_row_explanation", "<p>" . __('First row represents the form of sheet','import-users-from-csv-with-meta') . "</p>" );
    
                $row = 0;
                $positions = array();
                $errors = array();
                $results = array( 'created' => 0, 'updated' => 0 );
    
                ini_set('auto_detect_line_endings',TRUE);
    
                $delimiter = $acui_helper->detect_delimiter( $file );
    
                $manager = new SplFileObject( $file );
                while ( $data = $manager->fgetcsv( $delimiter ) ):
                    $row++;

                    if( count( $data ) == 1 )
                        $data = $data[0];
                    
                    if( $data == NULL ){
                        break;
                    }
                    elseif( !is_array( $data ) ){
                        echo apply_filters( 'acui_message_csv_file_bad_formed', __( 'CSV file seems to be bad formed. Please use LibreOffice to create and manage CSV to be sure the format is correct', 'import-users-from-csv-with-meta') );
                        break;
                    }
    
                    foreach ( $data as $key => $value ){
                        $data[ $key ] = preg_replace( '/<script\b[^>]*>(.*?)<\/script>/is', '', trim( $value ) );
                    }
    
                    for( $i = 0; $i < count($data); $i++ ){
                        $data[$i] = $acui_helper->string_conversion( $data[$i] );
    
                        if( is_serialized( $data[$i] ) ) // serialized
                            $data[$i] = maybe_unserialize( $data[$i] );
                        elseif( strpos( $data[$i], "::" ) !== false  ) // list of items
                            $data[$i] = explode( "::", $data[$i] );
                    }
                    
                    if( $row == 1 ):
                        $data = apply_filters( 'pre_acui_import_header', $data );
    
                        // check min columns username - email
                        if(count( $data ) < 2){
                            echo "<div id='message' class='error'>" . __( 'File must contain at least 2 columns: username and email', 'import-users-from-csv-with-meta' ) . "</div>";
                            break;
                        }
    
                        $i = 0;
                        $password_position = false;
                        $id_position = false;
                        
                        foreach ( $restricted_fields as $acui_restricted_field ) {
                            $positions[ $acui_restricted_field ] = false;
                        }
    
                        foreach( $data as $element ){
                            $headers[] = $element;
    
                            if( in_array( strtolower( $element ) , $restricted_fields ) )
                                $positions[ strtolower( $element ) ] = $i;
    
                            if( !in_array( strtolower( $element ), $restricted_fields ) )
                                $headers_filtered[] = $element;
    
                            $i++;
                        }
    
                        $columns = count( $data );
    
                        update_option( "acui_columns", $headers_filtered );
    
                        $acui_helper->basic_css();
                        
                        $acui_helper->print_table_header_footer( $headers );
                    else:
                        $data = apply_filters( 'pre_acui_import_single_user_data', $data, $headers );
                        
                        if( count( $data ) != $columns ): // if number of columns is not the same that columns in header
                            $errors[] = $acui_helper->new_error( $row, __( 'Row does not have the same columns than the header, we are going to ignore this row', 'import-users-from-csv-with-meta') );
                            continue;
                        endif;
    
                        do_action('pre_acui_import_single_user', $headers, $data );
                        $data = apply_filters('pre_acui_import_single_user_data', $data, $headers);
    
                        $username = empty( $data[0] ) ? $acui_helper->get_random_unique_username( 'user_' ) : $data[0];
                        $email = $data[1];
                        $user_id = 0;
                        $password_position = $positions["password"];
                        $password = ( $password_position === false ) ? wp_generate_password( apply_filters( 'acui_auto_password_length', 12 ), apply_filters( 'acui_auto_password_special_chars', true ), apply_filters( 'acui_auto_password_extra_special_chars', false ) ) : $data[ $password_position ];
                        $role_position = $positions["role"];
                        $role = "";
                        $id_position = $positions["id"];
                        $id = ( empty( $id_position ) ) ? '' : $data[ $id_position ];
                        $created = true;
                        
                        if( $role_position === false ){
                            $role = $role_default;
                        }
                        else{
                            $roles_cells = explode( ',', $data[ $role_position ] );
                            
                            if( !is_array( $roles_cells ) )
                                $roles_cells = array( $roles_cells );
    
                            array_walk( $roles_cells, 'trim' );
                            
                            foreach( $roles_cells as $it => $role_cell )
                                $roles_cells[ $it ] = strtolower( $role_cell );
                            
                            $role = $roles_cells;
                        }
    
                        if ( !empty( array_diff( $role, $all_roles ) ) ){
                            $errors[] = $acui_helper->new_error( $row, sprintf( __( 'Some of the next roles "%s" does not exists', 'import-users-from-csv-with-meta' ), implode( ', ', $role ) ) );
                            continue;
                        }
    
                        if ( !empty( array_diff( $role, $editable_roles ) ) ){ // users only are able to import users with a role they are allowed to edit
                            $errors[] = $acui_helper->new_error( $row, sprintf( __( 'You do not have permission to assign some of the next roles "%s"', 'import-users-from-csv-with-meta' ), implode( ', ', $role ) ) );     
                            $created = false;
                            continue;
                        }
    
                        if( !empty( $email ) && ( ( sanitize_email( $email ) == '' ) ) ){ // if email is invalid
                            $errors[] = $acui_helper->new_error( $row, sprintf( __( 'Invalid email "%s"', 'import-users-from-csv-with-meta' ), $email ) );     
                            $data[0] = __('Invalid EMail','import-users-from-csv-with-meta')." ($email)";
                        }
                        elseif( empty( $email) ) {
                            $errors[] = $acui_helper->new_error( $row, __( 'Email not specified', 'import-users-from-csv-with-meta' ) );     
                            $data[0] = __( 'EMail not specified', 'import-users-from-csv-with-meta' );
                        }
    
                        if( !empty( $id ) ){ // if user have used id
                            if( $acui_helper->user_id_exists( $id ) ){
                                if( $update_existing_users == 'no' ){
                                    $errors[] = $acui_helper->new_error( $row, sprintf( __( 'User with ID "%s" exists, we ignore it', 'import-users-from-csv-with-meta' ), $id ), 'notice' );     
                                    continue;
                                }
    
                                // we check if username is the same than in row
                                $user = get_user_by( 'ID', $id );
    
                                if( $user->user_login == $username ){
                                    $user_id = $id;
                                    
                                    if( $password !== "" && $update_allow_update_passwords == 'yes' )
                                        wp_set_password( $password, $user_id );
    
                                    $new_user_id = $acui_helper->maybe_update_email( $user_id, $email, $password, $update_emails_existing_users );
                                    if( empty( $new_user_id ) ){
                                        $errors[] = $acui_helper->new_error( $row, sprintf( __( 'User with email "%s" exists, we ignore it', 'import-users-from-csv-with-meta' ), $email ), 'notice' );     
                                        continue;
                                    }
                                    
                                    if( is_wp_error( $new_user_id ) ){
                                        $errors[] = $acui_helper->new_error( $row, $new_user_id->get_error_message() );     
                                        $data[0] = $new_user_id->get_error_message();
                                        $created = false;
                                    }
                                    elseif( $new_user_id == $user_id)
                                        $created = false;
                                    else{
                                        $user_id = $new_user_id;
                                        $new_user = get_user_by( 'id', $new_user_id );
                                        $data[0] = sprintf( __( 'Email has changed, new user created with username %s', 'import-users-from-csv-with-meta' ), $new_user->user_login );
                                        $errors[] = $acui_helper->new_error( $row, $data[0] );     
                                        $created = true;
                                    }
                                }
                                else{
                                    $errors[] = $acui_helper->new_error( $row, sprintf( __( 'Problems with ID "%s" username is not the same in the CSV and in database', 'import-users-from-csv-with-meta' ), $id ) );     
                                    continue;
                                }
                            }
                            else{
                                $user_id = wp_insert_user( array(
                                    'ID'		  =>  $id,
                                    'user_login'  =>  $username,
                                    'user_email'  =>  $email,
                                    'user_pass'   =>  $password
                                ) );
    
                                $created = true;
                            }
                        }
                        elseif( username_exists( $username ) ){
                            if( $update_existing_users == 'no' ){
                                $errors[] = $acui_helper->new_error( $row, sprintf( __( 'User with username "%s" exists, we ignore it', 'import-users-from-csv-with-meta' ), $username ), 'notice' );
                                continue;
                            }
    
                            $user_object = get_user_by( "login", $username );
                            $user_id = $user_object->ID;
    
                            if( $password !== "" && $update_allow_update_passwords == 'yes' )
                                wp_set_password( $password, $user_id );
    
                            $new_user_id = $acui_helper->maybe_update_email( $user_id, $email, $password, $update_emails_existing_users );
                            if( empty( $new_user_id ) ){
                                $errors[] = $acui_helper->new_error( $row, sprintf( __( 'User with email "%s" exists, we ignore it', 'import-users-from-csv-with-meta' ), $email ), 'notice' );     
                                continue;
                            }
                            
                            if( is_wp_error( $new_user_id ) ){
                                $data[0] = $new_user_id->get_error_message();
                                $errors[] = $acui_helper->new_error( $row, $data[0] );
                                $created = false;
                            }
                            elseif( $new_user_id == $user_id)
                                $created = false;
                            else{
                                $user_id = $new_user_id;
                                $new_user = get_user_by( 'id', $new_user_id );
                                $data[0] = sprintf( __( 'Email has changed, new user created with username %s', 'import-users-from-csv-with-meta' ), $new_user->user_login );
                                $errors[] = $acui_helper->new_error( $row, $data[0], 'warning' );     
                                $created = true;
                            }
                        }
                        elseif( email_exists( $email ) && $allow_multiple_accounts == "not_allowed" ){ // if the email is registered, we take the user from this and we don't allow repeated emails
                            if( $update_existing_users == 'no' ){
                                continue;
                            }
    
                            $user_object = get_user_by( "email", $email );
                            $user_id = $user_object->ID;
                            
                            $data[0] = sprintf( __( 'User already exists as: %s (in this CSV file is called: %s)', 'import-users-from-csv-with-meta' ), $user_object->user_login, $username );
                            $errors[] = $acui_helper->new_error( $row, $data[0], 'warning' );
    
                            if( $password !== "" && $update_allow_update_passwords == 'yes' )
                                wp_set_password( $password, $user_id );
    
                            $created = false;
                        }
                        elseif( email_exists( $email ) && $allow_multiple_accounts == "allowed" ){ // if the email is registered and repeated emails are allowed
                            // if user is new, but the password in csv is empty, generate a password for this user
                            if( $password === "" ) {
                                $password = wp_generate_password( apply_filters( 'acui_auto_password_length', 12 ), apply_filters( 'acui_auto_password_special_chars', true ), apply_filters( 'acui_auto_password_extra_special_chars', false ) );
                            }
                            
                            $hacked_email = ACUI_AllowMultipleAccounts::hack_email( $email );
                            $user_id = wp_create_user( $username, $password, $hacked_email );
                            ACUI_AllowMultipleAccounts::hack_restore_remapped_email_address( $user_id, $email );
                        }
                        else{
                            // if user is new, but the password in csv is empty, generate a password for this user
                            if( $password === "" ) {
                                $password = wp_generate_password( apply_filters( 'acui_auto_password_length', 12 ), apply_filters( 'acui_auto_password_special_chars', true ), apply_filters( 'acui_auto_password_extra_special_chars', false ) );
                            }
                            
                            $user_id = wp_create_user( $username, $password, $email );
                        }
                            
                        if( is_wp_error( $user_id ) ){ // in case the user is generating errors after this checks
                            $errors[] = $acui_helper->new_error( $row, sprintf( __( 'Problems with user: "%s" does not exists, error: %s', 'import-users-from-csv-with-meta' ), $username, $user_id->get_error_message() ) );
                            continue;
                        }
    
                        $users_registered[] = $user_id;
                        $user_object = new WP_User( $user_id );
    
                        if( $created || $update_roles_existing_users != 'no' ){
                            if( !( in_array("administrator", $acui_helper->get_roles_by_user_id( $user_id ), FALSE) || is_multisite() && is_super_admin( $user_id ) )){
                                
                                if( $update_roles_existing_users == 'yes' || $created ){
                                    $default_roles = $user_object->roles;
                                    foreach ( $default_roles as $default_role ) {
                                        $user_object->remove_role( $default_role );
                                    }
                                }
    
                                if( $update_roles_existing_users == 'yes' || $update_roles_existing_users == 'yes_no_override' || $created ){
                                    if( !empty( $role ) ){
                                        if( is_array( $role ) ){
                                            foreach ($role as $single_role) {
                                                $user_object->add_role( $single_role );
                                            }
                                        }
                                        else{
                                            $user_object->add_role( $role );
                                        }
                                    }
    
                                    $invalid_roles = array();
                                    if( !empty( $role ) ){
                                        if( !is_array( $role ) ){
                                            $role_tmp = $role;
                                            $role = array();
                                            $role[] = $role_tmp;
                                        }
                                        
                                        foreach ($role as $single_role) {
                                            $single_role = strtolower($single_role);
                                            if( get_role( $single_role ) ){
                                                $user_object->add_role( $single_role );
                                            }
                                            else{
                                                $invalid_roles[] = trim( $single_role );
                                            }
                                        }	
                                    }
    
                                    if ( !empty( $invalid_roles ) ){
                                        if( count( $invalid_roles ) == 1 )
                                            $data[0] = __('Invalid role','import-users-from-csv-with-meta').' (' . reset( $invalid_roles ) . ')';
                                        else
                                            $data[0] = __('Invalid roles','import-users-from-csv-with-meta').' (' . implode( ', ', $invalid_roles ) . ')';
                                
                                        $errors[] = $acui_helper->new_error( $row, $data[0], 'warning' );    
                                    }
                                }
                            }
                        }
    
                        // Multisite add user to current blog
                        if( is_multisite() ){
                            if( $created || $update_roles_existing_users != 'no' ){
                                if( empty( $role ) )
                                    $role = 'subscriber';

                                if( !is_array( $role ) )
                                    $role = array( $role );

                                foreach ($role as $single_role)
                                    add_user_to_blog( get_current_blog_id(), $user_id, $single_role );
                            }
                            elseif( $update_roles_existing_users == 'no' && !is_user_member_of_blog( $user_id, get_current_blog_id() ) ){
                                add_user_to_blog( get_current_blog_id(), $user_id, 'subscriber' );
                            }                            
                        }
    
                        if( $columns > 2 ){
                            for( $i = 2 ; $i < $columns; $i++ ):
                                $data[$i] = apply_filters( 'pre_acui_import_single_user_single_data', $data[$i], $headers[$i], $i );
    
                                if( !empty( $data ) ){
                                    if( strtolower( $headers[ $i ] ) == "password" ){ // passwords -> continue
                                        continue;
                                    }
                                    elseif( strtolower( $headers[ $i ] ) == "user_pass" ){ // hashed pass
                                        if( !$created && $update_allow_update_passwords == 'no' )
                                            continue;
    
                                        global $wpdb;
                                        $wpdb->update( $wpdb->users, array( 'user_pass' => wp_slash( $data[ $i ] ) ), array( 'ID' => $user_id ) );
                                        wp_cache_delete( $user_id, 'users' );
                                        continue;
                                    }
                                    elseif( in_array( $headers[ $i ], $acui_helper->get_wp_users_fields() ) ){ // wp_user data									
                                        if( $data[ $i ] === '' && $empty_cell_action == "leave" ){
                                            continue;
                                        }
                                        else{
                                            wp_update_user( array( 'ID' => $user_id, $headers[ $i ] => $data[ $i ] ) );
                                            continue;
                                        }										
                                    }
                                    elseif( in_array( $headers[ $i ], $acui_helper->get_not_meta_fields() ) ){
                                        continue;
                                    }
                                    else{				
                                        if( $data[ $i ] === '' ){
                                            if( $empty_cell_action == "delete" )
                                                delete_user_meta( $user_id, $headers[ $i ] );
                                            else
                                                continue;	
                                        }
                                        else{
                                            update_user_meta( $user_id, $headers[ $i ], $data[ $i ] );
                                            continue;
                                        }
                                    }
    
                                }
                            endfor;
                        }

                        $acui_helper->print_row_imported( $row, $data, $errors );
    
                        do_action('post_acui_import_single_user', $headers, $data, $user_id, $role, $positions, $form_data, $is_frontend, $is_cron );
    
                        $mail_for_this_user = false;
                        if( $is_cron ){
                            if( get_option( "acui_cron_send_mail" ) ){
                                if( $created || ( !$created && get_option( "acui_cron_send_mail_updated" ) ) ){
                                    $mail_for_this_user = true;
                                }							
                            }
                        }
                        else{
                            if( isset( $form_data["sends_email"] ) && $form_data["sends_email"] ){
                                if( $created || ( !$created && ( isset( $form_data["send_email_updated"] ) && $form_data["send_email_updated"] ) ) )
                                    $mail_for_this_user = true;
                            }
                        }
    
                        // wordpress default user created and edited emails
                        if( get_option('acui_automatic_created_edited_wordpress_email') === 'true' ){
                            ( $created ) ? do_action( 'register_new_user', $user_id ) : do_action( 'edit_user_created_user', $user_id, 'both' );
                        }
                            
                        // send mail
                        if( isset( $mail_for_this_user ) && $mail_for_this_user ){
                            ACUI_Email_Options::send_email( $user_object, $positions, $headers, $data, $created, $password );
                        }

                        // results
                        ( $created ) ? $results['created']++ : $results['updated']++;
                    endif;
                endwhile;

                $acui_helper->print_table_end();

                $acui_helper->print_errors( $errors );

                $acui_helper->print_results( $results, $errors );
    
                // let the filter of default WordPress emails as it were before deactivating them
                if( !get_option('acui_automatic_wordpress_email') ){
                    remove_filter( 'send_email_change_email', function() { return false; }, 999 );
                    remove_filter( 'send_password_change_email', function() { return false; }, 999 );
                }
    
                if( $attach_id != 0 )
                    wp_delete_attachment( $attach_id );
    
                // delete all users that have not been imported
                $delete_users_flag = false;
                $change_role_not_present_flag = false;
    
                if( $delete_users == 'yes' ){
                    $delete_users_flag = true;
                }
    
                if( $is_cron && get_option( "acui_cron_delete_users" ) ){
                    $delete_users_flag = true;
                    $delete_users_assign_posts = get_option( "acui_cron_delete_users_assign_posts");
                }
    
                if( $is_backend && $change_role_not_present == 'yes' ){
                    $change_role_not_present_flag = true;
                }
    
                if( $is_cron && !empty( get_option( "acui_cron_change_role_not_present" ) ) ){
                    $change_role_not_present_flag = true;
                    $change_role_not_present_role = get_option( "acui_cron_change_role_not_present_role");
                }
    
                if( $is_frontend && !empty( get_option( "acui_frontend_change_role_not_present" ) ) ){
                    $change_role_not_present_flag = true;
                    $change_role_not_present_role = get_option( "acui_frontend_change_role_not_present_role");
                }
    
                if( !empty( $errors ) ){ // if there is some problem of some kind importing we won't proceed with delete or changing role to users not present to avoid problems
                    $delete_users_flag = false;
                    $change_role_not_present_flag = false;
                }
    
                if( $delete_users_flag ):
                    require_once( ABSPATH . 'wp-admin/includes/user.php');	
    
                    global $wp_roles; // get all roles
                    $all_roles = $wp_roles->roles;
                    $exclude_roles = array_diff( array_keys( $all_roles ), $editable_roles ); // remove editable roles
    
                    if ( !in_array( 'administrator', $exclude_roles )){ // just to be sure
                        $exclude_roles[] = 'administrator';
                    }
    
                    $args = array( 
                        'fields' => array( 'ID' ),
                        'role__not_in' => $exclude_roles,
                        'exclude' => array( get_current_user_id() ), // current user never cannot be deleted
                    );
    
                    if( $delete_users_only_specified_role ){
                        $args[ 'role__in' ] = $role_default;
                    }
    
                    $all_users = get_users( $args );
                    $all_users_ids = array_map( function( $element ){ return intval( $element->ID ); }, $all_users );
                    $users_to_remove = array_diff( $all_users_ids, $users_registered );
    
                    $delete_users_assign_posts = ( get_userdata( $delete_users_assign_posts ) === false ) ? false : $delete_users_assign_posts;	
    
                    foreach ( $users_to_remove as $user_id ) {
                        ( empty( $delete_users_assign_posts ) ) ? wp_delete_user( $user_id ) : wp_delete_user( $user_id, $delete_users_assign_posts );
                    }
                endif;
    
                if( $change_role_not_present_flag && !$delete_users_flag ):
                    require_once( ABSPATH . 'wp-admin/includes/user.php');	
    
                    $all_users = get_users( array( 
                        'fields' => array( 'ID' ),
                        'role__not_in' => array( 'administrator' )
                    ) );
                    
                    foreach ( $all_users as $user ) {
                        if( !in_array( $user->ID, $users_registered ) ){
                            $user_object = new WP_User( $user->ID );
                            $user_object->set_role( $change_role_not_present_role );
                        }
                    }
                endif;			
                
                if( !$is_frontend ): ?>
                    <br/>
                    <p><?php printf( __( 'Process finished you can go <a href="%s">here to see results</a> or you can do <a href="%s">a new import</a>.', 'import-users-from-csv-with-meta' ), get_admin_url( null, 'users.php' ), get_admin_url( null, 'tools.php?page=acui&tab=homepage' ) ); ?></p>
                <?php endif; ?>
                
                <?php
                if( !$is_frontend && !$is_cron )
                    $acui_helper->execute_datatable();

                ini_set( 'auto_detect_line_endings', FALSE );
                
                do_action( 'after_acui_import_users' );
            ?>
        </div>
    <?php
    }
}