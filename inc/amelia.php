<?php
/**
 * Amelia Booking hook callbacks.
 *
 * @package tnbc-amelia-sync
 */

namespace TNBC\AmeliaSync;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Connect methods to hooks and filters.
 *
 * @return void
 */
function init_amelia(): void {
	add_action( 'woocommerce_before_single_product', __NAMESPACE__ . '\\amelia_remove_single_add_to_cart' );
	add_action( 'woocommerce_process_product_meta', __NAMESPACE__ . '\\amelia_sync_product_meta', 30 );
	add_action( 'amelia_after_service_added', __NAMESPACE__ . '\\amelia_create_product_for_service' );
	add_action( 'amelia_after_service_updated', __NAMESPACE__ . '\\amelia_sync_product_for_service' );
	add_action( 'amelia_after_package_added', __NAMESPACE__ . '\\amelia_create_product_for_package' );
	add_action( 'amelia_after_package_updated', __NAMESPACE__ . '\\amelia_sync_product_for_package' );
	add_action( 'amelia_after_service_deleted', __NAMESPACE__ . '\\amelia_delete_product_for_service' );
	add_action( 'amelia_after_package_deleted', __NAMESPACE__ . '\\amelia_delete_product_for_package' );
	add_filter( 'woocommerce_add_to_cart_validation', __NAMESPACE__ . '\\amelia_block_direct_add_to_cart', 10, 2 );
	add_filter( 'manage_edit-product_columns', __NAMESPACE__ . '\\amelia_product_column' );
	add_action( 'manage_product_posts_custom_column', __NAMESPACE__ . '\\amelia_product_column_content', 10, 2 );
	add_action( 'admin_head', __NAMESPACE__ . '\\amelia_product_column_styles' );
}

/**
 * Block direct add-to-cart for Amelia products.
 * Only allow if the request originates from Amelia's booking flow.
 *
 * @param bool $passed     Validation result.
 * @param int  $product_id Product ID.
 * @return bool
 */
function amelia_block_direct_add_to_cart( bool $passed, int $product_id ): bool {
	if ( ! $passed || ! is_amelia_product( $product_id ) ) {
		return $passed;
	}

	if ( doing_action( 'wp_ajax_wpamelia_api' ) || doing_action( 'wp_ajax_nopriv_wpamelia_api' ) ) {
		return $passed;
	}

	if ( did_action( 'AmeliaAddBookingToWcCart' ) || did_action( 'amelia_add_booking_to_wc_cart' ) ) {
		return $passed;
	}

	return false;
}

/**
 * Remove the single product Add to Cart form for Amelia products.
 */
function amelia_remove_single_add_to_cart(): void {
	global $product;

	if ( $product && is_amelia_product( $product->get_id() ) ) {
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
		remove_action( 'woocommerce_simple_add_to_cart', 'woocommerce_simple_add_to_cart', 30 );
	}
}

/**
 * Enforce Amelia product defaults on save.
 *
 * @param int $product_id Product ID.
 */
function amelia_sync_product_meta( int $product_id ): void {
	if ( ! is_amelia_product( $product_id ) ) {
		return;
	}

	amelia_set_product_defaults( $product_id );
}

/**
 * Create a WooCommerce product when an Amelia service is added.
 *
 * @param array $service_data Service data array.
 */
function amelia_create_product_for_service( array $service_data ): void {
	if ( empty( $service_data['id'] ) || empty( $service_data['name'] ) ) {
		return;
	}

	amelia_create_product(
		'amelia_services',
		(int) $service_data['id'],
		$service_data['name'],
		(int) ( $service_data['categoryId'] ?? 0 ),
		(string) ( $service_data['price'] ?? '0' )
	);

	amelia_sync_product(
		'amelia_services',
		(int) $service_data['id'],
		$service_data['name'],
		(int) ( $service_data['categoryId'] ?? 0 ),
		$service_data['description'] ?? '',
		$service_data['pictureFullPath'] ?? '',
		(string) ( $service_data['price'] ?? '0' )
	);
}

/**
 * Sync WooCommerce product when an Amelia service is updated.
 *
 * @param array $service_data Service data array.
 */
function amelia_sync_product_for_service( array $service_data ): void {
	if ( empty( $service_data['id'] ) ) {
		return;
	}

	amelia_sync_product(
		'amelia_services',
		(int) $service_data['id'],
		$service_data['name'] ?? '',
		(int) ( $service_data['categoryId'] ?? 0 ),
		$service_data['description'] ?? '',
		$service_data['pictureFullPath'] ?? '',
		(string) ( $service_data['price'] ?? '0' )
	);
}

/**
 * Create a WooCommerce product when an Amelia package is added.
 *
 * @param array $package_data Package data array.
 */
function amelia_create_product_for_package( array $package_data ): void {
	if ( empty( $package_data['id'] ) || empty( $package_data['name'] ) ) {
		return;
	}

	amelia_create_product(
		'amelia_packages',
		(int) $package_data['id'],
		$package_data['name'],
		(int) ( $package_data['bookable'][0]['service']['categoryId'] ?? 0 ),
		(string) ( $package_data['price'] ?? '0' )
	);

	amelia_sync_product(
		'amelia_packages',
		(int) $package_data['id'],
		$package_data['name'],
		(int) ( $package_data['bookable'][0]['service']['categoryId'] ?? 0 ),
		$package_data['description'] ?? '',
		$package_data['pictureFullPath'] ?? '',
		(string) ( $package_data['price'] ?? '0' )
	);
}

/**
 * Delete WooCommerce product when an Amelia service is deleted.
 *
 * @param array $service_data Service data array.
 */
function amelia_delete_product_for_service( array $service_data ): void {
	amelia_delete_product( 'amelia_services', $service_data );
}

/**
 * Delete WooCommerce product when an Amelia package is deleted.
 *
 * @param array $package_data Package data array.
 */
function amelia_delete_product_for_package( array $package_data ): void {
	amelia_delete_product( 'amelia_packages', $package_data );
}

/**
 * Sync WooCommerce product when an Amelia package is updated.
 *
 * @param array $package_data Package data array.
 */
function amelia_sync_product_for_package( array $package_data ): void {
	if ( empty( $package_data['id'] ) ) {
		return;
	}

	amelia_sync_product(
		'amelia_packages',
		(int) $package_data['id'],
		$package_data['name'] ?? '',
		(int) ( $package_data['bookable'][0]['service']['categoryId'] ?? 0 ),
		$package_data['description'] ?? '',
		$package_data['pictureFullPath'] ?? '',
		(string) ( $package_data['price'] ?? '0' )
	);
}
