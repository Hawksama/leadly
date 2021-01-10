<?php
include_once( dirname( __FILE__ ) . '/ultimate-member/include/vcard.php');

function nevechild_enqueue_styles() {
    wp_enqueue_style( 'um-profile.css', get_stylesheet_directory_uri() . '/assets/css/um-profile.css');
}
add_action( 'wp_enqueue_scripts', 'nevechild_enqueue_styles' );

/**
 * Profile header
 *
 * @param $args
 */
function my_profile_header( $args ) {
	$classes = null;

	if ( ! $args['cover_enabled'] ) {
		$classes .= ' no-cover';
	}

	$default_size = 'original';

	// Switch on/off the profile photo uploader
	$disable_photo_uploader = empty( $args['use_custom_settings'] ) ? UM()->options()->get( 'disable_profile_photo_upload' ) : $args['disable_photo_upload'];

	if ( ! empty( $disable_photo_uploader ) ) {
		$args['disable_photo_upload'] = 1;
		$overlay = '';
	} else {
		$overlay = '<span class="um-profile-photo-overlay">
			<span class="um-profile-photo-overlay-s">
				<ins>
					<i class="um-faicon-camera"></i>
				</ins>
			</span>
		</span>';
	} ?>

	<div class="um-header<?php echo esc_attr( $classes ); ?>">

		<?php
		/**
		 * UM hook
		 *
		 * @type action
		 * @title um_pre_header_editprofile
		 * @description Insert some content before edit profile header
		 * @input_vars
		 * [{"var":"$args","type":"array","desc":"Form Arguments"}]
		 * @change_log
		 * ["Since: 2.0"]
		 * @usage add_action( 'um_pre_header_editprofile', 'function_name', 10, 1 );
		 * @example
		 * <?php
		 * add_action( 'um_pre_header_editprofile', 'my_pre_header_editprofile', 10, 1 );
		 * function my_pre_header_editprofile( $args ) {
		 *     // your code here
		 * }
		 * ?>
		 */
		do_action( 'um_pre_header_editprofile', $args ); ?>

		<div class="um-profile-meta">

			<?php
			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_before_profile_main_meta
			 * @description Insert before profile main meta block
			 * @input_vars
			 * [{"var":"$args","type":"array","desc":"Form Arguments"}]
			 * @change_log
			 * ["Since: 2.0.1"]
			 * @usage add_action( 'um_before_profile_main_meta', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_action( 'um_before_profile_main_meta', 'my_before_profile_main_meta', 10, 1 );
			 * function my_before_profile_main_meta( $args ) {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( 'um_before_profile_main_meta', $args ); ?>

			<div class="um-main-meta">

				<?php if ( $args['show_name'] ) { ?>
					<div class="um-name">

						<a href="<?php echo esc_url( um_user_profile_url() ); ?>"
						   title="<?php echo esc_attr( um_user( 'display_name' ) ); ?>"><?php echo um_user( 'display_name', 'html' ); ?></a>

						<?php
						/**
						 * UM hook
						 *
						 * @type action
						 * @title um_after_profile_name_inline
						 * @description Insert after profile name some content
						 * @input_vars
						 * [{"var":"$args","type":"array","desc":"Form Arguments"}]
						 * @change_log
						 * ["Since: 2.0"]
						 * @usage add_action( 'um_after_profile_name_inline', 'function_name', 10, 1 );
						 * @example
						 * <?php
						 * add_action( 'um_after_profile_name_inline', 'my_after_profile_name_inline', 10, 1 );
						 * function my_after_profile_name_inline( $args ) {
						 *     // your code here
						 * }
						 * ?>
						 */
						do_action( 'um_after_profile_name_inline', $args ); ?>

					</div>
				<?php } ?>

				<div class="um-clear"></div>

				<?php
                
                /**
				 * UM hook
				 *
				 * @type action
				 * @title um_after_profile_name_inline
				 * @description Insert after profile name some content
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage add_action( 'um_after_profile_name_inline', 'function_name', 10 );
				 * @example
				 * <?php
				 * add_action( 'um_after_profile_name_inline', 'my_after_profile_name_inline', 10 );
				 * function my_after_profile_name_inline() {
				 *     // your code here
				 * }
				 * ?>
				 */
				do_action( 'um_after_profile_header_name' ); ?>

			</div>

			<?php if ( ! empty( $args['metafields'] ) ) { ?>
				<div class="um-meta">

					<?php echo UM()->profile()->show_meta( $args['metafields'] ); ?>

				</div>
			<?php }

			$description_key = UM()->profile()->get_show_bio_key( $args );

			if ( UM()->fields()->viewing == true && um_user( $description_key ) && $args['show_bio'] ) { ?>

				<div class="um-meta-text">
					<?php $description = get_user_meta( um_user( 'ID' ), $description_key, true );

					if ( UM()->options()->get( 'profile_show_html_bio' ) ) {
						echo make_clickable( wpautop( wp_kses_post( $description ) ) );
					} else {
						echo esc_html( $description );
					} ?>
				</div>

			<?php } elseif ( UM()->fields()->editing == true && $args['show_bio'] ) { ?>

				<div class="um-meta-text">
					<textarea id="um-meta-bio"
							  data-character-limit="<?php echo esc_attr( UM()->options()->get( 'profile_bio_maxchars' ) ); ?>"
							  placeholder="<?php esc_attr_e( 'Tell us a bit about yourself...', 'ultimate-member' ); ?>"
							  name="<?php echo esc_attr( $description_key . '-' . $args['form_id'] ); ?>"
							  id="<?php echo esc_attr( $description_key . '-' . $args['form_id'] ); ?>"><?php echo UM()->fields()->field_value( $description_key ) ?></textarea>
					<span class="um-meta-bio-character um-right"><span
							class="um-bio-limit"><?php echo UM()->options()->get( 'profile_bio_maxchars' ); ?></span></span>

					<?php if ( UM()->fields()->is_error( $description_key ) ) {
						echo UM()->fields()->field_error( UM()->fields()->show_error( $description_key ), true );
					} ?>

				</div>

			<?php } ?>

			<div class="um-profile-status <?php echo esc_attr( um_user( 'account_status' ) ); ?>">
				<span><?php printf( __( 'This user account status is %s', 'ultimate-member' ), um_user( 'account_status_name' ) ); ?></span>
			</div>

			<?php
			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_after_header_meta
			 * @description Insert after header meta some content
			 * @input_vars
			 * [{"var":"$user_id","type":"int","desc":"User ID"},
			 * {"var":"$args","type":"array","desc":"Form Arguments"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_after_header_meta', 'function_name', 10, 2 );
			 * @example
			 * <?php
			 * add_action( 'um_after_header_meta', 'my_after_header_meta', 10, 2 );
			 * function my_after_header_meta( $user_id, $args ) {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( 'um_after_header_meta', um_user( 'ID' ), $args ); ?>

		</div>
		<div class="um-clear"></div>

		<?php if ( UM()->fields()->is_error( 'profile_photo' ) ) {
			echo UM()->fields()->field_error( UM()->fields()->show_error( 'profile_photo' ), 'force_show' );
        }
        

		/**
		 * UM hook
		 *
		 * @type action
		 * @title um_after_header_info
		 * @description Insert after header info some content
		 * @input_vars
		 * [{"var":"$user_id","type":"int","desc":"User ID"},
		 * {"var":"$args","type":"array","desc":"Form Arguments"}]
		 * @change_log
		 * ["Since: 2.0"]
		 * @usage add_action( 'um_after_header_info', 'function_name', 10, 2 );
		 * @example
		 * <?php
		 * add_action( 'um_after_header_info', 'my_after_header_info', 10, 2 );
		 * function my_after_header_info( $user_id, $args ) {
		 *     // your code here
		 * }
		 * ?>
		 */
		do_action( 'um_after_header_info', um_user( 'ID' ), $args ); ?>

    </div>
    
    <?php 
}

remove_action( 'um_profile_header', 'um_profile_header', 9);
add_action( 'um_profile_header', 'my_profile_header', 10, 1 );

function my_um_social_links($args) {
    do_action( 'um_after_profile_header_name_args', $args );
}
add_action( 'um_main_profile_fields', 'my_um_social_links', 101, 1 );

/**
 * Profile header cover
 *
 * @param $args
 */
function my_profile_header_cover_area( $args ) {
	if ( isset( $args['cover_enabled'] ) && $args['cover_enabled'] == 1 ) {

		$default_cover = UM()->options()->get( 'default_cover' );

		$overlay = '<span class="um-cover-overlay">
				<span class="um-cover-overlay-s">
					<ins>
						<i class="um-faicon-picture-o"></i>
						<span class="um-cover-overlay-t">' . __( 'Change your cover photo', 'ultimate-member' ) . '</span>
					</ins>
				</span>
			</span>';

		?>

		<div class="um-cover <?php if ( um_user( 'cover_photo' ) || ( $default_cover && $default_cover['url'] ) ) echo 'has-cover'; ?>"
			 data-user_id="<?php echo esc_attr( um_profile_id() ); ?>" data-ratio="<?php echo esc_attr( $args['cover_ratio'] ); ?>">

			<?php
			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_cover_area_content
			 * @description Cover area content change
			 * @input_vars
			 * [{"var":"$user_id","type":"int","desc":"User ID"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_cover_area_content', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_action( 'um_cover_area_content', 'my_cover_area_content', 10, 1 );
			 * function my_cover_area_content( $user_id ) {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( 'um_cover_area_content', um_profile_id() );
			if ( UM()->fields()->editing ) {

				$hide_remove = um_user( 'cover_photo' ) ? false : ' style="display:none;"';

				$text = ! um_user( 'cover_photo' ) ? __( 'Upload a cover photo', 'ultimate-member' ) : __( 'Change cover photo', 'ultimate-member' ) ;

				$items = array(
					'<a href="javascript:void(0);" class="um-manual-trigger" data-parent=".um-cover" data-child=".um-btn-auto-width">' . $text . '</a>',
					'<a href="javascript:void(0);" class="um-reset-cover-photo" data-user_id="' . um_profile_id() . '" ' . $hide_remove . '>' . __( 'Remove', 'ultimate-member' ) . '</a>',
					'<a href="javascript:void(0);" class="um-dropdown-hide">' . __( 'Cancel', 'ultimate-member' ) . '</a>',
				);

				$items = apply_filters( 'um_cover_area_content_dropdown_items', $items, um_profile_id() );

				UM()->profile()->new_ui( 'bc', 'div.um-cover', 'click', $items );
			} else {

				if ( ! isset( UM()->user()->cannot_edit ) && ! um_user( 'cover_photo' ) ) {

					$items = array(
						'<a href="javascript:void(0);" class="um-manual-trigger" data-parent=".um-cover" data-child=".um-btn-auto-width">' . __( 'Upload a cover photo', 'ultimate-member' ) . '</a>',
						'<a href="javascript:void(0);" class="um-dropdown-hide">' . __( 'Cancel', 'ultimate-member' ) . '</a>',
					);

					$items = apply_filters( 'um_cover_area_content_dropdown_items', $items, um_profile_id() );

					UM()->profile()->new_ui( 'bc', 'div.um-cover', 'click', $items );

				}

			}

			UM()->fields()->add_hidden_field( 'cover_photo' ); ?>

			<div class="um-cover-e" data-ratio="<?php echo esc_attr( $args['cover_ratio'] ); ?>">

				<?php if ( um_user( 'cover_photo' ) ) {

					$get_cover_size = $args['coversize'];

					if ( ! $get_cover_size || $get_cover_size == 'original' ) {
						$size = null;
					} else {
						$size = $get_cover_size;
					}

					if ( UM()->mobile()->isMobile() ) {

						// set for mobile width = 300 by default but can be changed via filter
						if ( ! UM()->mobile()->isTablet() ) {
							$size = 300;
						}

						/**
						 * UM hook
						 *
						 * @type filter
						 * @title um_mobile_cover_photo
						 * @description Add size for mobile device
						 * @input_vars
						 * [{"var":"$size","type":"int","desc":"Form's agrument - Cover Photo size"}]
						 * @change_log
						 * ["Since: 2.0"]
						 * @usage
						 * <?php add_filter( 'um_mobile_cover_photo', 'change_size', 10, 1 ); ?>
						 * @example
						 * <?php
						 * add_filter( 'um_mobile_cover_photo', 'um_change_cover_mobile_size', 10, 1 );
						 * function um_change_cover_mobile_size( $size ) {
						 *     // your code here
						 *     return $size;
						 * }
						 * ?>
						 */
						$size = apply_filters( 'um_mobile_cover_photo', $size );
					}

					echo um_user( 'cover_photo', $size );

				} elseif ( $default_cover && $default_cover['url'] ) {

					$default_cover = $default_cover['url'];

					echo '<img src="' . esc_url( $default_cover ) . '" alt="" />';

				} else {

					if ( ! isset( UM()->user()->cannot_edit ) ) { ?>

						<a href="javascript:void(0);" class="um-cover-add"><span class="um-cover-add-i"><i
									class="um-icon-plus um-tip-n"
									title="<?php esc_attr_e( 'Upload a cover photo', 'ultimate-member' ); ?>"></i></span></a>

					<?php }

				} ?>

			</div>

			<?php echo $overlay; ?>

		</div>

		<?php

	}

}
remove_action( 'um_profile_header_cover_area', 'um_profile_header_cover_area', 9);
add_action( 'um_profile_header_cover_area', 'my_profile_header_cover_area', 11, 1 );

/**
 * The profile page SEO tags
 *
 * @see https://ogp.me/ - The Open Graph protocol
 * @see https://developer.twitter.com/en/docs/tweets/optimize-with-cards/overview/summary - The Twitter Summary card
 * @see https://schema.org/Person - The schema.org Person schema
 */
function my_profile_dynamic_meta_desc() {
	if ( um_is_core_page( 'user' ) && um_get_requested_user() ) {

		$user_id = um_get_requested_user();

		$privacy = get_user_meta( $user_id, 'profile_privacy', true );
		if ( $privacy == __( 'Only me', 'ultimate-member' ) || $privacy == 'Only me' ) {
			return;
		}

		$noindex = get_user_meta( $user_id, 'profile_noindex', true );
		if ( ! empty( $noindex ) ) { ?>

			<meta name="robots" content="noindex, nofollow" />

			<?php return;
		}

		um_fetch_user( $user_id );

		$locale = get_user_locale( $user_id );
		$site_name = get_bloginfo( 'name' );
		$twitter_site = '@' . sanitize_title( $site_name );

		$title = trim( um_user( 'display_name' ) );
		$description = um_convert_tags( UM()->options()->get( 'profile_desc' ) );
		$url = um_user_profile_url( $user_id );

		$size = 190;
		$sizes = UM()->options()->get( 'photo_thumb_sizes' );
		if ( is_array( $sizes ) ) {
			$size = um_closest_num( $sizes, $size );
		}
		$image = um_get_user_avatar_url( $user_id, $size );

		$person = array(
			"@context"      => "http://schema.org",
			"@type"         => "Person",
			"name"          => esc_attr( $title ),
			"description"   => esc_attr( stripslashes( $description ) ),
			"image"         => esc_url( $image ),
			"url"           => esc_url( $url ),
		);

		um_reset_user();
		?>
		<!-- START - Ultimate Member profile SEO meta tags -->

		<link rel="image_src" href="<?php echo esc_url( $image ); ?>"/>

		<meta name="description" content="<?php echo esc_attr( $description ); ?>"/>

		<meta property="og:type" content="profile"/>
		<meta property="og:locale" content="<?php echo esc_attr( $locale ); ?>"/>
		<meta property="og:site_name" content="<?php echo esc_attr( $site_name ); ?>"/>
		<meta property="og:title" content="<?php echo esc_attr( $title ); ?>"/>
		<meta property="og:description" content="<?php echo esc_attr( $description ); ?>"/>
		<meta property="og:image" content="<?php echo esc_url( $image ); ?>"/>
		<meta property="og:image:alt" content="<?php esc_attr_e( 'Profile photo', 'ultimate-member' ); ?>"/>
		<meta property="og:image:height" content="<?php echo (int) $size; ?>"/>
		<meta property="og:image:width" content="<?php echo (int) $size; ?>"/>
		<meta property="og:url" content="<?php echo esc_url( $url ); ?>"/>

		<meta name="twitter:card" content="summary"/>
		<meta name="twitter:site" content="<?php echo esc_attr( $twitter_site ); ?>"/>
		<meta name="twitter:title" content="<?php echo esc_attr( $title ); ?>"/>
		<meta name="twitter:description" content="<?php echo esc_attr( $description ); ?>"/>
		<meta name="twitter:image" content="<?php echo esc_url( $image ); ?>"/>
		<meta name="twitter:image:alt" content="<?php esc_attr_e( 'Profile photo', 'ultimate-member' ); ?>"/>
		<meta name="twitter:url" content="<?php echo esc_url( $url ); ?>"/>

		<script type="application/ld+json"><?php echo json_encode( $person ); ?></script>

		<!-- END - Ultimate Member profile SEO meta tags -->
		<?php
	}
}

remove_action( 'wp_head', 'um_profile_dynamic_meta_desc', 20);
add_action( 'wp_head', 'my_profile_dynamic_meta_desc', 21, 1);

function my_awesome_func( $data ) {
    
	$vpost = get_user_meta($data->get_param('id'));
	print_r($vpost);
	die;
	$wpUserData = get_userdata($data->get_param('id'));
	
    /* Instantiate a new vcard object. */
    $vc = new vcard();
    $vc->class = "PUBLIC";
     
    /* Fill in data for vCard */
    $vc->filename = strtolower(str_replace(" ","-",$vpost->post_title)); 
    $vc->data['first_name'] = $vpost['full_name'][0]; 
    $vc->data['company'] = get_field('company_name',$vpost->ID); 
    $vc->data['department'] = get_field('department',$vpost->ID); 
    $vc->data['title'] = $vpost['headline'][0]; 
    $vc->data['office_tel'] = get_field('phone',$vpost->ID); 
    $vc->data['email1'] = $wpUserData->user_email; 
    $vc->data['url'] = $wpUserData->user_url; 
    $vc->data['photo'] = get_avatar_url($data->get_param('id')); 
	$vc->data['note'] = $vpost['description'][0];

	if (empty($vpost['twitter'][0])) {
		$vc->data['twitter'] = $vpost['twitter'][0];
	}

	if (empty($vpost['facebook'][0])) {
		$vc->data['facebook'] = $vpost['facebook'][0];
	}

	if (empty($vpost['linkedin'][0])) {
		$vc->data['linkedin'] = $vpost['linkedin'][0];
	}

	if (empty($vpost['instagram'][0])) {
		$vc->data['instagram'] = $vpost['instagram'][0];
	}

	if (empty($vpost['youtube'][0])) {
		$vc->data['youtube'] = $vpost['youtube'][0];
	}

	if (empty($vpost['soundcloud'][0])) {
		$vc->data['soundcloud'] = $vpost['soundcloud'][0];
	}

    $vs->download();
}


add_action( 'rest_api_init', function () {
    register_rest_route( 'vcard/v1', '/user/id=(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'my_awesome_func',
        'args' => array(
            'id' => array(
                'validate_callback' => function($param, $request, $key) {
                    return is_numeric( $param );
                }
            ),
        ),
    ));
});

// function that runs when shortcode is called
function wpb_demo_shortcode() { 
    ?>
    <a href="<?php echo esc_url( $_SERVER['SERVER_NAME'] ); ?>/wp-json/vcard/v1/user/id/<?= um_profile_id() ?>" 
        class="um-button um-alt"    
        target="_blank"
        title="SAVE CONTACT">
        SAVE CONTACT
    </a>
     <?php
    // Things that you want to do. 
    $message = 'Hello world!'; 
     
    // Output needs to be return
    return $message;
} 
// register shortcode
add_shortcode('greeting', 'wpb_demo_shortcode'); 