<?php

class ACUI_Import{
    public function get_random_unique_username( $prefix = '' ){
        do {
            $rnd_str = sprintf("%06d", mt_rand(1, 999999));
        } while( username_exists( $prefix . $rnd_str ) );
        
        return $prefix . $rnd_str;
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