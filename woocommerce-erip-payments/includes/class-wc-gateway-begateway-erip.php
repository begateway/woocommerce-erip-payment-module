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

    // add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
    add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
    add_action( 'woocommerce_api_wc_gateway_' . $this->id, array( $this, 'validate_ipn_request' ) );
    add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
    add_action( 'woocommerce_update_options_payment_gateways', array($this, 'process_admin_options' ) );
    add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
  }

	protected function setup_properties() {
    $this->id                 = 'begateway_erip';
		$this->icon               = plugins_url( 'assets/images/erip.png' , dirname(__FILE__) );
		$this->method_title       = __( "ЕРИП", 'woocommerce-begateway-erip' );
		$this->method_description = __( "Приём ЕРИП платежей через агрегатора bePaid.by", 'woocommerce-begateway-erip' );
    $this->has_fields         = false;
		$this->title              = $this->get_option( 'name_sposoba_oplati' );
		$this->description        = wpautop( $this->get_option( 'description_sposoba_oplati') );
  }

  // function receipt_page( $order_id ) {
  //   echo $this->generate_erip_page( $order_id );
  // }

  function thankyou_page( $order_id )
  {
    $this->log( 'Печать ЕРИП информации об оплате заказа ' . $order_id );

    $this->_instruction = get_post_meta( $order_id, '_begateway_erip_instruction', true);

    if ( $this->_instruction ) {
      $instruction = wpautop( wptexturize( $this->_instruction ) );
      $this->log( 'ЕРИП инструкция заказа ' . $order_id . ' Инструкция ' . $instruction );
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
    $this->log( 'Received webhook json: ' . PHP_EOL . file_get_contents( 'php://input' ) );

    $webhook = new \BeGateway\Webhook;

    \BeGateway\Settings::$shopId = $this->get_option( 'erip_id_magazin' );
    \BeGateway\Settings::$shopKey = $this->get_option( 'erip_API_key' );

    list( $order_id, $order_key ) = explode( ';', $webhook->getTrackingId() );

    if ( ! $this->validate_ipn_order_key( $webhook ) ) {
      $this->log(
        '----------- Invalid order key --------------' . PHP_EOL .
        "Order No: " . $order_id . PHP_EOL .
        "UID: ".$webhook->getUid() . PHP_EOL .
        '--------------------------------------------'
      );

      die( "beGateway Notify Key Failure" );
    }

    if ( ! $this->validate_ipn_amount( $webhook ) ) {
      $this->log(
        '----------- Invalid amount webhook --------------' . PHP_EOL .
        "Order No: " . $order_id . PHP_EOL .
        "UID: ".$webhook->getUid() . PHP_EOL .
        '--------------------------------------------'
      );

      die( "beGateway Notify Amount Failure" );
    }

    if ( ! $this->validate_ipn_transaction_id( $webhook ) ) {
      $this->log(
        '----------- Mismatch transaction id webhook --------------' . PHP_EOL .
        "Order No: " . $order_id . PHP_EOL .
        "UID: " . $webhook->getUid() . PHP_EOL .
        "Saved UID: ". get_post_meta( $order_id, '_begateway_transaction_id', true ) . PHP_EOL .
        '--------------------------------------------'
      );

      die( "beGateway Notify Transaction Id Failure" );
    }

    if ( $webhook->isAuthorized() ) {
      $this->log(
        '-------------------------------------------' . PHP_EOL .
        "Order No: " . $order_id . PHP_EOL .
        "UID: ".$webhook->getUid() . PHP_EOL .
        '--------------------------------------------'
      );

      $this->process_ipn_request( $webhook );

    } else {
      $this->log(
        '----------- Unauthorized webhook --------------' . PHP_EOL .
        "Order No: " . $order_id . PHP_EOL .
        "UID: ".$webhook->getUid() . PHP_EOL .
        '--------------------------------------------'
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
        'Message: ' . $webhook->getMessage()
      );

      if ( $webhook->isSuccess() ) {
        $order->payment_complete( $webhook->getUid() );
      }
    }
  }

	// protected function generate_erip_page( $order_id ) {
  //
  //   $order = new WC_Order( $order_id );
  //   $this->log( __( 'Создаем страницу оплаты для заказа' ) . " ". $order->get_order_number());
  //
	// 	// Проверяем режим работы обработки заказов плагина
	// 	if ($this->get_option('type_sposoba_oplati') == 'manual') {
	// 		//В случае ручной обработки заказов, выдаём сообщение
	// 		return wpautop( $this->get_option( 'description_configuration_manual_mode' ) );
	// 	}
  //
	// 	$api = new Erip_API;
	// 	$api->setDomainAPI( self::DOMAIN_API );
	// 	$api->setIdShop( $this->get_option( 'erip_id_magazin') );
	// 	$api->setApiKeyShop( $this->get_option( 'erip_API_key') );
	// 	//Получаем информацию о проведенном платеже в системе ЕРИП
	// 	$dataPaymentsEripSystem = $api->getInfoPaymentsWithOrderID( $order->get_id() );
  //
	// 	//Замена плейсхолдеров на данные из отвера платёжной системы
	// 	$instructionEripPays = isset( $dataPaymentsEripSystem->transaction->erip->instruction[0] ) ?
	// 								$dataPaymentsEripSystem->transaction->erip->instruction[0]
	// 								:
	// 								$dataPaymentsEripSystem->transaction->erip->instruction;
  //
	// 	$message = wpautop( $this->get_option( 'description_configuration_auto_mode') );
	// 	$message = str_replace("{{instruction_erip}}", $instructionEripPays, $message);
	// 	$message = str_replace("{{order_number}}", $dataPaymentsEripSystem->transaction->order_id, $message);
  //
  //   $this->log( __( 'Сформирована инструкция по оплате' ) . " ". $message );
  //
	// 	return $message;
	// }

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

    $response = $this->_api_client( $arrayDataInvoice );

    if ( $response ) {
  		// Отправляем инструкцию как оплатить через ЕРИП
  		// $this->sendEripInstructionEmail( $order, $response );
  		update_post_meta( $order->get_id(), '_begateway_transaction_id', $response->transaction->uid );
    } else {
      return new WP_Error( 'begateway_error', __( 'Ошибка создания счета в ЕРИП', 'woocommerce-begateway' ) );
    }

		return $response;
	}

  /** API client
  * @param array $arData data to post to API
  * @return mixed
  **/
  protected function _api_client( $arData ) {

    $url = 'https://'. self::DOMAIN_API .'/beyag/payments';
    $headers = array(
            "Content-Type: application/json",
            "Content-Length: " . strlen( json_encode( $arData ) )
        );
    $ch = curl_init( $url );
    curl_setopt( $ch, CURLOPT_PORT, 443 );
    curl_setopt( $ch, CURLOPT_HEADER, 0 );
    curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 1 );
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
    curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
    curl_setopt( $ch, CURLOPT_FORBID_REUSE, 1 );
    curl_setopt( $ch, CURLOPT_FRESH_CONNECT, 1 );
    curl_setopt( $ch, CURLOPT_POST, 1 );
    curl_setopt( $ch, CURLOPT_USERPWD, $this->get_option( 'erip_id_magazin' ).':'.$this->get_option( 'erip_API_key' ) );
    curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $arData ) );

    $response = curl_exec( $ch );

    if ( $response === false ) {
      $this->log( curl_errno( $ch ) . ': ' . curl_error( $ch ), 'error' );
      curl_close( $ch );
      return false;
    }

    curl_close($ch);
    $response = json_decode( $response );

    if ( is_null( $response ) || $response === false ) {
      $this->log( __( 'Ошибка обработки JSON-ответа', 'woocommerce-begateway-erip' ), 'error' );
      return false;
    }

    if ( $response->transaction->status != 'pending' ) {
      $this->log( __( 'Ошибка регистрации счета в ЕРИП', 'woocommerce-begateway-erip' ), 'error' );
      return false;
    }

    return $response;
  }

	/*
		Отправка сообщения с инструкцией об оплате
	*/
	protected function sendEripInstructionEmail( $order, $dataPaymentsEripSystem )
	{
		global $woocommerce;
		if (!$order) return FALSE;

    // Create a mailer
  	$mailer = $woocommerce->mailer();

  	$message_body = wpautop( wptexturize( $this->get_option( 'description_erip_order_pay' ) ) );
		//Замена плейсхолдеров на данные из отвера платёжной системы
		$instructionEripPays = isset($dataPaymentsEripSystem->transaction->erip->instruction[0]) ?
									$dataPaymentsEripSystem->transaction->erip->instruction[0]
									:
									$dataPaymentsEripSystem->transaction->erip->instruction;

		$message_body = str_replace( "{{instruction_erip}}", $instructionEripPays, $message_body );
		$message_body = str_replace( "{{order_number}}", $dataPaymentsEripSystem->transaction->order_id, $message_body );
		$message_body = str_replace( "{{fio}}", $order->get_billing_first_name() ." ". $order->get_billing_last_name(), $message_body );
		$message_body = str_replace( "{{name_shop}}", get_option( 'blogname' ), $message_body);

  	//$message = $mailer->wrap_message($message_body);
  	$message 		= $mailer->wrap_message(
    // Message head and message body.
    sprintf( __('Инструкция об оплате заказа № %s'), $order->get_order_number() ), $message_body );
  	// Client email, email subject and message.
		$result = $mailer->send( $order->get_billing_email(), sprintf( __( 'Инструкция об оплате заказа № %s', 'woocommerce-begateway-erip' ), $order->get_order_number() ), $message );
	}

	// Submit payment and handle response
	public function process_payment( $order_id ) {
		global $woocommerce;

    $order = new WC_order( $order_id );

		if ($this->get_option( 'type_sposoba_oplati' ) == 'manual') {
      $this->log( 'Ручная выставление счета для заказа ' . $order_id );

			//Оплата будет происходить в ручном режиме
      $this->_instruction = $this->get_option( 'description_configuration_manual_mode' );
      $this->_instruction = $this->_update_instruction( $this->_instruction, $order );

      $this->log( 'ЕРИП инструкция ' . $this->_instruction );
  		update_post_meta( $order->get_id(), '_begateway_erip_instruction', $this->_instruction );

			// Mark as on-hold
			$order->update_status( 'on-hold', __( 'Заказ требует уточнения остатков и обратного звонка клиенту', 'woocommerce-begateway-erip' ) );

			// Reduce stock levels
			wc_reduce_stock_levels( $order );

			// Remove cart
			$woocommerce->cart->empty_cart();

			// Return thankyou redirect
			return array(
				'result' => 'success',
				'redirect' => $this->get_return_url( $order )
			);
		} else {
      $this->log( 'Автоматическое выставление счета для заказа ' . $order_id );
			$this->_response_erip = $this->create_invoice_with_erip( $order );

      if ( !is_wp_error( $this->_response_erip ) ) {
        $this->log( 'Успешно выставлен счет в ЕРИП для заказа ' . $order_id );

        $this->_instruction = $this->get_option( 'description_configuration_auto_mode' );
        $this->_instruction = $this->_update_instruction( $this->_instruction, $order );

        $this->log( 'ЕРИП инструкция ' . $this->_instruction );
    		update_post_meta( $order->get_id(), '_begateway_erip_instruction', $this->_instruction );

        $this->_instruction = $this->get_option( 'description_erip_order_pay' );
        $this->_instruction = $this->_update_instruction( $this->_instruction, $order );

    		update_post_meta( $order->get_id(), '_begateway_erip_instruction_email', $this->_instruction );

  			// Mark as pending
  			$order->update_status('pending', __( 'Ожидается оплата заказа', 'woocommerce-begateway-erip' ));
  			// Reduce stock levels
        wc_reduce_stock_levels( $order );
  			// Remove cart
  			$woocommerce->cart->empty_cart();

  			// Return thankyou redirect
  			return array(
  				'result' => 'success',
  				'redirect' => $this->get_return_url( $order )
  			);
      } else {
        $this->log( 'Ошибка выставленя счета в ЕРИП для заказа ' . $order_id );
        $this->log( 'Ошибка ' . $this->_response_erip->get_error_message() );
        wc_add_notice( $this->_response_erip->get_error_message(), 'error' );
      }
		}
	}

  /**
	 * Add content to the WC emails.
	 *
	 * @access public
	 * @param WC_Order $order Order object.
	 * @param bool     $sent_to_admin Sent to admin.
	 * @param bool     $plain_text Email format: plain text or HTML.
	 */
	public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
    $this->_instruction = $order->get_meta( '_begateway_erip_instruction_email', true);

		if ( $this->_instruction &&
         ! $sent_to_admin &&
         $this->id === $order->get_payment_method() &&
         $order->has_status( 'on-hold' ) ) {
			echo wp_kses_post( wpautop( wptexturize( $this->_instruction ) ) . PHP_EOL );
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
