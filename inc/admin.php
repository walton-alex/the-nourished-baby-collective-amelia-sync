<?php
/**
 * Admin product columns.
 *
 * @package tnbc-amelia-sync
 */

namespace TNBC\AmeliaSync;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add Amelia column to products list.
 *
 * @param array $columns Existing columns.
 * @return array
 */
function amelia_product_column( array $columns ): array {
	$new = [];

	foreach ( $columns as $key => $label ) {
		$new[ $key ] = $label;
		if ( 'product_cat' === $key ) {
			$new['amelia_sync'] = 'Amelia Synced';
		}
	}

	return $new;
}

/**
 * Render Amelia column content.
 *
 * @param string $column    Column name.
 * @param int    $post_id   Post ID.
 */
function amelia_product_column_content( string $column, int $post_id ): void {
	if ( 'amelia_sync' !== $column ) {
		return;
	}

	echo is_amelia_product( $post_id ) ? 'Yes' : 'No';
}

/**
 * Add styles for the Amelia column.
 */
function amelia_product_column_styles(): void {
	$screen = get_current_screen();
	if ( ! $screen || 'edit-product' !== $screen->id ) {
		return;
	}
	echo '<style>table.wp-list-table .column-amelia_sync { width: 10%; }</style>';
}
