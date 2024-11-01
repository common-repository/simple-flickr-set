<?php
/**
 * @package Simple_Flickr_Set
 * @version 1.0
 */
/*
Plugin Name: Simple Flickr Set
Plugin URI: http://github.com/claremontmckennacollege/simple-flickr-set
Description: This allows the user to embed auto-playing looping Flickr galleries with a simple shortcode using javascript and css (no flash!).
Author: Scott A. Williams
Version: 1.0
Author URI: http://cmc.edu/
*/

// Declare shortcode and menu
add_shortcode( 'simple-flickr', 'flickr_handlr');
add_action('admin_menu', 'sfs_menu');
add_action( 'wp_enqueue_scripts', 'get_sfs_js' );

// [simple-flickr] shortcode handler
function flickr_handlr($atts){
        //Sanity Check
        if (!isset($atts['set'])){
                return '<h5>Cannot load flickr set.  "set=" is required</h5>';
        }
	if (is_null(get_sfs_api())){
		return '<h5>You need to set your API in the admin menu.</h5>';
	}

	//Fetch CSS and JS
	get_sfs_css($atts);

	//Get the photos
	$photoset = get_sfs_photos($atts['set']);
	if ($photoset['stat'] != 'ok'){
		return '<h5>Could not get photos from Flickr.  ' . $photoset['message'] . '</h5>';
	}

	//Return the markup
	return parse_sfs_html($photoset);
}

// Expects array for API call options - see https://www.flickr.com/services/api/
function sfs_api_call($params){
	$params['format'] = 'php_serial';
	$encoded_params = array();
	foreach ($params as $k => $v){
	        $encoded_params[] = urlencode($k) . '=' . urlencode($v);
	}
	$url = "https://api.flickr.com/services/rest/?" . implode('&', $encoded_params);
	$rsp = file_get_contents($url);
	return unserialize($rsp);
}

// Get API key from Database
function get_sfs_api(){
	global $wpdb;
	return $wpdb->get_var("SELECT option_value FROM $wpdb->options WHERE option_name = 'sfsapi'");
}

// Get CSS styling
function get_sfs_css($atts){
	//If width and height are specified, use them
	if (isset($atts['width'])){
		$width = $atts['width'];
	}else{
		$width = '500px';
	}
	if (isset($atts['height'])){
		$height = $atts['height'];
	}else{
		$height = '426px';
	}
	echo "

<style type=\"text/css\">
div#flickr-images{
        height: $height;
        width: $width;
        text-align: center;
        display: inline-block;
	vertical-align:middle;
	content: ' ';
	padding-bottom:5px;
	background: #0f0f0f;
	max-width: 100%
}

div.sfs-img-container{
	height:100%;
	display: inline-block;
	vertical-align: middle;	
	text-align: center;
	line-height: $height;
	max-width: 100%
}

div#flickr-images .flickr-img{
        max-width: 100%;
        max-height:100%;
        vertical-align:middle;
        display: inline-block;
	border-radius: 0;
}

</style>";
}

// Get the script
function get_sfs_js(){
	wp_enqueue_script('sfs-js', plugin_dir_url( __FILE__ ) . "js/sfs.js", array( 'jquery'));
}

// Get the photos object
function get_sfs_photos($set){
	$params = array(
        	'api_key' => get_sfs_api(),
        	'method' => 'flickr.photosets.getPhotos',
        	'photoset_id' => $set,
        	'format' => 'php_serial'
	);
	return sfs_api_call($params);
}

// Get URL from Database
function get_sfs_url(){
        global $wpdb;
        return $wpdb->get_var("SELECT option_value FROM $wpdb->options WHERE option_name = 'sfsurl'");
}

// Get User ID from Database
function get_sfs_uid(){
        global $wpdb;
        return $wpdb->get_var("SELECT option_value FROM $wpdb->options WHERE option_name = 'sfsuid'");
}

// Get URL from Database
function get_sfs_user(){
        global $wpdb;
        return $wpdb->get_var("SELECT option_value FROM $wpdb->options WHERE option_name = 'sfsuser'");
}

//Parse HTML response
function parse_sfs_html($photoset){
	$markup =  '<div id="flickr-images"><a target="_blank" href="https://www.flickr.com/photos/' . $photoset['photoset']['owner'] . '/' . $photoset['photoset']['primary'] . '">';

	foreach ($photoset['photoset']['photo'] as $photo){
        	$markup .= '<div class="sfs-img-container"><img class="flickr-img" src="http://farm' . $photo['farm'] . '.static.flickr.com/' . $photo['server'] . '/' .  $photo['id'] . '_' . $photo['secret'] . '.jpg" alt="' . $photo['title'] . '"/></div>';
	}
	$markup .= '</a></div>';
	return $markup;
}

// Add Menu option on admin page.  Restrict access to Editor and above.
function sfs_menu(){
	add_menu_page( 'Simple Flickr Set', 'Simple Flickr Set', 'edit_pages','simple-flickr-set-menu','sfs_menu_handler'); 
}

// Call the actual menu handler
function sfs_menu_handler(){
	include( plugin_dir_path( __FILE__ ) . 'sfs-admin.php');
}

?>
