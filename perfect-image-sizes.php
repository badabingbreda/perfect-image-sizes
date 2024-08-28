<?php
/*
	Plugin Name: Perfect Image Sizes
	Description: Deliver perfectly sized images for every possible responsive media width using the picture element. Display small images on multiple column grids and bigger images on tablets, not the other way around! Restrict the maximum fallback size, allow retina image sizes and set the focal point of the image. Use your TwicPics or CloudImage account to deliver optimized images and cache locally.
	Version: 1.4.2
	Author: Badabing
	Author URI: https://www.badabing.nl
	Text Domain: perfect-image-sizes
	Domain Path: /languages
*/

use PerfectImageSizes\Autoloader;
use PerfectImageSizes\Init;
use PerfectImageSizes\Imager;
use PerfectImageSizes\FocalPoint;

if ( defined( 'ABSPATH' ) && ! defined( 'PERFECTIMAGESIZES_VERION' ) ) {
	register_activation_hook( __FILE__ , 'PERFECTIMAGESIZES_check_php_version' );

	/**
	 * Display notice for old PHP version.
	*/
	function PERFECTIMAGESIZES_check_php_version() {
		if ( version_compare( phpversion(), '7.4', '<' ) ) {
			die( esc_html__( 'Better Image Sizes requires PHP version 7.4+. Please contact your host to upgrade.', 'perfect-image-sizes' ) );
		}
	}
	
	define( 'PERFECTIMAGESIZES_VERSION'   , '1.4.2' );
	define( 'PERFECTIMAGESIZES_DIR'     , plugin_dir_path( __FILE__ ) );
	define( 'PERFECTIMAGESIZES_BASE'     , plugin_basename( __FILE__ ) );
	define( 'PERFECTIMAGESIZES_FILE'    , __FILE__ );
	define( 'PERFECTIMAGESIZES_URL'     , plugins_url( '/', __FILE__ ) );
	
	define( 'CHECK_PERFECTIMAGESIZES_PLUGIN_FILE', __FILE__ );
	
	add_action( 'plugins_loaded', function(){
		load_plugin_textdomain( 'bb-better-image-sizes', false, basename( __DIR__ ) . '/languages/' );
	});

} else {
	exit;
}

if ( ! class_exists( 'PerfectImageSizes\Init' ) ) {

	/**
	 * The file where the Autoloader class is defined.
	*/
	require_once PERFECTIMAGESIZES_DIR . 'inc/Autoloader.php';
	spl_autoload_register( array( new Autoloader(), 'autoload' ) );

	$better_image_sizes = new Init();

	if (!function_exists( 'perfect_get_picture' )) {
		function perfect_get_picture( $attachment_id , $breakpoints , $attr , $max_full = null ) {
			return Imager::get_attachment_picture( $attachment_id , $breakpoints , $attr , $max_full );
		}
	}

}
