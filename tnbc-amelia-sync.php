<?php
/**
 * Plugin Name: TNBC Amelia Sync
 * Description: Amelia Booking and WooCommerce integration — product sync, cart handling, email suppression, and routing.
 * Version: 1.0.0
 * Author: Alex Walton
 * Author URI: https://alex-walton.co.uk/
 * Requires PHP: 8.3
 * Requires at least: 6.9.4
 * Requires Plugins: woocommerce, ameliabooking
 * Text Domain: tnbc-amelia-sync
 *
 * @package tnbc-amelia-sync
 */

namespace TNBC\AmeliaSync;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'TNBC_AMELIA_SYNC_DIR', plugin_dir_path( __FILE__ ) );
define( 'TNBC_AMELIA_SYNC_URL', plugin_dir_url( __FILE__ ) );

require TNBC_AMELIA_SYNC_DIR . 'inc/database.php';
require TNBC_AMELIA_SYNC_DIR . 'inc/query.php';
require TNBC_AMELIA_SYNC_DIR . 'inc/product.php';
require TNBC_AMELIA_SYNC_DIR . 'inc/admin.php';
require TNBC_AMELIA_SYNC_DIR . 'inc/amelia.php';
require TNBC_AMELIA_SYNC_DIR . 'inc/woocommerce.php';

init_amelia();
init_woocommerce();
