<?php
/**
 * Amelia product CRUD and sync.
 *
 * @package tnbc-amelia-sync
 */

namespace TNBC\AmeliaSync;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Log an Amelia sync event via WooCommerce logger.
 *
 * @param string $message Log message.
 * @param array  $context Additional context data.
 */
function amelia_log( string $message, array $context = [] ): void {
	if ( ! function_exists( 'wc_get_logger' ) ) {
		return;
	}

	wc_get_logger()->info(
		$message . ( $context ? ' ' . wp_json_encode( $context ) : '' ),
		[ 'source' => 'tnbc-amelia-sync' ]
	);
}

/**
 * Get the default block template for Amelia product pages.
 *
 * @return string
 */
function amelia_product_template(): string {
	$template = <<<'BLOCKS'
<!-- wp:tnbc/image-content {"name":"tnbc/image-content","data":{"field_6a01decbf65f7":"header","field_69fca85a3053f":"","field_69fca86130540":"\u003cstrong\u003eInvestment: {{PRICE}}\u003c/strong\u003e","field_69fcb2f063637":{"field_69fcb2f063637_field_69fc9e006401f":{"row-0":{"field_69fc9e0964020":{"title":"Select this package","url":"#booking-calendar","target":""}}}},"field_6a05d19bc633a":{"row-0":{"field_69fca92e63c7a_field_69fc9f8bf9c6f":{"field_69fc992b3019b":"436","field_69fc993c3019c":"center"}}}}} /-->

<!-- wp:tnbc/content {"name":"tnbc/content","data":{"heading":"","_heading":"field_69fe02e1a5f9b","content":"","_content":"field_69fe02eda5f9c","button_group":"","_button_group":"field_69fc9e006401f","content_button_group":"","_content_button_group":"field_69fe03e910680"}} /-->

<!-- wp:tnbc/grid-content {"name":"tnbc/grid-content","data":{"heading":"","_heading":"field_6a01d5a6016ed","grid_group_0_heading":"","_grid_group_0_heading":"field_6a01d6ffb6444","grid_group_0_content":"\u003cstrong\u003eInvestment: {{PRICE}}\u003c/strong\u003e","_grid_group_0_content":"field_6a01d705b6445","grid_group_0_button_group":"","_grid_group_0_button_group":"field_6a01d71535f0e_field_69fc9e006401f","grid_group_1_heading":"","_grid_group_1_heading":"field_6a01d6ffb6444","grid_group_1_content":"","_grid_group_1_content":"field_6a01d705b6445","grid_group_1_button_group":"","_grid_group_1_button_group":"field_6a01d71535f0e_field_69fc9e006401f","grid_group":2,"_grid_group":"field_6a01d6f2b6443"}} /-->

<!-- wp:tnbc/card-content {"name":"tnbc/card-content","data":{"heading":"Support included","_heading":"field_6a01e26c0d7cc","content":"\u003cstrong\u003eInvestment: {{PRICE}}\u003c/strong\u003e","_content":"field_6a01e2740d7cd","button_group_0_button":{"title":"FAQs","url":"/faqs/","target":""},"_button_group_0_button":"field_69fc9e0964020","button_group":1,"_button_group":"field_69fc9e006401f","card_\u0026_content_button_group":"","_card_\u0026_content_button_group":"field_6a1464cfafae4","card_group_type":"image","_card_group_type":"field_6a01e5b453ae3","card_group_card_image_group_image_group_image":436,"_card_group_card_image_group_image_group_image":"field_69fc992b3019b","card_group_card_image_group_image_group_focal":"center","_card_group_card_image_group_image_group_focal":"field_69fc993c3019c","card_group_card_image_group_image_group":"","_card_group_card_image_group_image_group":"field_6a01e5d853ae4_field_69fc9f8bf9c6f","card_group_card_image_group":"","_card_group_card_image_group":"field_6a01ea1de2bb8","card_group_heading":"","_card_group_heading":"field_6a01e43a8016e","card_group_sub_heading":"Investment: {{PRICE}}","_card_group_sub_heading":"field_6a1af16d556ec","card_group_content":"","_card_group_content":"field_6a01e4488016f","card_group_button_group_0_button":{"title":"Select this package","url":"#booking-calendar","target":""},"_card_group_button_group_0_button":"field_69fc9e0964020","card_group_button_group":1,"_card_group_button_group":"field_6a01e45980170_field_69fc9e006401f","card_group":"","_card_group":"field_6a01e50dcf441","card_content_card":"","_card_content_card":"field_6a01e4348a3b2"}} /-->
BLOCKS;

	return $template;
}

/**
 * Set standard Amelia product meta on a WooCommerce product.
 *
 * @param int $product_id Product ID.
 */
function amelia_set_product_defaults( int $product_id ): void {
	wp_set_object_terms( $product_id, 'simple', 'product_type' );
	update_post_meta( $product_id, '_stock_status', 'instock' );
	update_post_meta( $product_id, 'total_sales', '0' );
	update_post_meta( $product_id, '_downloadable', 'no' );
	update_post_meta( $product_id, '_virtual', 'yes' );
	update_post_meta( $product_id, '_regular_price', '0' );
	update_post_meta( $product_id, '_sale_price', '' );
	update_post_meta( $product_id, '_purchase_note', '' );
	update_post_meta( $product_id, '_featured', 'no' );
	update_post_meta( $product_id, '_weight', '' );
	update_post_meta( $product_id, '_length', '' );
	update_post_meta( $product_id, '_width', '' );
	update_post_meta( $product_id, '_height', '' );
	update_post_meta( $product_id, '_sku', '' );
	update_post_meta( $product_id, '_product_attributes', [] );
	update_post_meta( $product_id, '_sale_price_dates_from', '' );
	update_post_meta( $product_id, '_sale_price_dates_to', '' );
	update_post_meta( $product_id, '_price', '0' );
	update_post_meta( $product_id, '_sold_individually', 'yes' );
	update_post_meta( $product_id, '_manage_stock', 'no' );
	update_post_meta( $product_id, '_backorders', 'no' );
	update_post_meta( $product_id, '_stock', '' );
}

/**
 * Check if an Amelia category ID maps to the 'pathway' WooCommerce product category.
 *
 * @param int $category_id Amelia category ID.
 * @return bool
 */
function amelia_is_pathway_category( int $category_id ): bool {
	if ( ! $category_id ) {
		return false;
	}

	global $wpdb;

	$table = amelia_table( 'amelia_categories' );
	if ( ! $table ) {
		return false;
	}

	$category_name = $wpdb->get_var( $wpdb->prepare( "SELECT name FROM {$table} WHERE id = %d", $category_id ) );

	if ( ! $category_name ) {
		return false;
	}

	$slug = sanitize_title( $category_name );
	$term = get_term_by( 'slug', $slug, 'product_cat' );

	return $term && 'pathway' === $term->slug;
}

/**
 * Assign a WooCommerce product category based on an Amelia category ID.
 *
 * @param int $product_id  WooCommerce product ID.
 * @param int $category_id Amelia category ID.
 */
function amelia_assign_product_category( int $product_id, int $category_id ): void {
	global $wpdb;

	$table = amelia_table( 'amelia_categories' );
	if ( ! $table ) {
		return;
	}

	$category_name = $wpdb->get_var( $wpdb->prepare( "SELECT name FROM {$table} WHERE id = %d", $category_id ) );

	if ( ! $category_name ) {
		return;
	}

	$slug = sanitize_title( $category_name );
	$term = get_term_by( 'slug', $slug, 'product_cat' );

	if ( ! $term ) {
		$result = wp_insert_term( $category_name, 'product_cat', [ 'slug' => $slug ] );

		if ( is_wp_error( $result ) ) {
			return;
		}

		$term_id = $result['term_id'];
	} else {
		$term_id = $term->term_id;
	}

	wp_set_object_terms( $product_id, [ (int) $term_id ], 'product_cat' );
}

/**
 * Sync the product featured image from an Amelia image URL.
 *
 * @param int    $product_id Product ID.
 * @param string $image_url  Full URL to the Amelia entity image.
 */
function amelia_sync_product_image( int $product_id, string $image_url ): void {
	if ( ! $image_url ) {
		return;
	}

	$upload_dir = wp_get_upload_dir();
	$image_path = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $image_url );

	if ( ! file_exists( $image_path ) ) {
		return;
	}

	$existing_id = get_post_thumbnail_id( $product_id );

	if ( $existing_id && wp_get_attachment_url( $existing_id ) === $image_url ) {
		return;
	}

	global $wpdb;

	$attachment_id = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE guid = %s AND post_type = 'attachment' LIMIT 1",
			$image_url
		)
	);

	if ( ! $attachment_id ) {
		$filetype      = wp_check_filetype( basename( $image_path ) );
		$attachment_id = wp_insert_attachment(
			[
				'post_mime_type' => $filetype['type'],
				'post_title'     => sanitize_file_name( basename( $image_path ) ),
				'post_status'    => 'inherit',
				'guid'           => $image_url,
			],
			$image_path,
			$product_id
		);

		if ( ! is_wp_error( $attachment_id ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
			wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $image_path ) );
		}
	}

	if ( $attachment_id && ! is_wp_error( $attachment_id ) ) {
		set_post_thumbnail( $product_id, $attachment_id );
	}
}

/**
 * Create a WooCommerce product for an Amelia entity.
 *
 * @param string $suffix      Table suffix ('amelia_services' or 'amelia_packages').
 * @param int    $entity_id   Entity ID.
 * @param string $name        Entity name for the product title.
 * @param int    $category_id Amelia category ID (0 to skip).
 */
function amelia_create_product( string $suffix, int $entity_id, string $name, int $category_id = 0, string $price = '0' ): void {
	$existing         = amelia_get_entity_settings( $suffix, $entity_id );
	$existing_product = $existing['payments']['wc']['productId'] ?? '';

	if ( $existing_product && get_post_status( $existing_product ) ) {
		return;
	}

	$is_pathway = amelia_is_pathway_category( $category_id );

	$product_id = wp_insert_post(
		[
			'post_title'   => $name,
			'post_status'  => 'publish',
			'post_type'    => 'product',
			'post_content' => $is_pathway ? wp_slash( amelia_product_template() ) : '',
		]
	);

	if ( ! $product_id || is_wp_error( $product_id ) ) {
		amelia_log( 'Product creation failed', [ 'entity_type' => $suffix, 'entity_id' => $entity_id, 'name' => $name ] );
		return;
	}

	amelia_set_product_defaults( $product_id );

	/* Woo-side meta markers — local index for fast lookups. Amelia settings remain authoritative. */
	update_post_meta( $product_id, '_tnbc_amelia_managed', 'yes' );
	update_post_meta( $product_id, '_tnbc_amelia_entity_type', $suffix );
	update_post_meta( $product_id, '_tnbc_amelia_entity_id', $entity_id );
	update_post_meta( $product_id, '_sku', sprintf( 'amelia-%s-%d', str_replace( 'amelia_', '', $suffix ), $entity_id ) );

	if ( $category_id ) {
		amelia_assign_product_category( $product_id, $category_id );
	}

	update_post_meta( $product_id, '_tnbc_amelia_price', $price );
	update_post_meta( $product_id, '_tnbc_amelia_savings', (string) amelia_calculate_savings( $suffix, $entity_id, (float) $price ) );

	amelia_set_entity_product_id( $suffix, $entity_id, $product_id );
	amelia_log( 'Product created', [ 'entity_type' => $suffix, 'entity_id' => $entity_id, 'product_id' => $product_id ] );
}

/**
 * Delete the WooCommerce product linked to an Amelia entity.
 *
 * @param string $suffix      Table suffix ('amelia_services' or 'amelia_packages').
 * @param array  $entity_data Entity data array (from Amelia's toArray()).
 */
function amelia_delete_product( string $suffix, array $entity_data ): void {
	if ( empty( $entity_data['id'] ) ) {
		return;
	}

	$settings = $entity_data['settings'] ?? [];
	if ( is_string( $settings ) ) {
		$settings = json_decode( $settings, true ) ?: [];
	}

	$product_id = $settings['payments']['wc']['productId'] ?? '';

	if ( $product_id && get_post_status( $product_id ) ) {
		wp_trash_post( (int) $product_id );
		amelia_log( 'Product trashed', [ 'entity_type' => $suffix, 'entity_id' => $entity_data['id'], 'product_id' => (int) $product_id ] );
	}
}

/**
 * Sync a WooCommerce product for an Amelia entity (update title/category, or create if missing).
 *
 * @param string $suffix      Table suffix ('amelia_services' or 'amelia_packages').
 * @param int    $entity_id   Entity ID.
 * @param string $name        Entity name for the product title.
 * @param int    $category_id Amelia category ID (0 to skip).
 */
function amelia_sync_product( string $suffix, int $entity_id, string $name, int $category_id = 0, string $description = '', string $image_url = '', string $price = '0' ): void {
	static $in_progress = false;

	if ( $in_progress ) {
		return;
	}

	$in_progress = true;

	try {
		$settings   = amelia_get_entity_settings( $suffix, $entity_id );
		$product_id = $settings['payments']['wc']['productId'] ?? '';

		/* If Amelia lost the link, try to find existing product by meta. */
		if ( ! $product_id || ! get_post_status( $product_id ) ) {
			$existing = get_posts(
				[
					'post_type'      => 'product',
					'post_status'    => 'any',
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'meta_query'     => [
						[
							'key'   => '_tnbc_amelia_entity_type',
							'value' => $suffix,
						],
						[
							'key'   => '_tnbc_amelia_entity_id',
							'value' => $entity_id,
						],
					],
				]
			);

			if ( $existing ) {
				$product_id = $existing[0];
				amelia_set_entity_product_id( $suffix, $entity_id, (int) $product_id );
			} else {
				amelia_create_product( $suffix, $entity_id, $name, $category_id, $price );
				$settings   = amelia_get_entity_settings( $suffix, $entity_id );
				$product_id = $settings['payments']['wc']['productId'] ?? '';

				if ( ! $product_id ) {
					return;
				}
			}
		}

		/* Ensure meta markers are set. */
		update_post_meta( (int) $product_id, '_tnbc_amelia_managed', 'yes' );
		update_post_meta( (int) $product_id, '_tnbc_amelia_entity_type', $suffix );
		update_post_meta( (int) $product_id, '_tnbc_amelia_entity_id', $entity_id );

		if ( $name ) {
			wp_update_post(
				[
					'ID'           => (int) $product_id,
					'post_title'   => $name,
					'post_excerpt' => wp_strip_all_tags( $description ),
				]
			);
		}

		if ( $category_id ) {
			amelia_assign_product_category( (int) $product_id, $category_id );
		}

		if ( $image_url ) {
			amelia_sync_product_image( (int) $product_id, $image_url );
		}

		/* Sync Amelia price to product meta for frontend use (Woo _price stays 0). */
		update_post_meta( (int) $product_id, '_tnbc_amelia_price', $price );
		update_post_meta( (int) $product_id, '_tnbc_amelia_savings', (string) amelia_calculate_savings( $suffix, $entity_id, (float) $price ) );

		amelia_log( 'Product synced', [ 'entity_type' => $suffix, 'entity_id' => $entity_id, 'product_id' => (int) $product_id ] );
	} finally {
		$in_progress = false;
	}
}

/**
 * Calculate savings from Amelia tables (used during sync only).
 *
 * @param string $suffix    Table suffix.
 * @param int    $entity_id Entity ID.
 * @param float  $price     Package price.
 * @return float
 */
function amelia_calculate_savings( string $suffix, int $entity_id, float $price ): float {
	if ( 'amelia_packages' !== $suffix || ! $entity_id ) {
		return 0.0;
	}

	global $wpdb;

	$services_table = amelia_table( 'amelia_services' );
	$pivot_table    = amelia_table( 'amelia_packages_to_services' );

	if ( ! $services_table || ! $pivot_table ) {
		return 0.0;
	}

	$services_total = (float) $wpdb->get_var( $wpdb->prepare(
		"SELECT SUM( s.price * ps.quantity ) FROM {$pivot_table} ps INNER JOIN {$services_table} s ON s.id = ps.serviceId WHERE ps.packageId = %d",
		$entity_id
	) );

	$savings = $services_total - $price;

	return $savings > 0 ? $savings : 0.0;
}
