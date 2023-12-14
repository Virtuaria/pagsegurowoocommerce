<?php
/**
 * Add credit payments.
 *
 * @package Virtuaria/PagSeguro/Classes/Gateway
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Gateway.
 */
class WC_Virtuaria_PagSeguro_Gateway_Credit extends WC_Payment_Gateway {
	use Virtuaria_PagSeguro_Common,
	Virtuaria_PagSeguro_Credit;

	/**
	 * Installments.
	 *
	 * @var int
	 */
	public $installments;

	/**
	 * Installments tax.
	 *
	 * @var float
	 */
	public $tax;

	/**
	 * Min value to installments.
	 *
	 * @var int
	 */
	public $min_installment;

	/**
	 * Apply tax from installments.
	 *
	 * @var int
	 */
	public $fee_from;

	/**
	 * Credit invoice description.
	 *
	 * @var string
	 */
	public $soft_descriptor;

	/**
	 * True if credit payment is enabled.
	 *
	 * @var bool
	 */
	public $credit_enable;

	/**
	 * True if login and register in checkout enable.
	 *
	 * @var bool
	 */
	public $signup_checkout;

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
	 * Store card info.
	 *
	 * @var string
	 */
	public $save_card_info;

	/**
	 * Observations.
	 *
	 * @var string
	 */
	public $comments;

	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
		$this->id                 = 'virt_pagseguro_credit';
		$this->icon               = apply_filters(
			'woocommerce_pagseguro_virt_icon',
			VIRTUARIA_PAGSEGURO_URL . '/public/images/pagseguro.png'
		);
		$this->has_fields         = true;
		$this->method_title       = __( 'PagSeguro Crédito', 'virtuaria-pagseguro' );
		$this->method_description = __(
			'Pague com cartão de crédito.',
			'virtuaria-pagseguro'
		);

		$this->supports = array( 'products', 'refunds' );

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Define user set variables.
		$this->title           = $this->get_option( 'title' );
		$this->description     = $this->get_option( 'description' );
		$this->installments    = $this->get_option( 'installments' );
		$this->tax             = $this->get_option( 'tax' );
		$this->min_installment = $this->get_option( 'min_installment' );
		$this->fee_from        = $this->get_option( 'fee_from' );
		$this->soft_descriptor = $this->get_option( 'soft_descriptor' );
		$this->save_card_info  = $this->get_option( 'save_card_info' );
		$this->credit_enable   = $this->enabled;
		$this->comments        = $this->get_option( 'comments' );
		$this->signup_checkout = 'yes' === get_option( 'woocommerce_enable_signup_and_login_from_checkout' );

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

		add_action(
			'wp_enqueue_scripts',
			array( $this, 'public_credit_scripts_styles' )
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

		add_action(
			'admin_init',
			array( $this, 'erase_cards' ),
			20
		);

		add_filter(
			'woocommerce_billing_fields',
			array( $this, 'billing_neighborhood_required' ),
			9999
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

		add_action(
			'woocommerce_single_product_summary',
			array( $this, 'display_product_installments' )
		);
		add_action(
			'woocommerce_after_shop_loop_item_title',
			array( $this, 'loop_products_installment' ),
			15
		);
		add_filter(
			'woocommerce_available_variation',
			array( $this, 'variation_discount_and_installment' ),
			10,
			3
		);
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {
		$this->form_fields = $this->get_default_settings()
			+ $this->get_credit_default_settings();
	}
}
