<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class WC_Gateway_Begateway_Erip extends WC_Payment_Gateway {
  protected $log;

  const DOMAIN_API = 'api.bepaid.by';

  protected $_instruction   = '';
  protected $_response_erip = null;

	function __construct() {
		$this->supports = array('products');

    $this->setup_properties();
		$this->init_form_fields();
		$this->init_settings();

    add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
    add_action( 'woocommerce_api_wc_gateway_' . $this->id, array( $this, 'validate_ipn_request' ) );
    add_action( 'woocommerce_update_options_payment_gateways', array($this, 'process_admin_options' ) );
    add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
  }

	protected function setup_properties() {
    $this->id                 = 'begateway_erip';
		$this->icon               = plugins_url( 'assets/images/erip.png' , dirname(__FILE__) );
		$this->method_title       = __( "ЕРИП", 'wc-begateway-erip-payment' );
		$this->method_description = __( "Приём ЕРИП платежей через агрегатора bePaid.by", 'wc-begateway-erip-payment' );
        $this->has_fields         = false;
		$this->title              = $this->get_option( 'name_sposoba_oplati' );
		$this->description        = wpautop( $this->get_option( 'description_sposoba_oplati') );
  }

  function thankyou_page( $order_id )
  {
    $this->log( 'Печать ЕРИП информации об оплате заказа ' . $order_id . PHP_EOL . ' -- ' . __FILE__ . ' - Line:' . __LINE__ );

    $this->_instruction = get_post_meta( $order_id, '_begateway_erip_instruction', true);

    if ( $this->_instruction ) {
      $instruction = wpautop( wptexturize( $this->_instruction ) );
      $this->log( 'ЕРИП инструкция заказа ' . $order_id . ' Инструкция ' . $instruction . PHP_EOL . ' -- ' . __FILE__ . ' - Line:' . __LINE__ );
      echo $instruction;
    }
  }

  private function plugin_url()
  {
    return $this->plugin;
  }

  /**
   *this function is called via the wp-api when the begateway server sends
   *callback data
  */
  function validate_ipn_request() {
    $this->log( 'Received webhook json: ' . PHP_EOL . file_get_contents( 'php://input' ) . PHP_EOL . ' -- ' . __FILE__ . ' - Line:' . __LINE__ );

    $webhook = new \BeGateway\Webhook;

    \BeGateway\Settings::$shopId = $this->get_option( 'erip_id_magazin' );
    \BeGateway\Settings::$shopKey = $this->get_option( 'erip_API_key' );

    list( $order_id, $order_key ) = explode( ';', $webhook->getTrackingId() );

    if ( ! $this->validate_ipn_order_key( $webhook ) ) {
      $this->log(
        '----------- Invalid order key --------------' . PHP_EOL .
        "Order No: " . $order_id . PHP_EOL .
        "UID: ".$webhook->getUid() . PHP_EOL .
        '--------------------------------------------' . PHP_EOL . ' -- ' . __FILE__ . ' - Line:' . __LINE__
      );

      die( "beGateway Notify Key Failure" );
    }

    if ( ! $this->validate_ipn_amount( $webhook ) ) {
      $this->log(
        '----------- Invalid amount webhook --------------' . PHP_EOL .
        "Order No: " . $order_id . PHP_EOL .
        "UID: ".$webhook->getUid() . PHP_EOL .
        '--------------------------------------------' . PHP_EOL . ' -- ' . __FILE__ . ' - Line:' . __LINE__
      );

      die( "beGateway Notify Amount Failure" );
    }

    if ( ! $this->validate_ipn_transaction_id( $webhook ) ) {
      $this->log(
        '----------- Mismatch transaction id webhook --------------' . PHP_EOL .
        "Order No: " . $order_id . PHP_EOL .
        "UID: " . $webhook->getUid() . PHP_EOL .
        "Saved UID: ". get_post_meta( $order_id, '_begateway_transaction_id', true ) . PHP_EOL .
        '--------------------------------------------' . PHP_EOL . ' -- ' . __FILE__ . ' - Line:' . __LINE__
      );

      die( "beGateway Notify Transaction Id Failure" );
    }

    if ( $webhook->isAuthorized() ) {
      $this->log(
        '-------------------------------------------' . PHP_EOL .
        "Order No: " . $order_id . PHP_EOL .
        "UID: ".$webhook->getUid() . PHP_EOL .
        '--------------------------------------------' . PHP_EOL . ' -- ' . __FILE__ . ' - Line:' . __LINE__
      );

      $this->process_ipn_request( $webhook );

    } else {
      $this->log(
        '----------- Unauthorized webhook --------------' . PHP_EOL .
        "Order No: " . $order_id . PHP_EOL .
        "UID: ".$webhook->getUid() . PHP_EOL .
        '--------------------------------------------' . PHP_EOL . ' -- ' . __FILE__ . ' - Line:' . __LINE__
      );

      die( "beGateway Notify Failure" );
    }
  }

  protected function validate_ipn_amount( $webhook ) {
    list( $order_id, $order_key ) = explode( ';', $webhook->getTrackingId() );
    $order = new WC_Order( $order_id );

    if ( ! $order ) {
      return false;
    }

    $money = new \BeGateway\Money;
    $money->setCurrency( $order->get_currency() );
    $money->setAmount( $order->get_total() );
    $money->setCurrency( $webhook->getResponse()->transaction->currency );
    $money->setCents( $webhook->getResponse()->transaction->amount );

    $transaction = $webhook->getResponse()->transaction;

    return $transaction->currency == $money->getCurrency() &&
      $transaction->amount == $money->getCents();
  }

  protected function validate_ipn_transaction_id( $webhook ) {
    list( $order_id, $order_key ) = explode( ';', $webhook->getTrackingId() );
    $order = new WC_Order( $order_id );

    return $webhook->getUid() === $order->get_meta( '_begateway_transaction_id', true );
  }

  protected function validate_ipn_order_key( $webhook ) {
    list( $order_id, $order_key ) = explode( ';', $webhook->getTrackingId() );
    $order = new WC_Order( $order_id );
    return $order->key_is_valid( $order_key );
  }

  function process_ipn_request($webhook) {
    list( $order_id, $order_key ) = explode( ';', $webhook->getTrackingId() );
    $order = new WC_Order( $order_id );
    $type = $webhook->getResponse()->transaction->type;
    if ( $type == 'payment' ) {
      $status = $webhook->getStatus();

      $this->log(
        'Transaction type: ' . $type . PHP_EOL .
        'Payment status: '. $status . PHP_EOL .
        'UID: ' . $webhook->getUid() . PHP_EOL .
        'Message: ' . $webhook->getMessage() . PHP_EOL . ' -- ' . __FILE__ . ' - Line:' . __LINE__
      );

      if ( $webhook->isSuccess() ) {
        $order->payment_complete( $webhook->getUid() );
      }
    }
  }

	// Build the administration fields for this specific Gateway
	public function init_form_fields() {
    $this->form_fields = include __DIR__ . '/settings.php';
	}

	/*
		Создание Счета на оплату в системе ЕРИП
	*/
	public function create_invoice_with_erip( $order ) {
		$money = new \BeGateway\Money( $order->get_total(), $order->get_currency() );

    $notification_url = WC()->api_request_url( 'WC_Gateway_Begateway_Erip', is_ssl() );
    $notification_url = str_replace( '0.0.0.0', 'webhook.begateway.com:8443', $notification_url );

		$arrayDataInvoice = [
 			"request" => [
				"amount" => $money->getCents(),
				"currency" => $money->getCurrency(),
				"description" => "Оплата заказа # ".$order->get_order_number(),
				"email" => $order->get_billing_email(),
				"ip" => $order->get_customer_ip_address(),
				"order_id" => $order->get_id(),
        "expired_at" => date( "c", (int)$this->get_option( 'payment_valid' ) * 60 + time() + 1 ),
				"notification_url" => $notification_url,
        "tracking_id" => $order->get_id() . ';' . $order->get_order_key(),
				"customer" => [
					"first_name" => $order->get_billing_first_name(),
					"last_name" => $order->get_billing_last_name(),
					"country" => $order->get_billing_country(),
					"city" => $order->get_billing_city(),
					"zip" => $order->get_billing_postcode(),
					"address" => $order->get_billing_address_1().' '.$order->get_billing_address_2(),
					"phone" => $order->get_billing_phone()
	 			],
        'additional_data' => [
          'platform_data' => 'WooCommerce v' . WC_VERSION,
          'integration_data' => 'BeGateway WooCommerce ERIP Module v' . $this->get_version()
        ],
	 			"payment_method" => [
					"type" => "erip",
					"account_number" => $order->get_id(),
					"service_no" => $this->get_option( 'erip_kod_uslugi' ),
					"service_info" => [
						"Оплата заказа ".$order->get_order_number()
					],
					"receipt" => [
						$this->_update_instruction( $this->get_option( 'info_message_in_check' ), $order )
					]
				]
			]
 		];

    $response = $this->_api_client( '/beyag/payments', $arrayDataInvoice );

    if ( $response ) {
      $order->update_meta_data( '_begateway_transaction_id', $response->transaction->uid );
  		return $response;
    } else {
      return new WP_Error( '', __( 'Ошибка создания счёта в ЕРИП', 'wc-begateway-erip-payment' ) );
    }
	}

  /** API client
  * @param array $arData data to post to API
  * @return mixed
  **/
  protected function _api_client( $path, $arData = array(), $method = 'POST' ) {

    $url = 'https://'. self::DOMAIN_API . $path;

    $ch = curl_init( $url );

    if ( $method == 'POST' ) {
      $headers = array(
        "Content-Type: application/json"
      );

      curl_setopt( $ch, CURLOPT_POST, 1 );

      if ( count( $arData) > 0) {
        $headers[ 'Content-Length: ' ] = strlen( json_encode( $arData ) );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $arData ) );
      }
    }

    curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
    curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, $method );
    curl_setopt( $ch, CURLOPT_PORT, 443 );
    curl_setopt( $ch, CURLOPT_HEADER, 0 );
    curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 1 );
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
    curl_setopt( $ch, CURLOPT_FORBID_REUSE, 1 );
    curl_setopt( $ch, CURLOPT_FRESH_CONNECT, 1 );
    curl_setopt( $ch, CURLOPT_USERPWD, $this->get_option( 'erip_id_magazin' ).':'.$this->get_option( 'erip_API_key' ) );

    $this->log( __( 'API запрос: ', 'wc-begateway-erip-payment' ) . $url . PHP_EOL . ' -- ' . __FILE__ . ' - Line:' . __LINE__ );
    $this->log( __( 'Данные запроса: ', 'wc-begateway-erip-payment' ) . print_r( $arData, true) . PHP_EOL . ' -- ' . __FILE__ . ' - Line:' . __LINE__ );

    $response = curl_exec( $ch );

    if ( $response === false ) {
      $this->log( curl_errno( $ch ) . ': ' . curl_error( $ch ) . PHP_EOL . ' -- ' . __FILE__ . ' - Line:' . __LINE__ );
      curl_close( $ch );
      return false;
    }

    curl_close($ch);
    $response = json_decode( $response );

    $this->log( __( 'API ответ:', 'wc-begateway-erip-payment' ) . print_r( $response, true) . PHP_EOL . ' -- ' . __FILE__ . ' - Line:' . __LINE__ );

    if ( is_null( $response ) || $response === false ) {
      $this->log( __( 'Ошибка обработки JSON-ответа', 'wc-begateway-erip-payment' ) . PHP_EOL . ' -- ' . __FILE__ . ' - Line:' . __LINE__ );
      return false;
    }

    if ( $method == 'DELETE' ) {
      if ( $response->message != 'Successfully deleted' ) {
        $this->log( __( 'Ошибка удаления счёта в ЕРИП', 'wc-begateway-erip-payment' ) . PHP_EOL . ' -- ' . __FILE__ . ' - Line:' . __LINE__ );
        return false;
      }
    } elseif ( $response->transaction->status != 'pending' ) {
      $this->log( __( 'Ошибка регистрации счёта в ЕРИП', 'wc-begateway-erip-payment' ) . PHP_EOL . ' -- ' . __FILE__ . ' - Line:' . __LINE__ );
      return false;
    }

    return $response;
  }

  /**
   * Отправка сообщения с инструкцией об оплате
   *
   * @access protected
   * @param WC_Order $order Order object.
   * @return         mixed
   */
	protected function _email_erip_instruction( $order ) {
    if ( ! $order ) {
      return false;
    }

  	$mailer = WooCommerce::instance()->mailer();

    $message = $this->get_option( 'description_email_erip_instruction' );

    if ( empty ( trim( $message ) ) ) {
      // пустое поле. не шлем письмо.
      return false;
    }
    $message = $this->_update_instruction( $message, $order );

    $this->log ( 'Отправляем e-mail ЕРИП инструкцию: ' . $message );

    $message = $mailer->wrap_message('', $message);
    $subject = sprintf(
      __( 'Инструкция об оплате заказа № %s', 'wc-begateway-erip-payment' ),
      $order->get_order_number()
    );

		return $mailer->send(
      $order->get_billing_email(),
      $subject,
      $message
    );
	}

  /**
   * Submit payment and handle response
   *
   * @access public
   * @param int $order_id Order id
   * @return         mixed
   */
	public function process_payment( $order_id ) {
		global $woocommerce;

    $order = new WC_order( $order_id );
    $result = true;

		if ($this->get_option( 'type_sposoba_oplati' ) == 'manual') {
      $this->create_bill_manual( $order );
			// Remove cart
			$woocommerce->cart->empty_cart();
		} else {
      $response = $this->create_bill ( $order );

      if ( ! is_wp_error( $response ) ) {
        // Remove cart
        $woocommerce->cart->empty_cart();
      } else {
        $result = false;
        wc_add_notice( $response->get_error_message(), 'error' );
      }
		}

    if ( $result ) {
      // Return thankyou redirect
      return array(
        'result' => 'success',
        'redirect' => $this->get_return_url( $order )
      );
    }
	}

  /**
	 * Replace intruction placeholders
	 *
	 * @access protected
	 * @param string $message Message to update
	 * @param WC_Order $order Order object.
   * @return string updated instruction
	 */
  protected function _update_instruction( $message, $order ) {
    $instruction = $message;

    if ( $this->_response_erip ) {
      $erip_steps = isset( $this->_response_erip->transaction->erip->instruction[0] ) ?
        $this->_response_erip->transaction->erip->instruction[0] :
        $this->_response_erip->transaction->erip->instruction;
      $qr_code = $this->_response_erip->transaction->erip->qr_code;
      $qr_code = '<img src="' . $qr_code . '">';

      $erip_sevice_code = $this->_response_erip->transaction->erip->service_no_erip;

      $instruction = str_replace( "{{instruction_erip}}", $erip_steps, $instruction );
      $instruction = str_replace( "{{qr_code}}", $qr_code, $instruction );
      $instruction = str_replace( "{{erip_service_code}}", $erip_sevice_code, $instruction );
    }

    $instruction = str_replace( "{{order_number}}", $order->get_id(), $instruction );
    $instruction = str_replace( "{{fio}}", $order->get_billing_first_name() ." ". $order->get_billing_last_name(), $instruction );
    $instruction = str_replace( "{{name_shop}}", get_option( 'bname' ), $instruction);

    return $instruction;
  }

  /**
	 * Cancels ERIP bill
	 *
	 * @param WC_Order $order The order object related to the transaction.
   * @return null|WP_Error
	 */
  public function cancel_bill( $order ) {
    $uid = $order->get_meta( '_begateway_transaction_id', true );

    if ( ! isset( $uid ) || empty( $uid ) ) {
      return new WP_Error( 'begateway_erip_error', __( 'Не найден номер операции, чтобы отменить ЕРИП счёт' , 'wc-begateway-erip-payment' ) );
    }

    if ( $this->id != $order->get_payment_method() ) {
			return new WP_Error( 'begateway_erip_error', __( 'Недействительный способ оплаты' , 'wc-begateway-erip-payment' ) );
		}

    $this->log( "Отменяем счёт {$uid} of {$order->get_id()}" . PHP_EOL . ' -- ' . __FILE__ . ' - Line:' . __LINE__ );

    $response = $this->_api_client( '/beyag/payments/' . $uid, [], 'DELETE' );

    if ( $response ) {
      $order->update_meta_data( '_begateway_transaction_id', null );
      $order->update_status( 'on-hold', __( 'Заказ требует уточнения остатков и обратного звонка клиенту', 'wc-begateway-erip-payment' ) );
    } else {
      return new WP_Error( 'begateway_erip_error', __( 'Ошибка удаления счёта в ЕРИП', 'wc-begateway-erip-payment' ) );
    }
		return $response;
  }

  /**
	 * Creates ERIP bill
	 *
	 * @param WC_Order $order The order object related to the transaction.
   * @return null|WP_Error
	 */
  public function create_bill( $order ) {
    $uid = $order->get_meta( '_begateway_transaction_id', true );

    if ( ! empty( $uid ) ) {
      return new WP_Error( 'begateway_erip_error', __( 'Счёт в ЕРИП уже создан' , 'wc-begateway-erip-payment' ) );
    }

    if ( $this->id != $order->get_payment_method() ) {
			return new WP_Error( 'begateway_erip_error', __( 'Недействительный способ оплаты' , 'wc-begateway-erip-payment' ) );
		}

    $this->log( 'Автоматическое выставление счёта для заказа ' . $order->get_id() . PHP_EOL . ' -- ' . __FILE__ . ' - Line:' . __LINE__ );
    $this->_response_erip = $this->create_invoice_with_erip( $order );

    if ( ! is_wp_error( $this->_response_erip ) ) {
      $this->log( 'Успешно выставлен счет в ЕРИП для заказа ' . $order->get_id() . PHP_EOL . ' -- ' . __FILE__ . ' - Line:' . __LINE__ );

      $this->_instruction = $this->get_option( 'description_configuration_auto_mode' );
      $this->_instruction = $this->_update_instruction( $this->_instruction, $order );

      $this->log( 'ЕРИП инструкция ' . $this->_instruction . PHP_EOL . ' -- ' . __FILE__ . ' - Line:' . __LINE__ );
      $order->update_meta_data( '_begateway_erip_instruction', $this->_instruction );

      $this->_email_erip_instruction( $order );

      // Mark as pending
      $order->update_status('pending', __( 'Ожидается оплата заказа', 'wc-begateway-erip-payment' ));
      // Reduce stock levels
      wc_reduce_stock_levels( $order );
    } else {
      $this->log( "Ошибка выставления счёта в ЕРИП для заказа {$order->get_id()}" . PHP_EOL . ' -- ' . __FILE__ . ' - Line:' . __LINE__ );
      $this->log( $this->_response_erip->get_error_message() . PHP_EOL . ' -- ' . __FILE__ . ' - Line:' . __LINE__ );
    }
		return $this->_response_erip;
  }

  /**
	 * Creates ERIP bill (manually mode)
	 *
	 * @param WC_Order $order The order object related to the transaction.
   * @return null
	 */
  public function create_bill_manual( $order ) {
    $this->log( 'Ручное выставление счёта для заказа ' . $order_id . PHP_EOL . ' -- ' . __FILE__ . ' - Line:' . __LINE__ );

    //Оплата будет происходить в ручном режиме
    $this->_instruction = $this->get_option( 'description_configuration_manual_mode' );
    $this->_instruction = $this->_update_instruction( $this->_instruction, $order );

    $this->log( 'ЕРИП инструкция ' . $this->_instruction . PHP_EOL . ' -- ' . __FILE__ . ' - Line:' . __LINE__ );
    $order->update_meta_data( '_begateway_erip_instruction', $this->_instruction );

    // Mark as on-hold
    $order->update_status( 'on-hold', __( 'Заказ требует уточнения остатков и обратного звонка клиенту', 'wc-begateway-erip-payment' ) );

    // Reduce stock levels
    wc_reduce_stock_levels( $order );
  }

  /**
  * Get Invoice data of Order.
  *
  * @param WC_Order $order
  *
  * @return array
  * @throws Exception
  */
  public function get_invoice_data( $order ) {
    // @TODO может удалить эту функцию?
    if ( is_int( $order ) ) {
      $order = wc_get_order( $order );
    }

    if ( $order->get_payment_method() !== $this->id ) {
      throw new Exception('Unable to get invoice data.');
    }

    return array(
      'state' => $order->get_status()
    );
  }

  /**
  	 * Check if an ERIP bill can be cancelled
  	 *
  	 * @param WC_Order $order The order object related to the transaction.
     * @return boolean
  	 */
  public function can_cancel_bill( $order ) {
    return $order->get_status() == 'pending' &&
      ! empty( $order->get_meta( '_begateway_transaction_id', true ) );
  }

  /**
	 * Check if an ERIP bill can be created
	 *
	 * @param WC_Order $order The order object related to the transaction.
   * @return boolean
  */
  public function can_create_bill( $order ) {
    return $order->get_status() == 'on-hold' &&
      empty( $order->get_meta( '_begateway_transaction_id', true ) );
  }

  /**
  * Log function
  */
  public function log( $message ) {
    if ( empty( $this->log ) ) {
      $this->log = new WC_Logger();
    }
    if ('yes' == $this->get_option( 'debug', 'no') ) {
      $this->log->debug( $message, array( 'source' => 'woocommerce-gateway-begateway-erip' ) );
      if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        error_log( $message );
      }
    }
  }

  /**
  * Set plugin version
  * @param string $version plugin version
  * @return void
  */
  public function set_version( $version ) {
    $this->version = strval( $version );
  }

  /**
  * Get plugin version
  * @return string
  */
  public function get_version() {
    return $this->version;
  }
}
