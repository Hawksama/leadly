 <?php

	if( !defined( 'ABSPATH') )
	exit('Restricted Access..! Please Login.');
 
	if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }

	wp_register_style( 'DC_MyWP_login_logo_Styles', DC_MyWP_LoginLogo_URL.'css/styles.css' );
	wp_enqueue_style( 'DC_MyWP_login_logo_Styles' );
	wp_enqueue_script('jquery');
	wp_enqueue_media();
	
	global $current_user;
    get_currentuserinfo();

	$invalid_number = false;
	$updated = false;
	
	if($_POST['update_MyWP_login_logo'] == 'update') {

		check_admin_referer('update_MyWP_login_logo');

		$wp_custom_login_logo_url 		= sanitize_text_field($_POST['wp_custom_login_logo_url']);
		$wp_custom_login_logo_height 	= sanitize_text_field($_POST['wp_custom_login_logo_height']);
		$wp_custom_login_logo_width 	= sanitize_text_field($_POST['wp_custom_login_logo_width']);
		$wp_custom_login_title			= sanitize_text_field($_POST['wp_custom_login_title']);
		$wp_custom_login_logo_fadein	= sanitize_text_field($_POST['wp_custom_login_logo_fadein']);
		$wp_custom_login_logo_fadetime	= sanitize_text_field($_POST['wp_custom_login_logo_fadetime']);
		$wp_custom_login_logo_message	= sanitize_text_field($_POST['wp_custom_login_logo_message']);

		if(!intval($wp_custom_login_logo_height) || !intval($wp_custom_login_logo_width)) {
			$invalid_number = true;
		} else {
			update_option('wp_custom_login_logo_url', $wp_custom_login_logo_url);
			update_option('wp_custom_login_logo_height', $wp_custom_login_logo_height);
			update_option('wp_custom_login_logo_width', $wp_custom_login_logo_width);
			update_option('wp_custom_login_title', $wp_custom_login_title);
			update_option('wp_custom_login_logo_fadein', $wp_custom_login_logo_fadein);
			update_option('wp_custom_login_logo_fadetime', $wp_custom_login_logo_fadetime);
			update_option('wp_custom_login_logo_message', $wp_custom_login_logo_message);
			$updated = true;
		}	
	}

		$custom_logo_url = esc_url(get_option('wp_custom_login_logo_url', DC_MyWP_LoginLogo_URL.'images/mylogo.png'));
		$custom_logo_height = esc_textarea(get_option('wp_custom_login_logo_height','70'));
		$custom_logo_width = esc_textarea(get_option('wp_custom_login_logo_width','320'));
		$custom_login_title = esc_textarea(get_option('wp_custom_login_title',get_bloginfo('description')));
		$custom_login_url = esc_textarea(get_option('wp_custom_login_url',home_url()));
		$custom_logo_fadein = esc_textarea(get_option('wp_custom_login_logo_fadein','true'));
		$custom_logo_fadetime = esc_textarea(get_option('wp_custom_login_logo_fadetime','2500'));
		$custom_logo_message = esc_textarea(get_option('wp_custom_login_logo_message',''));
?>


<div style="margin: 10px 20px 0 2px;">	
<div class="metabox-holder columns-2">
<div class="postbox-container">	
<div id="top-sortables" class="meta-box-sortables ui-sortable">


<table cellpadding="2" cellspacing="1" width="100%" class="fixed" border="0">
	<tbody>
		<tr>
			<td valign="top"><h3>My Wordpress Login Logo</h3></td>
		</tr>
	</tbody>
</table>
<hr>

<?php
	if($updated) {
		echo "<div class=\"updated\"><p><strong>Login Page Updated.</strong></p></div>";
	}
	if($invalid_number) {
		echo "<div class=\"error\"><p><strong> Error: Provide a valid height and width </strong></p></div>";
	}	
?>	

<table cellpadding="2" cellspacing="1" width="100%" class="fixed" border="0">
<tbody><tr>
	<td valign="top">

		<div class="postbox">
			<button class="handlediv button-link" aria-expanded="true" type="button">
				<span class="screen-reader-text">Toggle panel: Current Login Page Logo</span>
				<span class="toggle-indicator" aria-hidden="true"></span>
			</button>
			<h2 class="hndle ui-sortable-handle">
				<span>Current Login Page Logo</span>
			</h2>
			<div class="inside">
				<p class="description"><img src="<?php echo $custom_logo_url; ?>" alt="" /></p> 
			</div>
		</div>

		<div class="postbox">
			<button class="handlediv button-link" aria-expanded="true" type="button">
				<span class="screen-reader-text">Toggle panel: Customize Login Page</span>
				<span class="toggle-indicator" aria-hidden="true"></span>
			</button>
			<h2 class="hndle ui-sortable-handle">
				<span>Customize Login Page</span>
			</h2>

			<div class="inside">
				<form name="DC_MyWP_login_logo_form" method="post" action="">
					<?php wp_nonce_field('update_MyWP_login_logo'); ?>
					<input type="hidden" name="update_MyWP_login_logo" value="update">
					<h3>Customize Logo</h3> 

					<table class="form-table"><tbody>
					<tr>
						<th scope="row"><label for="logoimage">Logo Image</label></th>
						<td><input class="regular-text" type="text" id="wp_custom_login_logo_url" name="wp_custom_login_logo_url" value="<?php echo $custom_logo_url; ?>" size="70"> <input type="button" name="upload-btn" id="upload-btn" class="button-secondary" value="Upload Image"></td>
							<script type="text/javascript">
							jQuery(document).ready(function($){
								$('#upload-btn').click(function(e) {
									e.preventDefault();
									var image = wp.media({ 
										title: 'Upload Image',
										multiple: false
									}).open()
									.on('select', function(e){
										var uploaded_image = image.state().get('selection').first();
										var image_url = uploaded_image.toJSON().url;
										$('#wp_custom_login_logo_url').val(image_url);
									});
								});
							});
							</script>
					</tr>
					<tr>
						<th scope="row"><label for="logowidth">Logo Width</label></th>
						<td><input type="text" name="wp_custom_login_logo_width" value="<?php echo $custom_logo_width; ?>" size="5"> px</td>
					</tr>
					<tr>
						<th scope="row"><label for="logowidth">Logo Height</label></th>
						<td><input type="text" name="wp_custom_login_logo_height" value="<?php echo $custom_logo_height; ?>" size="5"> px</td>
					</tr>
					<tr>
						<th scope="row"><label for="logolinkurl">Logo Link URL</label></th>
						<td><input class="regular-text code" type="text" name="wp_custom_login_url" value="<?php echo $custom_login_url; ?>" size="70">
						<p class="description">This is the url opened when clicked on the logo in your login page.</p></td>
					</tr>
					<tr>
						<th scope="row"><label for="logotitle">Logo Page Title</label></th>
						<td><input class="regular-text code" type="text" name="wp_custom_login_title" value="<?php echo $custom_login_title; ?>" size="40">
						<p class="description">Title or description shown on hovering mouse over the logo.</p></td>
					</tr>
					<tr>
					<th scope="row"><label for="submit"></label></th>
					<td><p class="submit"><input type="submit" class="button-primary" name="Submit" value="Update" /></p></td>
					</tr>
					</tbody></table>

					<br/>
					<h3>Advanced Customization</h3>
					<table class="form-table"><tbody>
					<tr>
						<th scope="row"><label for="fadeineffect">FadeIn Effect</label></th>
						<td><input id="DisableFadeIn" type="checkbox" name="wp_custom_login_logo_fadein" value="false" <?php if($custom_logo_fadein) echo "checked"; ?>>Enable FadeIn Effect<p class="description"> Provides a fading effect to the login form</p></td>
					</tr>

					<tr <?php if(!$custom_logo_fadein) { echo 'style="display:none;"'; } ?>>
						<th scope="row"><label for="fadetime">FadeIn Time</label></th>
						<td><input id="DisableFadeIn" type="text" name="wp_custom_login_logo_fadetime" value="<?php echo $custom_logo_fadetime; ?>" size="5"> seconds<p class="description">Set fade in time.</p></td>
					</tr>
							<script type="text/javascript">// <![CDATA[
							jQuery('#DisableFadeIn').change(function(){
							if(jQuery(this).is(':checked')){
								jQuery('#fadetime').show();
							} else {
								jQuery('#fadetime').hide();
							}
							});
							// ]]></script>
					
					<tr>
						<th scope="row"><label for="fadetime">Custom Message</label></th>
						<td><textarea class="large-text code" name="wp_custom_login_logo_message"><?php echo $custom_logo_message; ?></textarea><p class="description">Shows the given message below the login form. Leave this empty if you don't want to show any custom message.</p></td>
					</tr>

					<tr>
					<th scope="row"><label for="submit"></label></th>
					<td><p class="submit"><input type="submit" class="button-primary" name="Submit" value="Update" /></p></td>
					</tr>
					</tbody></table>

				</form>
			</div>
		</div>			

	
		</td>
		<td width="300" valign="top">
			<div class="inner-sidebar" id="side-info-column">
			<?php include_once( DC_MyWP_LoginLogo_PATH . '/views/plugin-info.php' ); ?>	
			</div>	

			<div class="inner-sidebar" id="side-info-column">
			<?php include_once( DC_MyWP_LoginLogo_PATH . '/views/faq.php' ); ?>
			</div>
		</td>
</tr></tbody>
</table>
			
</div>
</div>
</div>
</div>