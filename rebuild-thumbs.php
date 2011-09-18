<?php
@ini_set( 'memory_limit', '512M' );
@ini_set( 'max_execution_time', -1 );

// Place this file into master folder of WordPress
require( dirname(__FILE__) . '/wp-load.php' );
require_once(ABSPATH . 'wp-admin/includes/admin.php');

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
		if( wp_update_attachment_metadata( $attachment->ID, wp_generate_attachment_metadata( $attachment->ID, $fullsizepath ) ) == false ) {
			// Success
			echo "\n".sprintf(__('%d/%d : Regeneration OK for the media %d : %s.'), $i, $total, $attachment->ID, $attachment->post_title);
			hardFlush();
		} else {
			// Echec
			echo "\n".sprintf(__('%d/%d : An error occured with the media %d :  %s. (updating post meta)'), $i, $total, $attachment->ID, $attachment->post_title);
			hardFlush();
		}
	} else {
		// Echec
		echo "\n".sprintf(__('%d/%d : An error occured with the media %d : %s. (full path)'), $i, $total, $attachment->ID, $attachment->post_title);
		hardFlush();
	}
}

echo "\n".__('Process finished.');
die();
?>