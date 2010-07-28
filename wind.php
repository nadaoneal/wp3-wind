<?php
/*
Plugin Name: Columbia WIND Logins
Plugin URI: http://wiki.cul.columbia.edu/
Description: Allows WIND login to WP 3.0
Version: 1.1
Author: Nada O'Neal
License: Apache 2 (http://www.apache.org/licenses/LICENSE-2.0.html)
*/

require_once("wind_plugin/wind_defaults.php");
require_once("wind_plugin/wind_functions.php");
require_once("wind_plugin/wind_pages.php");

date_default_timezone_set ("America/New_York");

// add links to "Super Admin Menu" for provisioning users and changing wind settings
add_action('admin_menu', 'wind_addProvisionUsersPage');
add_action('admin_menu', 'wind_addSettingsPage');

# get all the currently stored wind site options
extract(wind_getSiteOptions());

// get the base directory for the wp install; used various places
$wind_wp_path_pieces = explode("wp-content/", dirname(__FILE__)); 
$WIND_WP_PATH = $wind_wp_path_pieces[0];
#trigger_error("Abs path is $WIND_WP_PATH", E_USER_WARNING);


// plugin hooks into authentication system
if ($wind_enable_plugin) {
	add_action('wp_authenticate', 'wind_authenticate', 10, 2);
	add_action('wp_logout', 'wind_logout');
	add_action('lost_password', 'disable_function');
	add_action('retrieve_password', 'disable_function');
	add_action('password_reset', 'disable_function');
	add_filter('show_password_fields', 'wind_show_password_fields');
	add_filter('wpmu_welcome_notification', 'wp_new_user_notification');
}

// suppress the welcome email
function wp_new_user_notification( $user_id, $plaintext_pass ) {
        return 0;
}

# include registration.php
require_once( $WIND_WP_PATH . '/wp-includes/registration.php');

# sets the roles for student, instructor
$STUDENT = array('cworks_prefix' => 'CUcourse', 'nra_nra' => 'NRA', 'nra_student' => 'NRAS', 'nra_aud' => 'AUD', 'wind' => 'st', 
					'wpmu_setting_name'=> 'student_role', 'wpmu_setting_default' => 'author', 'generic_name' => 'a student');
$INSTRUCTOR = array('cworks_prefix' => 'CUinstr', 'nra_ta' => 'TA', 'nra_instructor' => 'NRAI', 'wind' => 'fc', 
					'wpmu_setting_name' => 'instructor_role', 'wpmu_setting_default' => 'editor', 'generic_name' => 'an instructor');
$ROLES = array( 'student'=> $STUDENT, 'instructor'=> $INSTRUCTOR );


?>