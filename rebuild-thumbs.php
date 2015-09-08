<?php
if ( php_sapi_name() !== 'cli' || isset( $_SERVER['REMOTE_ADDR'] ) ) {
	die( 'CLI Only' );
}

// Get first arg
if ( ! isset( $argv ) || count( $argv ) < 2 ) {
	echo "Missing parameters.\n";
	echo "script usage: phprebuild-thumbs.php [domain] [path] \n";
	die();
}

//Domain/path
$domain = ( isset( $argv[1] ) ) ? $argv[1] : '';
$path = ( isset( $argv[2] ) ) ? $argv[2] : '/';

// Fake WordPress, build server array
$_SERVER = array(
	'HTTP_HOST'       => $domain,
	'SERVER_NAME'     => $domain,
	'REQUEST_URI'     => $path,
	'REQUEST_METHOD'  => 'GET',
	'SCRIPT_NAME'     => basename( __FILE__ ),
	'SCRIPT_FILENAME' => basename( __FILE__ ),
	'PHP_SELF'        => basename( __FILE__ )
);

@ini_set( 'memory_limit', - 1 );
@ini_set( 'display_errors', 1 );

// Place this file into master folder of WordPress
require( dirname(__FILE__) . '/wp-load.php' );
require_once(ABSPATH . 'wp-admin/includes/admin.php');

@ini_set( 'memory_limit', - 1 );
@ini_set( 'display_errors', 1 );

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

echo "\n".__('Begin the regeneration.');
hardFlush();

$attachments = get_children( array(
	'post_type' => 'attachment',
	'post_mime_type' => 'image',
	'numberposts' => -1,
	'post_status' => null,
	'post_parent' => null, // any parent
	'output' => 'object',
) );
	
$total = count($attachments);
echo "\n".sprintf(__('Whe have %d attachments.'), $total);
hardFlush();

$i = 0;
foreach( $attachments as $attachment ) {
	$i++;
	
	// Get the path
	$fullsizepath = get_attached_file( $attachment->ID );
			
	// Regen the attachment
	if ( FALSE !== $fullsizepath && @file_exists( $fullsizepath ) ) {
		$meta_data = wp_generate_attachment_metadata( $attachment->ID, $fullsizepath );
		if ( is_wp_error($meta_data) || empty($meta_data) ) {
			// Echec
			echo "\n".sprintf(__('%d/%d : An error occured with the media %d :  %s. (wp_generate_attachment_metadata failed)'), $i, $total, $attachment->ID, $attachment->post_title);
			hardFlush();
			continue;
		}
		
		wp_update_attachment_metadata( $attachment->ID, $meta_data );
		
		echo "\n".sprintf(__('%d/%d : Regeneration OK for the media %d : %s.'), $i, $total, $attachment->ID, $attachment->post_title);
		hardFlush();
	} else {
		// Echec
		echo "\n".sprintf(__('%d/%d : An error occured with the media %d : %s. (full path)'), $i, $total, $attachment->ID, $attachment->post_title);
		hardFlush();
	}
}

echo "\n".__('Process finished.');
die();
?>
