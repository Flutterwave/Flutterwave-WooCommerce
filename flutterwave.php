<?php

/**
 * Plugin Name:     Flutterwave
 * Plugin URI:      https://flutterwave.com/ng
 * Description:     Official WooCommerce Plugin for Flutterwave for Business
 * Author:          Flutterwave Developers
 * Author URI:      http://developer.flutterwave.com
 * Text Domain:     flutterwave
 * Domain Path:     /languages
 * Version:         1.1.1
 * Tested up to: 5.8
 * WC tested up to: 5.5
 * WC requires at least: 2.6
 * Copyright: © 2021 Flutterwave
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

define('WC_FLUTTERWAVE_VERSION', '1.1.1');
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



/**
*get all existing plans
*
* @param int $flutterwave_data FLW SETTINGS
*/
function get_plans($flutterwave_data){

	// echo get_post_meta( get_the_ID(), 'flw_subaccount_assign', true );

	$secret_key = (isset($flutterwave_data['go_live']) && $flutterwave_data['go_live'] == 'yes')? $flutterwave_data['live_secret_key']: $flutterwave_data['test_secret_key'] ;

	// echo $auth;

	$args = array(
	  'headers' => array(
		'Content-Type'=> 'application/json',
		'Authorization' => 'Bearer '.$secret_key
	  ),
	);

	//returns all the list of subaccounts created
	$response_flw = wp_remote_get( 'https://api.flutterwave.com/v3/payment-plans', $args );

	// echo "<pre>";
	// print_r(json_decode(wp_remote_retrieve_body( $response_flw), true));
	// echo "</pre>";
	// exit();

	$flw_response_body = json_decode(wp_remote_retrieve_body( $response_flw), true);

	if ( is_wp_error( $response_flw ) ) {
	  $error_message = $response_flw->get_error_message();
	  echo "Something went wrong: $error_message";
	}else{

	  if($flw_response_body['status'] != 'success' && isset($flw_response_body['message'])){

		echo "<p>".$flw_response_body['message']."</p>";
	  }else{

		$plan_list = $flw_response_body['data'];

		echo '<label for="flw_plan_assign"> Assigned Plan:   </label>';
		echo '<select name="flw_plan_assign">';
		echo '<option value="">--Select Plan--</option>';
		foreach ($plan_list as $plan) {

				  echo '<option value="'.$plan['id'].'"'.selected( get_post_meta( get_the_ID(), 'flw_plan_assign', true ), $plan['id'], false ).'>'.$plan['name'].' </option>';

		}
		echo ' </select>';
		// foreach ($plan_list as $plan) {
		//   echo '<input type="hidden" name="flw_plan_amount_'.$plan['id'].'" value="'.$plan[''].'"/>';
		// }
	  }

	}
}


/**
* Save meta box content.
*
* @param int $post_id Post ID
*/
function flutterwave_save_paymentplan( $post_id ) {

	// echo '<pre>';
	// print_r($_POST);
	// echo '</pre>';
	// die();

	if(!empty($_POST['flw_plan_assign'])){
	  update_post_meta( $post_id, 'flw_plan_assign', $_POST['flw_plan_assign']);
	}

}

add_action( 'save_post', 'flutterwave_save_paymentplan' );


/**
* check the flutterwave settings.
*
* @param array $flw_option FLUTTERWAVE SETTINGS
*/
function check_flw_option( $flw_option){


	(empty($flw_option))? '<pre>Please Setup your Flutterwave account to assign a plan</pre>': get_plans($flw_option) ;

}

/**
* Meta box display callback.
*
* @param WP_Post $post Current post object.
*/
function flutterwave_add_plan_callback( $post_id ) {

	$flutterwave_data = get_option('woocommerce_flutterwave_settings');

	check_flw_option($flutterwave_data);

}



/**
 * Register meta box(es).
 */
function flutterwave_plan_meta_boxes() {
	add_meta_box( 'flutterwave_plan_box', 'Flutterwave - Add Payment Plan', 'flutterwave_add_plan_callback', ['product'], 'side');
}
add_action( 'add_meta_boxes', 'flutterwave_plan_meta_boxes' );
run_wc_flutterwave();