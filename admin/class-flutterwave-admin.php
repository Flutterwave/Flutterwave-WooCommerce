<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://developer.flutterwave.com/docs
 * @since      0.1.0
 *
 * @package    Flutterwave
 * @subpackage Flutterwave/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Flutterwave
 * @subpackage Flutterwave/admin
 * @author     Flutterwave Developers <developers@flutterwavego.com>
 */
class Flutterwave_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;


    private $admin_child_pages;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

    /**
     * Register the administration menu for this plugin into the WordPress Dashboard menu.
     *
     * @since    0.1.0
     */
    // public function add_plugin_admin_menu()
    // {
    //     // Add woocommerce menu subitem
    //     add_submenu_page(
    //         'woocommerce',
    //         'Flutterwave for WooCommerce',
    //         "PHP page",
    //         'manage_options',
    //         'flutterwave',
    //         array($this,'admin_overview_page')
    //     );
    // }

    public function add_extension_register_page() {
        if ( ! function_exists( 'wc_admin_register_page' ) ) {
            return;
        }

        $top_level_link = "/overview";

        wc_admin_register_page(
			[
				'id'         => 'wc-flutterwave',
				'title'      => __( 'Flutterwave', 'flutterwave' ),
				'capability' => 'manage_woocommerce',
				'path'       => $top_level_link,
				'position'   => '50', // Before WooCommerce & Product menu items.
                'icon'  => plugin_dir_url(__FILE__) . 'images/logo.png',
				'nav_args'   => [
					'title'        => __( 'Overview', 'flutterwave' ),
					'is_category'  => __return_true(),
					'menuId'       => 'plugins',
					'is_top_level' => true,
				],
			]
		);



        // wc_admin_register_page( array(
        //     'id'       => 'flutterwave-overview',
        //     'title'    => 'Flutterwave',
        //     'parent'   => 'woocommerce',
        //     'path'     => '/overview',
        //     'nav_args' => array(
        //         'order'  => 10,
        //         'url'    => 'wc-settings&tab=checkout&section=rave',
        //         'parent' => 'woocommerce',
        //     ),
        // ) );

		wc_admin_register_page( array(
				'id'       => 'flutterwave-settings',
				'title'    => 'Payment settings',
				'parent'   => 'wc-flutterwave',
				'capability' => 'manage_woocommerce',
				'path'     => '/payments/settings',
				'nav_args' => [
					'parent' => 'flutterwave',
					'order'  => 20,
				]
		));

		wc_admin_register_page( array(
			'id'       => 'flutterwave-transactions',
			'title'    => 'Transactions',
			'parent'   => 'wc-flutterwave',
			'capability' => 'manage_woocommerce',
			'path'     => '/flutterwave/transactions',
			'nav_args' => [
				'parent' => 'flutterwave',
				'order'  => 20,
			]
		));

		wc_admin_register_page( array(
			'id'       => 'flutterwave-plans',
			'title'    => 'Payment plans',
			'parent'   => 'wc-flutterwave',
			'capability' => 'manage_woocommerce',
			'path'     => '/flutterwave/plans',
			'nav_args' => [
				'parent' => 'flutterwave',
				'order'  => 20,
			]
		));

		wc_admin_register_page( array(
			'id'       => 'flutterwave-subaccounts',
			'title'    => 'Subaccounts',
			'parent'   => 'wc-flutterwave',
			'capability' => 'manage_woocommerce',
			'path'     => '/flutterwave/subaccounts',
			'nav_args' => [
				'parent' => 'flutterwave',
				'order'  => 20,
			]
		));

        // wc_admin_connect_page(
        //     [
        //         'id'        => 'woocommerce-settings-payments-flutterwave',
        //         'parent'    => 'wc-flutterwave',
        //         'screen_id' => 'woocommerce_page_wc-settings-checkout-flutterwave',
        //         'title'     => __( 'Flutterwave Payments', 'flutterwave' ),
        //         'nav_args'  => [
        //             'parent' => 'wc-flutterwave',
        //             'title'  => __( 'Settings', 'flutterwave' ),
        //             'url'    => 'wc-settings&tab=checkout&section=rave',
        //             'order'  => 50,
        //         ],
        //     ]
        // );
    }


	public function maybe_redirect_to_onboarding()
	{
		if ( wp_doing_ajax() ) {
			return false;
		}

		$url_params = $this->flw_sanitize(wp_unslash( $_GET )); // phpcs:ignore WordPress.Security.NonceVerification

		// if ( empty( $url_params['page'] ) || 'wc-admin' !== $url_params['page'] ) {
		// 	return;
		// }

		// $current_path = ! empty( $url_params['path'] ) ? $url_params['path'] : '';
		// if ( empty( $current_path ) ) {
		// 	return;
		// }

		if(!class_exists('WC_Flutterwave_Gateway')){
			return;
		}

		$is_on_settings_page = WC_Flutterwave_Gateway::is_current_page_settings();
		$should_redirect_to_onboarding = get_option( 'wcpay_should_redirect_to_onboarding', false );
		if (
			// If not loading the settings page...
			! $is_on_settings_page
			// ...and we have redirected before.
			&& ! $should_redirect_to_onboarding
		) {
			// Do not attempt to redirect again.
			return false;
		}

		if ( $should_redirect_to_onboarding ) {
			update_option( 'wcpay_should_redirect_to_onboarding', false );
		}
		// Redirect if public key and secret not present
		$this->redirect_to_onboarding_page();
		return true;
	}

	/**
	 * Utility function to immediately redirect to the main "Flutterwave Onboarding Page" onboarding page.
	 * Note that this function immediately ends the execution.
	 *
	 * @param string $error_message Optional error message to show in a notice.
	 */
	public function redirect_to_onboarding_page( $error_message = null ) {
		// if ( isset( $error_message ) ) {
		// 	set_transient( self::ERROR_MESSAGE_TRANSIENT, $error_message, 30 );
		// }

		$params = [
			'page' => 'wc-admin',
			'path' => '/overview',
		];
		if ( count( $params ) === count( array_intersect_assoc( $_GET, $params ) ) ) { // phpcs:disable WordPress.Security.NonceVerification.Recommended
			// We are already in the onboarding page, do nothing.
			return;
		}

		wp_safe_redirect( admin_url( add_query_arg( $params, 'admin.php' ) ) );
		exit();
	}

	/**
	 * Include the new Navigation Bar the Admin page.
	 *     // Add WooCommerce Navigation Bar
	 */
	// function add_woocommerce_navigation_bar() {
	// 	if ( function_exists( 'wc_admin_connect_page' ) ) {
    //         wc_admin_connect_page(
    //             array(
    //                 'id'        => "flutterwave",
    //                 'screen_id' => 'woocommerce_page_flutterwave',
    //                 'title'     => __( 'Flutterwave Settings', 'Overview' ),
    //                 'path'      => add_query_arg( 'page', 'flw-onboarding',)
	// 			)
	// 		);
    //     }
    // }

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles($hook) {
		$url_params = $this->flw_sanitize(wp_unslash( $_GET ));



		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Fpf_Flutterwave_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Fpf_Flutterwave_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

         //get page as well

        if ( !isset($url_params['path']) || empty($url_params['path'])) {
             return;
        }

		$flutterwave_paths = array(
			"/overview",
		 	"/payments/settings",
		 	"/flutterwave/plans",
		 	"/flutterwave/transactions",
			"/flutterwave/subaccounts"
		);

        if( in_array($url_params['path'], $flutterwave_paths) ){
			// wp_dequeue_style($this->plugin_name."_settings");
			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/flutterwave-admin.css', array(), $this->version, 'all' );
		}

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts($hook) {
        global $pagenow, $submenu;
        $url_params = $this->flw_sanitize(wp_unslash( $_GET ));
		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Fpf_Flutterwave_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Fpf_Flutterwave_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

        if ( !isset($url_params['path']) || empty($url_params['path']))
        {
            return;
        }

		$flutterwave_paths = array(
			"/overview",
		 	"/payments/settings",
		 	"/flutterwave/plans",
		 	"/flutterwave/transactions",
			"/flutterwave/subaccounts"
		);

        if( in_array($url_params['path'], $flutterwave_paths) ){
			$script_path = '/js/overview.js';
    		$script_asset_path = dirname( __FILE__ ) . '/js/overview.asset.php';
    		$script_asset = file_exists( $script_asset_path )
        	? require( $script_asset_path )
        	: array( 'dependencies' => array(), 'version' => filemtime( $script_path ) );
			wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/overview.js', $script_asset['dependencies'], $script_asset['version'], false );
		}

	}

	public function flw_sanitize( $input ) {

			// Initialize the new array that will hold the sanitize values
			$new_input = array();

			// Loop through the input and sanitize each of the values
			foreach ( $input as $key => $val ) {
				$new_input[ $key ] = sanitize_text_field( $val );
			}

			return $new_input;

	}

	/**
    * settings link
    * @return $links
    */
    public function settings_link(  $links ) {
        // $flonboarding = esc_url( get_admin_url( null, 'admin.php?page=wc-admin&path=/payments/settings' ) );
		$settings_link = '<a href="'.$flonboarding.'">Settings</a>';
		array_unshift( $links,  $settings_link);
		return $links;
	}

	/**
    * Overview page content
    * @return void
    */
    public function admin_overview_page() {
		include_once( plugin_dir_path( __FILE__ ) . 'views/admin-overview-page.php' );
		//include_once( plugin_dir_path( __FILE__ ) . 'partials/fpf-flutterwave-admin-display.php' );
	}

	/**
    * Admin page content
    * @return void
    */
    public function admin_setting_page() {
      include_once( plugin_dir_path( __FILE__ ) . 'views/admin-settings-page.php' );
    }

	/**
    * Form Settings page
    * @return void
    */
	public function form_setting_page() {
		include_once( plugin_dir_path( __FILE__ ) . 'views/admin-payment-form-page.php' );
	}

}