<?php

define( 'NEVE_CHILD_VERSION', '1.2.3' );


function nevechild_enqueue_scripts() {
	wp_enqueue_script( 'neve-child', get_stylesheet_directory_uri() . '/assets/js/custom.js', array( 'jquery' ), NEVE_CHILD_VERSION, true );
}
add_action( 'wp_enqueue_scripts', 'nevechild_enqueue_scripts' );


function nevechild_enqueue_styles() {
    wp_enqueue_style( 'um-profile.css', get_stylesheet_directory_uri() . '/assets/css/um-profile.css', array(), NEVE_CHILD_VERSION);
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

		<div class="um-profile-photo" data-user_id="<?php echo esc_attr( um_profile_id() ); ?>">

		<a href="<?php echo esc_url( um_user_profile_url() ); ?>" class="um-profile-photo-img" title="<?php echo esc_attr( um_user( 'display_name' ) ); ?>">
			<?php if ( ! $default_size || $default_size == 'original' ) {
				$profile_photo = UM()->uploader()->get_upload_base_url() . um_user( 'ID' ) . "/" . um_profile( 'profile_photo' );

				$data = um_get_user_avatar_data( um_user( 'ID' ) );
				echo $overlay . sprintf( '<img src="%s" class="%s" alt="%s" data-default="%s" onerror="%s" />',
					esc_url( $profile_photo ),
					esc_attr( $data['class'] ),
					esc_attr( $data['alt'] ),
					esc_attr( $data['default'] ),
					'if ( ! this.getAttribute(\'data-load-error\') ){ this.setAttribute(\'data-load-error\', \'1\');this.setAttribute(\'src\', this.getAttribute(\'data-default\'));}'
				);
			} else {
				echo $overlay . get_avatar( um_user( 'ID' ), $default_size );
			} ?>
		</a>

		<?php if ( empty( $disable_photo_uploader ) && empty( UM()->user()->cannot_edit ) ) {

			UM()->fields()->add_hidden_field( 'profile_photo' );

			if ( ! um_profile( 'profile_photo' ) ) { // has profile photo

				$items = array(
					'<a href="javascript:void(0);" class="um-manual-trigger" data-parent=".um-profile-photo" data-child=".um-btn-auto-width">' . __( 'Upload photo', 'ultimate-member' ) . '</a>',
					'<a href="javascript:void(0);" class="um-dropdown-hide">' . __( 'Cancel', 'ultimate-member' ) . '</a>',
				);

				/**
				 * UM hook
				 *
				 * @type filter
				 * @title um_user_photo_menu_view
				 * @description Change user photo on menu view
				 * @input_vars
				 * [{"var":"$items","type":"array","desc":"User Photos"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage
				 * <?php add_filter( 'um_user_photo_menu_view', 'function_name', 10, 1 ); ?>
				 * @example
				 * <?php
				 * add_filter( 'um_user_photo_menu_view', 'my_user_photo_menu_view', 10, 1 );
				 * function my_user_photo_menu_view( $items ) {
				 *     // your code here
				 *     return $items;
				 * }
				 * ?>
				 */
				$items = apply_filters( 'um_user_photo_menu_view', $items );

				UM()->profile()->new_ui( 'bc', 'div.um-profile-photo', 'click', $items );

			} elseif ( UM()->fields()->editing == true ) {

				$items = array(
					'<a href="javascript:void(0);" class="um-manual-trigger" data-parent=".um-profile-photo" data-child=".um-btn-auto-width">' . __( 'Change photo', 'ultimate-member' ) . '</a>',
					'<a href="javascript:void(0);" class="um-reset-profile-photo" data-user_id="' . esc_attr( um_profile_id() ) . '" data-default_src="' . esc_url( um_get_default_avatar_uri() ) . '">' . __( 'Remove photo', 'ultimate-member' ) . '</a>',
					'<a href="javascript:void(0);" class="um-dropdown-hide">' . __( 'Cancel', 'ultimate-member' ) . '</a>',
				);

				/**
				 * UM hook
				 *
				 * @type filter
				 * @title um_user_photo_menu_edit
				 * @description Change user photo on menu edit
				 * @input_vars
				 * [{"var":"$items","type":"array","desc":"User Photos"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage
				 * <?php add_filter( 'um_user_photo_menu_edit', 'function_name', 10, 1 ); ?>
				 * @example
				 * <?php
				 * add_filter( 'um_user_photo_menu_edit', 'my_user_photo_menu_edit', 10, 1 );
				 * function my_user_photo_menu_edit( $items ) {
				 *     // your code here
				 *     return $items;
				 * }
				 * ?>
				 */
				$items = apply_filters( 'um_user_photo_menu_edit', $items );

				UM()->profile()->new_ui( 'bc', 'div.um-profile-photo', 'click', $items );

			}

		} ?>

		</div>

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

function vcard_function( $data ) {
	include_once( dirname( __FILE__ ) . '/ultimate-member/include/vcard.php');
    
	$vpost = get_user_meta($data->get_param('id'));
	
	// print_r($vpost);
	// die("HERE");
	$wpUserData = get_userdata($data->get_param('id'));
	
    /* Instantiate a new vcard object. */
    $vc = new vcard();
    $vc->class = "PUBLIC";
     
    /* Fill in data for vCard */
	$vc->filename = strtolower(str_replace(" ","-",$vpost['full_name'][0])); 
	
	if (!empty($vpost['first_name'][0])) {
		$vc->vcardInformation['display_name'] = $vpost['full_name'][0]; 
	}
	
	if (!empty($vpost['first_name'][0])) {
		$vc->vcardInformation['first_name'] = $vpost['first_name'][0]; 
	}

	if (!empty($vpost['last_name'][0])) {
		$vc->vcardInformation['last_name'] = $vpost['last_name'][0]; 
	}
	
	if (!empty($vpost['company-name'][0])) {
		$vc->vcardInformation['company'] = $vpost['company-name'][0]; 
	}

	if (!empty($vpost['nickname'][0])) {
		$vc->vcardInformation['nickname'] = $vpost['nickname'][0]; 
	}

	if (!empty($vpost['job-title'][0])) {
		$vc->vcardInformation['title'] = $vpost['job-title'][0]; 
	}

	if (!empty($vpost['office_phone_number'][0])) {
		$vc->vcardInformation['office_tel'] = $vpost['office_phone_number'][0]; 
	}

	if (!empty($vpost['home_phone_number'][0])) {
		$vc->vcardInformation['home_tel'] = $vpost['home_phone_number'][0]; 
	}

	if (!empty($vpost['phone_number'][0])) {
		$vc->vcardInformation['cell_tel'] = $vpost['phone_number'][0]; 
	}
	
	if ($wpUserData->user_email) {
		$vc->vcardInformation['secondary_user_email'] = $wpUserData->user_email;
	}
    
	if (!empty($vpost['work-email'][0])) {
		$vc->vcardInformation['email2'] = $vpost['work-email'][0]; 
	}

	if ($wpUserData->user_url) {
		$vc->vcardInformation['url'] = $wpUserData->user_url; 
	}

	if ($data->get_param('id')) {
		$vc->vcardInformation['photo'] = get_avatar_url($data->get_param('id')); 
	}
	
	if (!empty($vpost['headline2'][0])) {
		$vc->vcardInformation['note'] = $vpost['headline2'][0];
	}

	if (!empty($vpost['twitter'][0])) {
		$vc->vcardInformation['twitter'] = $vpost['twitter'][0];
	}

	if (!empty($vpost['facebook'][0])) {
		$vc->vcardInformation['facebook'] = $vpost['facebook'][0];
	}

	if (!empty($vpost['linkedin'][0])) {
		$vc->vcardInformation['linkedin'] = $vpost['linkedin'][0];
	}

	if (!empty($vpost['instagram'][0])) {
		$vc->vcardInformation['instagram'] = $vpost['instagram'][0];
	}

	if (!empty($vpost['youtube'][0])) {
		$vc->vcardInformation['youtube'] = $vpost['youtube'][0];
	}

	if (!empty($vpost['soundcloud'][0])) {
		$vc->vcardInformation['soundcloud'] = $vpost['soundcloud'][0];
	}

	if (!empty($vpost['skype'][0])) {
		$vc->vcardInformation['skype'] = $vpost['skype'][0];
	}

	if (!empty($vpost['birthday'][0])) {
		$vc->vcardInformation['birthday'] = $vpost['birthday'][0];
	}

	if (!empty($vpost['work-po-box'][0])) {
		$vc->vcardInformation['work_po_box'] = $vpost['work-po-box'][0];
	}

	if (!empty($vpost['work-extended-address'][0])) {
		$vc->vcardInformation['work_extended_address'] = $vpost['work-extended-address'][0];
	}

	if (!empty($vpost['work-address'][0])) {
		$vc->vcardInformation['work_address'] = $vpost['work-address'][0];
	}

	if (!empty($vpost['work-city'][0])) {
		$vc->vcardInformation['work_city'] = $vpost['work-city'][0];
	}

	if (!empty($vpost['work-state'][0])) {
		$vc->vcardInformation['work_state'] = $vpost['work-state'][0];
	}

	if (!empty($vpost['work-postal-code'][0])) {
		$vc->vcardInformation['work_postal_code'] = $vpost['work-postal-code'][0];
	}

	if (!empty($vpost['work-country'][0])) {
		$vc->vcardInformation['work_country'] = $vpost['work-country'][0];
	}

	if (!empty($vpost['work-postal-code'][0])) {
		$vc->vcardInformation['home_postal_code'] = $vpost['work-postal-code'][0];
	}

	if (!empty($vpost['home-po-box'][0])) {
		$vc->vcardInformation['home_po_box'] = $vpost['home-po-box'][0];
	}

	if (!empty($vpost['home-extended-address'][0])) {
		$vc->vcardInformation['home_extended_address'] = $vpost['home-extended-address'][0];
	}

	if (!empty($vpost['home-address'][0])) {
		$vc->vcardInformation['home_address'] = $vpost['home-address'][0];
	}

	if (!empty($vpost['home-city'][0])) {
		$vc->vcardInformation['home_city'] = $vpost['home-city'][0];
	}

	if (!empty($vpost['home-state'][0])) {
		$vc->vcardInformation['home_state'] = $vpost['home-state'][0];
	}

	if (!empty($vpost['home-postal-code'][0])) {
		$vc->vcardInformation['home_postal_code'] = $vpost['home-postal-code'][0];
	}

	if (!empty($vpost['home-state'][0])) {
		$vc->vcardInformation['home_state'] = $vpost['home-state'][0];
	}

	if (!empty($vpost['home-postal-code'][0])) {
		$vc->vcardInformation['home_postal_code'] = $vpost['home-postal-code'][0];
	}

	if (!empty($vpost['country'][0])) {
		$vc->vcardInformation['home_country'] = $vpost['country'][0];
	}

	if (!empty($vpost['birth_date'][0])) {
		$vc->vcardInformation['birthday'] = $vpost['birth_date'][0];
	}
	
    $vc->download();
}

add_action( 'rest_api_init', function () {
    register_rest_route( 'vcard/v1', '/user/id=(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'vcard_function',
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
    $message = '<a href="' . esc_url( $_SERVER['SERVER_NAME'] ) . '/wp-json/vcard/v1/user/id=' . um_profile_id() . '"';
    $message .= 'class="um-button um-alt save-contact"';
    $message .= 'target="_blank"';
    $message .= 'title="Save Contact">';
	$message .= 'Save Contact';
	$message .= '</a>';
    
	return $message;
} 
// register shortcode
add_shortcode('greeting', 'wpb_demo_shortcode'); 


/**
 * Shows social links
 */
function my_show_social_urls() {
	$social = array();

	$message = '';

	$fields = UM()->builtin()->get_all_user_fields();
	foreach ( $fields as $field => $args ) {
		if ( isset( $args['advanced'] ) && $args['advanced'] == 'social' ) {
			$social[ $field ] = $args;
		}
	}

	foreach ( $social as $k => $arr ) {
		if ( um_profile( $k ) ) {

			$message .=	'<a class="redirect-social-' . esc_attr( $arr['metakey']) . '" href="' . esc_url( um_filtered_social_link( $k, $arr['match'] ) ) . '"';
			$message .=	'style="background: ' . esc_attr( $arr['color'] ) . '" target="_blank" class="um-tip-n"';
			$message .= 'title="' . esc_attr( $arr['title'] ) . '">';
			
			if($k != 'youtube') {
				$message .= '<i class="' . esc_attr( $arr['icon'] ) . '"></i>';
			} else {
				$message .= '<i class="um-faicon-youtube-play"></i>';
			}

			$message .= '</a>';
		}
	}

	return $message;
}

/**
 * Show social links as icons below profile name
 *
 * @param $args
 */
function my_social_links_icons( $args ) {
	$message = '<div class="um-profile-connect um-member-connect">';
	$message .=	my_show_social_urls();
	$message .= '</div>';

	return $message;
}

add_shortcode('social-links', 'my_social_links_icons');

remove_action( 'um_after_profile_header_name_args', 'um_social_links_icons', 50 );

function link_to_clipboard() {
	$message = '';

	$message .=	'<a href="#"';
	$message .=	'id="share-link"';
	$message .= 'target="_blank" class="um-button um-alt share-button"';
	$message .= 'title="Share me">Share profile</a>';

	return $message;
}

add_shortcode('link-clipboard', 'link_to_clipboard');


add_filter( 'um_predefined_fields_hook', 'my_predefined_fields', 10, 1 );
function my_predefined_fields( $predefined_fields ) {
	$builtin = new um\core\Builtin();


	global $wp_roles;
	$role_keys = get_option( 'um_roles' );
	if ( ! empty( $role_keys ) && is_array( $role_keys ) ) {
		$role_keys = array_map( function( $item ) {
			return 'um_' . $item;
		}, $role_keys );
	} else {
		$role_keys = array();
	}

	$exclude_roles = array_diff( array_keys( $wp_roles->roles ), array_merge( $role_keys, array( 'subscriber' ) ) );

	$um_roles = UM()->roles()->get_roles( false, $exclude_roles );

	$profile_privacy = apply_filters( 'um_profile_privacy_options', array(
		'Everyone'  => __( 'Everyone', 'ultimate-member' ),
		'Only me'   => __( 'Only me', 'ultimate-member' )
	) );

	$predefined_fields = array(
		//COPIED FROM wp-content/plugins/ultimate-member/includes/core/class-builtin.php
		
		'user_login' => array(
			'title' => __('Username','ultimate-member'),
			'metakey' => 'user_login',
			'type' => 'text',
			'label' => __('Username','ultimate-member'),
			'required' => 1,
			'public' => 1,
			'editable' => 0,
			'validate' => 'unique_username',
			'min_chars' => 3,
			'max_chars' => 24
		),

		'username' => array(
			'title' => __('Username or E-mail','ultimate-member'),
			'metakey' => 'username',
			'type' => 'text',
			'label' => __('Username or E-mail','ultimate-member'),
			'required' => 1,
			'public' => 1,
			'editable' => 0,
			'validate' => 'unique_username_or_email',
		),

		'user_password' => array(
			'title' => __('Password','ultimate-member'),
			'metakey' => 'user_password',
			'type' => 'password',
			'label' => __('Password','ultimate-member'),
			'required' => 1,
			'public' => 1,
			'editable' => 1,
			'min_chars' => 8,
			'max_chars' => 30,
			'force_good_pass' => 1,
			'force_confirm_pass' => 1,
		),

		'first_name' => array(
			'title' => __('First Name','ultimate-member'),
			'metakey' => 'first_name',
			'type' => 'text',
			'label' => __('First Name','ultimate-member'),
			'required' => 0,
			'public' => 1,
			'editable' => 1,
		),

		'last_name' => array(
			'title' => __('Last Name','ultimate-member'),
			'metakey' => 'last_name',
			'type' => 'text',
			'label' => __('Last Name','ultimate-member'),
			'required' => 0,
			'public' => 1,
			'editable' => 1,
		),

		'nickname' => array(
			'title' => __('Nickname','ultimate-member'),
			'metakey' => 'nickname',
			'type' => 'text',
			'label' => __('Nickname','ultimate-member'),
			'required' => 0,
			'public' => 1,
			'editable' => 1,
		),

		'user_url' => array(
			'title' => __('Website URL','ultimate-member'),
			'metakey' => 'user_url',
			'type' => 'url',
			'label' => __('Website URL','ultimate-member'),
			'required' => 1,
			'public' => 1,
			'editable' => 1,
			'validate' => 'url'
		),

		'user_registered' => array(
			'title' => __('Registration Date','ultimate-member'),
			'metakey' => 'user_registered',
			'type' => 'text',
			'label' => __('Registration Date','ultimate-member'),
			'required' => 0,
			'public' => 1,
			'editable' => 1,
			'edit_forbidden' => 1,
		),

		'_um_last_login' => array(
			'title' => __('Last Login','ultimate-member'),
			'metakey' => '_um_last_login',
			'type' => 'text',
			'label' => __('Last Login','ultimate-member'),
			'required' => 0,
			'public' => 1,
			'editable' => 1,
			'edit_forbidden' => 1,
		),

		'user_email' => array(
			'title' => __('E-mail Address','ultimate-member'),
			'metakey' => 'user_email',
			'type' => 'text',
			'label' => __('E-mail Address','ultimate-member'),
			'required' => 0,
			'public' => 1,
			'validate' => 'unique_email',
			'autocomplete' => 'off'
		),

		'secondary_user_email' => array(
			'title' => __('Secondary E-mail Address','ultimate-member'),
			'metakey' => 'secondary_user_email',
			'type' => 'text',
			'label' => __('Secondary E-mail Address','ultimate-member'),
			'required' => 0,
			'public' => 1,
			'editable' => 1,
			'validate' => 'unique_email',
			'autocomplete' => 'off'
		),

		'description' => array(
			'title' => __('Biography','ultimate-member'),
			'metakey' => 'description',
			'type' => 'textarea',
			'label' => __('Biography','ultimate-member'),
			'html' => 0,
			'required' => 0,
			'public' => 1,
			'editable' => 1,
			'max_words' => 40,
			'placeholder' => __('Enter a bit about yourself...','ultimate-member'),
		),

		'birth_date' => array(
			'title' => __('Birth Date','ultimate-member'),
			'metakey' => 'birth_date',
			'type' => 'date',
			'label' => __('Birth Date','ultimate-member'),
			'required' => 0,
			'public' => 1,
			'editable' => 1,
			'pretty_format' => 1,
			'years' => 115,
			'years_x' => 'past',
			'icon' => 'um-faicon-calendar'
		),

		'gender' => array(
			'title' => __('Gender','ultimate-member'),
			'metakey' => 'gender',
			'type' => 'radio',
			'label' => __('Gender','ultimate-member'),
			'required' => 0,
			'public' => 1,
			'editable' => 1,
			'options' => array( __('Male','ultimate-member'), __('Female','ultimate-member') )
		),

		'country' => array(
			'title' => __('Country','ultimate-member'),
			'metakey' => 'country',
			'type' => 'select',
			'label' => __('Country','ultimate-member'),
			'placeholder' => __('Choose a Country','ultimate-member'),
			'required' => 0,
			'public' => 1,
			'editable' => 1,
			'options' => $builtin->get('countries')
		),

		'facebook' => array(
			'title' => __('Facebook','ultimate-member'),
			'metakey' => 'facebook',
			'type' => 'url',
			'label' => __('Facebook','ultimate-member'),
			'required' => 0,
			'public' => 1,
			'editable' => 1,
			'url_target' => '_blank',
			'url_rel' => 'nofollow',
			'icon' => 'um-faicon-facebook',
			'validate' => 'facebook_url',
			'url_text' => 'Facebook',
			'advanced' => 'social',
			'color' => '#3B5999',
			'match' => 'https://facebook.com/',
		),

		'twitter' => array(
			'title' => __('Twitter','ultimate-member'),
			'metakey' => 'twitter',
			'type' => 'url',
			'label' => __('Twitter','ultimate-member'),
			'required' => 0,
			'public' => 1,
			'editable' => 1,
			'url_target' => '_blank',
			'url_rel' => 'nofollow',
			'icon' => 'um-faicon-twitter',
			'validate' => 'twitter_url',
			'url_text' => 'Twitter',
			'advanced' => 'social',
			'color' => '#4099FF',
			'match' => 'https://twitter.com/',
		),

		'linkedin' => array(
			'title' => __('LinkedIn','ultimate-member'),
			'metakey' => 'linkedin',
			'type' => 'url',
			'label' => __('LinkedIn','ultimate-member'),
			'required' => 0,
			'public' => 1,
			'editable' => 1,
			'url_target' => '_blank',
			'url_rel' => 'nofollow',
			'icon' => 'um-faicon-linkedin',
			'validate' => 'linkedin_url',
			'url_text' => 'LinkedIn',
			'advanced' => 'social',
			'color' => '#0976b4',
			'match' => 'https://linkedin.com/in/',
		),

		'instagram' => array(
			'title' => __('Instagram','ultimate-member'),
			'metakey' => 'instagram',
			'type' => 'url',
			'label' => __('Instagram','ultimate-member'),
			'required' => 0,
			'public' => 1,
			'editable' => 1,
			'url_target' => '_blank',
			'url_rel' => 'nofollow',
			'icon' => 'um-faicon-instagram',
			'validate' => 'instagram_url',
			'url_text' => 'Instagram',
			'advanced' => 'social',
			'color' => 'radial-gradient(circle at 30% 107%, #fdf497 0%, #fdf497 5%, #fd5949 45%,#d6249f 60%,#285AEB 90%)',
			'match' => 'https://instagram.com/',
		),

		'skype' => array(
			'title' => __('Skype ID','ultimate-member'),
			'metakey' => 'skype',
			'type' => 'url',
			'label' => __('Skype ID','ultimate-member'),
			'required' => 0,
			'public' => 1,
			'editable' => 1,
			'url_target' => '_blank',
			'url_rel' => 'nofollow',
			'icon' => 'um-faicon-skype',
			'validate' => 'skype',
			'url_text' => 'Skype',
		),

		'youtube' => array(
			'title' => __('YouTube','ultimate-member'),
			'metakey' => 'youtube',
			'type' => 'url',
			'label' => __('YouTube','ultimate-member'),
			'required' => 0,
			'public' => 1,
			'editable' => 1,
			'url_target' => '_blank',
			'url_rel' => 'nofollow',
			'icon' => 'um-faicon-youtube',
			'validate' => 'youtube_url',
			'url_text' => 'YouTube',
			'advanced' => 'social',
			'color' => '#e52d27',
			'match' => 'https://youtube.com/',
		),

		'soundcloud' => array(
			'title' => __('SoundCloud','ultimate-member'),
			'metakey' => 'soundcloud',
			'type' => 'url',
			'label' => __('SoundCloud','ultimate-member'),
			'required' => 0,
			'public' => 1,
			'editable' => 1,
			'url_target' => '_blank',
			'url_rel' => 'nofollow',
			'icon' => 'um-faicon-soundcloud',
			'validate' => 'soundcloud_url',
			'url_text' => 'SoundCloud',
			'advanced' => 'social',
			'color' => '#f50',
			'match' => 'https://soundcloud.com/',
		),

		'vkontakte' => array(
			'title' => __('VKontakte','ultimate-member'),
			'metakey' => 'vkontakte',
			'type' => 'url',
			'label' => __('VKontakte','ultimate-member'),
			'required' => 0,
			'public' => 1,
			'editable' => 1,
			'url_target' => '_blank',
			'url_rel' => 'nofollow',
			'icon' => 'um-faicon-vk',
			'validate' => 'vk_url',
			'url_text' => 'VKontakte',
			'advanced' => 'social',
			'color' => '#2B587A',
			'match' => 'https://vk.com/',
		),

		'role_select' => array(
			'title' => __('Roles (Dropdown)','ultimate-member'),
			'metakey' => 'role_select',
			'type' => 'select',
			'label' => __('Account Type','ultimate-member'),
			'placeholder' => 'Choose account type',
			'required' => 0,
			'public' => 1,
			'editable' => 1,
			'options' => $um_roles,
		),

		'role_radio' => array(
			'title' => __('Roles (Radio)','ultimate-member'),
			'metakey' => 'role_radio',
			'type' => 'radio',
			'label' => __('Account Type','ultimate-member'),
			'required' => 0,
			'public' => 1,
			'editable' => 1,
			'options' => $um_roles,
		),

		'languages' => array(
			'title' => __('Languages','ultimate-member'),
			'metakey' => 'languages',
			'type' => 'multiselect',
			'label' => __('Languages Spoken','ultimate-member'),
			'placeholder' => __('Select languages','ultimate-member'),
			'required' => 0,
			'public' => 1,
			'editable' => 1,
			'options' => $builtin->get('languages'),
		),

		'phone_number' => array(
			'title' => __('Cell Phone Number','ultimate-member'),
			'metakey' => 'phone_number',
			'type' => 'text',
			'label' => __('Cell Phone Number','ultimate-member'),
			'required' => 0,
			'public' => 1,
			'editable' => 1,
			'validate' => 'phone_number',
			'icon' => 'um-faicon-phone',
		),

		'office_phone_number' => array(
			'title' => __('Work Phone Number','ultimate-member'),
			'metakey' => 'office_phone_number',
			'type' => 'text',
			'label' => __('Work phone','ultimate-member'),
			'required' => 0,
			'public' => 1,
			'editable' => 1,
			'validate' => 'phone_number',
			'icon' => 'um-faicon-phone',
		),

		'home_phone_number' => array(
			'title' => __('Main Phone Number','ultimate-member'),
			'metakey' => 'home_phone_number',
			'type' => 'text',
			'label' => __('Main Phone Number','ultimate-member'),
			'required' => 0,
			'public' => 1,
			'editable' => 1,
			'validate' => 'phone_number',
			'icon' => 'um-faicon-phone',
		),

		// private use ( not public list )

		'profile_photo' => array(
			'title' => __('Profile Photo','ultimate-member'),
			'metakey' => 'profile_photo',
			'type' => 'image',
			'label' => __('Change your profile photo','ultimate-member'),
			'upload_text' => __('Upload your photo here','ultimate-member'),
			'icon' => 'um-faicon-camera',
			'crop' => 1,
			'max_size' => ( UM()->options()->get('profile_photo_max_size') ) ? UM()->options()->get('profile_photo_max_size') : 999999999,
			'min_width' => str_replace('px','',UM()->options()->get('profile_photosize')),
			'min_height' => str_replace('px','',UM()->options()->get('profile_photosize')),
			'private_use' => true,
		),

		'cover_photo' => array(
			'title' => __('Cover Photo','ultimate-member'),
			'metakey' => 'cover_photo',
			'type' => 'image',
			'label' => __('Change your cover photo','ultimate-member'),
			'upload_text' => __('Upload profile cover here','ultimate-member'),
			'icon' => 'um-faicon-picture-o',
			'crop' => 2,
			'max_size' => ( UM()->options()->get('cover_photo_max_size') ) ? UM()->options()->get('cover_photo_max_size') : 999999999,
			'modal_size' => 'large',
			'ratio' => str_replace(':1','',UM()->options()->get('profile_cover_ratio')),
			'min_width' => UM()->options()->get('cover_min_width'),
			'private_use' => true,
		),

		'username_b' => array(
			'title' => __('Username or E-mail','ultimate-member'),
			'metakey' => 'username_b',
			'type' => 'text',
			'placeholder' => __('Enter your username or email','ultimate-member'),
			'required' => 1,
			'public' => 1,
			'editable' => 0,
			'private_use' => true,
		),

		// account page use ( not public )

		'profile_privacy'       => array(
			'title'         => __( 'Profile Privacy', 'ultimate-member' ),
			'metakey'       => 'profile_privacy',
			'type'          => 'select',
			'label'         => __( 'Profile Privacy', 'ultimate-member' ),
			'help'          => __( 'Who can see your public profile?', 'ultimate-member' ),
			'required'      => 0,
			'public'        => 1,
			'editable'      => 1,
			'default'       => 'Everyone',
			'options'       => $profile_privacy,
			'allowclear'    => 0,
			'account_only'  => true,
			'required_perm' => 'can_make_private_profile',
		),

		'profile_noindex'       => array(
			'title'         => __( 'Avoid indexing my profile by search engines', 'ultimate-member' ),
			'metakey'       => 'profile_noindex',
			'type'          => 'select',
			'label'         => __( 'Avoid indexing my profile by search engines', 'ultimate-member' ),
			'help'          => __( 'Hide my profile for robots?', 'ultimate-member' ),
			'required'      => 0,
			'public'        => 1,
			'editable'      => 1,
			'default'       => '0',
			'options'       => array(
				'0'     => __( 'No', 'ultimate-member' ),
				'1'     => __( 'Yes', 'ultimate-member' ),
			),
			'allowclear'    => 0,
			'account_only'  => true,
			'required_perm' => 'can_make_private_profile',
		),

		'hide_in_members'       => array(
			'title'         => __( 'Hide my profile from directory', 'ultimate-member' ),
			'metakey'       => 'hide_in_members',
			'type'          => 'radio',
			'label'         => __( 'Hide my profile from directory', 'ultimate-member' ),
			'help'          => __( 'Here you can hide yourself from appearing in public directory', 'ultimate-member' ),
			'required'      => 0,
			'public'        => 1,
			'editable'      => 1,
			'default'       => UM()->member_directory()->get_hide_in_members_default() ? 'Yes' : 'No',
			'options'       => array(
				'No'    => __( 'No', 'ultimate-member' ),
				'Yes'   => __( 'Yes', 'ultimate-member' ),
			),
			'account_only'  => true,
			'required_opt'  => array( 'members_page', 1 ),
		),

		'delete_account'        => array(
			'title'         => __( 'Delete Account', 'ultimate-member' ),
			'metakey'       => 'delete_account',
			'type'          => 'radio',
			'label'         => __( 'Delete Account', 'ultimate-member'),
			'help'          => __( 'If you confirm, everything related to your profile will be deleted permanently from the site', 'ultimate-member' ),
			'required'      => 0,
			'public'        => 1,
			'editable'      => 1,
			'default'       => __( 'No', 'ultimate-member' ),
			'options'       => array(
				__( 'Yes', 'ultimate-member' ),
				__( 'No', 'ultimate-member' )
			),
			'account_only'  => true,
		),

		'single_user_password'  => array(
			'title'         => __( 'Password', 'ultimate-member' ),
			'metakey'       => 'single_user_password',
			'type'          => 'password',
			'label'         => __( 'Password', 'ultimate-member' ),
			'required'      => 1,
			'public'        => 1,
			'editable'      => 1,
			'account_only'  => true,
		),

		// CUSTOM --------------------------------------------------------------------------------
		// CUSTOM --------------------------------------------------------------------------------
		// CUSTOM --------------------------------------------------------------------------------
		// CUSTOM --------------------------------------------------------------------------------
		// CUSTOM --------------------------------------------------------------------------------
		// CUSTOM --------------------------------------------------------------------------------
		// CUSTOM --------------------------------------------------------------------------------
		// CUSTOM --------------------------------------------------------------------------------

		'snapchat' => array(
			'title' => __('Snapchat','ultimate-member'),
			'metakey' => 'snapchat',
			'type' => 'url',
			'label' => __('Snapchat','ultimate-member'),
			'required' => 0,
			'public' => 0,
			'editable' => 1,
			'url_target' => '_blank',
			'url_rel' => 'nofollow',
			'icon' => 'um-faicon-circle-thin',
			'validate' => 'custom',
			'url_text' => 'Snapchat',
			'advanced' => 'social',
			'color' => '#e52d27',
			'match' => 'https://snapchat.com/',
		),
		'venmo' => array(
			'title' => __('Venmo','ultimate-member'),
			'metakey' => 'venmo',
			'type' => 'url',
			'label' => __('Venmo','ultimate-member'),
			'required' => 0,
			'public' => 1,
			'editable' => 1,
			'url_target' => '_blank',
			'url_rel' => 'nofollow',
			'icon' => 'um-faicon-circle-thin',
			'validate' => 'custom',
			'url_text' => 'Venmo',
			'advanced' => 'social',
			'color' => '#e52d27',
			'match' => 'https://venmo.com/',
		),
		'paypal' => array(
			'title' => __('PayPal','ultimate-member'),
			'metakey' => 'paypal',
			'type' => 'url',
			'label' => __('PayPal','ultimate-member'),
			'required' => 0,
			'public' => 1,
			'editable' => 1,
			'url_target' => '_blank',
			'url_rel' => 'nofollow',
			'icon' => 'um-faicon-circle-thin',
			'validate' => 'custom',
			'url_text' => 'PayPal',
			'advanced' => 'social',
			'color' => '#e52d27',
			'match' => 'https://paypal.com/',
		),
		'cashapp' => array(
			'title' => __('Cash App','ultimate-member'),
			'metakey' => 'cashapp',
			'type' => 'url',
			'label' => __('Cash App','ultimate-member'),
			'required' => 0,
			'public' => 1,
			'editable' => 1,
			'url_target' => '_blank',
			'url_rel' => 'nofollow',
			'icon' => 'um-faicon-circle-thin',
			'validate' => 'custom',
			'url_text' => 'Cash App',
			'advanced' => 'social',
			'color' => '#e52d27',
			'match' => 'https://cash.app/',
		),
		'whatsapp' => array(
			'title' => __('WhatsApp','ultimate-member'),
			'metakey' => 'whatsapp',
			'type' => 'url',
			'label' => __('WhatsApp','ultimate-member'),
			'required' => 0,
			'public' => 1,
			'editable' => 1,
			'url_target' => '_blank',
			'url_rel' => 'nofollow',
			'icon' => 'um-faicon-circle-thin',
			'validate' => 'custom',
			'url_text' => 'WhatsApp',
			'advanced' => 'social',
			'color' => '#e52d27',
			'match' => 'https://whatsapp.com/',
		),
		'tumblr' => array(
			'title' => __('Tumblr','ultimate-member'),
			'metakey' => 'tumblr',
			'type' => 'url',
			'label' => __('Tumblr','ultimate-member'),
			'required' => 0,
			'public' => 1,
			'editable' => 1,
			'url_target' => '_blank',
			'url_rel' => 'nofollow',
			'icon' => 'um-faicon-circle-thin',
			'validate' => 'custom',
			'url_text' => 'Tumblr',
			'advanced' => 'social',
			'color' => '#e52d27',
			'match' => 'https://tumblr.com/',
		),
		'tiktok' => array(
			'title' => __('TikTok','ultimate-member'),
			'metakey' => 'tiktok',
			'type' => 'url',
			'label' => __('TikTok','ultimate-member'),
			'required' => 0,
			'public' => 1,
			'editable' => 1,
			'url_target' => '_blank',
			'url_rel' => 'nofollow',
			'icon' => 'um-faicon-circle-thin',
			'validate' => 'custom',
			'url_text' => 'TikTok',
			'advanced' => 'social',
			'color' => '#e52d27',
			'match' => 'https://tiktok.com/',
		),
		'spotify' => array(
			'title' => __('Spotify','ultimate-member'),
			'metakey' => 'spotify',
			'type' => 'url',
			'label' => __('Spotify','ultimate-member'),
			'required' => 0,
			'public' => 1,
			'editable' => 1,
			'url_target' => '_blank',
			'url_rel' => 'nofollow',
			'icon' => 'um-faicon-circle-thin',
			'validate' => 'custom',
			'url_text' => 'Spotify',
			'advanced' => 'social',
			'color' => '#e52d27',
			'match' => 'https://spotify.com/',
		),
		'musicapple' => array(
			'title' => __('Apple Music','ultimate-member'),
			'metakey' => 'musicapple',
			'type' => 'url',
			'label' => __('Apple Music','ultimate-member'),
			'required' => 0,
			'public' => 1,
			'editable' => 1,
			'url_target' => '_blank',
			'url_rel' => 'nofollow',
			'icon' => 'um-faicon-circle-thin',
			'validate' => 'custom',
			'url_text' => 'Apple Music',
			'advanced' => 'social',
			'color' => '#e52d27',
			'match' => 'https://music.apple.com/',
		),
	);
	
	return $predefined_fields;
}

add_action( 'um_custom_field_validation_social_url', 'my_custom_field_validation_social_url', 10, 3 );
function my_custom_field_validation_social_url( $key, $field, $args ) {
	if ( 
		! UM()->validation()->is_url( $args[ $key ], 'snapchat.com' ) &&
		! UM()->validation()->is_url( $args[ $key ], 'snapchat.com' ) &&
		! UM()->validation()->is_url( $args[ $key ], 'paypal.com' ) &&
		! UM()->validation()->is_url( $args[ $key ], 'cash.app' ) &&
		! UM()->validation()->is_url( $args[ $key ], 'whatsapp.com' ) &&
		! UM()->validation()->is_url( $args[ $key ], 'tumblr.com' )  &&
		! UM()->validation()->is_url( $args[ $key ], 'tiktok.com' ) &&
		! UM()->validation()->is_url( $args[ $key ], 'spotify.com' ) &&
		! UM()->validation()->is_url( $args[ $key ], 'venmo.com' ) &&
		! UM()->validation()->is_url( $args[ $key ], 'music.apple.com' )
	) {
		UM()->form()->add_error( $key, sprintf( __( 'Please enter a valid %s username or profile URL', 'ultimate-member' ), $field['label'] ) );
	}
}



/**
 * Show the submit button (highest priority)
 *
 * @param $args
 */
function my_add_submit_button_to_profile( $args ) {
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

	<div class="um-col-alt">

		<?php if ( isset( $args['secondary_btn'] ) && $args['secondary_btn'] != 0 ) { ?>

			<div class="um-left um-half ">
				<div class="um-button-wrapper-icon save-button um-button save-button">
					<span class="um-button-icon"> </span>
					<input type="submit" value="<?php esc_attr_e( wp_unslash( $args['primary_btn_word'] ), 'ultimate-member' ); ?>" />
				</div>
			</div>
			<div class="um-right um-half">
				<a href="<?php echo esc_url( um_edit_my_profile_cancel_uri() ); ?>" class="um-button um-alt cancel-button">
					<?php _e( wp_unslash( $args['secondary_btn_word'] ), 'ultimate-member' ); ?>
				</a>
			</div>

		<?php } else { ?>

			<div class="um-center">
				<div class="um-button-wrapper-icon save-button um-button save-button">
					<span class="um-button-icon"> </span>
					<input type="submit" value="<?php esc_attr_e( wp_unslash( $args['primary_btn_word'] ), 'ultimate-member' ); ?>" />
				</div>
			</div>

		<?php } ?>

		<div class="um-clear"></div>

	</div>

	<?php
}
remove_action( 'um_after_profile_fields', 'um_add_submit_button_to_profile', 1000);
add_action( 'um_after_profile_fields', 'my_add_submit_button_to_profile', 1001 );
