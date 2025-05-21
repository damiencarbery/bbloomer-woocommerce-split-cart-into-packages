<?php

/*
 * Plugin Name: Business Bloomer WooCommerce Split Cart Into Packages
 * Description: Split a cart (aka package) by shipping class, category, tag, weight, dimensions, attribute, tax class or price.
 * Plugin URI: https://www.businessbloomer.com/shop/plugins/woocommerce-split-cart-into-packages/
 * Update URI: https://www.businessbloomer.com/shop/plugins/woocommerce-split-cart-into-packages/
 * Author: Business Bloomer
 * Author URI: https://www.businessbloomer.com
 * Text Domain: bbloomer-woocommerce-split-cart-into-packages
 * Requires Plugins: woocommerce
 * Version: 0.1.20250521
 */

defined( 'ABSPATH' ) || exit;

define( 'BBWSCIP', 'https://www.businessbloomer.com/wp-json/bb/v1/downloads?product_id=XXXXXX' ); // BBloomer Read Product ID

add_filter( 'plugin_row_meta', 'bbwscip_hide_view_details', 9999, 4 );

function bbwscip_hide_view_details( $plugin_meta, $plugin_file, $plugin_data, $status ) {
	if ( isset( $plugin_data['TextDomain'] ) && $plugin_data['TextDomain'] == plugin_basename( __DIR__ ) ) unset( $plugin_meta[2] );
	return $plugin_meta;
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'bbwscip_action_links', 9999, 4 );

function bbwscip_action_links( $links, $plugin_file, $plugin_data, $context ) {
	$plugin_links = array(
		'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=bb&section=bbwscip' ) . '">' . __( 'Settings', 'woocommerce' ) . '</a>',
		'<a href="' . $plugin_data['PluginURI'] . '#tab-docs" target="_blank">' . __( 'Docs', 'woocommerce' ) . '</a>',
		'<a href="https://www.businessbloomer.com/contact-rodolfo/?title=' . rawurlencode( $plugin_data['Name'] ) . rawurlencode( ' ' ) . $plugin_data['Version'] . '#plugin" target="_blank">' . __( 'Get Support', 'woocommerce' ) . '</a>',
		'<a href="' . $plugin_data['PluginURI'] . '#tab-reviews" target="_blank">' . __( 'Add a review', 'woocommerce' ) . '</a>',
	);
	return array_merge( $plugin_links, $links );
}

function bbwscip_plugin_view_version_details( $res, $action, $args ) {
	if ( 'plugin_information' !== $action ) return $res;
	if ( $args->slug !== plugin_basename( __DIR__ ) ) return $res;
	$response = wp_remote_get( BBWSCIP, array(  'headers' => array( 'Accept' => 'application/json' ) ) );
	if ( ( ! is_wp_error( $response ) ) && ( 200 === wp_remote_retrieve_response_code( $response ) ) ) {
		$download = json_decode( wp_remote_retrieve_body( $response ), true );
	} else return $res;
	if ( $product['downloadable'] < 1 ) return $res;
	$res = new stdClass();
	$res->name = $download['name'];
	$res->slug = plugin_basename( __DIR__ );
	$res->path = plugin_basename( __DIR__ ) . '/' . plugin_basename( __DIR__ ) . '.php';
	$res->version = $download['version'];
	$res->download_link = $download['download_link'];
	$res->sections = array(
		'description' => $product['short_description'],
		'changelog' => '<h3>Version ' . $res->version . '</h3><ul><li>Fix: plugin header, product ID, text domain, save button</li></ul>',
	);
	return $res;
}

add_filter( 'update_plugins_www.businessbloomer.com', function( $update, array $plugin_data, string $plugin_file, $locales ) {    
    if ( $plugin_file !== plugin_basename( __DIR__ ) . '/' . plugin_basename( __DIR__ ) . '.php' ) return $update;
	if ( ! empty( $update ) ) return $update;
    $response = wp_remote_get( BBWSCIP, array(  'headers' => array( 'Accept' => 'application/json' ) ) );    
    if ( ( ! is_wp_error( $response ) ) && ( 200 === wp_remote_retrieve_response_code( $response ) ) ) {        
        $download = json_decode( wp_remote_retrieve_body( $response ), true );    
    } else return $update;      
    if ( ! version_compare( $plugin_data['Version'], $download['version'], '<' ) ) return $update;    
    return [        
        'slug' => plugin_basename( __DIR__ ),        
        'version' => $download['version'],        
        'url' => $plugin_data['PluginURI'],        
        'package' => $download['download_link'],    
    ];
}, 9999, 4 );

/*
// Return the wp_options option names - used in a number of functions.
function bbwscip_settings_option_name_when_split() {
	return 'bb_wscip_ws';
}
*/
function bbwscip_settings_option_name_split_criteria() {
	return 'bb_wscip_sc';
}

/*
// Return the possible options for when to split the order.
function bbwscip_when_split_options() {
	return array( 'cart'      => __( 'Split the order when viewing cart (default)', 'bbloomer-woocommerce-split-cart-into-packages' ),
				  'thankyou'  => __( 'Split after order placed (in Thank You page)', 'bbloomer-woocommerce-split-cart-into-packages' ),
				  //'vieworder' => __( 'Split when viewing order', 'bbloomer-woocommerce-split-cart-into-packages' ),
				);
}
*/
// Return the possible options for what to use to split an order.
function bbwscip_split_criteria_options() {
	return array( 'class'  => __( 'Shipping class (default)', 'bbloomer-woocommerce-split-cart-into-packages' ),
				  'category'=> __( 'Product category', 'bbloomer-woocommerce-split-cart-into-packages' ),
				  'tag'=> __( 'Product tag', 'bbloomer-woocommerce-split-cart-into-packages' ),
				  //'weight'=> __( 'Product weight', 'bbloomer-woocommerce-split-cart-into-packages' ),
				  //'dimensions'=> __( 'Product dimensions', 'bbloomer-woocommerce-split-cart-into-packages' ),
				  'attribute'=> __( 'Product attribute', 'bbloomer-woocommerce-split-cart-into-packages' ),
				  'tax_class'=> __( 'Tax class', 'bbloomer-woocommerce-split-cart-into-packages' ),
				  //'price'=> __( 'Product price', 'bbloomer-woocommerce-split-cart-into-packages' ),
				);
}
// Return the possible options for whether to enable Javascript.
function bbwscip_enable_js_options() {
	return array( 'yes'  => __( 'Yes, so timer counts down (default)', 'bbloomer-woocommerce-split-cart-into-packages' ),
				  'no'    => __( 'No, so timer does not change', 'bbloomer-woocommerce-split-cart-into-packages' ) );
}

add_action( 'woocommerce_init', 'bbwscip_set_split_hook' );
function bbwscip_set_split_hook() {
	// Allow add-ons disable cart split in favour of their own timing e.g. add_filter( 'bbwscip_split_in_cart', '__return_false' );
	if ( apply_filters( 'bbwscip_split_in_cart', true ) ) {
		add_filter( 'woocommerce_cart_shipping_packages', 'bbwscip_split_packages_at_cart' );
	}
}

// Split the order into packages when view the cart.
function bbwscip_split_packages_at_cart( $packages ) {
	$split_by = get_option( bbwscip_settings_option_name_split_criteria(), 'class' );
//error_log( 'Split by: ' . $split_by );

	$destination = $packages[0]['destination'];
	$user = $packages[0]['user'];
	$applied_coupons = $packages[0]['applied_coupons'];
// ToDo: Would it be safer to create a new array and return it if the cart is split.
// If the cart is not split then can return original $packages array.
	$packages = array();
	
	$all_attributes = array( 0 => 'none' );  // Store all product attributes (as $packages needs numeric keys).

	// Change package name to include the category, tag or shipping class name.
	// This is not run for 'attribute' as the string for the attribute cannot be retrieved.
	if ( in_array( $split_by, array( 'class', 'category', 'tag' ) ) ) {
		add_filter( 'woocommerce_shipping_package_name', 'bbwscip_package_name', 10, 3 );
	}

	$cart_split = false;
	foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
		if ( 'class' == $split_by ) {
			$key = $cart_item['data']->get_shipping_class_id();
			$packages[ $key ]['contents'][ $cart_item_key ] = $cart_item;
			$cart_split = true;
		}
		if ( 'category' == $split_by ) {
			$cats = $cart_item['data']->get_category_ids();
			if ( empty( $cats ) ) {
				$cats[0] = 0;  // Need a numeric value for $packages[].
			}
//error_log( sprintf( '%s: $cats[0]: %s', $cart_item['data']->get_name(), var_export( $cats[0], true ) ) );
//error_log( '$cats[0]: ' . var_export( $cats[0], true ) );
			$packages[ $cats[0] ]['contents'][ $cart_item_key ] = $cart_item;
			$cart_split = true;
		}
		if ( 'tag' == $split_by ) {
			$tags = $cart_item['data']->get_tag_ids();
			// Handle when the product does not have any tags.
			if ( empty( $tags ) ) {
				$tags[0] = 0;  // Need a numeric value for $packages[].
			}
			$packages[ $tags[0] ]['contents'][ $cart_item_key ] = $cart_item;
			$cart_split = true;
		}
		if ( 'attribute' == $split_by ) {
			$attributes = $cart_item['data']->get_attributes();
			$first_attribute = null;
			// Handle when the product does not have any attributes.
			if ( empty( $attributes ) ) {
				$first_attribute = 0;  // Need a numeric value for $packages[].
			}
			else {
				foreach ( $attributes as $att_slug => $att_name ) {
					// If the attribute is in $all_attributes then get the index for use with $packages.
					// Otherwise append the attribute to the end of $all_attributes and get that new index.
					$key = array_search( $att_slug, $all_attributes );
					if ( $key ) {
						$first_attribute = $key;
					}
					else {
						$all_attributes[] = $att_slug;
						$first_attribute = count( $all_attributes ) - 1; // Get key of last element in array.
					}
//error_log( sprintf( '$att_slug: %s; $att_name: %s', $att_slug, $att_name ) );
					break;
				}
				
			}
//error_log( '$first_attribute: ' . $first_attribute );
			$packages[ $first_attribute ]['contents'][ $cart_item_key ] = $cart_item;
			$cart_split = true;
//error_log( sprintf( 'Attributes: (%s): %s', $cart_item['data']->get_name(), var_export( $cart_item['data']->get_attributes(), true ) ) );
		}

//$attributes = $cart_item['data']->get_attributes();
//$tax_class = $cart_item['data']->get_tax_class();
//$tags = $cart_item['data']->get_tag_ids();
	}

	if ( $cart_split ) {
		foreach ( $packages as $index => $package ) {
			$total = array_sum( wp_list_pluck( $packages[ $index ]['contents'], 'line_total' ) );
			$packages[ $index ]['destination'] = $destination;
			$packages[ $index ]['user'] = $user;
			$packages[ $index ]['applied_coupons'] = $applied_coupons;
			$packages[ $index ]['contents_cost'] = $total;
		}
	}

	return $packages;
}

// Change package name to include the shipping class or product category name.
function bbwscip_package_name( $package_name, $i, $package ) {
	$split_by = get_option( bbwscip_settings_option_name_split_criteria(), 'class' );
//error_log( '(Package name filter) Split by: ' . $split_by );
//error_log( sprintf( '$i: %d, $package_name: %s', $i, $package_name ) );

	if ( 'class' == $split_by ) {
		$wc_shipping = WC_Shipping::instance();
		foreach ( $wc_shipping->get_shipping_classes() as $class ) {
			if ( $i == $class->term_id ) {
				return sprintf( 'Shipping (%s)', $class->name );
			}
		}
	}
	if ( 'category' == $split_by  ) {
		$cat = get_term_by( 'id', $i, 'product_cat' );
		return sprintf( 'Shipping (%s)', $cat->name );
	}
	if ( 'tag' == $split_by  ) {
		$tag = get_term_by( 'id', $i, 'product_tag' );
//error_log( sprintf( '$i: %d, $tag: %s', $i, var_export( $tag, true ) ) );
		if ( $tag ) {
			return sprintf( 'Shipping (%s)', $tag->name );
		}
		else {
			return $package_name;
		}
	}


	return $package_name;
}
/*
function bbwscip_split_packages_after_checkout( $order_id ) {
	$order = wc_get_order( $order_id );
	if ( ! $order || $order->get_meta( '_order_split' ) ) return;

	$split_by = get_option( bbwscip_settings_option_name_split_criteria(), 'class' );
	$items_by_shipping_class = array();

	foreach ( $order->get_items() as $item_id => $item ) {
		$product = $item->get_product();
		$class_id = $product->get_shipping_class_id();
		$items_by_shipping_class[$class_id][$item_id] = $item;
	}

	if ( count( $items_by_shipping_class ) > 1 ) {
		foreach ( array_slice( $items_by_shipping_class, 1 ) as $class_id => $items ) {
			$new_order = wc_create_order();
			$new_order->set_address( $order->get_address( 'billing' ), 'billing' );
			if ( $order->needs_shipping_address() ) $new_order->set_address( $order->get_address( 'shipping' ) ?? $order->get_address( 'billing' ), 'shipping' );

			foreach ( $items as $item_id => $item ) {
				$new_item = new WC_Order_Item_Product();
				$new_item->set_product( $item->get_product() );
				$new_item->set_quantity( $item->get_quantity() );
				$new_item->set_total( $item->get_total() );
				$new_item->set_subtotal( $item->get_subtotal() );
				$new_item->set_tax_class( $item->get_tax_class() );
				$new_item->set_taxes( $item->get_taxes() );

				foreach ( $item->get_meta_data() as $meta ) {
					$new_item->add_meta_data( $meta->key, $meta->value, true );
				}

				$new_order->add_item( $new_item );
				$order->remove_item( $item_id );
			}

			$new_order->add_order_note( sprintf( 'Split from order <a href="%s">%d</a>.', $order->get_edit_order_url(), $order_id ) );
			$new_order->calculate_totals();
			$new_order->set_payment_method( $order->get_payment_method() );
			$new_order->set_payment_method_title( $order->get_payment_method_title() );
			$new_order->update_status( $order->get_status() );

			$order->calculate_totals();
			$order->update_meta_data( '_order_split', true );
			$order->save();
		}
	}
}
*/
// Add Business Bloomer tab to Woo settings if it doesnt exist yet
add_filter( 'woocommerce_get_settings_pages', function( $settings ) {
	if ( ! class_exists( 'WC_Settings_BB' ) ) {
		class WC_Settings_BB extends WC_Settings_Page {
			function __construct() {
				$this->id = 'bb';
				$this->label = 'Business Bloomer';
				parent::__construct();
			}
			protected function get_settings_for_default_section() {
				$settings = array(
					array(
						'title' => 'Business Bloomer Mini-Plugins',
						'desc' => 'Here is the list of your active mini-plugins:',
						'type' => 'title',
					),
					array(
						'type' => 'sectionend',
					),
				);
				return $settings;
			}
		}
		$settings[] = new WC_Settings_BB();
	}
	return $settings;
});

// Add this plugin info to Business Bloomer tab > General
add_action( 'woocommerce_settings_bb', function() {
	if ( ! empty( $_GET[ 'section' ] ) ) return;
	$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . plugin_basename( __FILE__ ) );
	$quick_links = bbwscip_action_links( array(), '', $plugin_data, '' );
	echo '<p><b>&bull; ' . substr( $plugin_data['Name'], 16 ) . '</b> ';
	echo $plugin_data['Description'];
	echo ' Quick links: ' . implode( ' - ', $quick_links ) . '</p>';
}, 9999 );

// Create subtab for this plugin under Business Bloomer tab
add_filter( 'woocommerce_get_sections_bb', function( $sections ) {
	$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . plugin_basename( __FILE__ ) );
	$sections['bbwscip'] = substr( $plugin_data['Name'], 16 );
	return $sections;
});

// Plugin settings under Business Bloomer tab
add_filter('woocommerce_get_settings_bb', function( $settings, $current_section ) {
	if ( $current_section !== 'bbwscip' ) return $settings;
	$new_settings = [
		[
			'type' => 'bbwscip_html_wrapper',
			'id' => 'bbwscip_custom_settings_page_start',
			'content' => bbwscip_custom_settings_start(),
		],
		['type' => 'title'],
/*		[
			'title' => __( 'When split cart?', 'bbloomer-woocommerce-split-cart-into-packages' ),
			'desc' => __( 'Choose when to split the cart into multiple carts (and orders).', 'bbloomer-woocommerce-split-cart-into-packages' ),
			'type' => 'select',
			'id' => bbwscip_settings_option_name_when_split(),
			'class' => 'wc-enhanced-select',
			'default' => 'cart',
			'options' => bbwscip_when_split_options(),
			'autoload' => false,
		],
*/
		[
			'title'    => 'Split criteria',
			'desc'     => __( 'Choose what criteria to use to split the cart into multiple carts.', 'bbloomer-woocommerce-split-cart-into-packages' ),
			'type'     => 'select',
			'id'       => bbwscip_settings_option_name_split_criteria(),
			'class'    => 'wc-enhanced-select',
			'default'  => 'class',
			'options'  => bbwscip_split_criteria_options(),
			'autoload' => false,
		],
		['type' => 'sectionend', 'id' => 'bbwscip_custom_settings_end'],
	];
	return array_merge( $settings, $new_settings );
}, 9999, 2 );

add_action( 'woocommerce_admin_field_bbwscip_html_wrapper', function( $value ) {
	echo wp_kses_post( $value['content'] );
}, 10, 1);

function bbwscip_custom_settings_start() {
	$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . plugin_basename(__FILE__) );
	$quick_links = bbwscip_action_links( [], '', $plugin_data, '' );
	array_shift( $quick_links );
	return '<h1 id="bbwscip">' . $plugin_data['Name'] . ' <small>v ' . $plugin_data['Version'] . '</small></h1><p>' . $plugin_data['Description'] . '</p><h4>Quick links: ' . implode(' - ', $quick_links) . '</h4>';
}