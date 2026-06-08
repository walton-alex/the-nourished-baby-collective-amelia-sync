<?php
/**
 * Amelia database helpers.
 *
 * @package tnbc-amelia-sync
 */

namespace TNBC\AmeliaSync;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check if an Amelia table exists and return its full name.
 *
 * @param string $suffix Table suffix (e.g. 'amelia_services').
 * @return string|false Full table name or false.
 */
function amelia_table( string $suffix ) {
	static $cache = [];

	if ( isset( $cache[ $suffix ] ) ) {
		return $cache[ $suffix ];
	}

	global $wpdb;

	$table = $wpdb->prefix . $suffix;

	$cache[ $suffix ] = ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table )
		? $table
		: false;

	return $cache[ $suffix ];
}

/**
 * Get settings JSON for an Amelia entity (service or package).
 *
 * @param string $suffix Table suffix.
 * @param int    $id     Entity ID.
 * @return array
 */
function amelia_get_entity_settings( string $suffix, int $id ): array {
	global $wpdb;

	$table = amelia_table( $suffix );
	if ( ! $table ) {
		return [];
	}

	$json = $wpdb->get_var( $wpdb->prepare( "SELECT settings FROM {$table} WHERE id = %d", $id ) );

	return $json ? ( json_decode( $json, true ) ?: [] ) : [];
}

/**
 * Set the WooCommerce product ID in an Amelia entity's settings.
 *
 * @param string $suffix     Table suffix.
 * @param int    $entity_id  Entity ID.
 * @param int    $product_id WooCommerce product ID.
 */
function amelia_set_entity_product_id( string $suffix, int $entity_id, int $product_id ): void {
	global $wpdb;

	$table = amelia_table( $suffix );
	if ( ! $table ) {
		return;
	}

	$settings = amelia_get_entity_settings( $suffix, $entity_id );

	$settings['payments']['wc']['productId'] = $product_id;
	$settings['payments']['wc']['enabled']   = true;

	$wpdb->update(
		$table,
		[ 'settings' => wp_json_encode( $settings ) ],
		[ 'id' => $entity_id ],
		[ '%s' ],
		[ '%d' ]
	);
}
