<?php
//* Code goes here
add_action( 'wp_enqueue_scripts', 'enqueue_parent_styles' );

function enqueue_parent_styles() {
   wp_enqueue_style( 'parent-style', get_template_directory_uri().'/style.css' );
}

/*
 * A simple function to control the number of Twenty Seventeen Theme Front Page Sections
 */
function wpc_custom_front_sections( $num_sections )
	{
		return 6; //Change this number to change the number of the sections.
	}
add_filter( 'twentyseventeen_front_page_sections', 'wpc_custom_front_sections' );

//allow the ability to upload xml docs in wordpress
function my_custom_mime_types($mimes = array()) {
    // New allowed mime types.
	$mimes['xml'] = 'text/xml';
	$mimes['xml'] = 'application/xml';
	return $mimes;
}
add_filter( 'upload_mimes', 'my_custom_mime_types');