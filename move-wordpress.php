<?php
/*
 This tools allow to move a WordPress Installation.

 For move a WordPress standalone, you also can use this SQL generator, but serialized data remains in the state.
 http://farinspace.github.com/wp-migrate-gen/
 
 Place this file into master folder of WordPress
 
 Usage :
	CLI : 				php5-cli -f move-wordpress.php old-domain.com new-domain.com /old-path/ /new-path/
	Web Params : 		http://old-domain.com/move-wordpress.php?old_domain=old-domain.com&new_domain=new-domain.com&old_path=/old_path/&new_path=/new_path/
	Hardcoded values : 	http://old-domain.com/move-wordpress.php
 */

define('HARDCODED_OLD_DOMAIN', 'network2.lan');
define('HARDCODED_NEW_DOMAIN', 'localhost');
define('HARDCODED_OLD_PATH', '');
define('HARDCODED_NEW_PATH', '/wordpress');
 
 /* That's all, stop editing! Next section is for advanced user !. */
 
 // WP Configuration
define('WP_INSTALLING', true);
define('WP_CACHE', false);

// PHP Configuration
@error_reporting(E_ALL);
@ini_set('display_startup_errors', '1');
@ini_set('display_errors', '1');
@ini_set('memory_limit', '512M');
@ini_set('max_execution_time', -1);
if ( function_exists('ignore_user_abort') ) ignore_user_abort(1);
if ( function_exists('set_time_limit') ) set_time_limit(0);

// CLIT OR Web ?
if ( defined('STDIN') ) {
	
	echo("Running from CLI\n");
	
	// Get first arg
	if ( !isset($argv) || count($argv) < 3 ) {
		die('Missing args for CLI usage');
	}
	
	// Domain
	$old_domain = ( isset($argv[1]) ) ? $argv[1] : '';
	$new_domain = ( isset($argv[2]) ) ? $argv[2] : '';
	
	// Path
	$old_path = ( isset($argv[3]) ) ? $argv[3] : '';
	$new_path = ( isset($argv[4]) ) ? $argv[4] : '';
	
	// Fake WordPress, build server array
	$_SERVER = array(
		'HTTP_HOST'      => $old_domain,
		'SERVER_NAME'    => $old_domain,
		'REQUEST_URI'    => $old_path.basename(__FILE__),
		'REQUEST_METHOD' => 'GET',
		'SCRIPT_NAME' 	 => basename(__FILE__),
		'SCRIPT_FILENAME' 	 => basename(__FILE__),
		'PHP_SELF' 		 => $old_path.basename(__FILE__)
	);
	
} elseif ( isset($_GET['old_domain']) || isset($_GET['new_domain']) || isset($_GET['old_path']) || isset($_GET['new_path']) ) {
	
	echo("Running from GET values\n");
	
	// Domain
	$old_domain = ( isset($_GET['old_domain']) ) ? stripslashes(urldecode($_GET['old_domain'])) : '';
	$new_domain = ( isset($_GET['new_domain']) ) ? stripslashes(urldecode($_GET['new_domain'])) : '';
	
	// Path
	$old_path = ( isset($_GET['old_path']) ) ? stripslashes(urldecode($_GET['old_path'])) : '';
	$new_path = ( isset($_GET['new_path']) ) ? stripslashes(urldecode($_GET['new_path'])) : '';
	
} else {
	
	echo("Running from hardcoded values\n");
	
	// Domain
	$old_domain = HARDCODED_OLD_DOMAIN;
	$new_domain = HARDCODED_NEW_DOMAIN;
	
	// Path
	$old_path = HARDCODED_OLD_PATH;
	$new_path = HARDCODED_NEW_PATH;
	
}

function hardFlush() { 
	// Like said in PHP description above, some version of IE (7.0 for example) 
	// will not 'update' the page if less then 256 bytes are received 
	// Send 250 characters extra 
	echo '                                                  '; 
	echo '                                                  '; 
	echo '                                                  '; 
	echo '                                                  '; 
	echo '                                                  '; 
	flush(); 
	ob_flush(); 
}

// Custom error handler
function handleError($errno, $errstr, $errfile, $errline, array $errcontext){
	// error was suppressed with the @-operator
	if (!(error_reporting() & $errno)) {
		// Ce code d'erreur n'est pas inclus dans error_reporting()
		return;
	}
	
	throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}
set_error_handler('handleError');

// Start buffer
@ob_start();

// Try to load WordPress !
try {
	hardFlush();
	require (dirname(__FILE__) . '/wp-load.php');
} catch (ErrorException $e) {
	//var_dump($e->getMessage()); // Debug
	if ( strpos( $e->getMessage(), 'headers' ) !== false )
		die('Setting up your configuration file seems incomplete because WordPress is trying to do an HTTP redirect.');
}

 /* That's all, stop editing! Next section is for VERY advanced user !. */
class Move_WordPress {
	// Temp variable for rename usage
	private $_old_website_url = '';
	private $_new_website_url = '';
	
	/**
	 * Constructor, make the classic queries for installation and each website!
	 */
	function __construct( $old_domain = '', $new_domain = '', $old_path = '/', $new_path = '/' ) {
		global $wpdb;
		
		if ( empty($old_domain) || empty($new_domain) ) {// Values missing ?
			die('Missing old or new domain');
		}

		if ( $old_domain == $new_domain && $old_path == $new_path ) {// The same domain and same path ?
			die('Old and new domain/path are the same');
		}

		// Queries with path
		$this->_old_website_url = $old_domain . $old_path;
		$this->_new_website_url = $new_domain . $new_path;

		// Unserialized datas
		$this->genericReplace();

		// Serialized options
		$this->tableOptionsAdvancedReplace();

		// Serialized metas
		$this->tableMetaAdvancedReplace( 'comment' );
		$this->tableMetaAdvancedReplace( 'post' );
		$this->tableMetaAdvancedReplace( 'user' );
		
		echo 'OK, don\'t forget to edit the configuration file of WordPress with the new domain !';
		exit();
	}

	/**
	 * Classic SQL queries for replace old URL with new URL
	 */
	function genericReplace() {
		global $wpdb;
		
		// Classic queries
		$wpdb->query("UPDATE `{$wpdb->options}` SET `option_value` = REPLACE(`option_value`, '" . $this->_old_website_url . "', '" . $this->_new_website_url . "') WHERE `option_value` NOT REGEXP '^([adObis]:|N;)';");
		$wpdb->query("UPDATE `{$wpdb->posts}` SET `guid` = REPLACE(`guid`, '" . $this->_old_website_url . "', '" . $this->_new_website_url . "');");
		$wpdb->query("UPDATE `{$wpdb->posts}` SET `post_content` = REPLACE(`post_content`, '" . $this->_old_website_url . "', '" . $this->_new_website_url . "');");
		$wpdb->query("UPDATE `{$wpdb->comments}` SET `comment_author_url` = REPLACE(`comment_author_url`, '" . $this->_old_website_url . "', '" . $this->_new_website_url . "');");
		$wpdb->query("UPDATE `{$wpdb->comments}` SET `comment_content` = REPLACE(`comment_content`, '" . $this->_old_website_url . "', '" . $this->_new_website_url . "');");
		$wpdb->query("UPDATE `{$wpdb->links}` SET `link_url` = REPLACE(`link_url`, '" . $this->_old_website_url . "', '" . $this->_new_website_url . "');");
		$wpdb->query("UPDATE `{$wpdb->postmeta}` SET `meta_value` = REPLACE(`meta_value`, '" . $this->_old_website_url . "', '" . $this->_new_website_url . "') WHERE `meta_value` NOT REGEXP '^([adObis]:|N;)';");
		$wpdb->query("UPDATE `{$wpdb->commentmeta}` SET `meta_value` = REPLACE(`meta_value`, '" . $this->_old_website_url . "', '" . $this->_new_website_url . "') WHERE `meta_value` NOT REGEXP '^([adObis]:|N;)';");
		$wpdb->query("UPDATE `{$wpdb->usermeta}` SET `meta_value` = REPLACE(`meta_value`, '" . $this->_old_website_url . "', '" . $this->_new_website_url . "') WHERE `meta_value` NOT REGEXP '^([adObis]:|N;)';");
	}

	/**
	 * A special method for replace old URL with new URL with manage serialization datas
	 * Skip 2 options : user_roles and permalinks !
	 * 
	 */
	function tableOptionsAdvancedReplace() {
		global $wpdb;

		// Options
		$options = $wpdb->get_results("SELECT * 
			FROM `{$wpdb->options}`
			WHERE option_name NOT LIKE '\_%' 
			AND option_name NOT LIKE '%user_roles'
			AND option_name != 'permalink_structure'
			AND option_value REGEXP '^([adObis]:|N;)'
		");

		if ( $options == false || !is_array($options) ) {
			return false;
		}
		
		foreach ($options as $option) {
			if (is_serialized($option->option_value)) {
				if (is_serialized_string($option->option_value)) {
					$option_value = maybe_unserialize($option->option_value);
					$new_value = str_replace($this->_old_website_url, $this->_new_website_url, $option_value);
					if ($new_value != $option_value) {
						update_option($option->option_name, maybe_serialize($new_value));
					}
				} else {// A real array to map ?
					$option_value = maybe_unserialize($option->option_value);
					if (is_array($option_value)) {
						$new_value = $this->arrayMap(array(&$this, 'callback'), $option_value);
						if ($new_value != $option_value) {
							update_option($option->option_name, $new_value);
						}
					}
				}
			} else {// String
				$new_value = str_replace($this->_old_website_url, $this->_new_website_url, $option->option_value);
				if ($new_value != $option->option_value) {
					update_option($option->option_name, $new_value);
				}
			}
		}
	}

	/**
	 * A special method for replace old URL with new URL with manage serialization datas on any meta tables :)
	 * 
	 */
	function tableMetaAdvancedReplace( $meta_type = '' ) {
		global $wpdb;

		if ( ! $table = _get_meta_table($meta_type) )
			return false;

		// Meta table
		$metas = $wpdb->get_results("SELECT * 
			FROM `{$table}`
			WHERE 1 = 1
			AND meta_key REGEXP '^([adObis]:|N;)'
		");

		if ( $metas == false || !is_array($metas) ) {
			return false;
		}

		$column_obj_id = esc_sql($meta_type . '_id');
		
		foreach ($metas as $meta) {
			if (is_serialized($meta->meta_value)) {
				if (is_serialized_string($meta->meta_value)) {
					$meta_value = maybe_unserialize($meta->meta_value);
					$new_value = str_replace($this->_old_website_url, $this->_new_website_url, $meta_value);
					if ($new_value != $meta_value) {
						update_metadata( $meta_type, $meta->$column_obj_id, $meta->meta_key, maybe_serialize($new_value) );
					}
				} else {// A real array to map ?
					$meta_value = maybe_unserialize($meta->meta_value);
					if (is_array($meta_value)) {
						$new_value = $this->arrayMap(array(&$this, 'callback'), $meta_value);
						if ($new_value != $meta_value) {+
							update_metadata( $meta_type, $meta->$column_obj_id, $meta->meta_key, $new_value );
						}
					}
				}
			} else {// String
				$new_value = str_replace($this->_old_website_url, $this->_new_website_url, $meta->meta_value);
				if ($new_value != $meta->meta_value) {
					update_metadata( $meta_type, $meta->$column_obj_id, $meta->meta_key, $new_value );
				}
			}
		}

		return true;
	}

	/**
	 * Callback for replace
	 *
	 * @param string $value
	 * @return void
	 * @author Amaury Balmer
	 */
	function callback($value) {
		if ( is_string($value) )
			return str_replace($this->_old_website_url, $this->_new_website_url, $value);
		
		return $value;
	}

	/**
	 * arrayMap function. Customized array_map function which preserves keys/associate array indexes. Note that this costs a descent amount more memory (eg. 1.5k per call)
	 *
	 * @access public
	 * @param callback $callback Callback function to run for each element in each array.
	 * @param mixed $arr1 An array to run through the callback function.
	 * @param array $array Variable list of array arugments to run through the callback function.
	 * @return array Array containing all the elements of $arr1 after applying the callback function to each one, recursively, maintain keys.
	 */
	function arrayMap($callback, $arr1) {
		$results = array();
		$args = array();
		if (func_num_args() > 2)
			$args = (array) array_shift(array_slice(func_get_args(), 2));

		foreach ($arr1 as $key => $value) {
			$temp = $args;
			array_unshift($temp, $value);
			if (is_array($value)) {
				array_unshift($temp, $callback);
				$results[$key] = call_user_func_array(array('self', 'arrayMap'), $temp);
			} else {
				$results[$key] = call_user_func_array($callback, $temp);
			}
		}

		return $results;
	}
}

new Move_WordPress( $old_domain, $new_domain, $old_path, $new_path );
?>