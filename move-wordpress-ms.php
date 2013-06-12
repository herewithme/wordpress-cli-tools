<?php
/*
 * This tools allow to move a WordPress Multisite Installation.
 * 
 * If you want move a WordPress standalone, use: https://github.com/interconnectit/Search-Replace-DB
 * This script used  https://github.com/interconnectit/Search-Replace-DB for replacement in DB
 * 
 * Usage CLI : 
 * php5-cli -f move-wordpress-ms.php search-replace old_domain old_path search replace
 * php5-cli -f move-wordpress-ms.php deactive-mapping old_domain old_path site_id blog_id
 * php5-cli -f move-wordpress-ms.php flush-mapping old_domain old_path site_id blog_id
 * php5-cli -f move-wordpress-ms.php set-mapping old_domain old_path blog_id mapping_domain mapping_status
 * php5-cli -f move-wordpress-ms.php unmapped-links old_domain old_path site_id blog_id
 */

/* That's all, stop editing! Next section is for advanced user !. */

// WP Configuration
define( 'WP_INSTALLING', true );
define( 'WP_CACHE', false );
define( 'SUNRISE', false );
define( 'DONOTCACHEPAGE', true );
define( 'NO_MAINTENANCE', true );
define( 'WP_MEMORY_LIMIT', '512M' );
define( 'DISABLE_EXT_OBJECT_CACHE', true );
define( 'SHORTINIT', false );
define( 'SUNRISE_LOADED', 1 );

// PHP Configuration
@error_reporting( E_ALL );
@ini_set( 'display_startup_errors', '1' );
@ini_set( 'display_errors', '1' );
@ini_set( 'memory_limit', '512M' );
@ini_set( 'max_execution_time', -1 );
if ( function_exists( 'ignore_user_abort' ) )
	ignore_user_abort( 1 );
if ( function_exists( 'set_time_limit' ) )
	set_time_limit( 0 );

// CLIT OR Web ?
if ( defined( 'STDIN' ) ) {

	// Action
	$cli_action = ( isset( $argv[1] ) ) ? $argv[1] : 'search-replace';

	switch ( $cli_action ) {
		case 'cleanup' :
			$old_domain = ( isset( $argv[2] ) ) ? $argv[2] : '';
			$old_path = ( isset( $argv[3] ) ) ? $argv[3] : '';
			$current_site_id = ( isset( $argv[4] ) ) ? $argv[4] : -1;
			$current_blog_id = ( isset( $argv[5] ) ) ? $argv[5] : -1;
			break;

		case 'deactive-mapping' :
			$old_domain = ( isset( $argv[2] ) ) ? $argv[2] : '';
			$old_path = ( isset( $argv[3] ) ) ? $argv[3] : '';
			$current_site_id = ( isset( $argv[4] ) ) ? $argv[4] : -1;
			$current_blog_id = ( isset( $argv[5] ) ) ? $argv[5] : -1;
			break;

		case 'flush-mapping' :
			$old_domain = ( isset( $argv[2] ) ) ? $argv[2] : '';
			$old_path = ( isset( $argv[3] ) ) ? $argv[3] : '';
			$current_site_id = ( isset( $argv[4] ) ) ? $argv[4] : -1;
			$current_blog_id = ( isset( $argv[5] ) ) ? $argv[5] : -1;
			break;

		case 'set-mapping' :
			$old_domain = ( isset( $argv[2] ) ) ? $argv[2] : '';
			$old_path = ( isset( $argv[3] ) ) ? $argv[3] : '';
			$blog_id = ( isset( $argv[4] ) ) ? $argv[4] : 0;
			$mapping_domain = ( isset( $argv[5] ) ) ? $argv[5] : '';
			$mapping_status = ( isset( $argv[6] ) ) ? $argv[6] : 1;
			break;

		case 'unmapped-links' :
			$old_domain = ( isset( $argv[2] ) ) ? $argv[2] : '';
			$old_path = ( isset( $argv[3] ) ) ? $argv[3] : '';
			$current_site_id = ( isset( $argv[4] ) ) ? $argv[4] : -1;
			$current_blog_id = ( isset( $argv[5] ) ) ? $argv[5] : -1;
			break;

		case 'search-replace' :
			$old_domain = ( isset( $argv[2] ) ) ? $argv[2] : '';
			$old_path = ( isset( $argv[3] ) ) ? $argv[3] : '';
			$text_search = ( isset( $argv[4] ) ) ? $argv[4] : '';
			$text_replace = ( isset( $argv[5] ) ) ? $argv[5] : '';
			break;

		default :
			die( 'This action not exists for this script' );
			break;
	}

	// Fake WordPress, build server array
	$_SERVER = array(
		'HTTP_HOST' => $old_domain,
		'SERVER_NAME' => $old_domain,
		'REQUEST_URI' => $old_path,
		'REQUEST_METHOD' => 'GET',
		'SCRIPT_NAME' => basename( __FILE__ ),
		'SCRIPT_FILENAME' => basename( __FILE__ ),
		'PHP_SELF' => $old_path . basename( __FILE__ )
	);
} else {
	die( 'This script must be used only on CLI mode' );
}

// Custom error handler
function handleError( $errno, $errstr, $errfile, $errline, array $errcontext ) {
	// error was suppressed with the @-operator
	if ( !(error_reporting() & $errno) ) {
		// Ce code d'erreur n'est pas inclus dans error_reporting()
		return;
	}

	throw new ErrorException( $errstr, 0, $errno, $errfile, $errline );
}

set_error_handler( 'handleError' );

// Try to load WordPress !
try {
	if ( is_file( dirname( __FILE__ ) . '/wp-load.php' ) ) { // root/
		require (dirname( __FILE__ ) . '/wp-load.php');
	} elseif ( is_file( dirname( __FILE__ ) . '/../wp-load.php' ) ) { // root/wp-content/
		require (dirname( __FILE__ ) . '/../wp-load.php');
	} elseif ( is_file( dirname( __FILE__ ) . '/../../wp-load.php' ) ) { // root/wp-content/tools/
		require (dirname( __FILE__ ) . '/../../wp-load.php');
	} else {
		die( 'WP not found' );
	}
} catch ( ErrorException $e ) {
	if ( isset( $e->xdebug_message ) && !empty( $e->xdebug_message ) ) {
		//die($e->xdebug_message);
	}
	//var_dump($e); // Debug
	if ( strpos( $e->getMessage(), 'headers' ) !== false )
		die( 'Setting up your configuration file seems incomplete because WordPress is trying to do an HTTP redirect.' );
}

// WordPress MS is really loaded ?
global $wpdb;
if ( !isset( $wpdb ) || !function_exists( 'get_blog_option' ) ) {
	die( 'A problem occurred with initializing of WordPress Multisite. Perhaps the nework is already moved ? This script works only with WordPress Multisite enabled.' );
}

// A site exist ?
$site = $wpdb->get_row( "SELECT * FROM $wpdb->blogs" );
if ( $site == false ) {
	die( 'A problem occurred with initializing of WordPress Multisite. Perhaps the nework is already moved ? This script works only with WordPress Multisite enabled.' );
}

require_once( ABSPATH . WPINC . '/formatting.php' );
require_once( ABSPATH . WPINC . '/link-template.php' );

/* That's all, stop editing! Next section is for VERY advanced user !. */
class Move_WordPress_MS {

	/**
	 * Constructor, make no nothing
	 */
	public function __construct() {
		
	}

	/**
	 * Deactive all domains mapping for a network or a blog
	 */
	public static function deactive_mapping( $site_id = -1, $blog_id = -1 ) {
		global $wpdb;

		// Build condition for mapping table
		$condition_sql = '';
		if ( $site_id > 0 ) {
			$condition_sql .= " AND blog_id IN (SELECT blog_id FROM {$wpdb->blogs} WHERE site_id = {$site_id}) ";
		}
		if ( $blog_id > 0 ) {
			$condition_sql .= " AND blog_id = {$blog_id} ";
		}

		// Make query
		$counter = (int) $wpdb->query( "UPDATE {$wpdb->base_prefix}domain_mapping SET active = 0 WHERE 1 = 1 " . $condition_sql );

		echo( sprintf( 'Ok, deactive mapping are finished. (%d line(s) updated)', $counter ) . PHP_EOL );
		exit();
	}

	/**
	 * Delete all domains mapping for a network or a blog
	 */
	public static function flush_mapping( $site_id = -1, $blog_id = -1 ) {
		global $wpdb;

		// Build condition for mapping table
		$condition_sql = '';
		if ( $site_id > 0 ) {
			$condition_sql .= " AND blog_id IN (SELECT blog_id FROM {$wpdb->blogs} WHERE site_id = {$site_id}) ";
		}
		if ( $blog_id > 0 ) {
			$condition_sql .= " AND blog_id = {$blog_id} ";
		}

		// Make query
		$counter = (int) $wpdb->query( "DELETE FROM {$wpdb->base_prefix}domain_mapping WHERE 1 = 1 " . $condition_sql );

		echo( sprintf( 'Ok, flush mapping table are finished. (%d line(s) deleted)', $counter ) . PHP_EOL );
		exit();
	}

	/**
	 * Loop on domain mapped, and restore original blog link !
	 */
	public static function restore_umapped_links( $site_id = -1, $blog_id = -1 ) {
		global $wpdb;

		// Build condition for mapping table
		$condition_sql = '';
		if ( $site_id > 0 ) {
			$condition_sql .= " AND blog_id IN (SELECT blog_id FROM {$wpdb->blogs} WHERE site_id = {$site_id}) ";
		}
		if ( $blog_id > 0 ) {
			$condition_sql .= " AND blog_id = {$blog_id} ";
		}

		// Make query
		$domains = $wpdb->get_results( "SELECT * FROM {$wpdb->base_prefix}domain_mapping WHERE 1 = 1 " . $condition_sql );

		$output = '';

		// Get blogs
		foreach ( (array) $domains as $_domain ) {
			// Get domain to replace from DM plugin
			$search = rtrim( $_domain->domain, '/' );

			// Get real URL for blog
			$replace = get_home_url( $_domain->blog_id, '', 'http' );
			$replace = str_replace( 'http://', '', $replace );
			$replace = rtrim( $replace, '/' );

			$output .= self::search_replace( $search, $replace );
		}

		echo( 'Ok, restore unmapped links are finished. Results : ' . $output . PHP_EOL );
		exit();
	}

	/**
	 * Add mapping for a blog
	 */
	public static function add_mapping( $blog_id = 0, $mapping_domain = '', $mapping_status = 0 ) {
		global $wpdb;

		if ( empty( $blog_id ) || empty( $mapping_domain ) || $blog_id == 0 ) {
			die( 'Missing parameters' );
		}

		$line = $wpdb->insert( $wpdb->base_prefix . 'domain_mapping', array( 'blog_id' => $blog_id, 'domain' => $mapping_domain, 'active' => (int) $mapping_status ) );
		printf( 'Ok, set mapping done, %d line added.' . PHP_EOL, (int) $line );
		exit();
	}

	/**
	 * Allow to search and replace string for a blog or an network
	 */
	public static function search_replace( $search = '', $replace = '', $function_callback = 'Move_WordPress_MS::return_value' ) {
		if ( empty( $search ) && empty( $replace ) ) {
			return call_user_func( $function_callback, 'Missing parameters' . PHP_EOL );
		}

		// Script CLI for search/replace
		$script_path = dirname( __FILE__ ) . '/library/Search-Replace-DB-master/searchreplacedb2cli.php';
		if ( !is_file( $script_path ) ) {
			return call_user_func( $function_callback, 'Missing CLI script for search/replace, full path expected : ' . $script_path . PHP_EOL );
		}

		// Exec search replace
		$result = shell_exec( sprintf( 'php %1$s --host "%2$s" --user "%3$s" --pass "%4$s" --database "%5$s" --charset "%6$s" --search "%7$s" --replace "%8$s"', $script_path, DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_CHARSET, $search, $replace ) );

		return call_user_func( $function_callback, 'Ok, search and replace results : ' . $result . PHP_EOL );
	}
	
	public static function return_value($value) {
		return $value;
	}
	
	public static function die_value( $value ) {
		die($value);
	}

	public static function cleanup( $site_id = -1, $blog_id = -1 ) {
		global $wpdb;

		$condition_sql = '';
		if ( $site_id > 0 ) {
			$condition_sql .= " AND site_id = {$site_id} ";
		}
		if ( $blog_id > 0 ) {
			$condition_sql .= " AND blog_id = {$blog_id} ";
		}

		$counter = 0;

		// Get blogs
		$blogs = $wpdb->get_results( "SELECT * FROM $wpdb->blogs WHERE 1 = 1 {$condition_sql}" );
		foreach ( (array) $blogs as $blog ) {
			switch_to_blog( $blog->blog_id );
			
			// Make query
			$counter += $wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_site_transient_browser_%' OR option_name LIKE '_site_transient_timeout_browser_%' OR option_name LIKE '_transient_feed_%' OR option_name LIKE '_transient_timeout_feed_%'" );

			/*
			 * DELETE FROM wp_options WHERE option_name LIKE ('_transient_%');
			 * DELETE FROM wp_comments WHERE wp_comments.comment_type = 'pingback';
			 * DELETE FROM wp_comments WHERE wp_comments.comment_type = 'trackback';
			 * DELETE a,b,c FROM wp_posts a LEFT JOIN wp_term_relationships b ON (a.ID = b.object_id)
			 * LEFT JOIN wp_postmeta c ON (a.ID = c.post_id) WHERE a.post_type = 'revision';
			 */

			restore_current_blog();
		}

		echo( sprintf( 'Ok, cleanup transient are finished. (%d line(s) deleted)', $counter ) . PHP_EOL );
		exit();
	}

}

switch ( $cli_action ) {
	case 'cleanup' :
		Move_WordPress_MS::cleanup( (int) $current_site_id, (int) $current_blog_id );
		break;

	case 'deactive-mapping' :
		Move_WordPress_MS::deactive_mapping( (int) $current_site_id, (int) $current_blog_id );
		break;

	case 'flush-mapping' :
		Move_WordPress_MS::flush_mapping( (int) $current_site_id, (int) $current_blog_id );
		break;

	case 'set-mapping' :
		Move_WordPress_MS::add_mapping( (int) $blog_id, $mapping_domain, (int) $mapping_status );
		break;

	case 'unmapped-links' :
		Move_WordPress_MS::restore_umapped_links( (int) $current_site_id, (int) $current_blog_id );
		break;

	case 'search-replace' :
		Move_WordPress_MS::search_replace( $text_search, $text_replace, 'Move_WordPress_MS::die_value' );
		break;

	default :
		die( 'This action not exists for this script' );
		break;
}
