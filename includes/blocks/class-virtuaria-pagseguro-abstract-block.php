<?php
/**
 * Virtuaria Payments Blocks integration.
 *
 * @package Virtuaria_PagSeguro/blocks
 */

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

defined( 'ABSPATH' ) || exit;

/**
 * Virtuaria Payments Blocks integration
 */
abstract class Virtuaria_PagSeguro_Abstract_Block extends AbstractPaymentMethodType {

	/**
	 * The gateway instance.
	 *
	 * @var WC_Virtuaria_PagSeguro_Gateway,
	 * WC_Virtuaria_PagSeguro_Gateway_Credit,
	 * WC_Virtuaria_PagSeguro_Gateway_Pix,
	 * WC_Virtuaria_PagSeguro_Gateway_Ticket
	 */
	protected $gateway;

	/**
	 * Payment method name/id/slug.
	 *
	 * @var string
	 */
	protected $name = 'virt_pagseguro';

	/**
	 * Initializes the payment method type.
	 */
	public function initialize() {
		$this->settings = get_option( 'woocommerce_virt_pagseguro_settings', array() );
		$gateways       = WC()->payment_gateways->payment_gateways();
		$this->gateway  = $gateways[ $this->name ];
	}

	/**
	 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active() {
		return $this->gateway->is_available();
	}

	/**
	 * Returns an array of scripts/handles to be registered for this payment method.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles() {
		wp_register_script(
			'virtuaria-blocks',
			VIRTUARIA_PAGSEGURO_URL . 'public/blocks/index.js',
			array(),
			filemtime( VIRTUARIA_PAGSEGURO_DIR . 'public/blocks/index.js' ),
			true
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'virtuaria-blocks' );
		}

		return array(
			'virtuaria-blocks',
		);
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		ob_start();
		$this->gateway->payment_fields();
		return array(
			'title'       => $this->gateway->title,
			'description' => $this->gateway->description,
			'supports'    => array_filter(
				$this->gateway->supports,
				array( $this->gateway, 'supports' )
			),
			'method_id'   => $this->gateway->id,
			'content'     => ob_get_clean(),
		);
	}

	/**
	 * Returns an array of supported features.
	 *
	 * @return string[]
	 */
	public function get_supported_features() {
		$features = array(
			'products',
		);

		if ( 'virt_pagseguro_ticket' !== $this->name ) {
			$features[] = 'refunds';
		}

		return $features;
	}
}
