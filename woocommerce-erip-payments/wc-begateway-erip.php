<?php
if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

/*
Plugin Name: WooCommerce ЕРИП платежи
Plugin URI: https://github.com/begateway/woocommerce-erip-payment-module
Description: Модуль приёма ЕРИП платежей через агрегатора bePaid.by
Version: 3.5.0
Author: eComCharge
Author Email: help@bepaid.by

Text Domain: woocommerce-begateway-erip
Domain Path: /languages/

WC requires at least: 3.2.0
WC tested up to: 5.0.0
*/

class WC_Begateway_Erip {
  function __construct() {
    $this->id = 'begateway_erip';
    add_action( 'plugins_loaded', array( $this, 'init' ), 0 );
    add_action( 'woocommerce_loaded', array( $this, 'woocommerce_loaded' ), 40 );

    // add meta boxes
    add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 10, 2 );

    // // Add Admin Backend Actions
    // add_action( 'wp_ajax_bepaid_send_bill_to_erip', array(
    //   $this,
    //   'ajax_bepaid_gateway_send_bill_to_erip'
    // ) );
    //
    // add_action( 'wp_ajax_bepaid_erip_cancel', array(
    //   $this,
    //   'ajax_bepaid_gateway_erip_cancel'
    // ) );
  }

  public function init() {
    // Localization
    load_plugin_textdomain('woocommerce-begateway-erip', false, dirname( plugin_basename( __FILE__ ) ) . '/languages');
  }

  public function woocommerce_loaded() {
  	include_once( __DIR__ . '/includes/begateway-api-php/lib/BeGateway.php' );
  	include_once( __DIR__ . '/includes/class-wc-gateway-begateway-erip.php' );

    WC_Begateway_Erip::register_gateway('WC_Gateway_Begateway_Erip');
  }

  /**
  * Register payment gateway
  *
  * @param string $class_name
  */
  public static function register_gateway( $class_name ) {
    global $gateways;

		if ( ! $gateways ) {
			$gateways = array();
		}

		if ( ! isset( $gateways[ $class_name ] ) ) {
			// Initialize instance
			if ( $gateway = new $class_name ) {
				$gateways[] = $class_name;
        if ( method_exists( $gateway, 'set_version' ) ) {
          $gateway->set_version( self::_get_plugin_version() );
        }

				// Register gateway instance
				add_filter( 'woocommerce_payment_gateways', function ( $methods ) use ( $gateway ) {
					$methods[] = $gateway;

					return $methods;
				} );
			}
		}
  }

  /**
 * Add meta boxes in admin
 * @return void
 */
	public function add_meta_boxes( $post_type, $post ) {
    if ( ! isset( $post->ID ) ) {       // Exclude links.
      return;
    }

		$screen     = get_current_screen();
		$post_types = [ 'shop_order' ] ;

		if ( in_array( $screen->id, $post_types, true ) && in_array( $post_type, $post_types, true ) ) {
			if ( $order = wc_get_order( $post->ID ) ) {
				$payment_method = $order->get_payment_method();
				if ( $this->id == $payment_method ) {
					add_meta_box( 'begateway-erip-payment-actions', __( 'ЕРИП действия', 'woocommerce-begateway-erip' ), [
						&$this,
						'meta_box_payment',
					], $post_type, 'side', 'high', [
             '__block_editor_compatible_meta_box' => true
          ]
         );
				}
			}
		}
	}

  /**
	 * Inserts the content of the API actions into the meta box
	 */
	public function meta_box_payment($post) {
    if ( ! isset( $post->ID ) ) {       // Exclude links.
      return;
    }

		if ( $order = wc_get_order( $post->ID ) ) {

      $payment_method = $order->get_payment_method();

			if ( $this->id == $payment_method ) {

				do_action( 'woocommerce_begateway_erip_gateway_meta_box_payment_before_content', $order );

				// Get Payment Gateway
				$gateways = WC()->payment_gateways()->get_available_payment_gateways();

				/** @var WC_Payment_Gateway_Begateway_Erip $gateway */
				$gateway = 	$gateways[ $payment_method ];

				try {
					wc_get_template(
						'admin/metabox-order.php',
						array(
							'gateway'    => $gateway,
							'order'      => $order,
							'order_id'   => $order->get_id(),
							'order_data' => $gateway->get_invoice_data( $order )
						),
						'',
						dirname( __FILE__ ) . '/templates/'
					);
				} catch ( Exception $e ) {
				}
			}
		}
	}

  public function admin_enqueue_scripts( $hook ) {
    if ( $hook === 'post.php' ) {
      wp_register_script(
                'begateway-erip-gateway-admin-js',
                plugin_dir_url( __FILE__ ) . 'assets/js/admin.js',
                array(
                  'jquery'
                )
            );
      wp_enqueue_style( 'wc-gateway-begateway-erip', plugins_url( '/assets/css/style.css', __FILE__ ), array(), FALSE, 'all' );

      // Localize the script
      $translation_array = array(
        'ajax_url'  => admin_url( 'admin-ajax.php' ),
        'text_wait' => __( 'Обрабатываем запрос...', 'woocommerce-begateway-erip' ),
      );
      wp_localize_script( 'begateway-erip-gateway-admin-js', 'Begateway_Erip_Admin', $translation_array );

      // Enqueued script with localized data
      wp_enqueue_script( 'begateway-erip-gateway-admin-js' );
    }
  }

  /**
  * Get the pluging version
  * @return string
  */
  public static function _get_plugin_version() {
    $plugin_data = get_file_data( __FILE__, array( 'Version' => 'Version' ), false );
    return $plugin_data['Version'];
  }
}

new WC_Begateway_Erip();