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
class WC_Virtuaria_PagSeguro_Gateway_Ticket extends WC_Payment_Gateway {
	use Virtuaria_PagSeguro_Common,
	Virtuaria_PagSeguro_Ticket;

	/**
	 * Day to valid payment from ticket.
	 *
	 * @var int
	 */
	public $ticket_validate;

	/**
	 * True if ticket payment is enabled.
	 *
	 * @var bool
	 */
	public $ticket_enable;

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
	 * Constructor for the gateway.
	 */
	public function __construct() {
		$this->id                 = 'virt_pagseguro_ticket';
		$this->icon               = apply_filters(
			'woocommerce_pagseguro_virt_icon',
			VIRTUARIA_PAGSEGURO_URL . '/public/images/pagseguro.png'
		);
		$this->has_fields         = true;
		$this->method_title       = __( 'PagSeguro Boleto', 'virtuaria-pagseguro' );
		$this->method_description = __(
			'Pague com boleto bancário.',
			'virtuaria-pagseguro'
		);

		$this->supports = array( 'products' );

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Define user set variables.
		$this->title           = $this->get_option( 'title' );
		$this->description     = $this->get_option( 'description' );
		$this->ticket_enable   = $this->enabled;
		$this->ticket_validate = $this->get_option( 'ticket_validate' );
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
			'woocommerce_thankyou_' . $this->id,
			array( $this, 'ticket_thankyou_page' )
		);
		add_action(
			'woocommerce_email_after_order_table',
			array( $this, 'ticket_email_instructions' ),
			10,
			3
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
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {
		$this->form_fields = $this->get_default_settings()
			+ $this->get_ticket_default_settings();
	}
}
