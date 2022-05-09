<?php

/**
   * Main Flutterwave Gateway Class
*/

if( ! defined( 'ABSPATH' ) ) { exit; }
// if ( !class_exists( 'WC_Payment_Gateway' ) ) return;
require_once (WC_FLUTTERWAVE_DIR_PATH. "SDK/library/flutterwave.php");
require_once (WC_FLUTTERWAVE_DIR_PATH . 'includes/eventHandler.php' );

use FLW_SDK\FlutterwaveSdk;

class WC_Flutterwave_Gateway extends WC_Payment_Gateway
{
	public static $instance;

	/**
	 * Set of parameters to build the URL to the gateway's settings page.
	 *
	 * @var string[]
	 */
	private static $settings_url_params = [
		'page'    => 'wc-settings',
		'tab'     => 'checkout',
		'section' => 'flutterwave',
	];

	public static function getInstance() {

        if (!isset(self::$instance)) {
            self::$instance = new WCFG();
        }

        return self::$instance;
    }

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct() {

        $this->base_url = 'https://api.flutterwave.com/v3/';
        $this->id = 'flutterwave';
        // $this->icon = plugin_dir_url(__FILE__) . 'images/logo.png';
        $this->icon = '';
        $this->has_fields         = false;
        $this->method_title       = __( 'Flutterwave for WooCommerce', 'flutterwave' );
        $this->method_description = __( 'Flutterwave allows you to accept payment from cards and bank accounts in multiple currencies. You can also accept payment offline via USSD and POS.', 'flutterwave' );
        $this->supports = array(
          'products',
        );

        $this->init_form_fields();
        $this->init_settings();

        $this->title        = $this->get_option( 'title' );
        $this->description  = $this->get_option( 'description' );
        $this->enabled      = $this->get_option( 'enabled' );
        $this->test_public_key   = $this->get_option( 'test_public_key' );
        $this->test_secret_key   = $this->get_option( 'test_secret_key' );
        $this->live_public_key   = $this->get_option( 'live_public_key' );
        $this->live_secret_key   = $this->get_option( 'live_secret_key' );
        $this->auto_complete_order = get_option('autocomplete_order');
        $this->go_live      = $this->get_option( 'go_live' );
        $this->payment_options = $this->get_option( 'payment_options' );
        $this->payment_style = $this->get_option( 'payment_style' );
        $this->barter = $this->get_option( 'barter' );
        $this->logging_option = $this->get_option('logging_option');
        $this->secret_hash = $this->get_option( 'secret_hash' );
		$this->plan_id = '';
        $this->country ="";
        $this->supports = array(
          'products',
          'tokenization',
        );
        add_action( 'woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
        add_action( 'woocommerce_api_wc_flutterwave_gateway', array($this, 'flw_verify_payment'));
		// Webhook listener/API hook
		add_action( 'woocommerce_api_flutterwave_wc_payment_webhook', array($this, 'flutterwave_webhooks'));
		add_action( 'woocommerce_after_checkout_validation', array($this, 'validate_checkout_values'), 10, 2);
        // if ( is_admin() ) {
        //   add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        // }

        $this->public_key   = $this->test_public_key;
        $this->secret_key   = $this->test_secret_key;

        if ( 'yes' === $this->go_live ) {
          // $this->base_url = 'https://api.ravepay.co';
          $this->public_key   = $this->live_public_key;
          $this->secret_key   = $this->live_secret_key;

        }

		add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts' ) );
    }

    # Build the administration fields for this specific Gateway
	public function init_form_fields()

	{
		$this->form_fields = array(
			'enabled' => array(
			  'title'       => __( 'Enable/Disable', 'flutterwave' ),
			  'label'       => __( 'Enable  Flutterwave', 'flutterwave' ),
			  'type'        => 'checkbox',
			  'description' => __( 'Enable Flutterwave as a payment option on the checkout page', 'flutterwave' ),
			  'default'     => 'no',
			  'desc_tip'    => true
			),
			'go_live' => array(
			  'title'       => __( 'Mode', 'flutterwave' ),
			  'label'       => __( 'Live mode', 'flutterwave' ),
			  'type'        => 'checkbox',
			  'description' => __( 'Check this box if you\'re using your live keys.', 'flutterwave' ),
			  'default'     => 'no',
			  'desc_tip'    => true
			),
			'logging_option' => array(
			  'title'       => __( 'Disable Logging', 'flutterwave' ),
			  'label'       => __( 'Disable Logging', 'flutterwave' ),
			  'type'        => 'checkbox',
			  'description' => __( 'Check this box if you\'re disabling logging.', 'flutterwave' ),
			  'default'     => 'no',
			  'desc_tip'    => true
			),
			'barter' => array(
			  'title'       => __( 'Disable Barter', 'flutterwave' ),
			  'label'       => __( 'Disable Barter', 'flutterwave' ),
			  'type'        => 'checkbox',
			  'description' => __( 'Check the box if you want to disable barter.', 'flutterwave' ),
			  'default'     => 'no',
			  'desc_tip'    => true
			),
			'webhook' => array(
			  'title'       => __( 'Webhook Instruction', 'flutterwave' ),
			  'type'        => 'hidden',
			  'description' => __( 'Please copy this webhook URL and paste on the webhook section on your dashboard <strong style="color: red"><pre><code>'.WC()->api_request_url('Flw_WC_Payment_Webhook').'</code></pre></strong> (<a href="https://rave.flutterwave.com/dashboard/settings/webhooks" target="_blank">Rave Account</a>)', 'flutterwave' ),
			),
			'secret_hash' => array(
			  'title'       => __( 'Enter Secret Hash', 'flutterwave' ),
			  'type'        => 'text',
			  'description' => __( 'Ensure that <b>SECRET HASH</b> is the same with the one on your Rave dashboard', 'flutterwave' ),
			  'default'     => 'Flutterwave-Secret-Hash'
			),
			'title' => array(
			  'title'       => __( 'Payment method title', 'flutterwave' ),
			  'type'        => 'text',
			  'description' => __( 'Optional', 'flutterwave' ),
			  'default'     => 'Flutterwave'
			),
			'description' => array(
			  'title'       => __( 'Payment method description', 'flutterwave' ),
			  'type'        => 'text',
			  'description' => __( 'Optional', 'flutterwave' ),
			  'default'     => 'Powered by Flutterwave: Accepts Mastercard, Visa, Verve, Discover, AMEX, Diners Club and Union Pay.'
			),
			'test_public_key' => array(
			  'title'       => __( 'Flutterwave Test Public Key', 'flutterwave' ),
			  'type'        => 'text',
			  // 'description' => __( 'Required! Enter your Rave test public key here', 'flutterwave' ),
			  'default'     => ''
			),
			'test_secret_key' => array(
			  'title'       => __( 'Flutterwave Test Secret Key', 'flutterwave' ),
			  'type'        => 'text',
			  // 'description' => __( 'Required! Enter your Rave test secret key here', 'flutterwave' ),
			  'default'     => ''
			),
			'live_public_key' => array(
			  'title'       => __( 'Flutterwave Live Public Key', 'flutterwave' ),
			  'type'        => 'text',
			  // 'description' => __( 'Required! Enter your Rave live public key here', 'flutterwave' ),
			  'default'     => ''
			),
			'live_secret_key' => array(
			  'title'       => __( 'Flutterwave Live Secret Key', 'flutterwave' ),
			  'type'        => 'text',
			  // 'description' => __( 'Required! Enter your Rave live secret key here', 'flutterwave' ),
			  'default'     => ''
			),
			'payment_style' => array(
			  'title'       => __( 'Payment Style on checkout', 'flutterwave' ),
			  'type'        => 'select',
			  'description' => __( 'Optional - Choice of payment style to use. Either inline or redirect. (Default: inline)', 'flutterwave' ),
			  'options'     => array(
				'inline' => esc_html_x( 'Popup(Keep payment experience on the website)', 'payment_style', 'flutterwave' ),
				'redirect'  => esc_html_x( 'Redirect',  'payment_style', 'flutterwave' ),
			  ),
			  'default'     => 'inline'
			),
			'autocomplete_order'               => array(
			  'title'       => __( 'Autocomplete Order After Payment', 'flutterwave' ),
			  'label'       => __( 'Autocomplete Order', 'flutterwave' ),
			  'type'        => 'checkbox',
			  'class'       => 'wc-flw-autocomplete-order',
			  'description' => __( 'If enabled, the order will be marked as complete after successful payment', 'flutterwave' ),
			  'default'     => 'no',
			  'desc_tip'    => true,
			),
			'payment_options' => array(
			  'title'       => __( 'Payment Options', 'flutterwave' ),
			  'type'        => 'select',
			  'description' => __( 'Optional - Choice of payment method to use. Card, Account etc.', 'flutterwave' ),
			  'options'     => array(
				'' => esc_html_x( 'Default', 'payment_options', 'flutterwave' ),
				'card'  => esc_html_x( 'Card Only',  'payment_options', 'flutterwave' ),
				'account'  => esc_html_x( 'Account Only',  'payment_options', 'flutterwave' ),
				'ussd'  => esc_html_x( 'USSD Only',  'payment_options', 'flutterwave' ),
				'qr'  => esc_html_x( 'QR Only',  'payment_options', 'flutterwave' ),
				'mpesa'  => esc_html_x( 'Mpesa Only',  'payment_options', 'flutterwave' ),
				'mobilemoneyghana'  => esc_html_x( 'Ghana MM Only',  'payment_options', 'flutterwave' ),
			  ),
			  'default'     => ''
			),

		  );

	}

	/**
     * Process payment at checkout
     *
     * @return int $order_id
     */
    public function process_payment( $order_id ) {

		$order = wc_get_order( $order_id );

		return array(
		  'result'   => 'success',
		  'redirect' => $order->get_checkout_payment_url( true )
		);

	}

	/**
	 * Whether the current page is the Flutterwave settings page.
	 *
	 * @return bool
	 */
	public static function is_current_page_settings() {
		return count( self::$settings_url_params ) === count( array_intersect_assoc( $_GET, self::$settings_url_params ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}


	/**
     * Checkout receipt page
     *
     * @return void
     */
    public function receipt_page( $order ) {

		$order = wc_get_order( $order );
		echo '<p>'.__( 'Thank you for your order, please click the <b>Make Payment</b> button below to make payment. You will be redirected to a secure page where you can enter you card details or bank account details. <b>Please, do not close your browser at any point in this process.</b>', 'flutterwave' ).'</p>';
		echo '<a class="button cancel" href="' . esc_url( $order->get_cancel_order_url() ) . '">';
		echo __( 'Cancel order &amp; restore cart', 'flutterwave' ) . '</a> ';
		echo '<button class="button alt  wc-forward" id="flw-pay-now-button">Make Payment</button> ';

	}

	/**
     * Loads (enqueue) static files (js & css) for the checkout page
     *
     * @return void
     */
    public function load_scripts() {

		if ( ! is_checkout_pay_page() ) return;

		$p_key = $this->public_key;
		$payment_options = $this->payment_options;

		wp_enqueue_script( 'flutterwave_js', plugins_url( 'public/js/flutterwave.js',  WC_FLUTTERWAVE_PLUGIN_FILE), array( 'jquery' ), '1.1.0', true );

		if( get_query_var( 'order-pay' ) ) {
		  $order_key = urldecode( sanitize_text_field($_REQUEST['key']) );
		  $order_id  = absint( get_query_var( 'order-pay' ) );
		  $cb_url = WC()->api_request_url( 'WC_Flutterwave_Gateway' ).'?flutterwave_id='.$order_id;

		  if( $this->payment_style == 'inline'){
			$cb_url = WC()->api_request_url('WC_Flutterwave_Gateway');
		  }

		  $order     = wc_get_order( $order_id );

		  $txnref    = "WOOC_" . $order_id . '_' . time();
		  $txnref    = filter_var($txnref, FILTER_SANITIZE_STRING);//sanitize this field

		  if (version_compare(WOOCOMMERCE_VERSION, '2.7.0', '>=')){
				$amount    = $order->get_total();
				$email     = $order->get_billing_email();
				$currency     = $order->get_currency();
				$main_order_key = $order->get_order_key();
		  }else{
			  $args = array(
				  'name'    => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
				  'email'   => $order->get_billing_email(),
				  'contact' => $order->get_billing_phone(),
			  );
			  $amount    = $order->get_total();
			  $main_order_key = $order->get_order_key();
			  $email     = $order->get_billing_email();
			  $currency     = $order->get_currency();
		  }


		  $items = $order->get_items();
          foreach ( $items as $item ) {
            $product_name = $item->get_name();
            $product_id = (int)$item->get_product_id();
				if(!empty(get_post_meta( $product_id, 'flw_plan_assign', true )))
				{
					$this->plan_id = get_post_meta( $product_id, 'flw_plan_assign', true );
				}
			}
		  // $amount    = $order->order_total;
		  // $email     = $order->billing_email;
		  // $currency     = $order->get_order_currency();

		  //set the currency to route to their countries
		  switch ($currency) {
			  case 'KES':
				$this->country = 'KE';
				break;
			  case 'GHS':
				$this->country = 'GH';
				break;
			  case 'ZAR':
				$this->country = 'ZA';
				break;
			  case 'TZS':
				$this->country = 'TZ';
				break;

			  default:
				$this->country = 'NG';
				break;
		  }

		  $country  = $this->country;
		  $payment_style  = $this->payment_style;

		  if ( $main_order_key == $order_key ) {

			$payment_args = compact( 'amount', 'email', 'txnref', 'p_key', 'currency', 'country', 'payment_options','cb_url','payment_style');
			$payment_args['desc']   = filter_var($this->description, FILTER_SANITIZE_STRING);
			$payment_args['title']  = filter_var($this->title, FILTER_SANITIZE_STRING);
			// $payment_args['logo'] = filter_var($this->modal_logo, FILTER_SANITIZE_URL);
			$payment_args['firstname'] = $order->get_billing_first_name();
			$payment_args['lastname'] = $order->get_billing_last_name();
			$payment_args['barter'] = $this->barter;
			$payment_args['plan'] = (empty($this->plan_id)) ? '' : (int) $this->plan_id;
		  }

		  update_post_meta( $order_id, '_flw_payment_txn_ref', $txnref );

		}

		wp_localize_script( 'flutterwave_js', 'flw_payment_args', $payment_args );

	}



	    /**
     * Verify payment made on the checkout page
     *
     * @return void
     */
    public function flw_verify_payment() {
		$logger = wc_get_logger();
		$publicKey = $this->public_key;
		$secretKey = $this->secret_key;
		$logging_option = $this->logging_option;

		$overrideRef = true;
		if(isset($_GET['flutterwave_id']) && urldecode( $_GET['flutterwave_id'] )){
		  $order_id = urldecode( sanitize_text_field($_GET['flutterwave_id']) );

		  if(!$order_id){
			$order_id = urldecode( sanitize_text_field($_GET['order_id']) );
		  }
		  $order = wc_get_order( $order_id );

		  $redirectURL =  WC()->api_request_url( 'WC_Flutterwave_Gateway' ).'?order_id='.$order_id;

		  $ref = uniqid("WOOC_". $order_id."_".time()."_");

		  	echo wp_get_script_tag(
			array(
					'id'        => 'flutterwave-v3-inline',
					'src'       => esc_url( 'https://checkout.flutterwave.com/v3.js' ),
				)
			);

		  $payment = new FlutterwaveSdk($publicKey, $secretKey, $ref, $overrideRef);

		  // if($this->modal_logo){
		  //   $rave_m_logo = $this->modal_logo;
		  // }

		  //set variables
		  $modal_desc = $this->description != '' ? filter_var($this->description, FILTER_SANITIZE_STRING) : "Payment for Order ID: $order_id on ". get_bloginfo('name');
		  $modal_title = $this->title != '' ? filter_var($this->title, FILTER_SANITIZE_STRING) : get_bloginfo('name');

		  // Make payment
		  $payment
		  ->eventHandler(new myEventHandler($order))
		  ->setAmount($order->get_total())
		  ->setPaymentOptions($this->payment_options) // value can be card, account or both
		  ->setDescription($modal_desc)
		  ->setOrderId($order_id)
		  ->setTitle($modal_title)
		  ->setCountry($this->country)
		  ->setCurrency($order->get_currency())
		  ->setEmail($order->get_billing_email())
		  ->setFirstname($order->get_billing_first_name())
		  ->setLastname($order->get_billing_last_name())
		  ->setPhoneNumber($order->get_billing_phone())
		  ->setDisableBarter($this->barter)
		  ->setRedirectUrl($redirectURL)
		  // ->setMetaData(array('metaname' => 'SomeDataName', 'metavalue' => 'SomeValue')) // can be called multiple times. Uncomment this to add meta datas
		  // ->setMetaData(array('metaname' => 'SomeOtherDataName', 'metavalue' => 'SomeOtherValue')) // can be called multiple times. Uncomment this to add meta datas
		  ->initialize();
		  die();
		}else{
		  if(isset($_GET['cancelled']) && isset($_GET['order_id'])){
			if(!$order_id){
			  $order_id = urldecode( sanitize_text_field($_GET['order_id']) );
			}
			$order = wc_get_order( $order_id );
			$redirectURL = $order->get_checkout_payment_url( true );
			header("Location: ".$redirectURL);
			die();
		  }

		  if (isset($_GET['tx_ref']) && isset($_GET['transaction_id']) ) {
			  $txn_ref = urldecode(sanitize_text_field($_GET['tx_ref']));
			  $txn_transaction_id = urldecode(sanitize_text_field($_GET['transaction_id']));
			  $o = explode('_', $txn_ref);
			  $order_id = intval( $o[1] );
			  $order = wc_get_order( $order_id );
			  $payment = new FlutterwaveSdk($publicKey, $secretKey, $txn_ref, $overrideRef);

			  $logger->notice('Payment completed. Now requerying payment.');

			  $payment->eventHandler(new myEventHandler($order))->requeryTransaction(urldecode($txn_ref),urldecode($txn_transaction_id));

			  $redirect_url = $this->get_return_url( $order );
			  header("Location: ".$redirect_url);
			  die();
		  }else{
			$payment = new FlutterwaveSdk($publicKey, $secretKey, $txn_ref, $overrideRef);

			$logger->notice('Error with requerying payment.');

			// $payment->eventHandler(new myEventHandler($order))->doNothing();
			  die();
		  }
		}
	  }

	private function verifyTransactionWithId($id){

		//make a call to verify the transaction
		return true;

	}

	/**
	 * Verifies that no customer fields exceed the allowed lengths
	 *
	 * @param array    $values The values of the submitted fields.
	 * @param WP_Error $errors Validation errors. This is a reference.
	 */
	public function validate_checkout_values( $values, $errors ) {

		if ( $this->id !== $values['payment_method'] ) {
			// No need to validate for other gateways.
			return;
		}
		// Load the fields that are used on the checkout page to make sure they exist and use their labels.
		$checkout_fields = WC()->countries->get_address_fields( $values['billing_country'], 'billing_' );
		$requirements = [
			'billing_first_name' => 50,
			'billing_last_name'  => 50,
			'billing_company'    => 50,
			'billing_address_1'  => 50,
			'billing_address_2'  => 50,
			'billing_city'       => 50,
			'billing_state'      => 50,
			'billing_postcode'   => 30,
			'billing_country'    => 2,
			'billing_email'      => 50,
			'billing_phone'      => 32,
		];
		foreach( $requirements as $name => $length ) {
			if ( ! isset( $checkout_fields[ $name ], $values[ $name ] ) ) {
				// The field is not available.
				continue;
			}
			$value = $values[ $name ];
			if ( empty( $value ) || strlen( $value ) <= $length ) {
				continue;
			}
			$errors->add(
				'validation',
				sprintf(
					__( 'The value of "%s" must not exceed the length of %d characters for payments with Flutterwave.', 'flutterwave' ),
					$checkout_fields[ $name ]['label'],
					$length
				)
			);
		}
	}

	public function flutterwave_webhooks()
	{
		// Retrieve the request's body
		$body = @file_get_contents("php://input");
		// retrieve the signature sent in the request header's.
		$signature = (isset($_SERVER['HTTP_VERIF_HASH']) ? $_SERVER['HTTP_VERIF_HASH'] : '');

		/* It is a good idea to log all events received. Add code *
		* here to log the signature and body to db or file       */

		if (!$signature) {
			// only a post with rave signature header gets our attention
			echo "Access Denied Hash does not match";
			exit();
		}

		// Store the same signature on your server as an env variable and check against what was sent in the headers
		$local_signature = $this->get_option('secret_hash');

		// confirm the event's signature
		if( $signature !== $local_signature ){
		  // silently forget this ever happened
		  exit();
		}
		// sleep(10);

		http_response_code(200); // PHP 5.4 or greater
		// parse event (which is json string) as object
		// Give value to your customer but don't give any output
		// Remember that this is a call from rave's servers and
		// Your customer is not seeing the response here at all
		$response = json_decode($body);
		if ($response->status == 'successful'|| $response->data->status == 'successful') {
			$getOrderId = explode('_', $response->txRef ?? $response->data->tx_ref);
			$orderId = $getOrderId[1];
			// $order = wc_get_order( $orderId );
			$order = new WC_Order($orderId);
			$secretKey = $this->secret_key;
			$publicKey = $this->public_key;
			$payment = new Rave($publicKey, $secretKey, $txn_ref, $overrideRef, $this->logging_option);
			$payment->eventHandler(new myEventHandler($order))->requeryTransaction( $response->txRef ?? $response->data->tx_ref );
			do_action('flw_webhook_after_action', json_encode($response, TRUE));
		  }else{
				do_action('flw_webhook_transaction_failure_action', json_encode($response, TRUE));
			}
		  exit();
	}
}



class WCFG extends WC_Flutterwave_Gateway{

    public function receipt_page( $order ) {}

	public function load_scripts(){}

    public function flw_verify_payment() {}
}

function add_flutterwave_class( $methods ) {
    $methods[] = 'WC_Flutterwave_Gateway';
    return $methods;
}

?>