<?php

######### CONTENTS ##################
# provision users page
# options page for wind plugin

############# PROVISION USERS PAGE #################
function wind_addProvisionUsersPage() {
		if (function_exists('add_submenu_page')) {        
			add_submenu_page( 'ms-admin.php', 'Provision Users', 'Provision Users', 'manage_network_options', 'wind_provision_users', 'wind_provisionUsersPage');
		} 
} // end wind_addProvisionUsersPage()

function wind_provisionUsersPage() {
	global $wpdb, $wp_rewrite;
	$wind_wp_path =  explode("wp-content/", dirname(__FILE__)); 
		
	add_contextual_help($current_screen, 
		'<p>' . __('Include contextual help here') . '</p>' .
		'<p><strong>' . __('For more information:') . '</strong></p>' .
		'<p>' . __('<a href="http://wordpress.org/support/">Support Forums</a>') . '</p>'
	);
	
	# process updates on post
	if ($_SERVER['REQUEST_METHOD'] == 'POST') wind_addNewUsers();
	
	# now all the site options are available as variables
	extract(wind_getSiteOptions());
	
	# check for "updated" on get
	if (isset($_GET['updated'])) {
	?>
	<div id="message" class="updated"><p><?php _e( 'Options saved.' ) ?></p></div>
	<?php
		}
	?>
	<div class="wrap"> 
		<h2>Provision New WIND Users...</h2>
	<p>Separate multiple unis by a new line. </p>
	<form method="post" id="wind_new_users">
		<?php wp_nonce_field('wind_new_users') ?>
		<fieldset class="options">
                <table class="form-table" cellpadding="3" cellspacing="3">
                        <tr valign="top">
                                <th scope='row'><label for="wind_new_users"><?php _e('Grant these users:') ?></label></th>
                                <td><textarea name="wind_new_users" id="wind_new_users" rows="15" cols="40"></textarea></td>
                        </tr>
			<tr valign="top">
 				<th scope="row"><label for="wind_new_roles"><?php _e('... this role:') ?></label></th>
				<td><?php wind_roles_dropdown() ?></td>
			</tr>
			<tr valign="top">
 				<th scope="row"><label for="wind_new_blogs"><?php _e('... on this blog:') ?></label></th>
				<td><?php wind_blogs_dropdown() ?></td>
			</tr>
                </table>
                <p class="submit">
                        <input class="button" type="submit" name="windProvisionUsers" value="<?php _e('Provision Users') ?>" />
		</p>
		</fieldset>
	</form>
	

		<?php
} // end wind_provisionUsersPage()

function wind_blogs_dropdown() {
# need to limit this list to just those blogs you have admin privs on
// (is_super_admin($current_user->username) || is_admin($current_user->username))
	global $wpdb, $base;
	
	$all_blogs = $wpdb->get_results( "SELECT blog_id, replace(replace(path, '$base', ''), '/', '') as path from wp_blogs" );
	
	print '<select name="wind_new_blog" id="wind_new_blog">';
	
	foreach ($all_blogs as $blog) {
		if ($blog->path) {
			$blog_name = $blog->path;
		} else {
			$blog_name = "[main blog]";
		}
		print "<option  value='{$blog->blog_id}'>$blog_name</option>";
	}
	
	print '</select>';
} // end wind_blogs_dropdown()

function wind_roles_dropdown() {
	?>
	<select name="wind_new_role" id="wind_new_role">
		<!--  <option  value="administrator">Administrator</option> -->
		<option value="editor">Editor</option>
		<option selected='selected' value="author">Author</option>
		<option value="contributor">Contributor</option>
		<!--  <option  value="subscriber">Subscriber</option> -->
	</select> 	
	<?
} // end wind_roles_dropdown()

function wind_addNewUsers() {
	require_once("wind_functions.php");
	if($_POST['wind_new_users'] && $_POST['wind_new_role'] && $_POST['wind_new_blog']) {
		print "<div id='message' class='updated fade'><p>Adding new users...</p>";
		
		$users_to_add = array();
		$users_to_add = explode("\n", $_POST['wind_new_users']);
		$users_to_add = array_filter(array_map('trim', $users_to_add)); 
		$users_to_add = array_map('strtolower', $users_to_add);
		
		foreach ($users_to_add as $user) {
			// does this look like a valid uni?
			if ( preg_match('/^[a-z]{2,}[0-9]+$/', $user) ) {
				// yes, go ahead
				// Check to see if user already exists; if so, subscribe them
				if ( $existing_user = get_userdatabylogin($user) ) {
					// user already exists; add to blog
					print "<br>$user already exists, adding to blog";
					add_user_to_blog($_POST['wind_new_blog'], $existing_user->ID, $_POST['wind_new_role']);
				}  else {
					// user doesn't exist 
					//look up their ldap info... make warning if user can't be found in ldap
					print "<br>$user does not exist yet... ";
					$user_ldap_info = get_ldap_information($user);
					if ($user_ldap_info['first_name']) {
						print "found {$user_ldap_info['first_name']} {$user_ldap_info['last_name']} in LDAP. Adding...";
					} else {
						print "No match in LDAP. Bad uni or FERPA-protected student? Adding user regardless...";
					} // done warning admin about user LDAP status
					
					// add user to WP; pass in empty array of wind affiliations
					$wind_affiliations = array();
					wind_create_wp_user( $user, $wind_affiliations );
					
					// now get their user ID and add them to this blog
					$existing_user = get_userdatabylogin($user);
					add_user_to_blog($_POST['wind_new_blog'], $existing_user->ID, $_POST['wind_new_role']);
					
				} // done checking whether user exists in WP
			} else {
				// no, it does not ... abort
				print "<br>'$user' does not appear to be a valid uni; skipping...";
			} // done checking for valid uni

		} // done iterating through the list of users
		
		print "</div>";
		
    }	else {
    	// some field was missing 
    	print "<div id='message' class='updated fade'><p>A list of unis, the role, and a blog are all required. Please make a selection for each.</p></div>";
    }
} // end wind_addNewUsers()

############# OPTIONS PAGE FOR WIND PLUGIN ##############
// this adds the link to the settings page in the global admin menu
function wind_addSettingsPage() {
		if (function_exists('add_submenu_page')) {        
			add_submenu_page( 'ms-admin.php', 'WIND Login Settings', 'WIND Login Settings', 'manage_network_options', 'wind_wind_settings', 'wind_settingsPage');
		} 
} // end wind_addSettingsPage()

// this function processes the settings page when it's submitted
function wind_processUpdates() {
        if($_POST['wind_update_now']) {
			foreach ($_POST as $key => $item) {
				update_site_option($key,stripslashes($item));
			}
		echo "<div id='message' class='updated fade'><p>Your changes have been saved.</p></div>";
			
        }	
   
} //end wind_processUpdates()


// this function is necessary to get existing values of the various settings from the database
function wind_getSiteOptions() {
	$windOpt = array();
	$windOpt['wind_has_settings']			= get_site_option('wind_has_settings', 0);
	$windOpt['wind_super_admins']			= get_site_option('wind_super_admins', WIND_SUPER_ADMINS);
	$windOpt['wind_enable_plugin']				= get_site_option('wind_enable_plugin', WIND_ENABLE_PLUGIN);
		# extra var helps us know whether to check the box on the settings page
		$windOpt['wind_enable_plugin_checked'] = "";
		$windOpt['wind_enable_plugin_unchecked'] = "";
		if($windOpt['wind_enable_plugin']) {
			$windOpt['wind_enable_plugin_checked'] = "checked='checked'";
		} else {
			$windOpt['wind_enable_plugin_unchecked'] = "checked='checked'";
		}
	$windOpt['wind_help_email']				= get_site_option('wind_help_email', WIND_HELP_EMAIL);
	$windOpt['wind_check_course_affils']	= get_site_option('wind_check_course_affils', WIND_CHECK_COURSE_AFFILS);
		# extra var helps us know whether to check the box on the settings page
		$windOpt['wind_check_course_affils_checked'] = "";
		$windOpt['wind_check_course_affils_unchecked'] = "";
		if($windOpt['wind_check_course_affils']) {
			$windOpt['wind_check_course_affils_checked'] = "checked='checked'";
		} else {
			$windOpt['wind_check_course_affils_unchecked'] = "checked='checked'";
		}
	$windOpt['wind_nra_list_location']		= get_site_option('wind_nra_list_location', WIND_NRA_LIST_LOCATION);
	$windOpt['wind_log_file']				= get_site_option('wind_log_file', WIND_LOG_FILE);
	$windOpt['wind_service_name']			= get_site_option('wind_service_name', WIND_SERVICE_NAME);
	$windOpt['wind_server']					= get_site_option('wind_server', WIND_SERVER);
	$windOpt['wind_login_uri']				= get_site_option('wind_login_uri', WIND_LOGIN_URI);
	$windOpt['wind_logout_uri']				= get_site_option('wind_logout_uri', WIND_LOGOUT_URI);
	$windOpt['wind_validate_uri']			= get_site_option('wind_validate_uri', WIND_VALIDATE_URI);
	return $windOpt;
} // end wind_getSiteOptions()

// this function creates the page
function wind_settingsPage() {
	global $wpdb, $wp_rewrite;
	$wind_wp_path =  explode("wp-content/", dirname(__FILE__)); 
		
	add_contextual_help($current_screen, 
		'<p>' . __('Include contextual help here') . '</p>' .
		'<p><strong>' . __('For more information:') . '</strong></p>' .
		'<p>' . __('<a href="http://wordpress.org/support/">Support Forums</a>') . '</p>'
	);
	
	# process updates on post
	if ($_SERVER['REQUEST_METHOD'] == 'POST') wind_processUpdates();
	
	# now all the site options are available as variables
	extract(wind_getSiteOptions());
	
	# check for "updated" on get
	if (isset($_GET['updated'])) {
	?>
	<div id="message" class="updated"><p><?php _e( 'Options saved.' ) ?></p></div>
	<?php
		}
	?>
	<div class="wrap"> 
	<h2>WIND Settings Page</h2>
	
	<form method="post" id="wind_wind_settings">
	<?php wp_nonce_field( 'siteoptions' ); ?>
	
	
	<h3><?php _e( 'General Settings' ); ?></h3>
		<table class="form-table">
			<tr valign="top" id="windenableplugin">
				<th scope="row"><?php _e( 'Enable the plugin to allow WIND login?' ) ?></th>
				<td>
					<input type='radio' name='wind_enable_plugin' id='wind_enable_plugin_yes' value='1' 
						<?php echo $wind_enable_plugin_checked ?>/> <label for="wind_enable_plugin_yes">Yes</label>
					<input type='radio' name='wind_enable_plugin' id='wind_enable_plugin_no' value='0' 
						<?php echo $wind_enable_plugin_unchecked ?>/> <label for="wind_enable_plugin_no">No</label>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="wind_super_admins"><?php _e( 'Super Admins' ) ?></label></th>
				<td>
					<input name="wind_super_admins" type="text" id="wind_super_admins" class="large-text" 
						value="<?php echo $wind_super_admins; ?>" size="80" />
					<br />
					<?php _e( 'Enter a list of unis separated by spaces to grant users super admin status automatically when they\'re first provisioned.' ) ?>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="wind_help_email"><?php _e( 'Help email address' ) ?></label></th>
				<td>
					<input name="wind_help_email" type="text" id="wind_help_email" class="regular-text" 
						value="<?php echo $wind_help_email; ?>" />
					<br />
					<?php _e( 'If users encounter an error, they will be requested to email this address.' ) ?>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="wind_log_file"><?php _e( 'Log file location' ) ?></label></th>
				<td>
					<input name="wind_log_file" type="text" id="wind_log_file" class="large-text" 
						value="<?php echo $wind_log_file; ?>" size="80" />
					<br />
					<?php _e( 'Save a provisioning log.' ) ?>
				</td>
			</tr>
		</table>
	
	<h3><?php _e( 'Course Affiliations (Optional)' ); ?></h3>
		<table class="form-table">
			<tr valign="top" id="checkcourseaffils">
				<th scope="row"><?php _e( 'Check Course Affiliations?' ) ?></th>
				<td>
					<input type='radio' name='wind_check_course_affils' id='wind_check_course_affils_yes' value='1' 
						<?php echo $wind_check_course_affils_checked ?>/> <label for="wind_check_course_affils_yes">Yes</label>
					<input type='radio' name='wind_check_course_affils' id='wind_check_course_affils_no' value='0' 
						<?php echo $wind_check_course_affils_unchecked ?>/> <label for="wind_check_course_affils_no">No</label>

				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="wind_nra_list_location"><?php _e( 'NRA List Location' ) ?></label></th>
				<td>
					<input name="wind_nra_list_location" type="text" id="wind_nra_list_location" class="regular-text" 
						value="<?php echo $wind_nra_list_location; ?>" />
					<br />
					<?php _e( 'Location of the non-registered attendees file.' ) ?>
				</td>
			</tr>
		</table>
	
	<h3><?php _e( 'WIND Server Settings' ); ?></h3>
		<table class="form-table">
			<tr valign="top" id="wind_service_name">
				<th scope="row"><?php _e( 'WIND Service Name' ) ?></th>
				<td>
					<input name="wind_service_name" type="text" id="wind_service_name" class="regular-text" 
						value="<?php echo $wind_service_name; ?>" />
				</td>
			</tr>
			<tr valign="top" id="wind_server">
				<th scope="row"><?php _e( 'WIND Server' ) ?></th>
				<td>
					<input name="wind_server" type="text" id="wind_server" class="regular-text" 
						value="<?php echo $wind_server; ?>" />
				</td>
			</tr>
			<tr valign="top" id="wind_login_uri">
				<th scope="row"><?php _e( 'WIND Login URI' ) ?></th>
				<td>
					<input name="wind_login_uri" type="text" id="wind_login_uri" class="regular-text" 
						value="<?php echo $wind_login_uri; ?>" />
				</td>
			</tr>
			<tr valign="top" id="wind_logout_uri">
				<th scope="row"><?php _e( 'WIND Logout URI' ) ?></th>
				<td>
					<input name="wind_logout_uri" type="text" id="wind_logout_uri" class="regular-text" 
						value="<?php echo $wind_logout_uri; ?>" />
				</td>
			</tr>
			<tr valign="top" id="wind_validate_uri">
				<th scope="row"><?php _e( 'WIND Validate URI' ) ?></th>
				<td>
					<input name="wind_validate_uri" type="text" id="wind_validate_uri" class="regular-text" 
						value="<?php echo $wind_validate_uri; ?>" />
				</td>
			</tr>
		</table>	
	
	<input type="hidden" name="wind_has_settings" value=1 />
	<input type="hidden" name="wind_update_now" value=1 />
	<input type="hidden" name="action" value="update" />
	<p class="submit">
	<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
	</p>
	</form>
	</div>

	<?php 
} // end wind_settingsPage()

?>