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

  public static $module_id = 'begateway_erip';

  function __construct() {
    $this->id = self::$module_id;
    add_action( 'plugins_loaded', array( $this, 'init' ), 0 );
    add_action( 'woocommerce_loaded', array( $this, 'woocommerce_loaded' ), 40 );

    // add meta boxes
    add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 10, 2 );
    // Add scripts and styles for admin
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

    // Add Admin Backend Actions
    add_action( 'wp_ajax_begateway_cancel_bill', array(
      $this,
      'ajax_begateway_cancel'
    ) );

    add_action( 'wp_ajax_begateway_create_bill', array(
      $this,
      'ajax_begateway_create'
    ) );
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
        'module_id' => self::$module_id
      );
      wp_localize_script( 'begateway-erip-gateway-admin-js', 'Begateway_Erip_Admin', $translation_array );

      // Enqueued script with localized data
      wp_enqueue_script( 'begateway-erip-gateway-admin-js' );
    }
  }

  /**
	 * Action for ERIP bill cancel
	 */
	public function ajax_begateway_cancel() {
		if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'begateway' ) ) {
      wp_send_json_error( 'Invalid nonce' );
			die();
		}

		$order_id = (int) $_REQUEST['order_id'];
		$order = wc_get_order( $order_id );

    if ( ! $order ) {
      wp_send_json_error( __( 'Не верный номер заказа' ) );
      die();
    }

		// Get Payment Gateway
		$payment_method = $order->get_payment_method();
		$gateways = WC()->payment_gateways()->get_available_payment_gateways();

		/** @var WC_Gateway_Begateway_Erip $gateway */
		$gateway = 	$gateways[ $payment_method ];
		$result = $gateway->cancel_bill( $order );

    if (!is_wp_error($result)) {
			wp_send_json_success( self::get_admin_notice_message( 'cancel' ) );
    } else {
			wp_send_json_error( $result->get_error_message() );
    }
	}

  public static function get_admin_notice_message( $type ) {
    $messages = [
      'cancel' => __( 'Счёт в ЕРИП успешно отменён', 'woocommerce-begateway-erip' ),
      'create' => __( 'Счёт в ЕРИП успешно создан', 'woocommerce-begateway-erip' )
    ];

    $result = ( isset( $messages[ $type ] ) ) ? $messages[ $type ] : null;
    return $result;
  }

  /**
	 * Action for ERIP bill creation
	 */
	public function ajax_begateway_create() {
    if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'begateway' ) ) {
      wp_send_json_error( 'Invalid nonce' );
			die();
		}

		$order_id = (int) $_REQUEST['order_id'];
		$order = wc_get_order( $order_id );

    if ( ! $order ) {
      wp_send_json_error( __( 'Не верный номер заказа' ) );
      die();
    }

		// Get Payment Gateway
		$payment_method = $order->get_payment_method();
		$gateways = WC()->payment_gateways()->get_available_payment_gateways();

		/** @var WC_Gateway_Begateway_Erip $gateway */
		$gateway = 	$gateways[ $payment_method ];
		$result = $gateway->create_bill( $order );

    if (!is_wp_error($result)) {
			wp_send_json_success( self::get_admin_notice_message( 'create' ) );
    } else {
			wp_send_json_error( $result->get_error_message() );
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

  public static function admin_notice( $message, $type = 'success' ) {
    ?>
    <div class="notice notice-<?php echo $type;?> is-dismissible">
        <p><?php echo $message; ?></p>
    </div>
    <?php
  }
}

new WC_Begateway_Erip();

function admin_notice_erip_message() {
    $plugin = isset( $_GET['plugin'] ) ? $_GET['plugin'] : false;

    if ( ! $plugin || $plugin != WC_Begateway_Erip::$module_id ) {
      return;
    }

    $message_type = isset( $_GET['plugin_message'] ) ? $_GET['plugin_message'] : null;

    $message = WC_Begateway_Erip::get_admin_notice_message( $message_type );

    if ( ! $message ) {
      return;
    }

    WC_Begateway_Erip::admin_notice( $message, 'success' );
}
add_action( 'admin_notices', 'admin_notice_erip_message' );
