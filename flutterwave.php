<?php

/**
 * Plugin Name:     Flutterwave
 * Plugin URI:      https://flutterwave.com/ng
 * Description:     Official WooCommerce Plugin for Flutterwave for Business
 * Author:          Flutterwave Developers
 * Author URI:      http://developer.flutterwave.com
 * Text Domain:     flutterwave
 * Domain Path:     /languages
 * Version:         1.1.0
 * Tested up to: 5.8
 * WC tested up to: 5.5
 * WC requires at least: 2.6
 * Copyright: © 2021 Flutterwave
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

define('WC_FLUTTERWAVE_VERSION', '1.1.0');
define('WC_FLUTTERWAVE_PLUGIN_FILE', __FILE__);
define('WC_FLUTTERWAVE_DIR_PATH', plugin_dir_path(WC_FLUTTERWAVE_PLUGIN_FILE));
define('WC_FLUTTERWAVE_URL', trailingslashit(plugins_url('/', __FILE__)));
/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-flutterwave-activator.php
 */
function activate_wc_flutterwave()
{
	// Test to see if WooCommerce is active (including network activated).
	require_once plugin_dir_path(__FILE__) . 'includes/class-flutterwave-activator.php';
	Flutterwave_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-flutterwave-deactivator.php
 */
function deactivate_wc_flutterwave()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-flutterwave-deactivator.php';
	Flutterwave_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_wc_flutterwave');
register_deactivation_hook(__FILE__, 'deactivate_wc_flutterwave');


/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-flutterwave.php';


/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.1.0
 */
function run_wc_flutterwave()
{

	$plugin = new Flutterwave();
	$plugin->run();
}

function flw_add_action_plugin($links)
{
	$flonboarding = esc_url(get_admin_url(null, 'admin.php?page=wc-settings&tab=checkout'));
	$mylinks_flw = array(
		'<a href="' . $flonboarding . '">' . __('Settings', 'General') . '</a>',
		'<a href="https://developer.flutterwave.com/discuss" target="_blank">Support</a>'
	);
	return array_merge($links, $mylinks_flw);
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'flw_add_action_plugin');


/**
 * WooCommerce fallback notice.
 *
 * @since 4.1.2
 */
function woocommerce_flutterwave_missing_wc_notice()
{
	/* translators: 1. URL link. */
	echo '<div class="error"><p><strong>' . sprintf(esc_html__('Flutterwave requires WooCommerce to be installed and active. You can download %s here.', 'flutterwave'), '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>') . '</strong></p></div>';
}

function init_flutterwave_woocommerce()
{
	if (!class_exists('WooCommerce')) {
		add_action('admin_notices', 'woocommerce_flutterwave_missing_wc_notice');
		return;
	}

	include_once('includes/class-flutterwave-gateway.php');

	new WP_Flutterwave_Settings_Rest_Route(WC_Flutterwave_Gateway::getInstance());
	new WP_Flutterwave_Subaccounts_Rest_Route(WC_Flutterwave_Gateway::getInstance());
	new WP_Flutterwave_Transactions_Rest_Route(WC_Flutterwave_Gateway::getInstance());
	new WP_Flutterwave_Plan_Rest_Route(WC_Flutterwave_Gateway::getInstance());
	add_filter('woocommerce_payment_gateways', 'add_flutterwave_class');
}
add_action('plugins_loaded', 'init_flutterwave_woocommerce', 0);

function load_scripts($hook)
{
	$flw_woo_check = false;
	if (class_exists('woocommerce')) {
		$flw_woo_check = true;
	}

	if ($flw_woo_check) {
		wp_enqueue_script('flutterwavenew_js', WC_FLUTTERWAVE_URL . 'admin/sample/test.js');
		wp_localize_script('flutterwavenew_js', 'flutterwave_data', [
			'apiUrl' => home_url('/wp-json'),
			'nonce' => wp_create_nonce('wp_rest'),
			'hook' => $hook,
			'logo_src' => plugins_url('src/icons/flutterwave-logo.svg', __FILE__),
			'webhook' => WC()->api_request_url('Flutterwave_WC_Payment_Webhook')
		]);
	}
}
add_action('admin_enqueue_scripts', 'load_scripts');

add_action('woocommerce_blocks_loaded', 'woocommerce_flutterwave_woocommerce_blocks_support');

function woocommerce_flutterwave_woocommerce_blocks_support()
{
	if (class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
		require_once dirname(__FILE__) . '/includes/class-wc-gateway-flutterwave-blocks-support.php';
		add_action(
			'woocommerce_blocks_payment_method_type_registration',
			function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
				$payment_method_registry->register(new WC_Gateway_Flutterwave_Blocks_Support);
			}
		);
	}
}
run_wc_flutterwave();