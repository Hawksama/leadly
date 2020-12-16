<?php

if ( ! defined( 'ABSPATH' ) ) exit; 

if( !is_plugin_active( 'buddypress/bp-loader.php' ) && !function_exists( 'bp_is_active' ) ){
	return;
}

class ACUI_Buddypress{
	function __construct(){
		add_filter( 'acui_restricted_fields', array( $this, 'restricted_fields' ), 10, 1 );
		add_action( 'acui_tab_import_before_import_button', array( $this, 'show_compatibility' ) );
		add_action( 'acui_documentation_after_plugins_activated', array( $this, 'documentation' ) );
		add_filter( 'acui_export_columns', array( $this, 'export_columns' ), 10, 1 );
		add_filter( 'acui_export_data', array( $this, 'export_data' ), 10, 3 );
		add_action( 'post_acui_import_single_user', array( $this, 'import_avatar' ), 10, 3 );		
	}

	function restricted_fields( $acui_restricted_fields ){
		return array_merge( $acui_restricted_fields, array( 'bp_avatar' ) );
	}

	function show_compatibility(){
		if( !class_exists( "BP_XProfile_Group" ) ){
			require_once( WP_PLUGIN_DIR . "/buddypress/bp-xprofile/classes/class-bp-xprofile-group.php" );
		}
	
		$buddypress_fields = array();
		$buddypress_types = array();
		$profile_groups = BP_XProfile_Group::get( array( 'fetch_fields' => true	) );
	
		if ( !empty( $profile_groups ) ) {
			 foreach ( $profile_groups as $profile_group ) {
				if ( !empty( $profile_group->fields ) ) {				
					foreach ( $profile_group->fields as $field ) {
						$buddypress_fields[] = $field->name;
						$buddypress_types[] = $field->type;
					}
				}
			}
		}
		?>
		<h2><?php _e( 'BuddyPress & BuddyBoss compatibility', 'import-users-from-csv-with-meta'); ?></h2>
	
		<table class="form-table">
			<tbody>
			<tr class="form-field form-required">
				<th scope="row"><label><?php _e( 'BuddyPress/BuddyBoss users', 'import-users-from-csv-with-meta' ); ?></label></th>
				<td><?php _e( 'You can insert any profile from BuddyPress using his name as header. Plugin will check, before import, which fields are defined in BuddyPress and will assign it in the update. You can use this fields:', 'import-users-from-csv-with-meta' ); ?>
				<ul style="list-style:disc outside none;margin-left:2em;">
					<?php foreach ( $buddypress_fields as $buddypress_field ): ?><li><?php echo $buddypress_field; ?></li><?php endforeach; ?>
				</ul>
				<?php _e( 'Remember that all date fields have to be imported using a format like this: 2016-01-01 00:00:00', 'import-users-from-csv-with-meta' ); ?>
	
				<p class="description"><strong>(<?php _e( 'Only for', 'import-users-from-csv-with-meta' ); ?> <a href="https://wordpress.org/plugins/buddypress/">BuddyPress</a> <?php _e( 'users', 'import-users-from-csv-with-meta' ); ?></strong>.)</p>
				</td>					
			</tr>
			</tbody>
		</table>
		<?php
	}
	
	function documentation(){
		?>
		<tr valign="top">
			<th scope="row"><?php _e( 'BuddyPress/BuddyBoss avatar', 'import-users-from-csv-with-meta' ); ?></th>
			<td><?php _e( 'You can import users avatars using a column called <strong>bp_avatar</strong>, in this field you can place:', 'import-users-from-csv-with-meta' ); ?>
			<ul style="list-style:disc outside none;margin-left:2em;">
					<li>An integer which identify the ID of an attachment uploaded to your media library</li>
					<li>A string that contain a path or an URL to the image</li>
				</ul>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e( "BuddyPress or BuddyBoss is activated", 'import-users-from-csv-with-meta' ); ?></th>
			<td><?php _e( "You can use the <strong>profile fields</strong> you have created and also you can set one or more groups for each user. For example:", 'import-users-from-csv-with-meta' ); ?>
				<ul style="list-style:disc outside none; margin-left:2em;">
					<li><?php _e( "If you want to assign an user to a group you have to create a column 'bp_group' and a column 'bp_group_role'", 'import-users-from-csv-with-meta' ); ?></li>
					<li><?php _e( "Then in each cell you have to fill with the BuddyPress <strong>group slug</strong>", 'import-users-from-csv-with-meta' ); ?></li>
					<li><?php _e( "And the role assigned in this group: <em>Administrator, Moderator or Member</em>", 'import-users-from-csv-with-meta' ); ?></li>
					<li><?php _e( "You can do it with multiple groups at the same time using commas to separate different groups, in bp_group column, i.e.: <em>group_1, group_2, group_3</em>", 'import-users-from-csv-with-meta' ); ?></li>
					<li><?php _e( "But you will have to assign a role for each group: <em>Moderator,Moderator,Member,Member</em>", 'import-users-from-csv-with-meta' ); ?></li>
					<li><?php _e( "If you get some error of this kind:", 'import-users-from-csv-with-meta' ); ?> <code>Fatal error: Class 'BP_XProfile_Group'</code> <?php _e( "please enable Buddypress Extended Profile then import the csv file. You can then disable this afterwards", 'import-users-from-csv-with-meta' ); ?></li>
				</ul>
			</td>
		</tr>
		<?php
	}

	function get_fields(){
		if( !is_plugin_active( 'buddypress/bp-loader.php' ) && !function_exists( 'bp_is_active' ) ){
			return array();
		}

		if( !class_exists( "BP_XProfile_Group" ) ){
			require_once( WP_PLUGIN_DIR . "/buddypress/bp-xprofile/classes/class-bp-xprofile-group.php" );
		}

		$buddypress_fields = array();
		$profile_groups = BP_XProfile_Group::get( array( 'fetch_fields' => true	) );

		if ( !empty( $profile_groups ) ) {
			 foreach ( $profile_groups as $profile_group ) {
				if ( !empty( $profile_group->fields ) ) {				
					foreach ( $profile_group->fields as $field ) {
						$buddypress_fields[] = $field->name;
					}
				}
			}
		}

		return $buddypress_fields;
	}

	function get_groups( $user_id ){
		$groups = BP_Groups_Member::get_group_ids( $user_id );
		return implode( ",", $groups['groups'] );
	}

	function get_member_type( $user_id ){
		return implode( ",", bp_get_member_type( $user_id, false ) );
	}

	function export_columns( $row ){
		foreach ( $this->get_fields() as $key ) {
			$row[] = $key;
		}

		$row[] = 'bp_group';
		$row[] = 'bp_member_type';

		return $row;
	}

	function export_data( $row, $user ){
		foreach ( $this->get_fields() as $key ) {
			$row[] = ACUI_Exporter::prepare( $key, xprofile_get_field_data( $key, $user, 'comma' ), $datetime_format );
		}

		$row[] = $this->get_groups( $user );
		$row[] = $this->get_member_type( $user );

		return $row;
	}

	function import_avatar( $headers, $row, $user_id ){
		$pos = array_search( 'bp_avatar', $headers );

		if( $pos === FALSE )
			return;

		$this->import_avatar_raw( $user_id, $row[ $pos ] );
	}

	function import_avatar_raw( $user_id, $source ){
		$avatar_dir = bp_core_avatar_upload_path() . '/avatars';

		if ( ! file_exists( $avatar_dir ) ) {
			if ( ! wp_mkdir_p( $avatar_dir ) ) {
				return false;
			}
		}

		$avatar_folder_dir = apply_filters( 'bp_core_avatar_folder_dir', $avatar_dir . '/' . $user_id, $user_id, 'user', 'avatars' );

		if ( ! is_dir( $avatar_folder_dir ) ) {
			if ( ! wp_mkdir_p( $avatar_folder_dir ) ) {
				return false;
			}
		}

		$original_file = $avatar_folder_dir . '/import-export-users-customers-bp-avatar-' . $user_id . '.png';
		$data = ( (string)(int)$source == $source ) ? file_get_contents( get_attached_file( $source ) ) : file_get_contents( $source );
		
		if ( file_put_contents( $original_file, $data ) ) {
			$avatar_to_crop = str_replace( bp_core_avatar_upload_path(), '', $original_file );

			$crop_args = array(
				'item_id'       => $user_id,
				'original_file' => $avatar_to_crop,
				'crop_x'        => 0,
				'crop_y'        => 0,
			);

			return bp_core_avatar_handle_crop( $crop_args );
		} else {
			return false;
		}
	}
}
new ACUI_Buddypress();