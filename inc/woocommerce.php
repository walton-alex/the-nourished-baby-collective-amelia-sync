<?php
/**
 * WooCommerce integration for Amelia products.
 *
 * @package tnbc-amelia-sync
 */

namespace TNBC\AmeliaSync;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Inject Amelia price into WooCommerce product price for Amelia-managed products.
 *
 * @param string      $price   Product price.
 * @param \WC_Product $product Product object.
 * @return string
 */
function inject_amelia_price( $price, $product ): string {
	if ( ! is_amelia_product( $product->get_id() ) ) {
		return $price;
	}

	$amelia_price = get_post_meta( $product->get_id(), '_tnbc_amelia_price', true );

	return $amelia_price ? $amelia_price : $price;
}

/**
 * Strip product description/short description in cart/checkout/mini-cart.
 * Only allow on the single product frontend template render.
 *
 * @param string      $description Product description.
 * @param \WC_Product $product     Product object.
 * @return string
 */
function strip_product_description_in_cart( string $description, $product ): string {
	if ( wp_is_serving_rest_request() || defined( 'DOING_AJAX' ) || defined( 'REST_REQUEST' ) ) {
		return '';
	}

	if ( ! is_singular( 'product' ) ) {
		return '';
	}

	return $description;
}

/**
 * Filter Amelia cart item metadata to only show package/service name and time.
 *
 * @param array $item_data Cart item data.
 * @param array $cart_item Cart item.
 * @return array
 */
function filter_amelia_cart_item_data( array $item_data, $cart_item ): array {
	if ( empty( $cart_item['ameliabooking'] ) ) {
		return $item_data;
	}

	foreach ( $item_data as &$data ) {
		if ( empty( $data['value'] ) ) {
			continue;
		}

		$lines = preg_split( '/\R/', $data['value'] );
		$filtered = array_filter(
			$lines,
			function ( $line ) {
				return preg_match( '/<strong>\s*(service|Local Time)\s*:/', $line );
			}
		);

		$data['value'] = implode( "\n", $filtered );
		$data['value'] = preg_replace( '/<strong>\s*service\s*:<\/strong>/', '<strong>Support</strong>', $data['value'] );
		$data['value'] = preg_replace( '/<strong>\s*Local Time\s*:<\/strong>\s*/', '<br/>', $data['value'] );
	}

	return $item_data;
}

/**
 * Check if a WooCommerce order contains only Amelia-managed products.
 *
 * @param \WC_Order $order Order object.
 * @return bool
 */
function order_is_amelia_only( $order ): bool {
	if ( ! $order instanceof \WC_Order ) {
		return false;
	}

	$items = $order->get_items();
	if ( empty( $items ) ) {
		return false;
	}

	foreach ( $items as $item ) {
		if ( ! is_amelia_product( $item->get_product_id() ) ) {
			return false;
		}
	}

	return true;
}

/**
 * Suppress WooCommerce order email recipients for Amelia-only orders.
 * Returns empty string to prevent the email from sending.
 *
 * @param string    $recipient Email recipient.
 * @param \WC_Order $order     Order object.
 * @return string
 */
function suppress_woo_email_for_amelia_order( $recipient, $order ): string {
	if ( $order instanceof \WC_Order && order_is_amelia_only( $order ) ) {
		return '';
	}

	return $recipient;
}

/**
 * Hide Amelia line items from WooCommerce order emails (mixed carts).
 * Amelia handles its own booking notification for those items.
 *
 * @param array     $items Item rows (id => item).
 * @param \WC_Order $order Order object.
 * @return array
 */
function hide_amelia_items_from_order_email( $items, $order ) {
	if ( ! $order instanceof \WC_Order ) {
		return $items;
	}

	foreach ( $items as $item_id => $item ) {
		if ( $item instanceof \WC_Order_Item_Product && is_amelia_product( $item->get_product_id() ) ) {
			unset( $items[ $item_id ] );
		}
	}

	return $items;
}

/**
 * Enable the Amelia item filter before the order table renders in emails.
 *
 * @param \WC_Order $order Order object.
 */
function enable_amelia_item_filter( $order ): void {
	add_filter( 'woocommerce_order_get_items', __NAMESPACE__ . '\\hide_amelia_items_from_order_email', 10, 2 );
}

/**
 * Disable the Amelia item filter after the order table renders in emails.
 *
 * @param \WC_Order $order Order object.
 */
function disable_amelia_item_filter( $order ): void {
	remove_filter( 'woocommerce_order_get_items', __NAMESPACE__ . '\\hide_amelia_items_from_order_email', 10 );
}

/**
 * Connect WooCommerce hooks for Amelia integration.
 *
 * @return void
 */
function init_woocommerce(): void {
	/* Suppress WooCommerce order emails for Amelia-only orders (Amelia sends its own). */
	$suppress_email_types = [
		'woocommerce_email_recipient_new_order',
		'woocommerce_email_recipient_customer_processing_order',
		'woocommerce_email_recipient_customer_completed_order',
		'woocommerce_email_recipient_customer_on_hold_order',
	];
	foreach ( $suppress_email_types as $filter ) {
		add_filter( $filter, __NAMESPACE__ . '\\suppress_woo_email_for_amelia_order', 10, 2 );
	}

	/* Strip Amelia items from Woo order emails in mixed carts. */
	add_action( 'woocommerce_email_before_order_table', __NAMESPACE__ . '\\enable_amelia_item_filter', 10, 1 );
	add_action( 'woocommerce_email_after_order_table', __NAMESPACE__ . '\\disable_amelia_item_filter', 10, 1 );

	/* Inject Amelia price into WooCommerce product price getters. */
	add_filter( 'woocommerce_product_get_price', __NAMESPACE__ . '\\inject_amelia_price', 10, 2 );
	add_filter( 'woocommerce_product_get_regular_price', __NAMESPACE__ . '\\inject_amelia_price', 10, 2 );

	/* Remove product description from cart/checkout metadata (blocks render as HTML). */
	add_filter( 'woocommerce_product_get_description', __NAMESPACE__ . '\\strip_product_description_in_cart', 10, 2 );
	add_filter( 'woocommerce_product_get_short_description', __NAMESPACE__ . '\\strip_product_description_in_cart', 10, 2 );
	add_filter( 'woocommerce_get_item_data', __NAMESPACE__ . '\\filter_amelia_cart_item_data', 20, 2 );

	/* Enable block editor only for pathway products. */
	add_filter(
		'use_block_editor_for_post',
		function ( $use, $post ) {
			if ( 'product' === get_post_type( $post ) ) {
				return is_amelia_product( $post->ID ) && has_term( 'pathway', 'product_cat', $post->ID );
			}
			return $use;
		},
		10,
		2
	);

	/* Disable quick edit for all products. */
	add_filter(
		'post_row_actions',
		function ( $actions, $post ) {
			if ( 'product' === $post->post_type ) {
				unset( $actions['inline hide-if-no-js'] );
			}
			return $actions;
		},
		10,
		2
	);

	/* Remove WooCommerce product data metabox for Amelia products - Amelia source of truth. */
	add_action(
		'add_meta_boxes',
		function () {
			$post_id = get_the_ID();

			if ( $post_id && is_amelia_product( $post_id ) ) {
				remove_meta_box( 'woocommerce-product-data', 'product', 'normal' );
				remove_meta_box( 'postexcerpt', 'product', 'normal' );
				remove_meta_box( 'product_catdiv', 'product', 'side' );
				remove_meta_box( 'postimagediv', 'product', 'side' );
				remove_meta_box( 'woocommerce-product-images', 'product', 'side' );
				remove_post_type_support( 'product', 'thumbnail' );
			}

			/* Disable product editor globally; only Amelia pathway products re-enable Gutenberg above. */
			remove_post_type_support( 'product', 'editor' );

			/* Disable reviews globally. */
			remove_meta_box( 'commentsdiv', 'product', 'normal' );
		},
		50
	);

	/* Prevent category and featured image changes for Amelia products. */
	add_filter(
		'rest_pre_insert_product',
		function ( $prepared_post, $request ) {
			if ( ! empty( $prepared_post->ID ) && is_amelia_product( $prepared_post->ID ) ) {
				$request->offsetUnset( 'product_cat' );
				$request->offsetUnset( 'featured_media' );
			}
			return $prepared_post;
		},
		10,
		2
	);

	/* Hide category and featured image panels in block editor for Amelia products. */
	add_action(
		'enqueue_block_editor_assets',
		function () {
			global $post;

			if ( ! $post || 'product' !== $post->post_type || ! is_amelia_product( $post->ID ) ) {
				return;
			}

			wp_add_inline_script(
				'wp-edit-post',
				"wp.domReady( function() {
					var dispatch = wp.data.dispatch('core/editor');
					if ( dispatch.removeEditorPanel ) {
						dispatch.removeEditorPanel('taxonomy-panel-product_cat');
						dispatch.removeEditorPanel('taxonomy-panel-product_tag');
						dispatch.removeEditorPanel('featured-image');
					}
					var editPost = wp.data.dispatch('core/edit-post');
					if ( editPost.removeEditorPanel ) {
						editPost.removeEditorPanel('post-excerpt');
					}
				});"
			);
		}
	);

	/* Redirect Route non-pathway products away from their single page. */
	add_action(
		'template_redirect',
		function () {
			if ( ! is_singular( 'product' ) ) {
				return;
			}

			$product_id = get_the_ID();
			$is_amelia  = is_amelia_product( $product_id );

			/* Pathway - Keep page. */
			if ( $is_amelia && has_term( 'pathway', 'product_cat', $product_id ) ) {
				return;
			}

			/* Additional Support - Redirect to booking calendar page. */
			if ( $is_amelia ) {
				$service_id   = get_amelia_service_for_product( $product_id );
				$booking_page = get_field( 'booking_page', 'option' ) ?: home_url( '/pathway/' );

				wp_safe_redirect( add_query_arg( 'service', $service_id, $booking_page ), 301 );
				exit;
			}

			/* Other - Add to cart only via deliberate POST with nonce. */
			if (
				isset( $_POST['tnbc_add_to_cart_nonce'] )
				&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tnbc_add_to_cart_nonce'] ) ), 'tnbc_add_to_cart' )
			) {
				WC()->cart->add_to_cart( $product_id );
				wp_safe_redirect( wc_get_checkout_url(), 302 );
				exit;
			}

			/* GET request — redirect to shop. */
			wp_safe_redirect( get_permalink( wc_get_page_id( 'shop' ) ), 302 );
			exit;
		}
	);

	/* Noindex Route non-pathway products and exclude from Yoast sitemap. */
	add_filter(
		'wpseo_exclude_from_sitemap_by_post_ids',
		function ( $excluded ) {
			$pathway_term = get_term_by( 'slug', 'pathway', 'product_cat' );
			$pathway_ids  = $pathway_term
				? get_posts( [
					'post_type'      => 'product',
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'tax_query'      => [ [ 'taxonomy' => 'product_cat', 'terms' => $pathway_term->term_id ] ],
				] )
				: [];

			$all_products = get_posts( [
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			] );

			return array_merge( $excluded, array_diff( $all_products, $pathway_ids ) );
		}
	);

	add_filter(
		'wpseo_robots',
		function ( $robots ) {
			if ( ! is_singular( 'product' ) ) {
				return $robots;
			}

			$product_id = get_the_ID();

			/* Pathway - Keep indexed. */
			if ( is_amelia_product( $product_id ) && has_term( 'pathway', 'product_cat', $product_id ) ) {
				return $robots;
			}

			/* Not Pathway - Remove from index. */
			return 'noindex, nofollow';
		}
	);

	/* Replace {{PRICE}} in product content with the current Amelia entity price. */
	add_filter(
		'the_content',
		function ( $content ) {
			if ( ! is_singular( 'product' ) || strpos( $content, '{{PRICE}}' ) === false ) {
				return $content;
			}

			$product_id = get_the_ID();
			$product    = wc_get_product( $product_id );
			$formatted  = $product ? $product->get_price_html() : '';
			$rate       = (float) apply_filters( 'yay_currency_rate', 1 );

			/* Package product — show its own savings. */
			$savings = get_amelia_package_savings( $product_id );
			if ( $savings > 0 ) {
				$formatted .= ' <span class="tnbc-savings">(save ' . wc_price( $savings * $rate ) . ')</span>';
			}

			/* Service product — show upgrade to package option. */
			if ( ! $savings ) {
				$package = get_package_for_service_product( $product_id );
				if ( $package ) {
					$pkg_name = get_the_title( $package['product_id'] );
					$pkg_url  = get_permalink( $package['product_id'] );
					$formatted .= sprintf(
						'<br/><span>or <a href="%s">book as a package</a> and save %s</span>',
						esc_url( $pkg_url ),
						wc_price( $package['savings'] * $rate )
					);
				}
			}

			return str_replace( '{{PRICE}}', $formatted, $content );
		}
	);

	/* Anchor back to booking calendar after successful checkout. */
	add_filter(
		'woocommerce_get_return_url',
		function ( $url, $order ) {
			if ( ! $order ) {
				return $url;
			}

			foreach ( $order->get_items() as $item ) {
				if ( is_amelia_product( $item->get_product_id() ) ) {
					return $url . '#booking-calendar';
				}
			}

			return $url;
		},
		10,
		2
	);
}
