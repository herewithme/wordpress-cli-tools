<?php
/*
 This tools allow to move a WordPress Multisite Installation.

 If you want move a WordPress standalone, use the lighter script :
 http://farinspace.github.com/wp-migrate-gen/
 */

// CLIT OR Web ?
/*
if ( defined('STDIN') ) {
	
	echo("Running from CLI");
	$old_domain = '';
	$new_domain = '';
	
} else {
	
	echo("Not Running from CLI");
	$old_domain = 'old-domain.com';
	$new_domain = 'new-domain.com';
	
}
*/

// PHP Constants
// define('SUNRISE', 'on');
// define('MOVE_WP_MS', true);
// define('SHORTINIT', true); // No used here...

define('DOMAIN_CURRENT_SITE', 'old-domain.com');
define('PATH_CURRENT_SITE', '/');
define('SITE_ID_CURRENT_SITE', 1);
define('BLOG_ID_CURRENT_SITE', 1);

// PHP Configuration
@ini_set('memory_limit', '512M');
@ini_set('max_execution_time', -1);

// Place this file into master folder of WordPress
require (dirname(__FILE__) . '/wp-load.php');

// Old/New
define('OLD_DOMAIN', 'old-domain.com');
define('NEW_DOMAIN', 'new-domain.com');

class Simple_Move_WP {
	/**
	 * Constructor, make the classic queries for installation and each website!
	 */
	function __construct() {
		global $wpdb;

		if (!defined('OLD_DOMAIN') || !defined('NEW_DOMAIN')) {// A constant missing ?
			return false;
		}

		if (constant('OLD_DOMAIN') == constant('NEW_DOMAIN')) {// The same value ?
			return false;
		}

		// Rename each sites
		$wpdb -> query("UPDATE $wpdb->site SET domain = replace(domain, '" . OLD_DOMAIN . "', '" . NEW_DOMAIN . "');");

		// Rename wp_blogs table
		$wpdb -> query("UPDATE $wpdb->blogs SET domain = replace(domain, '" . OLD_DOMAIN . "', '" . NEW_DOMAIN . "');");

		// Update wide meta table users
		$wpdb -> query("UPDATE `wp_usermeta` SET `meta_value` = REPLACE(`meta_value`, '" . OLD_DOMAIN . "', '" . NEW_DOMAIN . "') WHERE `meta_value` NOT REGEXP '^([adObis]:|N;)';");

		// First web site
		$wpdb -> query("UPDATE `wp_options` SET `option_value` = REPLACE(`option_value`, '" . OLD_DOMAIN . "', '" . NEW_DOMAIN . "') WHERE `option_value` NOT REGEXP '^([adObis]:|N;)';");
		$wpdb -> query("UPDATE `wp_posts` SET `guid` = REPLACE(`guid`, '" . OLD_DOMAIN . "', '" . NEW_DOMAIN . "');");
		$wpdb -> query("UPDATE `wp_posts` SET `post_content` = REPLACE(`post_content`, '" . OLD_DOMAIN . "', '" . NEW_DOMAIN . "');");
		$wpdb -> query("UPDATE `wp_comments` SET `comment_author_url` = REPLACE(`comment_author_url`, '" . OLD_DOMAIN . "', '" . NEW_DOMAIN . "');");
		$wpdb -> query("UPDATE `wp_comments` SET `comment_content` = REPLACE(`comment_content`, '" . OLD_DOMAIN . "', '" . NEW_DOMAIN . "');");
		$wpdb -> query("UPDATE `wp_links` SET `link_url` = REPLACE(`link_url`, '" . OLD_DOMAIN . "', '" . NEW_DOMAIN . "');");
		$wpdb -> query("UPDATE `wp_postmeta` SET `meta_value` = REPLACE(`meta_value`, '" . OLD_DOMAIN . "', '" . NEW_DOMAIN . "') WHERE `meta_value` NOT REGEXP '^([adObis]:|N;)';");
		$wpdb -> query("UPDATE `wp_commentmeta` SET `meta_value` = REPLACE(`meta_value`, '" . OLD_DOMAIN . "', '" . NEW_DOMAIN . "') WHERE `meta_value` NOT REGEXP '^([adObis]:|N;)';");

		// Get all blogs of each website
		$blog_ids = $wpdb -> get_col("SELECT blog_id FROM $wpdb->blogs");

		// Loop on each blogs
		foreach ($blog_ids as $blog_id) {
			// Classic queries
			$wpdb -> query("UPDATE `wp_{$blog_id}_options` SET `option_value` = REPLACE(`option_value`, '" . OLD_DOMAIN . "', '" . NEW_DOMAIN . "') WHERE `option_value` NOT REGEXP '^([adObis]:|N;)';");
			$wpdb -> query("UPDATE `wp_{$blog_id}_posts` SET `guid` = REPLACE(`guid`, '" . OLD_DOMAIN . "', '" . NEW_DOMAIN . "');");
			$wpdb -> query("UPDATE `wp_{$blog_id}_posts` SET `post_content` = REPLACE(`post_content`, '" . OLD_DOMAIN . "', '" . NEW_DOMAIN . "');");
			$wpdb -> query("UPDATE `wp_{$blog_id}_comments` SET `comment_author_url` = REPLACE(`comment_author_url`, '" . OLD_DOMAIN . "', '" . NEW_DOMAIN . "');");
			$wpdb -> query("UPDATE `wp_{$blog_id}_comments` SET `comment_content` = REPLACE(`comment_content`, '" . OLD_DOMAIN . "', '" . NEW_DOMAIN . "');");
			$wpdb -> query("UPDATE `wp_{$blog_id}_links` SET `link_url` = REPLACE(`link_url`, '" . OLD_DOMAIN . "', '" . NEW_DOMAIN . "');");
			$wpdb -> query("UPDATE `wp_{$blog_id}_postmeta` SET `meta_value` = REPLACE(`meta_value`, '" . OLD_DOMAIN . "', '" . NEW_DOMAIN . "') WHERE `meta_value` NOT REGEXP '^([adObis]:|N;)';");
			$wpdb -> query("UPDATE `wp_{$blog_id}_commentmeta` SET `meta_value` = REPLACE(`meta_value`, '" . OLD_DOMAIN . "', '" . NEW_DOMAIN . "') WHERE `meta_value` NOT REGEXP '^([adObis]:|N;)';");

			// Advanced queries
			switch_to_blog($blog_id);
			$this -> replaceInOptionsTable();
			restore_current_blog();
		}
	}

	/**
	 * A special method for replace old URL with new URL with manage serialization datas
	 */
	function replaceInOptionsTable() {
		global $wpdb;

		// Widgets & options
		$options = $wpdb -> get_results("SELECT * 
			FROM $wpdb->options
			WHERE option_name NOT LIKE '\_%' 
			AND option_name NOT LIKE '%user_roles'
			AND option_name != 'permalink_structure'
		");
		foreach ($options as $option) {
			if (is_serialized($option -> option_value)) {
				if (is_serialized_string($option -> option_value)) {
					$option_value = maybe_unserialize($option -> option_value);
					$new_value = str_replace(OLD_DOMAIN, NEW_DOMAIN, $option_value);
					if ($new_value != $option_value) {
						update_option($option -> option_name, maybe_serialize($new_value));
					}
				} else {// A real array to map ?
					$option_value = maybe_unserialize($option -> option_value);
					if (is_array($option_value)) {
						$new_value = $this -> arrayMap(array(&$this, 'callback'), $option_value);
						if ($new_value != $option_value) {
							update_option($option -> option_name, $new_value);
						}
					}
				}
			} else {// String
				$new_value = str_replace(OLD_DOMAIN, NEW_DOMAIN, $option -> option_value);
				if ($new_value != $option -> option_value) {
					update_option($option -> option_name, $new_value);
				}
			}
		}
	}

	/**
	 * Callback for replace
	 *
	 * @param string $value
	 * @return void
	 * @author Amaury Balmer
	 */
	function callback($value) {
		return str_replace(OLD_DOMAIN, NEW_DOMAIN, $value);
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

$Simple_Move_WP = new Simple_Move_WP();
die('OK, don\'t forget to fix the configuration file of WordPress !');
?>