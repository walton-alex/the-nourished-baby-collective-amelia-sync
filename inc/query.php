<?php
/**
 * Amelia product queries.
 *
 * @package tnbc-amelia-sync
 */

namespace TNBC\AmeliaSync;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check if a product is linked to Amelia.
 * Uses Woo-side meta marker exclusively — kept in sync by create/update/delete hooks.
 *
 * @param int $product_id Product ID.
 * @return bool
 */
function is_amelia_product( int $product_id ): bool {
	return 'yes' === get_post_meta( $product_id, '_tnbc_amelia_managed', true );
}

/**
 * Get the Amelia service ID for a given product ID.
 *
 * @param int $product_id Product ID.
 * @return int|string
 */
function get_amelia_service_for_product( int $product_id ) {
	$type = get_post_meta( $product_id, '_tnbc_amelia_entity_type', true );
	$id   = absint( get_post_meta( $product_id, '_tnbc_amelia_entity_id', true ) );

	return ( 'amelia_services' === $type && $id ) ? $id : '';
}

/**
 * Get the Amelia package ID for a given product ID.
 *
 * @param int $product_id Product ID.
 * @return int|string
 */
function get_amelia_package_for_product( int $product_id ) {
	$type = get_post_meta( $product_id, '_tnbc_amelia_entity_type', true );
	$id   = absint( get_post_meta( $product_id, '_tnbc_amelia_entity_id', true ) );

	return ( 'amelia_packages' === $type && $id ) ? $id : '';
}

/**
 * Get the Amelia entity name for a given product ID (service or package).
 *
 * @param int $product_id Product ID.
 * @return string
 */
function get_amelia_entity_name( int $product_id ): string {
	return (string) get_the_title( $product_id );
}

/**
 * Get the Amelia service name by service ID (via product meta lookup).
 *
 * @param int $service_id Service ID.
 * @return string
 */
function get_amelia_service_name( int $service_id ): string {
	return amelia_get_entity_name_by_id( 'amelia_services', $service_id );
}

/**
 * Get the Amelia package name by package ID (via product meta lookup).
 *
 * @param int $package_id Package ID.
 * @return string
 */
function get_amelia_package_name( int $package_id ): string {
	return amelia_get_entity_name_by_id( 'amelia_packages', $package_id );
}

/**
 * Resolve an Amelia entity name by finding its linked product title.
 *
 * @param string $suffix    Entity type (e.g. 'amelia_services').
 * @param int    $entity_id Entity ID.
 * @return string
 */
function amelia_get_entity_name_by_id( string $suffix, int $entity_id ): string {
	if ( ! $entity_id ) {
		return '';
	}

	$products = get_posts( [
		'post_type'      => 'product',
		'post_status'    => 'any',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'meta_query'     => [
			[ 'key' => '_tnbc_amelia_entity_type', 'value' => $suffix ],
			[ 'key' => '_tnbc_amelia_entity_id', 'value' => $entity_id ],
		],
	] );

	return $products ? get_the_title( $products[0] ) : '';
}

/**
 * Get the Amelia entity price for a given product ID (service or package).
 *
 * @param int $product_id Product ID.
 * @return string
 */
function get_amelia_entity_price( int $product_id ): string {
	$price = get_post_meta( $product_id, '_tnbc_amelia_price', true );

	return $price ? (string) $price : '0';
}

/**
 * Calculate savings for an Amelia package product.
 *
 * @param int $product_id Product ID.
 * @return float Savings amount (0 if not a package or no savings).
 */
function get_amelia_package_savings( int $product_id ): float {
	$savings = get_post_meta( $product_id, '_tnbc_amelia_savings', true );

	return $savings ? (float) $savings : 0.0;
}
