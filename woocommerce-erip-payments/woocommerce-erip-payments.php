<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/*
Plugin Name: WooCommerce ERIP Gateway Payments
Plugin URI: https://github.com/begateway/woocommerce-erip-payment-module
Description: Модуль оплаты для системы "Расчёт" (ЕРИП) через агрегатора bePaid.by
Version: 1.0.3
Author: Markun Vladislav
Author Email: techsupport@bepaid.by
Text Domain: woocommerce-erip-payments
*/

define('PLUGIN_NAME_DOMAIN', 'spyr_erip_gateway');

// Include our Gateway Class and register Payment Gateway with WooCommerce
add_action( 'plugins_loaded', 'spyr_erip_gateway_init', 0 );
function spyr_erip_gateway_init() {
	// If the parent WC_Payment_Gateway class doesn't exist
	// it means WooCommerce is not installed on the site
	// so do nothing
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) return;

	// If we made it this far, then include our Gateway Class
	include_once( 'erip-payments-plugin.php' );

	//Подключение модели для работы с API
	include_once( 'modelAPIErip.php' );

	// Now that we have successfully included our class,
	// Lets add it too WooCommerce
	add_filter( 'woocommerce_payment_gateways', 'spyr_add_erip_gateway_gateway' );
	add_filter( 'woocommerce_order_actions', 'wdm_add_order_meta_box_actions' );

	register_post_status( 'wc-shipped', array(
         'label' => "Сгенерировать платежное поручение в системе ЕРИП",
         'public' => true,
         'exclude_from_search' => false,
         'show_in_admin_all_list' => true,
         'show_in_admin_status_list' => true,
         'label_count' => _n_noop( 'Shipped <span class="count">(%s)</span>', 'Shipped <span class="count">(%s)</span>' )
        ));

	/* Add Order action to Order action meta box */

	function wdm_add_order_meta_box_actions($actions)
	{
	   $actions['wdm_shipped'] = "Сгенерировать платежное поручение в системе ЕРИП";
	   return $actions;
	}

	function spyr_add_erip_gateway_gateway( $methods ) {
		$methods[] = 'SPYR_ERIP_GATEWAY';
		return $methods;
	}
}

// Add custom action links
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'spyr_erip_gateway_action_links' );
function spyr_erip_gateway_action_links( $links ) {
	$plugin_links = array(
		'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section='.PLUGIN_NAME_DOMAIN ) . '">' . __( 'settings', 'woocommerce-erip-payments' ) . '</a>',
	);

	// Merge our new link with the default ones
	return array_merge( $plugin_links, $links );
}

//Инициализация плагина и загрузка языкового пакета для плагина
add_action("init", "pluginname_init");
function pluginname_init() {
	$res = load_plugin_textdomain("woocommerce-erip-payments", false, basename( dirname( __FILE__ ) ) . '/languages/');
}

/**
 * Custom text on the receipt page.
 */
function isa_order_received_text( $text, $order ) {
    return SPYR_ERIP_GATEWAY::thankyou_order_received_text_generate($order);
}
add_filter('woocommerce_thankyou_order_received_text', 'isa_order_received_text', 10, 2 );

//Add callback if Shipped action called
add_filter( 'woocommerce_order_action_wdm_shipped', 'wdm_order_shipped_callback', 10, 1);
function wdm_order_shipped_callback($order)
{
  $plugin = new SPYR_ERIP_GATEWAY;
  $order->update_status('pending', __( 'Ожидание оплаты', 'woocommerce-erip-payments' ));
  return $plugin->create_invoice_with_erip($order);
}
