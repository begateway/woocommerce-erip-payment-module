<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class SPYR_ERIP_GATEWAY extends WC_Payment_Gateway {
	static protected $optionsPlugin = null;

	// Setup our Gateway's id, description and other values
	function __construct() {

		// The global ID for this Payment method
		$this->id = "SPYR_ERIP_GATEWAY";

		// This basically defines your settings which are then loaded with init_settings()
		$this->init_form_fields();

		// After init_settings() is called, you can get the settings and load them into variables, e.g:
		// $this->title = $this->get_option( 'title' );
		$this->init_settings();

		// Turn these settings into variables we can use
		foreach ( $this->settings as $setting_key => $value ) {
			$this->$setting_key = $value;
			self::$optionsPlugin[$setting_key] = $value;
		}

		// The Title shown on the top of the Payment Gateways Page next to all the other Payment Gateways
		$this->method_title = __( "plugin_method_title", 'woocommerce-erip-payments' );

		// The description for this Payment Gateway, shown on the actual Payment options page on the backend
		$this->method_description = __( "plugin_method_description", 'woocommerce-erip-payments' );

		// The title to be used for the vertical tabs that can be ordered top to bottom
		$this->title = $this->get_option( 'name_sposoba_oplati' );

		// If you want to show an image next to the gateway's name on the frontend, enter a URL to an image.
		$this->icon = null;

		// Bool. Can be set to true if you want payment fields to show on the checkout
		// if doing a direct integration, which we are doing in this case
		$this->has_fields = true;

		// Supports the default description
		$this->supports = array('');

		$this->description = wpautop( self::$optionsPlugin['description_sposoba_oplati'] );

    new WC_ERIP;

		// Save settings
		if ( is_admin() ) {
			// Versions over 2.0
			// Save our administration options. Since we are not going to be doing anything special
			// we have not defined 'process_admin_options' in this class so the method in the parent
			// class will be used instead
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		}
	} // End __construct()

	static function thankyou_order_received_text_generate($order) {
		//Проверяем режим работы обработки заказов плагина
		if (self::$optionsPlugin['type_sposoba_oplati'] == 'manual') {
			//В случае ручной обработки заказов, выдаём сообщение
			return wpautop( self::$optionsPlugin['description_confiration_manual_mode'] );
		} else {
			global $woocommerce;

			$api = new API;
			$api->setDomainAPI( self::$optionsPlugin['erip_API_domain'] );
			$api->setIdShop( self::$optionsPlugin['erip_id_magazin'] );
			$api->setApiKeyShop( self::$optionsPlugin['erip_API_key'] );
			//Получаем информацию о проведенном платеже в системе ЕРИП
			$dataPaymentsEripSystem = $api->getInfoPaymentsWithOrderID($order->post->ID);

			//Замена плейсхолдеров на данные из отвера платёжной системы
			$instructionEripPays = isset($dataPaymentsEripSystem->transaction->erip->instruction[0]) ?
										$dataPaymentsEripSystem->transaction->erip->instruction[0]
										:
										$dataPaymentsEripSystem->transaction->erip->instruction;

			$message = wpautop( self::$optionsPlugin['description_confiration_auto_mode'] );
			$message = str_replace("{{instruction_value_from_response}}", $instructionEripPays, $message);
			$message = str_replace("{{order_number}}", $dataPaymentsEripSystem->transaction->order_id, $message);
			return $message;
		}
	}

	// Build the administration fields for this specific Gateway
	public function init_form_fields() {
		$this->form_fields = array(
			//Включение платежного шлюза
			'enabled' => array(
				'title'		=> __( 'enable_disable_payments_gateway', 'woocommerce-erip-payments' ),
				'label'		=> __( 'enable_disable_payments_gateway_label', 'woocommerce-erip-payments' ),
				'type'		=> 'checkbox',
				'default'	=> 'no',
			),
			//Id магазина
			'erip_id_magazin' => array(
				'title'		=> __( 'erip_id_magazin_title', 'woocommerce-erip-payments' ),
				'type'		=> 'text',
				'desc_tip'	=> __( 'erip_id_magazin_desk', 'woocommerce-erip-payments' ),
				'placeholder'	=> '000',
			),
			//Ключ магазина
			'erip_API_key' => array(
				'title'		=> __( 'erip_API_key_title', 'woocommerce-erip-payments' ),
				'type'		=> 'text',
				'desc_tip'	=> __( 'erip_API_key_desk', 'woocommerce-erip-payments' ),
				'default'	=> '',
			),
			//Домен API формата api.example.com
			'erip_API_domain' => array(
				'title'		=> __( 'erip_API_domain_title', 'woocommerce-erip-payments' ),
				'type'		=> 'text',
				'desc_tip'	=> __( 'erip_API_domain_desk', 'woocommerce-erip-payments' ),
				'default'	=> 'api.bepaid.by',
			),
			//Код услуги  ЕРИП
			'erip_kod_uslugi' => array(
				'title'		=> __( 'erip_kod_uslugi_title', 'woocommerce-erip-payments' ),
				'type'		=> 'text',
				'desc_tip'	=> __( 'erip_kod_uslugi_desk', 'woocommerce-erip-payments' ),
				'default'	=> '',
			),
			//Имя поставщивка услуги в ЕРИП
			'erip_name_provider_uslugi' => array(
				'title'		=> __( 'erip_name_provider_uslugi_title', 'woocommerce-erip-payments' ),
				'type'		=> 'text',
				'desc_tip'	=> __( 'erip_name_provider_uslugi_desk', 'woocommerce-erip-payments' ),
				'default'	=> '',
			),
			//Информация для плательщика для печати на чеке
			'info_message_in_check' => array(
				'title'		=> __( 'info_message_in_check_title', 'woocommerce-erip-payments' ),
				'type'		=> 'text',
				'desc_tip'	=> __( 'info_message_in_check_desk', 'woocommerce-erip-payments' ),
				'default'	=> __( 'info_message_in_check_default', 'woocommerce-erip-payments' ),
			),
			//Имя способа оплаты для покупателя на странице выбора
			'name_sposoba_oplati' => array(
				'title'		=> __( 'name_sposoba_oplati_title', 'woocommerce-erip-payments' ),
				'type'		=> 'text',
				'desc_tip'	=> __( 'name_sposoba_oplati_desk', 'woocommerce-erip-payments' ),
				'default'	=> __( 'name_sposoba_oplati_default', 'woocommerce-erip-payments' ),
			),
			//Описание способа оплаты для покупателя
			'description_sposoba_oplati' => array(
				'title'		=> __( 'description_sposoba_oplati_title', 'woocommerce-erip-payments' ),
				'type'		=> 'textarea',
				'desc_tip'	=> __( 'description_sposoba_oplati_desk', 'woocommerce-erip-payments' ),
				'default'	=> __( 'description_sposoba_oplati_default', 'woocommerce-erip-payments' ),
				'css'		=> 'max-width:80%;'
			),
			//Текст-подтверждение заказа для ручного режима
			'description_confiration_manual_mode' => array(
				'title'		=> __( 'description_confiration_manual_mode_title', 'woocommerce-erip-payments' ),
				'type'		=> 'textarea',
				'desc_tip'	=> __( 'description_confiration_manual_mode_desk', 'woocommerce-erip-payments' ),
				'default'	=> __( 'description_confiration_manual_mode_default', 'woocommerce-erip-payments' ),
				'css'		=> 'max-width:80%;'
			),
			//Текст-подтверждение заказа для автоматического режима
			'description_confiration_auto_mode' => array(
				'title'		=> __( 'description_confiration_auto_mode_title', 'woocommerce-erip-payments' ),
				'type'		=> 'textarea',
				'desc_tip'	=> __( 'description_confiration_auto_mode_desk', 'woocommerce-erip-payments' ),
				'default'	=> __( 'description_confiration_auto_mode_default', 'woocommerce-erip-payments' ),
				'css'		=> 'max-width:80%;'
			),
			//Текст E-mail сообщения с информацией как оплатить заказ в системе ЕРИП
			'description_erip_order_pay' => array(
				'title'		=> __( 'description_erip_order_pay_title', 'woocommerce-erip-payments' ),
				'type'		=> 'textarea',
				'desc_tip'	=> __( 'description_erip_order_pay_desk', 'woocommerce-erip-payments' ),
				'default'	=> __( 'description_erip_order_pay_default', 'woocommerce-erip-payments' ),
				'css'		=> 'max-width:80%;'
			),
			//Создание счета в ЕРИП
			'type_sposoba_oplati' => array(
				'title'		=> __( 'type_invoice_create_title', 'woocommerce-erip-payments' ),
		    	'desc'    	=> __( 'type_invoice_create_desk', 'woocommerce-erip-payments' ),
		    	'css'     	=> 'min-width:150px;',
		    	'std'     	=> 'left',
		    	'default' 	=> 'left',
		    	'type'    	=> 'select',
		    	'options' 	=> array(
		      		'manual'	=> __( 'manual_type_create_invoice_title', 'woocommerce-erip-payments' ),
		      		'auto'		=> __( 'auto_type_create_invoice_title', 'woocommerce-erip-payments' ),
		    	),
		    	'desc_tip' 	=>  true,
		  	),
		);
	}

	/*
		Создание Счета на оплату в системе ЕРИП
	*/
	public function create_invoice_with_erip(&$order_sybmol_link) {
		$moneyTool = new Money($order_sybmol_link->get_total(), $order_sybmol_link->get_order_currency());

    $notification_url = WC()->api_request_url('WC_ERIP');
    $notification_url = str_replace('carts.local', 'webhook.begateway.com:8443', $notification_url);

		$arrayDataInvoice = [
 			"request" => [
 				//@NOTICE: getAmount vs getCents
				"amount" => $moneyTool->getCents(),
				"currency" => $moneyTool->getCurrency(),
				"description" => "Оплата заказа #".$order_sybmol_link->get_order_number(),
				"email" => $order_sybmol_link->billing_email,
				"ip" => Tools::getIp(),
				"order_id" => $order_sybmol_link->get_order_number(),
				"notification_url" => $notification_url,
        "tracking_id" => $order_sybmol_link->order_key,
				"customer" => [
					"first_name" => $order_sybmol_link->shipping_first_name,
					"last_name" => $order_sybmol_link->shipping_last_name,
					"country" => $order_sybmol_link->shipping_country,
					"city" => $order_sybmol_link->shipping_city,
					"zip" => $order_sybmol_link->shipping_postcode,
					"address" => $order_sybmol_link->shipping_address_1.' '.$order_sybmol_link->shipping_address_2,
					"phone" => $order_sybmol_link->billing_phone
	 			],
	 			"payment_method" => [
					"type" => "erip",
					"account_number" => $order_sybmol_link->get_order_number(),
					"service_no" => $this->get_option( 'erip_kod_uslugi' ),
					"service_info" => [
						"Оплата заказа ".$order_sybmol_link->get_order_number()
					],
					"receipt" => [
						$this->get_option( 'info_message_in_check' )
					]
				]
			]
 		];

		$url = 'https://'.$this->get_option( 'erip_API_domain' ).'/beyag/payments';
		$headers = array(
            "Content-Type: application/json",
            "Content-Length: " . strlen(json_encode($arrayDataInvoice))
        );
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_PORT, 443);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
    curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_USERPWD, $this->get_option( 'erip_id_magazin' ).':'.$this->get_option( 'erip_API_key' ));
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($arrayDataInvoice));

		$response = curl_exec($ch);

    if ($response === false) {
			wc_add_notice( curl_errno($ch) . ': ' . curl_error($ch), 'error' );
      curl_close($ch);
      return false;
    }
		curl_close($ch);
    $response = json_decode($response);

    if (is_null($response) || $response === false) {
			wc_add_notice( 'Ошибка обработки JSON-ответа', 'error' );
      return false;
    }

    if ($response->transaction->status != 'pending') {
      wc_add_notice( 'Ошибка регистрации счета в ЕРИП', 'error' );
      return false;
    }

		//Отправляем инструкцию как оплатить через ЕРИП
		$this->sendInfoPaymentsErip($order_sybmol_link, $response);
		update_post_meta($order_sybmol_link->get_order_number(), 'UID требования', $response->transaction->uid);

		return true;
	}

	/*
		Отправка сообщения с инструкцией об оплате
	*/
	protected function sendInfoPaymentsErip($order, $dataPaymentsEripSystem)
	{
		global $woocommerce;
		if (!$order) return FALSE;

    $to_email = $order->billing_email;
    $headers = 'From: '. $order->billing_email . "\r\n";
    // Create a mailer
  	$mailer = $woocommerce->mailer();

  	$message_body = wpautop( wptexturize( $this->get_option( 'description_erip_order_pay' ) ) );
		//Замена плейсхолдеров на данные из отвера платёжной системы
		$instructionEripPays = isset($dataPaymentsEripSystem->transaction->erip->instruction[0]) ?
									$dataPaymentsEripSystem->transaction->erip->instruction[0]
									:
									$dataPaymentsEripSystem->transaction->erip->instruction;

		$message_body = str_replace("{{instruction_erip}}", $instructionEripPays, $message_body);
		$message_body = str_replace("{{order_num}}", $dataPaymentsEripSystem->transaction->order_id, $message_body);
		$message_body = str_replace("{{fio}}", $order->shipping_first_name." ".$order->shipping_last_name, $message_body);
		$message_body = str_replace("{{name_shop}}", get_option( 'blogname' ), $message_body);
		$message_body = str_replace("{{name_provider_service}}", $this->get_option('erip_name_provider_uslugi'), $message_body);

  	//$message = $mailer->wrap_message($message_body);
  	$message 		= $mailer->wrap_message(
    // Message head and message body.
    sprintf('Инструкция об оплате заказа № %s', $order->get_order_number() ), $message_body );
  	// Client email, email subject and message.
		$result = $mailer->send( $order->billing_email, sprintf( 'Инструкция об оплате заказа № %s', $order->get_order_number() ), $message );
	}

	// Submit payment and handle response
	public function process_payment( $order_id ) {
		global $woocommerce;

		if ($this->get_option( 'type_sposoba_oplati' ) == 'manual') {
			//Оплата будет происходить в ручном режиме
			$order = wc_get_order( $order_id );

			// Mark as on-hold
			$order->update_status('on-hold', __( 'manual_mode_order_processing_title', 'woocommerce-erip-payments' ));

			// Remove cart
			$woocommerce->cart->empty_cart();

			// Reduce stock levels
			$order->reduce_order_stock();

			// Return thankyou redirect
			return array(
				'result' => 'success',
				'redirect' => $this->get_return_url( $order )
			);
		} else {
			//Оплата будет происходить в автоматическом режиме

			//Помечаем как оплаченый
			$order = wc_get_order( $order_id );

			//Создаем заказ в системе ЕРИП
			$dataResponseErip = $this->create_invoice_with_erip($order);

      if ($dataResponseErip) {
  			// Reduce stock levels
  			$order->reduce_order_stock();
  			// Remove cart
  			$woocommerce->cart->empty_cart();
  			// Mark as pending
  			$order->update_status('pending', __( 'Ожидается оплата заказа', 'woocommerce-erip-payments' ));
  			// Return thankyou redirect
  			return array(
  				'result' => 'success',
  				'redirect' => $this->get_return_url( $order )
  			);
      } else {
  			return array(
  				'result' => 'error',
  				'redirect' => $this->get_return_url( $order )
  			);
      }
		}
	}
}

class Tools {
	// lowercase first letter of functions. It is more standard for PHP
	static function getIP() {
	    if (isset($_SERVER)) {
	        if (isset($_SERVER["HTTP_X_FORWARDED_FOR"]))
	            return $_SERVER["HTTP_X_FORWARDED_FOR"];

	        if (isset($_SERVER["HTTP_CLIENT_IP"]))
	            return $_SERVER["HTTP_CLIENT_IP"];

	        return $_SERVER["REMOTE_ADDR"];
	    }

	    if (getenv('HTTP_X_FORWARDED_FOR'))
	        return getenv('HTTP_X_FORWARDED_FOR');

	    if (getenv('HTTP_CLIENT_IP'))
	        return getenv('HTTP_CLIENT_IP');

	    return getenv('REMOTE_ADDR');
	}
}

class Money {
  protected $_amount;
  protected $_currency;
  protected $_cents;
  public function __construct($amount = 0, $currency = 'USD') {
    $this->_currency = $currency;
    $this->setAmount($amount);
  }
  public function getCents() {
    $cents = ($this->_cents) ? $this->_cents : (int)($this->_amount * $this->_currency_multiplyer());
    return $cents;
  }
  public function setCents($cents) {
    $this->_cents = (int)$cents;
    $this->_amount = NULL;
  }
  public function setAmount($amount){
    $this->_amount = (float)$amount;
    $this->_cents = NULL;
  }
  public function getAmount() {
    $amount = ($this->_amount) ? $this->_amount : (float)($this->_cents / $this->_currency_multiplyer());
    return $amount;
  }
  public function setCurrency($currency){
    $this->_currency = $currency;
  }
  public function getCurrency() {
    return $this->_currency;
  }
  private function _currency_multiplyer() {
    //array currency code => mutiplyer
    $exceptions = array(
        'BIF' => 1,
        'BYR' => 1,
        'CLF' => 1,
        'CLP' => 1,
        'CVE' => 1,
        'DJF' => 1,
        'GNF' => 1,
        'IDR' => 1,
        'IQD' => 1,
        'IRR' => 1,
        'ISK' => 1,
        'JPY' => 1,
        'KMF' => 1,
        'KPW' => 1,
        'KRW' => 1,
        'LAK' => 1,
        'LBP' => 1,
        'MMK' => 1,
        'PYG' => 1,
        'RWF' => 1,
        'SLL' => 1,
        'STD' => 1,
        'UYI' => 1,
        'VND' => 1,
        'VUV' => 1,
        'XAF' => 1,
        'XOF' => 1,
        'XPF' => 1,
        'MOP' => 10,
        'BHD' => 1000,
        'JOD' => 1000,
        'KWD' => 1000,
        'LYD' => 1000,
        'OMR' => 1000,
        'TND' => 1000
    );
    $multiplyer = 100; //default value
    foreach ($exceptions as $key => $value) {
        if (($this->_currency == $key)) {
            $multiplyer = $value;
            break;
        }
    }
    return $multiplyer;
  }
}

/*
	Класс для обработки callback от ЕРИП
*/
class WC_ERIP extends SPYR_ERIP_GATEWAY {
	public function __construct() {
    	add_action('woocommerce_api_'.strtolower(get_class($this)), array(&$this, 'handle_callback'));
  	}

	public function handle_callback() {

		$postData =  (string)file_get_contents("php://input");
    $post_array = json_decode($postData, false);
    $order_id = $post_array->transaction->order_id;
    $order_key = $post_array->transaction->tracking_id;
    $status = $post_array->transaction->status;

    global $woocommerce;
		$order = wc_get_order($order_id);

    if (!$order || $order->order_key !== $order_key) {
      die('ERROR');
    }

		if ($post_array->transaction->status == 'successful') {
			$order->update_status('processing', 'Оплата через ЕРИП произведена');
			$response = 'OK Данные успешно получены! Заказ поставлен на выполнение.';
		} else {
			$response = 'OK Данные успешно получены! Статус заказа не изменён.';
		}

  	die($response);
	}
}
