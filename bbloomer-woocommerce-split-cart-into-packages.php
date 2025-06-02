<?php

/*
 * Plugin Name: Business Bloomer WooCommerce Split Cart Into Packages
 * Description: Let customers choose different shipping methods at checkout by automatically splitting the cart into "packages" based on shipping class, category, weight, and more.
 * Plugin URI: https://www.businessbloomer.com/shop/plugins/woocommerce-split-cart-into-packages/
 * Update URI: https://www.businessbloomer.com/shop/plugins/woocommerce-split-cart-into-packages/
 * Author: Business Bloomer
 * Author URI: https://www.businessbloomer.com
 * Text Domain: bbloomer-woocommerce-split-cart-into-packages
 * Requires Plugins: woocommerce
 * Version: 0.3.20250602
 */

defined( 'ABSPATH' ) || exit;

define( 'BBWCSCIP', 'https://www.businessbloomer.com/wp-json/bb/v1/downloads?product_id=252335' ); // BBloomer Read Product ID

add_filter( 'plugin_row_meta', 'bbwcscip_hide_view_details', 9999, 4 );

function bbwcscip_hide_view_details( $plugin_meta, $plugin_file, $plugin_data, $status ) {
	if ( isset( $plugin_data['TextDomain'] ) && $plugin_data['TextDomain'] == plugin_basename( __DIR__ ) ) unset( $plugin_meta[2] );
	return $plugin_meta;
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'bbwcscip_action_links', 9999, 4 );

function bbwcscip_action_links( $links, $plugin_file, $plugin_data, $context ) {
	$plugin_links = array(
		'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=bb&section=bbwcscip' ) . '">' . __( 'Settings', 'woocommerce' ) . '</a>',
		'<a href="' . $plugin_data['PluginURI'] . '#tab-docs" target="_blank">' . __( 'Docs', 'woocommerce' ) . '</a>',
		'<a href="https://www.businessbloomer.com/contact-rodolfo/?title=' . rawurlencode( $plugin_data['Name'] ) . rawurlencode( ' ' ) . $plugin_data['Version'] . '#plugin" target="_blank">' . __( 'Get Support', 'woocommerce' ) . '</a>',
		'<a href="' . $plugin_data['PluginURI'] . '#tab-reviews" target="_blank">' . __( 'Add a review', 'woocommerce' ) . '</a>',
	);
	return array_merge( $plugin_links, $links );
}

function bbwcscip_plugin_view_version_details( $res, $action, $args ) {
	if ( 'plugin_information' !== $action ) return $res;
	if ( $args->slug !== plugin_basename( __DIR__ ) ) return $res;
	$response = wp_remote_get( BBWCSCIP, array(  'headers' => array( 'Accept' => 'application/json' ) ) );
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
    $response = wp_remote_get( BBWCSCIP, array(  'headers' => array( 'Accept' => 'application/json' ) ) );    
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

// Return the wp_options option names - used in a number of functions.
function bbwcscip_settings_option_name_when_split() {
	return 'bb_wscip_ws';
}
function bbwcscip_settings_option_name_split_criteria() {
	return 'bb_wscip_sc';
}
function bbwcscip_show_packages_option() {
	return 'bb_wscip_sp';
}

// Return the possible options for when to split the order.
function bbwcscip_when_split_options() {
	return apply_filters( 'bbwcscip_when_split_options',
				array(	'cart'      => __( 'Split the order when viewing cart (default)', 'bbloomer-woocommerce-split-cart-into-packages' ),
					//	'thankyou'  => __( 'Split after order placed (in Thank You page)', 'bbloomer-woocommerce-split-cart-into-packages' ),
					//	'vieworder' => __( 'Split when viewing order', 'bbloomer-woocommerce-split-cart-into-packages' ),
				)
			);
}
// Return the possible options for what to use to split an order.
function bbwcscip_split_criteria_options() {
	return apply_filters( 'bbwcscip_split_criteria_options',
				array( 'class'  => __( 'Shipping class (default)', 'bbloomer-woocommerce-split-cart-into-packages' ),
				  'category'=> __( 'Product category', 'bbloomer-woocommerce-split-cart-into-packages' ),
				  'tag'=> __( 'Product tag', 'bbloomer-woocommerce-split-cart-into-packages' ),
				  'attribute'=> __( 'Product attribute', 'bbloomer-woocommerce-split-cart-into-packages' ),
				  'tax_class'=> __( 'Tax class', 'bbloomer-woocommerce-split-cart-into-packages' ),
				  //'weight'=> __( 'Product weight', 'bbloomer-woocommerce-split-cart-into-packages' ),
				  //'dimensions'=> __( 'Product dimensions', 'bbloomer-woocommerce-split-cart-into-packages' ),
				  //'price'=> __( 'Product price', 'bbloomer-woocommerce-split-cart-into-packages' ),
				)
				);
}

add_action( 'woocommerce_init', 'bbwcscip_set_split_hook' );
function bbwcscip_set_split_hook() {
	// Allow add-ons disable cart split in favour of their own timing e.g. add_filter( 'bbwcscip_split_in_cart', '__return_false' );
	if ( 'cart' == get_option( bbwcscip_settings_option_name_when_split(), 'cart' ) ) {
		add_filter( 'woocommerce_cart_shipping_packages', 'bbwcscip_split_packages_at_cart' );
	}

	// Display the package id/name/label on the View Order page and in order emails.
	if ( 'yes' == get_option( bbwcscip_show_packages_option(), 'no' ) ) {
		add_action( 'woocommerce_order_details_after_order_table', 'bbwcscip_display_order_packages_full', 10 );
		add_action( 'woocommerce_email_after_order_table', 'bbwcscip_display_order_packages_full', 10 );
	}
}

// Split the order into packages when view the cart.
function bbwcscip_split_packages_at_cart( $packages ) {
	$split_by = get_option( bbwcscip_settings_option_name_split_criteria(), 'class' );
//error_log( 'DEBUG: Split by: ' . $split_by );

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
		add_filter( 'woocommerce_shipping_package_name', 'bbwcscip_package_name', 10, 3 );
	}

	$cart_split = false;
	foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
		if ( 'class' == $split_by ) {
			$key = $cart_item['data']->get_shipping_class_id();
			$packages[ $key ]['contents'][ $cart_item_key ] = $cart_item;
			$cart_split = true;
//error_log( sprintf( 'DEBUG: Shipping class %d: %s', $key, $cart_item['data']->get_name() ) );
		}
		if ( 'category' == $split_by ) {
			$cats = $cart_item['data']->get_category_ids();
			if ( empty( $cats ) ) {
				$cats[0] = 0;  // Need a numeric value for $packages[].
			}
//error_log( sprintf( 'DEBUG: %s: $cats[0]: %s', $cart_item['data']->get_name(), var_export( $cats[0], true ) ) );
//error_log( 'DEBUG: $cats[0]: ' . var_export( $cats[0], true ) );
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
//error_log( sprintf( 'DEBUG: $att_slug: %s; $att_name: %s', $att_slug, $att_name ) );
					break;
				}
				
			}
//error_log( 'DEBUG: $first_attribute: ' . $first_attribute );
			$packages[ $first_attribute ]['contents'][ $cart_item_key ] = $cart_item;
			$cart_split = true;
//error_log( sprintf( 'DEBUG: Attributes: (%s): %s', $cart_item['data']->get_name(), var_export( $cart_item['data']->get_attributes(), true ) ) );
		}
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

// Store the package ID with the order item so that it can be retrieved later.
add_action( 'woocommerce_checkout_create_order_line_item', 'bbwcscip_save_package_id_to_order_items', 10, 4 );
function bbwcscip_save_package_id_to_order_items( $item, $cart_item_key, $values, $order ) {
	$packages = WC()->shipping()->get_packages();
	if ( is_array( $packages ) ) {
		foreach ( $packages as $package_id => $package ) {
			if ( isset( $package['contents'][ $cart_item_key ] ) ) {
				$item->add_meta_data( '_package_id', $package_id, true );
			}
		}
	}
}

// Store the package name/id/label with the shipping item.
add_action( 'woocommerce_checkout_create_order_shipping_item', 'bbwcscip_save_shipping_method_package_id', 10, 4 );
function bbwcscip_save_shipping_method_package_id( $shipping_item, $package_key, $package, $order ) {
	if ( has_filter( 'woocommerce_shipping_package_name' ) ) {
		$custom_name = apply_filters( 'woocommerce_shipping_package_name', '', $package_key, $package );
	} else {
		$custom_name = sprintf( __( 'Package %d', 'woocommerce' ), $package_key + 1 );
	}

	$shipping_item->add_meta_data( '_package_name', $custom_name, true );
	$shipping_item->add_meta_data( '_package_id', $package_key, true );
	$shipping_item->add_meta_data( '_package_label', $shipping_item->get_name(), true );
}

// Display the package id/name/label on the View Order page and in order emails.
function bbwcscip_display_order_packages_full( $order ) {
	
	if ( ! is_a( $order, 'WC_Order' ) ) return;
	
	$items_by_package = [];
	$shipping_by_package = [];

	// Group items by package
	foreach ( $order->get_items() as $item_id => $item ) {
		$package_id = $item->get_meta( '_package_id', true );
		if ( $package_id === '' ) $package_id = 0; // fallback
		$items_by_package[ $package_id ][ $item_id ] = $item;
	}

	// Get shipping methods by package
	foreach ( $order->get_items( 'shipping' ) as $shipping_item_id => $shipping_item ) {
		$package_id = $shipping_item->get_meta( '_package_id', true );
		$package_name = $shipping_item->get_meta( '_package_name', true );
		$method_label = $shipping_item->get_meta( '_package_label', true );
		if ( $package_id !== '' ) {
			$shipping_by_package[ $package_id ] = [ $method_label, $package_name ];
		}
	}

	// Bail if nothing to show
	if ( empty( $items_by_package ) ) return;

	// If running the order email action then add some CSS for the ul element.
	$ul_css = '';
	if ( 'woocommerce_email_after_order_table' == current_action() ) {
		$ul_css = 'style="list-style-type: none; padding-left: 0;"';
	}
	echo '<h2>' . __( 'Shipping Packages', 'woocommerce' ) . '</h2>';
	
	echo '<ul class="products wc-block-product-template__responsive columns-3" ', $ul_css, ' >';

	foreach ( $items_by_package as $package_id => $items ) {
		
		echo '<li class="product" style="border: 1px solid #ccc; padding: 0.5em; text-align: left; width: 31%; float: left; margin-bottom: 1em; margin-right: 2%;">';
		
		if ( isset( $shipping_by_package[ $package_id ] ) ) {
			echo '<p><span class="dashicons dashicons-open-folder"></span><strong>' . esc_html( $shipping_by_package[ $package_id ][1] ) . '</strong></p>';
			echo '<p>' . __( 'Shipping Method', 'woocommerce' ) . ': ' . esc_html( $shipping_by_package[ $package_id ][0] ) . '</p>';
		}

		echo '<ul>';
		foreach ( $items as $item ) {
			echo '<li>' . $item->get_name() . ' × ' . $item->get_quantity() . '</li>';
		}
		echo '</ul>';
		
		echo '</li>';
		
	}
	
	echo '</ul>';
	
}

// Change order meta keys into readable text.
add_filter( 'woocommerce_order_item_get_formatted_meta_data', 'bbwcscip_readable_order_meta_display_key', 10, 2 );
function bbwcscip_readable_order_meta_display_key( $formatted_meta, $order_obj ) {
//error_log( 'DEBUG: bbwcscip_readable_order_meta_display_key: $formatted_meta: ' . var_export( $formatted_meta, true ) );
	
	$readable_package_meta = array(
		'_package_id'    => __( 'Package ID', 'bbloomer-woocommerce-split-cart-into-packages' ),
		'_package_name'  => __( 'Package name', 'bbloomer-woocommerce-split-cart-into-packages' ),
		'_package_label' => __( 'Package label', 'bbloomer-woocommerce-split-cart-into-packages' ),
	);
	
	foreach ( $formatted_meta as &$meta_item ) {
		if ( array_key_exists( $meta_item->key, $readable_package_meta ) ) {
			$meta_item->display_key = $readable_package_meta[ $meta_item->key ];
		}
	}

	return $formatted_meta;
}

// Change package name to include the shipping class or product category name.
function bbwcscip_package_name( $package_name, $i, $package ) {
	$split_by = get_option( bbwcscip_settings_option_name_split_criteria(), 'class' );
//error_log( 'DEBUG: (Package name filter) Split by: ' . $split_by );
//error_log( sprintf( 'DEBUG: $i: %d, $package_name: %s', $i, $package_name ) );

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
//error_log( sprintf( 'DEBUG: $i: %d, $tag: %s', $i, var_export( $tag, true ) ) );
		if ( $tag ) {
			return sprintf( 'Shipping (%s)', $tag->name );
		}
		else {
			return $package_name;
		}
	}


	return $package_name;
}

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
	$quick_links = bbwcscip_action_links( array(), '', $plugin_data, '' );
	echo '<p><b>&bull; ' . substr( $plugin_data['Name'], 16 ) . '</b> ';
	echo $plugin_data['Description'];
	echo ' Quick links: ' . implode( ' - ', $quick_links ) . '</p>';
}, 9999 );

// Create subtab for this plugin under Business Bloomer tab
add_filter( 'woocommerce_get_sections_bb', function( $sections ) {
	$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . plugin_basename( __FILE__ ) );
	$sections['bbwcscip'] = substr( $plugin_data['Name'], 16 );
	return $sections;
});

// Plugin settings under Business Bloomer tab
add_filter('woocommerce_get_settings_bb', function( $settings, $current_section ) {
	if ( $current_section !== 'bbwcscip' ) return $settings;
	$new_settings = [
		[
			'type' => 'bbwcscip_html_wrapper',
			'id' => 'bbwcscip_custom_settings_page_start',
			'content' => bbwcscip_custom_settings_start(),
		],
		['type' => 'title'],
		[
			'title' => __( 'When split cart?', 'bbloomer-woocommerce-split-cart-into-packages' ),
			'desc' => __( 'Choose when to split the cart into multiple carts (and orders).', 'bbloomer-woocommerce-split-cart-into-packages' ),
			'type' => 'select',
			'id' => bbwcscip_settings_option_name_when_split(),
			'class' => 'wc-enhanced-select',
			'default' => 'cart',
			'options' => bbwcscip_when_split_options(),
			'autoload' => false,
		],
		[
			'title'    => 'Split criteria',
			'desc'     => __( 'Choose what criteria to use to split the cart into multiple carts.', 'bbloomer-woocommerce-split-cart-into-packages' ),
			'type'     => 'select',
			'id'       => bbwcscip_settings_option_name_split_criteria(),
			'class'    => 'wc-enhanced-select',
			'default'  => 'class',
			'options'  => bbwcscip_split_criteria_options(),
			'autoload' => false,
		],
		[
			'title' => __( 'Show packages after checkout?', 'bbloomer-woocommerce-split-cart-into-packages' ),
			'desc' => __( 'Whether to show the shipping packages on the Thank You page, My Account/View Order page and the order emails.', 'bbloomer-woocommerce-split-cart-into-packages' ),
			'type' => 'checkbox',
			'id' => bbwcscip_show_packages_option(),
			'default' => 'no',
		],
		['type' => 'sectionend', 'id' => 'bbwcscip_custom_settings_end'],
	];
	return array_merge( $settings, $new_settings );
}, 9999, 2 );

add_action( 'woocommerce_admin_field_bbwcscip_html_wrapper', function( $value ) {
	echo wp_kses_post( $value['content'] );
}, 10, 1);

function bbwcscip_custom_settings_start() {
	$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . plugin_basename(__FILE__) );
	$quick_links = bbwcscip_action_links( [], '', $plugin_data, '' );
	array_shift( $quick_links );
	return '<h1 id="bbwcscip">' . $plugin_data['Name'] . ' <small>v ' . $plugin_data['Version'] . '</small></h1><p>' . $plugin_data['Description'] . '</p><h4>Quick links: ' . implode(' - ', $quick_links) . '</h4>';
}
