<?php
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * BeGateway payment method integration
 *
 * @since 2.2.0
 */
final class WC_Begateway_Erip_Blocks_Support extends AbstractPaymentMethodType {
	/**
	 * Name of the payment method.
	 *
	 * @var string
	 */
	protected $name = 'begateway_erip';

	/**
	 * Initializes the payment method type.
	 */
	public function initialize() {
		$this->settings = get_option( 'woocommerce_begateway_erip_settings', [] );
	}

	/**
	 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active() {
		$payment_gateways_class   = WC()->payment_gateways();
		$payment_gateways         = $payment_gateways_class->payment_gateways();

		return $payment_gateways['begateway_erip']->is_available();
	}

	/**
	 * Returns an array of scripts/handles to be registered for this payment method.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles() {
		$script_path       = '/assets/js/frontend/blocks.js';
		$script_asset_path = WC_BeGateway_Erip::plugin_abspath() . 'assets/js/frontend/blocks.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require( $script_asset_path )
			: array(
				'dependencies' => array(),
				'version'      => WC_BeGateway_Erip::plugin_version()
			);
		$script_url        = WC_BeGateway_Erip::plugin_url() . $script_path;

		wp_register_script(
			'wc-begateway-erip-payment-payments-blocks',
			$script_url,
			$script_asset[ 'dependencies' ],
			$script_asset[ 'version' ],
			true
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'wc-begateway-erip-payment-payments-blocks', 'wc-begateway-erip-payment-payment', WC_BeGateway_Erip::plugin_abspath() . 'languages/' );
		}

		return [ 'wc-begateway-erip-payment-payments-blocks' ];
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		return [
			'title'       => $this->get_setting( 'name_sposoba_oplati' ),
			'description' => $this->get_setting( 'description_sposoba_oplati' ),
			'supports'    => $this->get_supported_features(),
			'logo_url'    => WC_BeGateway_Erip::plugin_url() . '/assets/images/erip.png',
		];
	}

	/**
	 * Returns an array of supported features.
	 *
	 * @return string[]
	 */
	public function get_supported_features() {
		$payment_gateways = WC()->payment_gateways->payment_gateways();
		return $payment_gateways['begateway_erip']->supports;
	}
}
