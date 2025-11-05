<?php
/**
* Plugin Name: WP MT Recaptcha V2
* Description: Adds multiple reCAPTCHA V2 checkbox widgets (myaccount login/register, reviews, checkout) and validates server-side. Coexists with external reCAPTCHA V3 by rendering V2 explicitly and loading grecaptcha only once.
* Version: 1.0.0
* Author: 
* Text Domain: wmr
*/

if ( ! defined( 'ABSPATH' ) ) {
exit;
}

define( 'WMR_DIR', plugin_dir_path( __FILE__ ) );
define( 'WMR_URL', plugin_dir_url( __FILE__ ) );

require_once WMR_DIR . 'includes/class-wmr-settings.php';
require_once WMR_DIR . 'includes/class-wmr-loader.php';
require_once WMR_DIR . 'includes/class-wmr-frontend.php';
require_once WMR_DIR . 'includes/class-wmr-validators.php';

add_action( 'plugins_loaded', 'wmrInit' );

function wmrInit() 
{
$settings = new WMR_Settings();
$loader = new WMR_Loader( $settings );
$frontend = new WMR_Frontend( $settings );
$validator = new WMR_Validators( $settings );
}
