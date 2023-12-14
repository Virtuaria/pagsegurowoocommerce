<?php
/**
 * Gateway class.
 *
 * @package Virtuaria/PagSeguro/Classes/Gateway
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Gateway.
 */
class WC_Virtuaria_PagSeguro_Gateway_Pix extends WC_Payment_Gateway {
	use Virtuaria_PagSeguro_Common,
	Virtuaria_PagSeguro_Pix;

	/**
	 * Hours to valid payment from pix.
	 *
	 * @var int
	 */
	public $pix_validate;

	/**
	 * True if pix payment is enabled.
	 *
	 * @var bool
	 */
	public $pix_enable;

	/**
	 * Percentage from pix discount.
	 *
	 * @var float
	 */
	public $pix_discount;

	/**
	 * True if login and register in checkout enable.
	 *
	 * @var bool
	 */
	public $signup_checkout;

	/**
	 * Message to confirm payment from pix.
	 *
	 * @var string
	 */
	public $pix_msg_payment;

	/**
	 * True if pix discount is disabled together coupons.
	 *
	 * @var bool
	 */
	public $pix_discount_coupon;

	/**
	 * Prefix to transactions.
	 *
	 * @var string
	 */
	public $invoice_prefix;

	/**
	 * Global settings.
	 *
	 * @var array
	 */
	public $global_settings;

	/**
	 * Log instance.
	 *
	 * @var WC_logger
	 */
	public $log;

	/**
	 * Instance from WC_Virtuaria_PagSeguro_API.
	 *
	 * @var WC_Virtuaria_PagSeguro_API
	 */
	protected $api;

	/**
	 * Token.
	 *
	 * @var token
	 */
	public $token;

	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
		$this->id                 = 'virt_pagseguro_pix';
		$this->icon               = apply_filters(
			'woocommerce_pagseguro_virt_icon',
			VIRTUARIA_PAGSEGURO_URL . '/public/images/pagseguro.png'
		);
		$this->has_fields         = true;
		$this->method_title       = __( 'PagSeguro Pix', 'virtuaria-pagseguro' );
		$this->method_description = __(
			'Pague com pix.',
			'virtuaria-pagseguro'
		);

		$this->supports = array( 'products', 'refunds' );

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Define user set variables.
		$this->title               = $this->get_option( 'title' );
		$this->description         = $this->get_option( 'description' );
		$this->pix_validate        = $this->get_option( 'pix_validate' );
		$this->pix_enable          = $this->enabled;
		$this->pix_discount        = $this->get_option( 'pix_discount' );
		$this->signup_checkout     = 'yes' === get_option( 'woocommerce_enable_signup_and_login_from_checkout' );
		$this->pix_msg_payment     = $this->get_option( 'pix_msg_payment' );
		$this->pix_discount_coupon = 'yes' === $this->get_option( 'pix_discount_coupon' );

		$this->global_settings = get_option( 'woocommerce_virt_pagseguro_settings' );
		$this->invoice_prefix  = $this->get_invoice_prefix();

		// Active logs.
		$this->log = $this->get_log();

		$this->token = $this->get_token();

		// Set the API.
		$this->api = new WC_Virtuaria_PagSeguro_API( $this );

		// // Main actions.
		add_action(
			'woocommerce_update_options_payment_gateways_' . $this->id,
			array( $this, 'process_admin_options' )
		);

		// Transparent checkout actions. Pix code in mail and thankyou page.
		add_action(
			'woocommerce_thankyou_' . $this->id,
			array( $this, 'pix_thankyou_page' )
		);
		add_action(
			'woocommerce_email_after_order_table',
			array( $this, 'pix_email_instructions' ),
			10,
			3
		);
		add_action(
			'wp_enqueue_scripts',
			array( $this, 'public_pix_scripts_styles' )
		);

		// Additional charge.
		add_action(
			'add_meta_boxes_shop_order',
			array( $this, 'additional_charge_metabox' )
		);
		add_action(
			'save_post_shop_order',
			array( $this, 'do_additional_charge' )
		);

		// Simulate Pix payment.
		add_action(
			'add_meta_boxes_shop_order',
			array( $this, 'pix_payment_metabox' )
		);
		add_action(
			'save_post_shop_order',
			array( $this, 'make_pix_payment' )
		);

		add_filter(
			'woocommerce_billing_fields',
			array( $this, 'billing_neighborhood_required' ),
			9999
		);
		add_filter(
			'virtuaria_pagseguro_disable_discount',
			array( $this, 'disable_discount_by_product_categoria' ),
			10,
			2
		);
		add_filter(
			'woocommerce_gateway_title',
			array( $this, 'discount_pix_text' ),
			10,
			2
		);
		add_action(
			'after_virtuaria_pix_validate_text',
			array( $this, 'info_about_categories' )
		);

		// Fetch order status.
		add_action(
			'add_meta_boxes_shop_order',
			array( $this, 'fetch_order_status_metabox' ),
		);
		add_action(
			'save_post_shop_order',
			array( $this, 'search_order_payment_status' )
		);
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {
		$this->form_fields = $this->get_default_settings()
			+ $this->get_pix_default_settings();
	}
}
