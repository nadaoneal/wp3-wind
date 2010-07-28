<?php 
// wind_functions.php last updated 2010-07-20 by nco2104

##############################################################################################	
#	CONTENTS
#	1. Classes
#	2. WordPress Override Functions
#	3. External data functions
#	4. Per-blog functions
#	5. Helper functions
##############################################################################################


##############################################################################################	
#	CLASSES
#	the Affiliations class provides a convenient way to store all the data about a user
##############################################################################################

/**
 * class object to store all the affiliations data about a user
 */
class Affiliation {
	var $_windCourseNumber = "";
	var $_wpmuCourseNumber = "";
	var $_cworksCourseNumber = "";
	var $_nraCourseNumber = "";
	var $_uni = "";
	var $_role;
	var $_blog_id;
	var $_is_subscribed;
	
	// constructor
	function Affiliation($courseNumber, $uni, $role, $orig_type = "UNKNOWN") {
		$this->_uni = $uni;
		//holds "student" or "instructor" array here.
		$this->_role = $role;
		
		// get an array back with all the course numbers we've been passed
		#echo "course number was " . $courseNumber . "<br>";
		$courseNumbers = convert_course_number($courseNumber, $orig_type, $role);
		$this->_windCourseNumber = $courseNumbers['wind_num'];
		$this->_wpmuCourseNumber = $courseNumbers['wpmu_num'];
		$this->_cworksCourseNumber = $courseNumbers['cworks_num'];
		$this->_nraCourseNumber = $courseNumbers['nra_num'];
		$this->_role = $courseNumbers['role'];
		if( $courseNumbers['role_flag'] ) {
			//TODO : get role from instance variable and update cworks and wind course numbers	
		}
		$this->_blog_id = null;
		$this->_is_subscribed = null;
	}
	
	function set_blog_id($blog_id) {
		$this->_blog_id = $blog_id;
	}

	function set_subscribed($is_subscribed) {
		$this->_is_subscribed = $is_subscribed;
	}
	
	
	function toHTMLString() {
		return $this->_uni . " is " . $this->_role['generic_name'] . " for course:<br>"
							. $this->_cworksCourseNumber . " (cworks)<br>"
							. $this->_wpmuCourseNumber . " (wpmu)<br>"
							. $this->_windCourseNumber . " (wind)<br>"
							. $this->_nraCourseNumber . " (nra)<br>"; 
	}
	
}

##############################################################################################	
#	WORDPRESS OVERRIDE FUNCTIONS
#	Override WordPress login, logout, user creation, show passwords, etc
##############################################################################################


/**
 * adds logged-in user to any blogs they should be affiliated with but aren't already a member of
 * @param $user array from wordpress ($user->ID, $user->user_login)
 * @param $wind_affiliations array
 * @param $debug value (true to print more errors to log)
 * @return nothing
 */
function wind_add_to_blogs ($user, $wind_affiliations, $debug) {
	# now all the site options are available as variables
	extract(wind_getSiteOptions());
	global $wpdb, $base;
	$debug=false;
	// get three lists: affiliations, current subscribed blogs, and all blogs
	// TODO: cull these lists somehow? Just match blogs for this year?
	$affiliations_array = get_all_course_affils ($user, $wind_affiliations, $debug);
		$num_affils = sizeof($affiliations_array);	
	$mrUserID = $user->ID;	
	$user_current_blogs = get_blogs_of_user($mrUserID);
	
	// add base to the convert course number function instead
	#$all_blogs = $wpdb->get_results( "SELECT blog_id, replace(replace(replace( path, '$base', '' ), '/', ''), '-', '_') as path from wp_blogs where blog_id != 1 and public > -3" );
	$all_blogs = $wpdb->get_results( "SELECT blog_id, path from wp_blogs where blog_id != 1 and public > -3" );
	$blogless_affils = "";
	
	
	// iterate through affiliations. For each:
	// - is the user already subscribed to a blog? if so, note
	// - if not, does the blog exist? if so, subscribe user with correct role
	foreach ( $affiliations_array as $this_affil ) {
		$affilCourse = $this_affil->_wpmuCourseNumber;
		if (is_array($user_current_blogs)) {	
			foreach ($user_current_blogs as $user_blog) {
				// if this blog matches this affil...
				$blog_path = $user_blog->path;
				#error_log("Checking if current $blog_path matches affil $affilCourse\n", 3, $wind_log_file);
				if ( $blog_path == $affilCourse ) {
					#error_log("yes! current $blog_path matches affil $affilCourse\n", 3, $wind_log_file);
					$this_affil->set_subscribed(true);
					$this_affil->set_blog_id($user_blog->blog_id);
					break;
				}
			}
		}
		if ( $this_affil->_is_subscribed ) {
			// we're done 
			windlogger("{$this_affil->_uni} Already subscribed to {$this_affil->_wpmuCourseNumber}");
		} else {
			// does a matching blog exist?
			foreach ($all_blogs as $wpmu_blog) {
				$blog_path = $wpmu_blog->path;
				$blog_id = $wpmu_blog->blog_id;
				//error_log("blog: $blog_id . $blog_path \n", 3, $wind_log_file);
				//error_log("checking blog $blog_path against $affilCourse... \n", 3, $wind_log_file);
				if ( $blog_path == $affilCourse ) {
					$this_affil->set_subscribed(true);
					$this_affil->set_blog_id($blog_id);
					windlogger("Subscribing {$user->ID} to $blog_path, $blog_id as " . get_blog_role_option($wpmu_blog->blog_id, $this_affil->_role ) );

					# this doesn't have the desired effect
					$result = add_user_to_blog($blog_id, $user->ID, get_blog_role_option($wpmu_blog->blog_id, $this_affil->_role ));
					$tmp = get_blog_role_option($blog_id, $this_affil->_role );
					
					if ($debug) error_log("result was $result from blog $blog_id, {$user->ID}, $tmp\n", 3, $wind_log_file);
					break;
				}
			}
			// if we went through all the blogs and didn't find a match...
			if (is_null($this_affil->_is_subscribed)) {
				$this_affil->set_subscribed(false);
				if ($debug) error_log("No blog found for affil {$this_affil->_uni} to {$this_affil->_wpmuCourseNumber}\n", 3, $wind_log_file);
				$blogless_affils .= " {$this_affil->_cworksCourseNumber}";
			}
		}
	
	} // end foreach

	//update the usermeta setting for this user
	update_usermeta($user->ID,'blogless_affils',trim($blogless_affils));
	
}


/**
 * provisions new user account - does not add to any particular blog
 * @param $user_name
 * @return nothing
 */

function wind_create_wp_user( $user_name, $wind_affiliations ) {
	# now all the site options are available as variables
	extract(wind_getSiteOptions());
	require_once("wind_defaults.php");
	global $wpdb;
	$debug = false;

	// get_ldap_info returns 
	// array(first_name => $firstName, last_name => $lastName, email => $email, uni => $uni);
	error_log("getting ldap info for $user_name\n", 3, $wind_log_file);
	$ldap_user_data = get_ldap_information($user_name);
	$user_email = $ldap_user_data['email'];
	$random_password = substr( md5( uniqid( microtime( ))), 0, 20 );
	
	// create user
	$user_id = wpmu_create_user($user_name, $random_password, $user_email );
					/*	 for reference - other options
					$user_data = array(
						'ID' => $user_id,
						'user_login' => x,
						'user_nicename' => x,
						'first_name' => x,
						'last_name' => x,
						'nickname' => x,
						'display_name' => x,
						'user_email' => x,
						);
					*/		
	update_usermeta( $user_id, 'first_name', $ldap_user_data['first_name'] );
	update_usermeta( $user_id, 'last_name', $ldap_user_data['last_name'] );

	$superadmins = explode(" ", $wind_super_admins );
	
	if ( in_array($user_name, $superadmins) ) {
		error_log("$user_name is a super admin\n", 3, $wind_log_file);
		require_once(WIND_WP_PATH . "wp-admin/includes/ms.php");
		grant_super_admin( $user_id );
	} 
	
	$display_name = ($ldap_user_data['display_name'] ? $ldap_user_data['display_name'] : $ldap_user_data['nickname']);
	if (empty($display_name) & !empty($ldap_user_data['first_name'])) $display_name = $ldap_user_data['first_name'] . " " . $ldap_user_data['last_name'];
	
	if (!empty($display_name)) $wpdb->update( $wpdb->users, compact( 'display_name' ), array( 'ID' => $user_id ) );
	
	//This is for plugin events
	do_action('wpmu_activate_user', $user_id, $random_password, false);	

	error_log("In create user - wind check course affils is $wind_check_course_affils \n", 3, $wind_log_file);
	if ($wind_check_course_affils) {
							error_log("yes check course affils for {$result->user_login}\n", 3, $wind_log_file);
							wind_add_to_blogs( $result, $wind_affiliations, $debug );
	}
	
	
	
} // end function wind_create_wp_user()
	
function wind_authenticate () {
	# now all the site options are available as variables
	extract(wind_getSiteOptions());
	global $UNI;
	
	$wind_auth_status = 0;
	$debug = true;
	
	if ($debug) { echo "<br> In authenticate function... <br>"; }
	
	if($_GET["ticketid"]) {
			if ($debug) { echo "<br> got ticket id <br>"; }
		    // If they have a ticket, validate it against the wind server.
		    $validate = "https://$wind_server$wind_validate_uri?ticketid=" . $_GET["ticketid"];
		    if ($debug) echo "Checking your credentials....<br>";
	
		    // let's try wrapping wget!
		    $wget_args = " -q -O - $validate";
		    $wget_output = wrap_wget($wget_args);
		    //list($firstline, $secondline) = wrap_wget($wget_args);
			$wind_response = $wget_output[0];
			$UNI = $wget_output[1];
			windlogger("$UNI logging in");
			
			if ( $wind_response == "yes" ) {
			// valid ticket
			$wind_auth_status = 1;
			#echo "UNI is $UNI";
			if ($debug) echo "Your password is valid for $UNI...<br>";
			} else {
			// not valid ticket
			if ($debug) echo "Need to log in, please wait...";
			$wind_auth_status = 0;
			}
				
		    } else {
		    // no ticket
		    if ($debug) echo "Sending you to Columbia WIND authentication...<br>";
		    $wind_auth_status = 0;
		    }
		    // done validating ticket against WIND server
	
	
		if ( $wind_auth_status ) {
			$wind_affiliations = array_slice($wget_output, 2, (sizeof($wget_output)-2), false);
			if ($debug) {
				foreach ($wind_affiliations as $mrAffil) {
					windlogger("$UNI: Affil is $mrAffil");
				}
			}
			if ( $user = get_userdatabylogin($UNI) ) { // user already exists
				if ($debug) { windlogger("user $UNI already exists ");}
				// the CAS user has a WP account
				if ($debug) echo "Finding your WordPress account... <br>";
					$result = wp_set_auth_cookie( $user->ID );
					if ($debug && $wind_check_course_affils) { windlogger("$UNI yes check course affils");}
					if ($wind_check_course_affils) wind_add_to_blogs( $user, $wind_affiliations, $debug );
					wp_redirect( site_url( '/wp-admin/' ));
					die();
				} else {
				// the CAS user does not have a WP account
				if ($debug) { error_log("user $UNI does not exist\n", 3, $wind_log_file);}
				if ($debug) echo "Making you a new WordPress account... <br>";
					if (function_exists( 'wind_create_wp_user' )) {
						if ($debug) { error_log("provisioning account $UNI\n", 3, $wind_log_file);}
						wind_create_wp_user( $UNI, $wind_affiliations );
						wp_redirect( site_url( '/wp-admin/' ));
					} else {
						echo "Sorry, your account isn't provisioned. Please contact <a href='mailto:$wind_help_email'>$wind_help_email</a>.<br>";
						die();
					}
				}
	
		} else {
		// better authenticate, then!
		  	if($_SERVER["SERVER_PORT"] == 443) {
		  	  $server_protocol = "https";
		  	} else {
		  	  $server_protocol = "http";
		  	}
		
		  	// this causes "too many redirects" problem on some LAMP instances
			//if ( isset($_GET['redirect_to'])) {
		 	//	$my_redir_to = "&redirect_to=" . $_GET['redirect_to'];
			//} else {
			//	$my_redir_to = "";
			//}
		
			#$destination = $server_protocol . "://" . $_SERVER["SERVER_NAME"] . ":"  . $_SERVER["SERVER_PORT"] . $_SERVER["PHP_SELF"];
			#$destination = $server_protocol . "://" . $_SERVER["SERVER_NAME"] . $_SERVER["PHP_SELF"];
			$path = (isset($_SERVER['REDIRECT_URL'])) ? $_SERVER['REDIRECT_URL'] : $_SERVER["PHP_SELF"];
			$destination = $server_protocol . "://" . $_SERVER["SERVER_NAME"] . $path;
		        $login_link = "https://" . $wind_server . $wind_login_uri . "?service=" . $wind_service_name  . "&destination=" . urlencode($destination);
		
			
			echo "Login redirect...<br> You should be redirected within 5 seconds. Otherwise, <A href='$login_link'>click here</a>.";
			echo "<meta http-equiv='refresh' content='0;url=$login_link'>";
			die();
		}
	
	
} // end function authenticate


// function logout
function wind_logout() {
	# now all the site options are available as variables
	extract(wind_getSiteOptions());
	
	if ($_SERVER["SERVER_PORT"] == 443) {
  	 	  $server_protocol = "https";
  	} else {
  	  	  $server_protocol = "http";
  	}
		
  	$destination = $server_protocol . "://" . $_SERVER["SERVER_NAME"] . $_SERVER["PHP_SELF"];
    $logout_link = "https://" . $wind_server . $wind_logout_uri . "?service=" . $wind_service_name  . "&destination=" . urlencode($destination) . "&destinationtext=Log+In+Again";
	wp_redirect($logout_link);
	exit();
	
}
// end function logout



// function show password fields
	// hide password fields on user profile page.
function wind_show_password_fields( $show_password_fields ) {
	if( 'user-new.php' <> basename( $_SERVER['PHP_SELF'] ))
		return false;

		$random_password = substr( md5( uniqid( microtime( ))), 0, 20 );

?>
<input name="pass1" type="hidden" id="pass1" value="<?php echo $random_password ?>" />
<input name="pass2" type="hidden" id="pass2" value="<?php echo $random_password ?>" />
<?php
		return false;
	}

//end show password fields function

##############################################################################################	
#	EXTERNAL DATA FUNCTIONS
#	gets data about the user from either the NRA list or LDAP
##############################################################################################

/**
 * gets firstname, lastname, email from ldap by uni
 * @param $uni
 * @return associative array with firstname, lastname, email, uni - names are blank, email guessed, on ldap fail
 */
function get_ldap_information($uni) {
	// initialize return values
	$firstName = "";
	$lastName = "";
	$email = "";
	
	// connect to ldap locally
	$ds=ldap_connect("ldap.columbia.edu"); 
	if ($ds) { 
		$r=ldap_bind($ds);     // this is an "anonymous" bind
		$sr=ldap_search($ds, "ou=People,o=Columbia University,c=us", ("uni=" . $uni));
		$info = ldap_get_entries($ds, $sr);
		// check that we received exactly one entry
		if ($info["count"] == 1) {
			$email = $info[0]["mail"][0];
			if (is_null($email)) {
				$email = $uni . "@columbia.edu";
			}
			// note: "should" be "givenName", but appears to be case-senstive and all lower case
			$firstName = $info[0]["givenname"][0];
			$lastName = $info[0]["sn"][0];
			windlogger("$uni email is $email; $firstName $lastName");
		} else {
			$resCount = $info["count"];
			windlogger("Wrong number of LDAP results returned for $uni - $resCount results (may be 0 due to FERPA)");
			$email = $uni . "@columbia.edu";
		}
	} else {
		// ldap connection failed
		windlogger("Could not connect to LDAP for $uni");
		$email = $uni . "@columbia.edu";
	}
	#return array of values
	return array(first_name => $firstName, last_name => $lastName, email => $email, uni => $uni);
}

/**
 * gets affiliations from NRA list
 * @param $uni
 * @param $NRA_LIST_LOCATION
 * @return array of Affiliation objects
 */
function get_nra_affiliations($uni, $nra_list_location) {
	$cmd = "grep $uni $nra_list_location";
	// put each line into an array
	$output = explode("\n", shell_exec($cmd));
	$affiliations_array = array();
	foreach ($output as $line) {
		$tmpArray = parseCsvLine($line);
		//check that there's a course listed - this ensures a full line
		if (isset($tmpArray[2])) {
			//create a new Affiliation object and add to array
			// function Affiliation($courseNumber, $uni, $affilType, $orig_type = "UNKNOWN") 
			$affiliations_array[] = new Affiliation($tmpArray[2],$tmpArray[0],find_role($tmpArray[1]),"NRA");
		}
	}
	return $affiliations_array;
}
	

/**
 * gets all course affiliations - used by wind_add_to_blogs
 * @param $user
 * @param $wind_affiliations
 * @param $debug
 * @return unknown_type
 */
function get_all_course_affils ($user, $wind_affiliations, $debug) {
	extract(wind_getSiteOptions());
	$debug = false;
	$affiliations_array = array();
	
	// get NRA affiliations
	array_splice( $affiliations_array, 0, 0, get_nra_affiliations($user->user_login, $wind_nra_list_location) );
	
	// add in WIND affiliations
	foreach ($wind_affiliations as $affilStr) {
		$affiliations_array[] = new Affiliation($affilStr, $user->user_login, get_role_from_course_num, 'WIND');
	}
	
	if ( $debug ) {
		foreach ($affiliations_array as $newAffil) {
			error_log("{$newAffil->_uni} is {$newAffil->_role['generic_name']} on {$newAffil->_wpmuCourseNumber}\n", 3, $wind_log_file);
		}
	}
	
	return $affiliations_array;
}

	
##############################################################################################
# 	PER-BLOG FUNCTIONS
#	To get the WPMU role defined for this kind of user
##############################################################################################

/**
 * gets the WPMU role (editor, author, etc) for the Columbia role passed through
 * @param $blog_id - pass "0" to default
 * @param (instructor_role, student_role)
 * @return setting (string)
 */
function get_blog_role_option($blog_id, $this_role ) {
	$setting = $this_role['wpmu_setting_default'];
	if (function_exists('get_blog_option'))  {
		$custom_setting = get_blog_option($blog_id, $this_role['wpmu_setting_name']);
		if ($custom_setting) {
			$setting = $custom_setting;
		}
	}
	return $setting;
}

/**
 * sets the Columbia -> WPMU role mapping for this blog
 * @param $blog_id
 * @param $setting_name - CU role, e.g. instructor_role, student_role
 * @param $setting_value - WPMU role, e.g. editor, author
 * @return true if no change made, null if change made
 */
function wind_add_role_option($blog_id, $setting_name, $setting_value) {
	if (function_exists('add_blog_option')) {
		if ($custom_setting = get_blog_option($blog_id, $setting_name)) {
			return true;
		} else {
			return add_blog_option($blog_id, $setting_name, $setting_value);
		}
	}
}
	


##############################################################################################
#   HELPER FUNCTIONS
##############################################################################################

/**
 * windlogger() sends to the wind log file
 */
function windlogger($message) {
	extract(wind_getSiteOptions());
	$timestamp = date("m/d/y H:i:s");
	error_log("$message at $timestamp\n", 3, $wind_log_file);
}


/**
 * if $args matches a value in $ROLES['instructor'], returns $ROLES['instructor']
 * otherwise returns $ROLES['student']
 * @param String $args
 */
function find_role ($args) {
	global $ROLES;
	if ( in_array( $args, array_values($ROLES['instructor']) )) {
		// $args matches one of the values in $INSTRUCTOR
		return $ROLES['instructor'];
	} else {
		return $ROLES['student'];
	}
}	
	
	

	
// returns 'false' to hide some functions	
function wind_return_false() { return false; }	

/**
 * legacy function - wraps wget and returns output array. provides extremely basic error handling
 * @param $args
 * @return array of wget ouput
 */

function wrap_wget($args){

	//return array("yes","nco2104");
	
  exec("wget $args", $output_array, $x);
  
  if($x != 0) {
    echo("<br>wget returned $x, args = $args <br>");
    //print_r($output_array);
    return(0);
  }

  //return( array($output_array[0], $output_array[1], $output_array[2]));
  return $output_array;
}

/**
 * parses csv string into a nice array, taking into account delimiters and qualifiers
 * @author unknown....
 * @param $str
 * @return unknown_type
 */

function parseCsvLine($str) {
        $delimiter = ',';
        $qualifier = "'";
        $qualifierEscape = '\\';

        $fields = array();
        while (strlen($str) > 0) {
            if ($str{0} == $delimiter)
                $str = substr($str, 1);
            if ($str{0} == $qualifier) {
                $value = '';
                for ($i = 1; $i < strlen($str); $i++) {
                    if (($str{$i} == $qualifier) && ($str{$i-1} != $qualifierEscape)) {
                        $str = substr($str, (strlen($value) + 2));
                        $value = str_replace(($qualifierEscape.$qualifier), $qualifier, $value);
                        break;
                    }
                    $value .= $str{$i};
                }
            } else {
                $end = strpos($str, $delimiter);
                $value = ($end !== false) ? substr($str, 0, $end) : $str;
                $str = substr($str, strlen($value));
            }
            $fields[] = $value;
        }
        return $fields;
}


/**
 * Converts course number of any type into array of all types
 * Note - bug - if you pass in an NRA or WPMU course number but no role type, the function will assume the role is "student"
 * if it does this, it will set "role_flag" in the output array to "true"
 * if this can't determine the type, will return null
 * @param $course_num_orig
 * @param $orig_type
 * @param $orig_role
 * @return associative array of converted course numbers, or null
 */

function convert_course_number($course_num_orig, $orig_type = "UNKNOWN", $orig_role = "UNKNOWN") {	
	// if we don't know the course number type, determine it...
	$debug=false;
	global $ROLES, $base;
	// "role_flag" is used to indicate that there was weird guessing about the affil's role, student/instructor
	$role_flag = false;
	if ($debug) error_log("orig course was $course_num_orig \n", 3, $wind_log_file);
	if ($debug) error_log("orig type was $orig_type \n", 3, $wind_log_file);
	if ($orig_type == "UNKNOWN") {
		// wind and wpmu have only lowercase letters while
		// courseworks and NRA have (mostly) uppercase letters
		if (ereg('[A-Z]', $course_num_orig)) {
			// then is either courseworks or NRA
			if ((substr_count($course_num_orig, "_") == 4) && 
					(ereg($ROLES['student']['cworks_prefix'], $course_num_orig) || 
					 ereg($ROLES['instructor']['cworks_prefix'], $course_num_orig) ) ) {
				// has underscores and matches the cworks prefix, is cworks
				$orig_type = "CWORKS";
			} else if (substr_count($course_num_orig, "_") == 3 ) {
				// has three underscores, is NRA
				$orig_type = "NRA";
			} 
			// if neither matches, keep "unknown"
		} else {
			// then is either wind or wpmu
			if (substr_count($course_num_orig, ".") > 6 ) {
				// has dots, is wind
				$orig_type = "WIND";
			} else if (substr_count($course_num_orig, "-") == 3 ) {
				// has dashes, is wpmu
				$orig_type = "WPMU";
			} 
			// if neither matches, keep "unknown"
		}
	if ($debug) error_log("orig type is $orig_type \n", 3, $wind_log_file);
		
	}

	switch ($orig_type) {
		case 'CWORKS':
			// number will look like CUcourse_CIENE4010_001_2009_1
			// e.g. XX(role)_(dept)(letter)(number)_(section)_(year)_(term)
			$cworks_pieces = split("_", $course_num_orig);
			$cRole = find_role($cworks_pieces[0]);
			$cDept = substr($cworks_pieces[1], 0, 4);
			$cLetter = substr($cworks_pieces[1], 4, 1);
			$cNumber = substr($cworks_pieces[1], 5, 4);
			$cSection = $cworks_pieces[2];
			$cYear = $cworks_pieces[3];
			$cTerm = $cworks_pieces[4];		 
			break;
			
		case 'WIND':
			// number will look like t1.y2009.s001.e4010.cien.st.course.columbia:edu
			// e.g. t(term).y(year).s(section).c(letter)(number).(dept).(role).course.columbia:edu
			// this is the only one that's lower case, so change to upper 
			$wind_pieces = split('[.]', $course_num_orig);
			$cTerm = substr($wind_pieces[0], 1, 1);
			$cYear = substr($wind_pieces[1], 1, 4);
			$cSection = substr($wind_pieces[2], 1, 3);
			$cLetter = strtoupper(substr($wind_pieces[3], 1, 1));
			$cNumber = substr($wind_pieces[3], 2, 4);
			$cDept = strtoupper($wind_pieces[4]);
			$cRole = find_role($wind_pieces[5]);
			break;
			
		case 'NRA':
			// number will look like INAFU4623_001_2009_2
			// e.g. (dept)(letter - maybe)(number) _(section)_(year)_(term)
			// WARNING: does not include role!!
			$nra_pieces = split("_", $course_num_orig);
			if ($orig_role != "UNKNOWN") {
				$cRole = $orig_role;
			} else {
				// TODO BUG: Assumes role is "student" and flags if no role was passed in
				$cRole = $ROLES['student'];
				$role_flag = 'true';
			};
			$cDept = substr($nra_pieces[0], 0, 4);
			$tmp = substr($nra_pieces[0], 4);
			if ( strlen($tmp) == 5 ) {
				$cLetter = substr($tmp, 0, 1);
				$cNumber = substr($tmp, 1, 4);
			} else {
				$cLetter = "";
				$cNumber = substr($tmp, 0, 4);
			}
			$cSection = $nra_pieces[1];
			$cYear = $nra_pieces[2];
			$cTerm = $nra_pieces[3];		 
			break;	
			
			case 'WPMU':
			if ($debug) error_log("$course_num_orig is WPMU-type \n", 3, $wind_log_file);
			$clean_path = str_replace( $base, '', $course_num_orig);
			$clean_path = str_replace( '/', '', $clean_path);	
			if ($debug) error_log("$course_num_orig cleaned to $clean_path \n", 3, $wind_log_file);
			$wpmu_pieces = split("-", $clean_path);
			if ($orig_role != "UNKNOWN") {
				$cRole = $orig_role;
			} else {
				// TODO BUG: Assumes role is "student" and flags if no role was passed in
				$cRole = $ROLES['student'];
				$role_flag = 'true';
			};
			$cDept = strtoupper(substr($wpmu_pieces[0], 0, 4));
			$tmp = substr($wpmu_pieces[0], 4);
			if ( strlen($tmp) == 5 ) {
				$cLetter = strtoupper(substr($tmp, 0, 1));
				$cNumber = substr($tmp, 1, 4);
			} else {
				$cLetter = "";
				$cNumber = substr($tmp, 0, 4);
			}
			$cSection = $wpmu_pieces[1];
			$cYear = $wpmu_pieces[2];
			$cTerm = $wpmu_pieces[3];
			break;
			
			
		default:
			// return null if unknown or non-valid type passed
			if ($debug) error_log("hit default! \n", 3, $wind_log_file);
			return null;
			break;
	
	}
	
	// now generate new course numbers and return an array of: (cworks, wind, wpmu, role_not_known)
	$cworks_num = $cRole['cworks_prefix'] . "_" . $cDept . $cLetter . $cNumber . 
									"_" . $cSection . "_" . $cYear . "_" . $cTerm;
	if ($debug) echo "<br>cworks num is " . $cworks_num . "<br>";
	// t1.y2009.s001.e4010.cien.st.course:columbia.edu

	$wind_num = "t" . $cTerm . ".y" . $cYear . ".s" . $cSection . ".c" . strtolower($cLetter) . $cNumber 
							. "." . strtolower($cDept) . "." . $cRole['wind'] . ".course:columbia.edu";
	if ($debug) error_log("wind num is $wind_num \n", 3, $wind_log_file);
							
	$wpmu_num = $base . strtolower($cDept. $cLetter) . $cNumber . "-" . $cSection . "-" . $cYear . "-" . $cTerm . "/"; 
	if ($debug) error_log("wpmu num is $wpmu_num \n", 3, $wind_log_file);
	
	$nra_num = $cDept . $cLetter . $cNumber . "_" . $cSection . "_" . $cYear . "_" . $cTerm;
	if ($debug) error_log("nra num is $nra_num \n", 3, $wind_log_file);
	
	if ($debug) error_log("role flag is $role_flag \n", 3, $wind_log_file);
	$coursenumbers = array('cworks_num' => $cworks_num, 'wind_num' => $wind_num, 'wpmu_num' => $wpmu_num, 
							'nra_num'=>$nra_num, 'role'=> $cRole, 'role_flag' => $role_flag);
	return ($coursenumbers);
	
	
}

?>