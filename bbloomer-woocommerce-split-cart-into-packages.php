<?php

/*
 * Plugin Name: Business Bloomer WooCommerce Split Cart Into Packages
 * Description: Split a package by shipping class, category, tag, weight, dimensions, attribute, tax class or price.
 * Plugin URI: https://www.businessbloomer.com/shop/plugins/woocommerce-split-cart-into-packages/
 * Update URI: https://www.businessbloomer.com/shop/plugins/woocommerce-split-cart-into-packages/
 * Author: Business Bloomer
 * Author URI: https://www.businessbloomer.com
 * Text Domain: bbloomer-woocommerce-split-cart-into-packages
 * Requires Plugins: woocommerce
 * Version: 0.1.20250428
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

// Return the wp_options option names - used in a number of functions.
function bbwscip_settings_option_name_when_split() {
	return 'bb_wscip_ws';
}

function bbwscip_settings_option_name_split_criteria() {
	return 'bb_wscip_sc';
}

// Return the possible options for when to split the order.
function bbwscip_when_split_options() {
	return array( 'cart'      => __( 'Split the order when viewing cart (default)', 'bbloomer-woocommerce-split-cart-into-packages' ),
				  'thankyou'  => __( 'Split after order placed (in Thank Youu page)', 'bbloomer-woocommerce-split-cart-into-packages' ),
				  'vieworder' => __( 'Split when viewing order', 'bbloomer-woocommerce-split-cart-into-packages' ), );
}
// Return the possible options for what to use to split an order.
function bbwscip_split_criteria_options() {
	return array( 'class'  => __( 'Shipping class (default)', 'bbloomer-woocommerce-split-cart-into-packages' ),
				  'category'=> __( 'Product category', 'bbloomer-woocommerce-split-cart-into-packages' ),
				  'tag'=> __( 'Product tag', 'bbloomer-woocommerce-split-cart-into-packages' ),
				  'weight'=> __( 'Product weight', 'bbloomer-woocommerce-split-cart-into-packages' ),
				  'dimensions'=> __( 'Product dimensions', 'bbloomer-woocommerce-split-cart-into-packages' ),
				  'attribute'=> __( 'Product attribute', 'bbloomer-woocommerce-split-cart-into-packages' ),
				  'tax_class'=> __( 'Tax class', 'bbloomer-woocommerce-split-cart-into-packages' ),
				  'price'=> __( 'Product price', 'bbloomer-woocommerce-split-cart-into-packages' ), );
}
// Return the possible options for whether to enable Javascript.
function bbwscip_enable_js_options() {
	return array( 'yes'  => __( 'Yes, so timer counts down (default)', 'bbloomer-woocommerce-split-cart-into-packages' ),
				  'no'    => __( 'No, so timer does not change', 'bbloomer-woocommerce-split-cart-into-packages' ) );
}

add_action( 'woocommerce_init', 'bbwscip_set_split_hook' );
function bbwscip_set_split_hook() {
	$when_split = get_option( bbwscip_settings_option_name_when_split(), 'class' );

	if ( 'cart' == $when_split ) {
		add_filter( 'woocommerce_cart_shipping_packages', 'bbwscip_split_packages_at_cart' );
	}
	if ( 'thankyou' == $when_split ) {
	}
	if ( 'vieworder' == $when_split ) {
	}
}

// Split the order into packages when view the cart.
function bbwscip_split_packages_at_cart( $packages ) {
	$destination = $packages[0]['destination'];
	$user = $packages[0]['user'];
	$applied_coupons = $packages[0]['applied_coupons'];
	$packages = array();

	foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
		$key = $cart_item['data']->get_shipping_class_id();
		$packages[ $key ]['contents'][ $cart_item_key ] = $cart_item;
	}

	foreach ( $packages as $index => $package ) {
		$total = array_sum( wp_list_pluck( $packages[ $index ]['contents'], 'line_total' ) );
		$packages[ $index ]['destination'] = $destination;
		$packages[ $index ]['user'] = $user;
		$packages[ $index ]['applied_coupons'] = $applied_coupons;
		$packages[ $index ]['contents_cost'] = $total;
	}

	return $packages;
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
		[
			'title' => __( 'When split package?', 'bbloomer-woocommerce-split-cart-into-packages' ),
			'desc' => __( 'Choose when to split the package.', 'bbloomer-woocommerce-split-cart-into-packages' ),
			'type' => 'select',
			'id' => bbwscip_settings_option_name_when_split(),
			'class' => 'wc-enhanced-select',
			'default' => 'cart',
			'options' => bbwscip_when_split_options(),
			'autoload' => false,
		],
		[
			'title'    => 'Split criteria',
			'desc'     => __( 'Choose what criteria to use to split the order into packages', 'bbloomer-woocommerce-split-cart-into-packages' ),
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