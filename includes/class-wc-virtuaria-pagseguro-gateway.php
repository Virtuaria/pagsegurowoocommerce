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
class WC_Virtuaria_PagSeguro_Gateway extends WC_Payment_Gateway {
	/**
	 * APP ID
	 *
	 * @var int
	 */
	private $app_id;

	/**
	 * APP url
	 *
	 * @var string
	 */
	private $app_url;

	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
		$this->id                 = 'virt_pagseguro';
		$this->icon               = apply_filters( 'woocommerce_pagseguro_virt_icon', VIRTUARIA_PAGSEGURO_URL . '/public/images/pagseguro.png' );
		$this->has_fields         = true;
		$this->method_title       = __( 'PagSeguro', 'virtuaria-pagseguro' );
		$this->method_description = __( 'Pague com cartão de crédito e boleto.', 'virtuaria-pagseguro' );

		$this->supports = array( 'products', 'refunds' );

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Define user set variables.
		$this->title               = $this->get_option( 'title' );
		$this->description         = $this->get_option( 'description' );
		$this->environment         = $this->get_option( 'environment' );
		$this->email               = $this->get_option( 'email' );
		$this->debug               = $this->get_option( 'debug' );
		$this->installments        = $this->get_option( 'installments' );
		$this->tax                 = $this->get_option( 'tax' );
		$this->min_installment     = $this->get_option( 'min_installment' );
		$this->fee_from            = $this->get_option( 'fee_from' );
		$this->soft_descriptor     = $this->get_option( 'soft_descriptor' );
		$this->ticket_validate     = $this->get_option( 'ticket_validate' );
		$this->pix_validate        = $this->get_option( 'pix_validate' );
		$this->pix_enable          = $this->get_option( 'pix_enable' );
		$this->ticket_enable       = $this->get_option( 'ticket_enable' );
		$this->credit_enable       = $this->get_option( 'credit_enable' );
		$this->process_mode        = $this->get_option( 'process_mode' );
		$this->debug               = $this->get_option( 'debug' );
		$this->pix_discount        = $this->get_option( 'pix_discount' );
		$this->invoice_prefix      = $this->get_option( 'invoice_prefix', 'WC-' );
		$this->signup_checkout     = 'yes' === get_option( 'woocommerce_enable_signup_and_login_from_checkout' );
		$this->pix_msg_payment     = $this->get_option( 'pix_msg_payment' );
		$this->pix_discount_coupon = 'yes' === $this->get_option( 'pix_discount_coupon' );

		// Active logs.
		if ( 'yes' === $this->debug ) {
			if ( function_exists( 'wc_get_logger' ) ) {
				$this->log = wc_get_logger();
			} else {
				$this->log = new WC_Logger();
			}
		}

		if ( 'sandbox' === $this->environment ) {
			$this->app_id     = 'a2c55b69-d66f-4bf0-80f9-21d504ebf559';
			$this->app_url    = 'pagseguro.virtuaria.com.br/auth/pagseguro-sandbox';
			$this->app_revoke = 'https://pagseguro.virtuaria.com.br/revoke/pagseguro-sandbox';
			$this->token      = $this->get_option( 'token_sanbox' );
		} else {
			$this->app_id     = '7acbe665-76c3-4312-afd5-29c263e8fb93';
			$this->app_url    = 'pagseguro.virtuaria.com.br/auth/pagseguro';
			$this->app_revoke = 'https://pagseguro.virtuaria.com.br/revoke/pagseguro';
			$this->token      = $this->get_option( 'token_production' );
		}

		// Set the API.
		$this->api = new WC_Virtuaria_PagSeguro_API( $this );

		// // Main actions.
		add_action( 'woocommerce_api_wc_virtuaria_pagseguro_gateway', array( $this, 'ipn_handler' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

		// Transparent checkout actions. Pix code in mail and thankyou page.
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
		add_action( 'woocommerce_email_after_order_table', array( $this, 'email_instructions' ), 10, 3 );
		add_action( 'wp_enqueue_scripts', array( $this, 'checkout_scripts' ) );

		// Additional charge.
		add_action( 'add_meta_boxes_shop_order', array( $this, 'additional_charge_metabox' ), 10 );
		add_action( 'save_post_shop_order', array( $this, 'do_additional_charge' ) );

		// Pix.
		add_action( 'pagseguro_pix_check_payment', array( $this, 'check_order_paid' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_checkout_scripts' ) );

		// Simulate Pix payment.
		add_action( 'add_meta_boxes_shop_order', array( $this, 'pix_payment_metabox' ), 10 );
		add_action( 'save_post_shop_order', array( $this, 'make_pix_payment' ) );

		add_action( 'pagseguro_process_update_order_status', array( $this, 'process_order_status' ), 10, 2 );

		add_action( 'admin_init', array( $this, 'save_store_token' ) );
		add_action( 'admin_notices', array( $this, 'virtuaria_pagseguro_not_authorized' ) );

		add_filter( 'woocommerce_billing_fields', array( $this, 'billing_neighborhood_required' ), 9999 );
		add_filter( 'virtuaria_pagseguro_disable_discount', array( $this, 'disable_discount_by_product_categoria' ), 10, 2 );
		add_filter( 'woocommerce_gateway_title', array( $this, 'discount_pix_text' ), 10, 2 );
		add_action( 'after_virtuaria_pix_validate_text', array( $this, 'info_about_categories' ) );

		// Fetch order status.
		add_action( 'add_meta_boxes_shop_order', array( $this, 'fetch_order_status_metabox' ), 10 );
		add_action( 'save_post_shop_order', array( $this, 'search_order_payment_status' ) );
	}

	/**
	 * Returns a value indicating the the Gateway is available or not. It's called
	 * automatically by WooCommerce before allowing customers to use the gateway
	 * for payment.
	 *
	 * @return bool
	 */
	public function is_available() {
		// Test if is valid for use.
		$available = 'yes' === $this->get_option( 'enabled' );

		if ( ! class_exists( 'Extra_Checkout_Fields_For_Brazil' ) ) {
			$available = false;
		}

		return $available;
	}

	/**
	 * Checkout scripts.
	 */
	public function checkout_scripts() {
		if ( is_checkout() && $this->is_available() ) {
			if ( ! get_query_var( 'order-received' ) ) {
				wp_enqueue_script(
					'pagseguro-virt',
					VIRTUARIA_PAGSEGURO_URL . 'public/js/checkout.js',
					array( 'jquery' ),
					filemtime( VIRTUARIA_PAGSEGURO_DIR . 'public/js/checkout.js' ),
					true
				);

				wp_enqueue_style(
					'pagseguro-virt',
					VIRTUARIA_PAGSEGURO_URL . 'public/css/checkout.css',
					'',
					filemtime( VIRTUARIA_PAGSEGURO_DIR . 'public/css/checkout.css' )
				);

				wp_enqueue_script(
					'pagseguro-sdk',
					'https://assets.pagseguro.com.br/checkout-sdk-js/rc/dist/browser/pagseguro.min.js',
					array(),
					'1.1.1',
					true
				);

				$pub_key = $this->api->get_public_key();
				if ( $pub_key ) {
					wp_localize_script(
						'pagseguro-virt',
						'encriptation',
						array( 'pub_key' => $pub_key )
					);
				}

				if ( 'one' === $this->get_option( 'display' ) ) {
					wp_enqueue_style(
						'checkout-fields',
						VIRTUARIA_PAGSEGURO_URL . 'public/css/full-width.css',
						'',
						filemtime( VIRTUARIA_PAGSEGURO_DIR . 'public/css/full-width.css' )
					);
				}

				if ( 'yes' !== $this->credit_enable ) {
					wp_enqueue_style(
						'form-height',
						VIRTUARIA_PAGSEGURO_URL . 'public/css/form-height.css',
						'',
						filemtime( VIRTUARIA_PAGSEGURO_DIR . 'public/css/form-height.css' )
					);
				}
			} else {
				global $wp;
				wp_enqueue_script(
					'pagseguro-payment-on-hold',
					VIRTUARIA_PAGSEGURO_URL . 'public/js/on-hold-payment.js',
					array( 'jquery' ),
					filemtime( VIRTUARIA_PAGSEGURO_DIR . 'public/js/on-hold-payment.js' ),
					true
				);

				wp_localize_script(
					'pagseguro-payment-on-hold',
					'payment',
					array(
						'ajax_url'        => admin_url( 'admin-ajax.php' ),
						'order_id'        => $wp->query_vars['order-received'],
						'nonce'           => wp_create_nonce( 'fecth_order_status' ),
						'confirm_message' => $this->pix_msg_payment,
					)
				);

				wp_enqueue_style(
					'pagseguro-payment-on-hold',
					VIRTUARIA_PAGSEGURO_URL . 'public/css/on-hold-payment.css',
					'',
					filemtime( VIRTUARIA_PAGSEGURO_DIR . 'public/css/on-hold-payment.css' )
				);
			}
		}
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'             => array(
				'title'   => __( 'Habilitar', 'virtuaria-pagseguro' ),
				'type'    => 'checkbox',
				'label'   => __( 'Habilita o método de Pagamento Virtuaria PagSeguro', 'virtuaria-pagseguro' ),
				'default' => 'yes',
			),
			'title'               => array(
				'title'       => __( 'Título', 'virtuaria-pagseguro' ),
				'type'        => 'text',
				'description' => __( 'Isto controla o título exibido ao usuário durante o checkout.', 'virtuaria-pagseguro' ),
				'desc_tip'    => true,
				'default'     => __( 'PagSeguro', 'virtuaria-pagseguro' ),
			),
			'description'         => array(
				'title'       => __( 'Descrição', 'virtuaria-pagseguro' ),
				'type'        => 'textarea',
				'description' => __( 'Controla a descrição exibida ao usuário durante o checkout.', 'virtuaria-pagseguro' ),
				'default'     => __( 'Pague com PagSeguro.', 'virtuaria-pagseguro' ),
			),
			'comments'            => array(
				'title'       => __( 'Observações', 'virtuaria-pagseguro' ),
				'type'        => 'textarea',
				'description' => __( 'Exibe suas observações logo abaixo da descrição na tela de finalização da compra.', 'virtuaria-pagseguro' ),
				'default'     => __( 'Na área "Detalhes de Faturamento", recomendamos inserir os dados do titular do cartão. Caso a compra seja para outra pessoa, escolha "Entregar para um endereço diferente".', 'virtuaria-pagseguro' ),
			),
			'integration'         => array(
				'title'       => __( 'Integração', 'virtuaria-pagseguro' ),
				'type'        => 'title',
				'description' => '',
			),
			'environment'         => array(
				'title'       => __( 'Ambiente', 'virtuaria-pagseguro' ),
				'type'        => 'select',
				'description' => __( 'Selecione Sanbox para testes ou Produção para vendas reais.', 'virtuaria-pagseguro' ),
				'options'     => array(
					'sandbox'    => 'Sandbox',
					'production' => 'Produção',
				),
				'default'     => 'sandbox',
			),
			'email'               => array(
				'title'       => __( 'E-mail', 'virtuaria-pagseguro' ),
				'type'        => 'text',
				'description' => __( 'Informe seu e-mail utilizado na conta do Pagseguro. Isto é necessário para a confirmação do pagamento.', 'virtuaria-pagseguro' ),
				'default'     => '',
			),
			'autorization'        => array(
				'title'       => __( 'Autorização', 'virtuaria-pagseguro' ),
				'type'        => 'auth',
				'description' => __( 'Autorize o plugin a processar compras e reembolsos junto ao PagSeguro.', 'virtuaria-pagseguro' ),
				'default'     => '',
			),
			'process_mode'        => array(
				'title'       => __( 'Modo de processamento', 'virtuaria-pagseguro' ),
				'type'        => 'select',
				'description' => __( 'Define como os dados de retorno da API serão tratados. Se for assíncrono, o processamento do checkout será mais veloz, porém a mudança de status do pedido será feita via agendamento (cron).', 'virtuaria-pagseguro' ),
				'options'     => array(
					'sync'  => __( 'Síncrono', 'virtuaria-pagseguro' ),
					'async' => __( 'Assíncrono', 'virtuaria-pagseguro' ),
				),
				'default'     => 'sync',
			),
			'invoice_prefix'      => array(
				'title'       => __( 'Prefixo da transação', 'virtuaria-pagseguro' ),
				'type'        => 'text',
				'description' => __( 'Defina se você usa sua conta do PagSeguro para várias lojas, certifique-se de que esse prefixo seja único, pois o PagSeguro não permitirá pedidos com o mesmo número de fatura.', 'virtuaria-pagseguro' ),
				'default'     => 'WC-',
			),
			'payment_status'      => array(
				'title'       => __( 'Status após confirmação', 'virtuaria-pagseguro' ),
				'type'        => 'select',
				'description' => __( 'Define o status que o plugin usará ao receber confirmação de pagamento.', 'virtuaria-pagseguro' ),
				'options'     => $this->get_payment_status(),
				'default'     => 'processing',
			),
			'credit'              => array(
				'title'       => __( 'Cartão de crédito', 'virtuaria-pagseguro' ),
				'type'        => 'title',
				'description' => '',
			),
			'credit_enable'       => array(
				'title'       => __( 'Habilitar', 'virtuaria-pagseguro' ),
				'type'        => 'checkbox',
				'description' => __( 'Define se a opção de pagamento Crédito deve estar disponível durante o checkout.', 'virtuaria-pagseguro' ),
				'default'     => 'yes',
			),
			'installments'        => array(
				'title'       => __( 'Número de parcelas', 'virtuaria-pagseguro' ),
				'type'        => 'select',
				'description' => __( 'Selecione o número máximo de parcelas disponíveis para seus clientes.', 'virtuaria-pagseguro' ),
				'options'     => array(
					'1'  => '1x',
					'2'  => '2x',
					'3'  => '3x',
					'4'  => '4x',
					'5'  => '5x',
					'6'  => '6x',
					'7'  => '7x',
					'8'  => '8x',
					'9'  => '9x',
					'10' => '10x',
					'11' => '11x',
					'12' => '12x',
				),
				'default'     => 12,
			),
			'min_installment'     => array(
				'title'             => __( 'Valor mínimo da parcela (R$)', 'virtuaria-pagseguro' ),
				'type'              => 'number',
				'description'       => __( 'Define o valor mínimo que uma parcela pode receber.', 'virtuaria-pagseguro' ),
				'custom_attributes' => array(
					'min'  => 0,
					'step' => 'any',
				),
			),
			'tax'                 => array(
				'title'             => __( 'Taxa de juros (%)', 'virtuaria-pagseguro' ),
				'type'              => 'number',
				'description'       => __( 'Define o percentual de juros aplicado ao parcelamento.', 'virtuaria-pagseguro' ),
				'custom_attributes' => array(
					'min'  => 0,
					'step' => '0.01',
				),
			),
			'fee_from'            => array(
				'title'       => __( 'Parcelamento com juros ', 'virtuaria-pagseguro' ),
				'type'        => 'select',
				'description' => __( 'Define a partir de qual parcela os juros serão aplicados.', 'virtuaria-pagseguro' ),
				'options'     => array(
					'2'  => '2x',
					'3'  => '3x',
					'4'  => '4x',
					'5'  => '5x',
					'6'  => '6x',
					'7'  => '7x',
					'8'  => '8x',
					'9'  => '9x',
					'10' => '10x',
					'11' => '11x',
					'12' => '12x',
				),
			),
			'soft_descriptor'     => array(
				'title'             => __( 'Nome na fatura', 'virtuaria-pagseguro' ),
				'type'              => 'text',
				'description'       => 'Texto exibido na fatura do cartão para identificar a loja (máximo de <b>17 caracteres</b>, não deve conter caracteres especiais ou espaços em branco).',
				'custom_attributes' => array(
					'maxlength' => '17',
				),
			),
			'save_card_info'      => array(
				'title'       => __( 'Salvar dados de pagamento?', 'woocommerce-pagseguro' ),
				'type'        => 'select',
				'description' => __( 'Define se será possível memorizar as informações de pagamento do cliente para compras futuras', 'woocommerce-pagseguro' ),
				'desc_tip'    => true,
				'default'     => 'do_not_store',
				'class'       => 'wc-enhanced-select',
				'options'     => array(
					'do_not_store'     => __( 'Não memorizar (padrão)', 'woocommerce-pagseguro' ),
					'customer_defines' => __( 'O cliente decide sobre o armazenamento', 'woocommerce-pagseguro' ),
					'always_store'     => __( 'Sempre memorizar', 'woocommerce-pagseguro' ),
				),
			),
			'display'             => array(
				'title'       => __( 'Formulário de crédito', 'virtuaria-pagseguro' ),
				'type'        => 'select',
				'description' => __( 'Define como serão exibidos os campos do checkout.' ),
				'default'     => 'two',
				'options'     => array(
					'one' => __( 'Uma coluna', 'virtuaria-pagseguro' ),
					'two' => __( 'Duas colunas', 'virtuaria-pagseguro' ),
				),
			),
			'ticket'              => array(
				'title'       => __( 'Boleto', 'virtuaria-pagseguro' ),
				'type'        => 'title',
				'description' => '',
			),
			'ticket_enable'       => array(
				'title'       => __( 'Habilitar', 'virtuaria-pagseguro' ),
				'type'        => 'checkbox',
				'description' => __( 'Define se a opção de pagamento Boleto deve estar disponível durante o checkout.', 'virtuaria-pagseguro' ),
				'default'     => 'yes',
			),
			'ticket_validate'     => array(
				'title'             => __( 'Validade', 'virtuaria-pagseguro' ),
				'type'              => 'number',
				'description'       => __( 'Define o limite de dias onde o boleto pode ser pago.', 'virtuaria-pagseguro' ),
				'default'           => '5',
				'custom_attributes' => array(
					'min' => 1,
				),
			),
			'pix'                 => array(
				'title' => __( 'PIX', 'virtuaria-pagseguro' ),
				'type'  => 'title',
			),
			'pix_enable'          => array(
				'title'       => __( 'Habilitar', 'virtuaria-pagseguro' ),
				'type'        => 'checkbox',
				'description' => __( 'Define se a opção de pagamento Pix deve estar disponível durante o checkout.', 'virtuaria-pagseguro' ),
				'default'     => 'yes',
			),
			'pix_validate'        => array(
				'title'       => __( 'Validade do Código PIX', 'virtuaria-pagseguro' ),
				'type'        => 'select',
				'description' => __( 'Define o limite de tempo para aceitar pagamentos com PIX.', 'virtuaria-pagseguro' ),
				'options'     => array(
					'1800'  => '30 Minutos',
					'3600'  => '1 hora',
					'5400'  => '1 hora e 30 minutos',
					'7200'  => '2 horas',
					'9000'  => '2 horas e 30 minutos',
					'10800' => '3 horas',
				),
				'default'     => '1800',
			),
			'pix_msg_payment'     => array(
				'title'       => __( 'Pagamento confirmado', 'virtuaria-pagseguro' ),
				'type'        => 'textarea',
				'description' => __( 'Define a mensagem a ser exibida, na tela de pedido recebido, após a confirmação do pagamento.', 'virtuaria-pagseguro' ),
				'default'     => 'Seu pagamento foi aprovado!',
			),
			'pix_discount'        => array(
				'title'             => __( 'Desconto (%)', 'virtuaria-pagseguro' ),
				'type'              => 'number',
				'description'       => __( 'Define um percentual de desconto para aplicar sob o total da venda(não inclui o valor do frete) com pix', 'virtuaria-pagseguro' ),
				'custom_attributes' => array(
					'min'  => 0,
					'step' => '0.01',
				),
			),
			'pix_discount_coupon' => array(
				'title'       => __( 'Desabilitar desconto em cupons', 'virtuaria-pagseguro' ),
				'type'        => 'checkbox',
				'label'       => __( 'Desabilita o desconto Pix em conjunto com cupons', 'virtuaria-pagseguro' ),
				'description' => __( 'Marque se deseja desabilitar o desconto pix quando houver cupom de desconto aplicado.', 'virtuaria-pagseguro' ),
				'default'     => '',
			),
			'pix_discount_ignore' => array(
				'title'       => __( 'Desabilitar desconto em produtos das seguintes categorias', 'virtuaria-pagseguro' ),
				'type'        => 'ignore_discount',
				'description' => __( 'Define as categorias que serão ignoradas para o cálculo do desconto pix.', 'virtuaria-pagseguro' ),
				'default'     => '',
			),
		);

		if ( current_user_can( 'install_themes' ) ) {
			$this->form_fields['testing'] = array(
				'title'       => __( 'Depurar', 'virtuaria-pagseguro' ),
				'type'        => 'title',
				'description' => '',
			);
			$this->form_fields['debug']   = array(
				'title'       => __( 'Debug Log', 'virtuaria-pagseguro' ),
				'type'        => 'checkbox',
				'label'       => __( 'Habilitar registro de log', 'virtuaria-pagseguro' ),
				'default'     => 'yes',
				/* translators: %s: log page link */
				'description' => __( 'Registra eventos de comunição com a API e erros. Para visualizar clique <a href="' . admin_url( 'admin.php?page=wc-status&tab=logs&source=virt_pagseguro' ), 'virtuaria-pagseguro' ) . '">aqui</a>.',
			);
		}

		$this->form_fields['tecvirtuaria'] = array(
			'title' => __( 'Tecnologia Virtuaria', 'virtuaria-pagseguro' ),
			'type'  => 'title',
		);
	}

	/**
	 * Send email notification.
	 *
	 * @param string $recipient Email recipient.
	 * @param string $subject   Email subject.
	 * @param string $title     Email title.
	 * @param string $message   Email message.
	 */
	protected function send_email( $recipient, $subject, $title, $message ) {
		$mailer = WC()->mailer();

		$mailer->send( $recipient, $subject, $mailer->wrap_message( $title, $message ) );
	}

	/**
	 * Process the payment and return the result.
	 *
	 * @param  int $order_id Order ID.
	 * @return array
	 */
	public function process_payment( $order_id ) {
		if ( $this->signup_checkout
			|| ( isset( $_POST['new_charge_nonce'] )
			&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['new_charge_nonce'] ) ), 'do_new_charge' ) ) ) {
			$order = wc_get_order( $order_id );

			$paid = $this->api->new_charge( $order, $_POST );

			if ( ! isset( $paid['error'] ) ) {
				if ( $paid ) {
					$charge_amount = get_post_meta( $order_id, '_charge_amount', true );
					// $order->add_order_note( 'PagSeguro: cobrança recebida R$' . number_format( $charge_amount / 100, 2, ',', '.' ) );
					if ( $this->tax && ( ( $charge_amount / 100 ) - $order->get_total() ) > 0 ) {
						$fee = new WC_Order_Item_Fee();
						$fee->set_name( __( 'Parcelamento pagseguro', 'virtuaria-pagseguro' ) );
						$fee->set_total( ( $charge_amount / 100 ) - $order->get_total() );

						$order->add_item( $fee );
						$order->calculate_totals();
						$order->save();
					}
					if ( 'async' !== $this->process_mode ) {
						$order->update_status( $this->get_option( 'payment_status' ), __( 'PagSeguro: Pagamento aprovado.', 'virtuaria-pagseguro' ) );
					} else {
						$args = array( $order_id, $this->get_option( 'payment_status' ) );
						if ( ! wp_next_scheduled( 'pagseguro_process_update_order_status', $args ) ) {
							wp_schedule_single_event(
								strtotime( 'now' ) + 60,
								'pagseguro_process_update_order_status',
								$args
							);
						}
					}
				} else {
					$qr_code = $order->get_meta( '_pagseguro_qrcode' );
					if ( $qr_code ) {
						$this->add_qrcode_in_note( $order, $qr_code );

						$args = array( $order_id );
						if ( ! wp_next_scheduled( 'pagseguro_pix_check_payment', $args ) ) {
							wp_schedule_single_event(
								strtotime( 'now' ) + $this->pix_validate + 1800,
								'pagseguro_pix_check_payment',
								$args
							);
						}
					}

					if ( 'async' !== $this->process_mode ) {
						$order->update_status( 'on-hold', __( 'PagSeguro: Aguardando confirmação de pagamento.', 'virtuaria-pagseguro' ) );
					} else {
						$args = array( $order_id, 'on-hold' );
						if ( ! wp_next_scheduled( 'pagseguro_process_update_order_status', $args ) ) {
							wp_schedule_single_event(
								strtotime( 'now' ) + 60,
								'pagseguro_process_update_order_status',
								$args
							);
						}
					}
				}

				$payment_method = get_post_meta( $order_id, '_payment_mode', true );
				if ( 'PIX' === $payment_method ) {
					$order->set_payment_method_title(
						'PagSeguro Pix'
					);
				} elseif ( 'CREDIT_CARD' === $payment_method ) {
					$order->set_payment_method_title(
						'PagSeguro Crédito'
					);
				} elseif ( 'BOLETO' === $payment_method ) {
					$order->set_payment_method_title(
						'PagSeguro Boleto'
					);
				}
				$order->save();

				wc_reduce_stock_levels( $order_id );
				// Remove cart.
				WC()->cart->empty_cart();

				return array(
					'result'   => 'success',
					'redirect' => $this->get_return_url( $order ),
				);
			} else {
				wc_add_notice( 'Pagseguro: ' . $paid['error'], 'error' );

				return array(
					'result'   => 'fail',
					'redirect' => '',
				);
			}
		} else {
			wc_add_notice( 'Não foi possível processar a sua compra. Por favor, tente novamente mais tarde.', 'error' );

			return array(
				'result'   => 'fail',
				'redirect' => '',
			);
		}
	}

	/**
	 * Retrieve the raw request entity (body).
	 *
	 * @return string
	 */
	private function get_raw_data() {
		if ( function_exists( 'phpversion' ) && version_compare( phpversion(), '5.6', '>=' ) ) {
			return file_get_contents( 'php://input' );
		}
	}

	/**
	 * IPN handler.
	 */
	public function ipn_handler() {
		$body = $this->get_raw_data();

		$this->log->add( $this->id, 'IPN request...', WC_Log_Levels::INFO );
		$request = json_decode( $body, true );
		$this->log->add(
			$this->id,
			'Request to order ' . $body,
			WC_Log_Levels::INFO
		);

		if ( isset( $request['charges'] ) && isset( $request['reference_id'] ) ) {
			$this->log->add( $this->id, 'IPN valid', WC_Log_Levels::INFO );

			$order = wc_get_order(
				sanitize_text_field(
					wp_unslash(
						str_replace( $this->invoice_prefix, '', $request['reference_id'] )
					)
				)
			);

			$is_additional_charge = false;
			if ( $order && $order->get_transaction_id() !== $request['id'] ) {
				$is_additional_charge = true;
			}

			if ( $order
				&& isset( $request['charges'][0]['id'] )
				&& isset( $request['charges'][0]['status'] ) ) {

				if ( ! get_post_meta( $order->get_id(), '_charge_id', true ) && ! $is_additional_charge ) {
					update_post_meta( $order->get_id(), '_charge_id', $request['charges'][0]['id'] );
				}

				switch ( $request['charges'][0]['status'] ) {
					case 'CANCELED':
						$old_webhook = get_post_meta( $order->get_id(), '_canceled_webhook', true );
						if ( ! $is_additional_charge ) {
							$old_webhook = get_post_meta( $order->get_id(), '_canceled_webhook', true );
						} else {
							$old_webhook = get_post_meta( $order->get_id(), '_canceled_additional_webhook', true );
						}

						if ( ! $old_webhook || $body !== $old_webhook ) {
							$order->add_order_note(
								sprintf(
									/* translators: %s: amount */
									__( 'PagSeguro: R$ %s Devolvido(s).', 'virtuaria-pagseguro' ),
									number_format( $request['charges'][0]['amount']['summary']['refunded'] / 100, 2, ',', '.' )
								)
							);

							if ( ! $is_additional_charge ) {
								update_post_meta( $order->get_id(), '_canceled_webhook', $body );
							} else {
								update_post_meta( $order->get_id(), '_canceled_additional_webhook', $body );
							}
						}
						break;
					case 'IN_ANALYSIS':
						$order->add_order_note( __( 'PagSeguro: O PagSeguro está analisando o risco da transação.', 'virtuaria-pagseguro' ) );
						break;
					case 'DECLINED':
						$order->add_order_note( __( 'PagSeguro: Compra não autorizada.', 'virtuaria-pagseguro' ) );
						if ( ! $is_additional_charge ) {
							$order->update_status( 'cancelled', __( 'PagSeguro: Pagamento não aprovado.', 'virtuaria-pagseguro' ) );
						}
						break;
					case 'PAID':
						if ( 0 == $request['charges'][0]['amount']['summary']['refunded'] ) {
							if ( ! $is_additional_charge ) {
								$old_webhook = get_post_meta( $order->get_id(), '_paid_webhook', true );
							} else {
								$old_webhook = get_post_meta( $order->get_id(), '_paid_additional_charge_webhook', true );
							}

							if ( ! $old_webhook || $body !== $old_webhook ) {
								$order->add_order_note(
									sprintf(
										/* translators: %s: amount */
										__( 'PagSeguro: Cobrança recebida R$ %s.', 'virtuaria-pagseguro' ),
										// phpcs:ignore
										number_format( (string) $request['charges'][0]['amount']['value'] / 100, 2, ',', '.' )
									)
								);

								if ( ! $order->has_status( $this->get_option( 'payment_status' ) ) ) {
									$order->update_status( $this->get_option( 'payment_status' ), __( 'PagSeguro: Pagamento aprovado.', 'virtuaria-pagseguro' ) );
								}

								if ( $is_additional_charge ) {
									$adittionals = get_post_meta( $order->get_id(), '_additionals_charge_id', true );
									if ( ! $adittionals ) {
										$adittionals = array();
									}
									$adittionals[] = $request['charges'][0]['id'];
									update_post_meta( $order->get_id(), '_additionals_charge_id', $adittionals );
								}

								if ( ! get_post_meta( $order->get_id(), '_charge_id', true ) ) {
									update_post_meta( $order->get_id(), '_charge_id', $request['charges'][0]['id'] );
								}

								if ( ! $is_additional_charge ) {
									update_post_meta( $order->get_id(), '_paid_webhook', $body );
								} else {
									update_post_meta( $order->get_id(), '_paid_additional_charge_webhook', $body );
								}
							}
						}
						break;
				}
			}
			header( 'HTTP/1.1 200 OK' );
			return;
		} elseif ( 'transaction' === $request['notificationType'] && isset( $request['notificationCode'] ) ) {
			$this->log->add( $this->id, 'IPN valid', WC_Log_Levels::INFO );
			$sandbox = 'sandbox' === $this->environment ? 'sandbox.' : '';
			$url     = 'https://ws.' . $sandbox . 'pagseguro.uol.com.br/v3/transactions/notifications/';
			$url    .= $request['notificationCode'] . '?email=' . $this->email . '&token=' . $this->token;

			$transaction = wp_remote_get(
				$url,
				array( 'timeout' => 120 )
			);

			$this->log->add( $this->id, 'Recovery transactions status: ' . wp_json_encode( $transaction ), WC_Log_Levels::INFO );

			if ( is_wp_error( $transaction ) || 200 !== wp_remote_retrieve_response_code( $transaction ) ) {
				$error = is_wp_error( $transaction ) ? $transaction->get_error_message() : wp_remote_retrieve_body( $transaction );
				$this->log->add( $this->id, 'Get transaction status error: ' . $error, WC_Log_Levels::ERROR );
				wp_die( esc_html( $error ), esc_html( $error ), array( 'response' => 401 ) );
			}

			$transaction = simplexml_load_string( wp_remote_retrieve_body( $transaction ) );

			$order = wc_get_order( (string) $transaction->reference );

			$is_additional_charge = false;
			if ( false === strpos( $order->get_meta( '_charge_id' ), (string) $transaction->code ) ) {
				$is_additional_charge = true;
			}
			if ( $order ) {
				switch ( (int) $transaction->status ) {
					case 1:
						$order->add_order_note( __( 'PagSeguro: O comprador iniciou a transação, mas até o momento o PagSeguro não recebeu nenhuma informação sobre o pagamento.', 'virtuaria-pagseguro' ) );
						if ( ! $is_additional_charge ) {
							$order->update_status( 'on-hold' );
						}
						break;
					case 2:
						$order->add_order_note( __( 'PagSeguro: O comprador optou por pagar com um cartão de crédito e o PagSeguro está analisando o risco da transação.', 'virtuaria-pagseguro' ) );
						break;
					case 3:
						$order->add_order_note(
							sprintf(
								/* translators: %s: amount */
								__( 'PagSeguro: Cobrança recebida R$ %s' ),
								// phpcs:ignore
								number_format( (string) $transaction->grossAmount, 2, ',', '.' )
							)
						);
						if ( ! $is_additional_charge ) {
							$order->update_status( $this->get_option( 'payment_status' ), __( 'PagSeguro: Pagamento aprovado.', 'virtuaria-pagseguro' ) );
						}
						break;
					case 4:
						$order->add_order_note(
							sprintf(
								/* translators: %s: amount */
								__( 'PagSeguro: R$ %s disponível na conta.', 'virtuaria-pagseguro' ),
								// phpcs:ignore
								number_format( (string) $transaction->grossAmount, 2, ',', '.' )
							)
						);
						if ( ! $is_additional_charge ) {
							$order->update_status( $this->get_option( 'payment_status' ), __( 'PagSeguro: Pagamento aprovado.', 'virtuaria-pagseguro' ) );
						}
						break;
					case 5:
						$order->add_order_note( __( 'PagSeguro: O comprador, dentro do prazo de liberação da transação, abriu uma disputa. Acesse o painel da conta pagseguro para mais detalhes.' ) );
						break;
					case 6:
						if ( ! $is_additional_charge ) {
							$order->add_order_note( __( 'PagSeguro: O valor da transação foi devolvido para o comprador. ', 'virtuaria-pagseguro' ) );
							$order->update_status( 'refunded', __( 'PagSeguro: Pedido reembolsado.', 'virtuaria-pagseguro' ) );
						} else {
							$order->add_order_note( __( 'PagSeguro: O valor da cobrança adicional foi devolvido para o comprador. ', 'virtuaria-pagseguro' ) );
						}
						break;
					case 7:
						if ( ! $is_additional_charge ) {
							$order->add_order_note( __( 'PagSeguro: Pedido cancelado.' ) );
							$order->update_status( 'cancelled', __( 'PagSeguro: Pedido cancelado.', 'virtuaria-pagseguro' ) );
						} else {
							$order->add_order_note( __( 'PagSeguro: Cobrança adicional cancelada.' ) );
						}
						break;
					case 8:
						if ( ! $is_additional_charge ) {
							$order->add_order_note( __( 'PagSeguro: O valor da transação foi devolvido para o comprador.' ) );
						} else {
							$order->add_order_note( __( 'PagSeguro: O valor da cobrança adicional foi devolvido para o comprador.' ) );
						}
						break;
					case 9:
						$order->add_order_note(
							__( 'PagSeguro: O comprador abriu uma solicitação de chargeback junto à operadora do cartão de crédito.', 'virtuaria-pagseguro' )
						);
						break;
				}
			}
			return;
		} else {
			$this->log->add( $this->id, 'REJECT IPN request...', WC_Log_Levels::INFO );
			$error = __( 'Requisição PagSeguro Não autorizada', 'virtuaria-pagseguro' );
			wp_die( esc_html( $error ), esc_html( $error ), array( 'response' => 401 ) );
		}
	}

	/**
	 * Update order status.
	 *
	 * @param array $posted PagSeguro post data.
	 */
	private function update_order_status( $posted ) {
	}

	/**
	 * Thank You page message.
	 *
	 * @param int $order_id Order ID.
	 */
	public function thankyou_page( $order_id ) {
		$type = get_post_meta( $order_id, '_payment_mode', true );

		if ( 'BOLETO' === $type ) {
			$this->get_ticket_info( $order_id );
		} elseif ( 'PIX' === $type ) {
			$qr_code     = get_post_meta( $order_id, '_pagseguro_qrcode', true );
			$qr_code_png = get_post_meta( $order_id, '_pagseguro_qrcode_png', true );

			if ( $qr_code && $qr_code_png ) {
				$validate = $this->format_pix_validate( $this->pix_validate );
				require plugin_dir_path( __FILE__ ) . '../templates/payment-instructions.php';
			}
		}
	}

	/**
	 * Display ticket info.
	 *
	 * @param int $order_id the order id.
	 */
	private function get_ticket_info( $order_id ) {
		echo '<div class="ticket-info">';
		echo '<h3 style="margin: 0;">' . esc_html_e( 'Utilize o código de barras abaixo para efetuar o pagamento em lotéricas, instituições financeiras ou internet banking.', 'virtuaria-pagseguro' ) . '</h3>';
		echo '<strong style="display:block;margin: 15px 0;">' . esc_html( get_post_meta( $order_id, '_formatted_barcode', true ) ) . '</strong>';
		echo '<a class="pdf-link" target="_blank" href="' . esc_url( get_post_meta( $order_id, '_pdf_link', true ) ) . '">';
		echo '<img class="barcode-icon" src="' . esc_url( home_url( 'wp-content/plugins/virtuaria-pagseguro/public/images/codigo-de-barras.png' ) ) . '" alt="Boleto"/>';
		echo 'Imprimir Boleto Bancário</a>';
		echo '</div>';
		echo '<style>
		.ticket-info {
			border: 1px solid #ddd;
			padding: 20px;
			max-width: 600px;
		}
		.ticket-info > .pdf-link {
			background-color: green;
			color: #fff;
			padding: 5px 15px;
			border-radius: 6px;
			display: table;
			margin-top: 10px;
			transition: filter .2s;
			text-decoration: none;
		}
		div.ticket-info > .pdf-link:hover {
			color: #fff;
			filter: brightness(1.3);
		}
		.ticket-info > .pdf-link .barcode-icon {
			display: inline-block;
			vertical-align: middle;
			margin-right: 5px;
			max-width: 20px;
		}
		</style>';
	}

	/**
	 * Add content to the WC emails.
	 *
	 * @param  WC_Order $order         Order object.
	 * @param  bool     $sent_to_admin Send to admin.
	 * @param  bool     $plain_text    Plain text or HTML.
	 * @return string
	 */
	public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		if ( $sent_to_admin || 'on-hold' !== $order->get_status() || $this->id !== $order->get_payment_method() ) {
			return;
		}

		$type = get_post_meta( $order->get_id(), '_payment_mode', true );

		if ( 'BOLETO' === $type ) {
			$this->get_ticket_info( $order->get_id() );
		} elseif ( 'PIX' === $type ) {
			$qr_code     = get_post_meta( $order->get_id(), '_pagseguro_qrcode', true );
			$qr_code_png = get_post_meta( $order->get_id(), '_pagseguro_qrcode_png', true );

			if ( $qr_code && $qr_code_png ) {
				$validate = $this->format_pix_validate( $this->pix_validate );
				require_once plugin_dir_path( __FILE__ ) . '../templates/payment-instructions.php';
			}
		}
	}

	/**
	 * Process refund order.
	 *
	 * @param  int    $order_id Order ID.
	 * @param  float  $amount Refund amount.
	 * @param  string $reason Refund reason.
	 * @return boolean True or false based on success, or a WP_Error object.
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$order = wc_get_order( $order_id );

		if ( $amount
			&& $amount > 1
			&& $this->get_option( 'payment_status' ) === $order->get_status()
			&& 'BOLETO' !== $order->get_meta( '_payment_mode' ) ) {
			if ( $this->api->refund_order( $order_id, $amount ) ) {
				$order->add_order_note( 'PagSeguro: Reembolso de R$' . $amount . ' bem sucedido.', 0, true );
				return true;
			}
		}

		$order->add_order_note( 'PagSeguro: Não foi possível reembolsar R$' . $amount . '. Verifique o status da transação e o valor a ser reembolsado e tente novamente.', 0, true );

		return false;
	}

	/**
	 * Payment fields.
	 */
	public function payment_fields() {
		$description = $this->get_description();
		if ( $description ) {
			echo wp_kses_post( wpautop( wptexturize( $description ) ) );
		}

		$comments = $this->get_option( 'comments' );
		if ( $comments ) {
			echo '<span class="pagseguro-info">' . wp_kses_post( $comments ) . '</span>';
		}

		$cart_total = $this->get_order_total();

		$combo_installments = array();
		foreach ( range( 1, $this->installments ) as $installment ) {
			if ( $this->fee_from > $installment ) {
				$combo_installments[] = $cart_total;
				continue;
			}

			$combo_installments[] = $this->get_installment_value(
				$cart_total,
				$installment
			);
		}

		$disable_discount = $this->pix_discount_coupon && count( WC()->cart->get_applied_coupons() ) > 0;

		wc_get_template(
			'transparent-checkout.php',
			array(
				'cart_total'      => $cart_total,
				'flag'            => plugins_url( 'assets/images/brazilian-flag.png', plugin_dir_path( __FILE__ ) ),
				'installments'    => $combo_installments,
				'has_tax'         => floatval( $this->tax ) > 0,
				'min_installment' => floatval( $this->min_installment ),
				'fee_from'        => $this->fee_from,
				'pix_validate'    => $this->format_pix_validate( $this->pix_validate ),
				'methods_enabled' => array(
					'pix'    => 'yes' === $this->pix_enable,
					'ticket' => 'yes' === $this->ticket_enable,
					'credit' => 'yes' === $this->credit_enable,
				),
				'full_width'      => 'one' === $this->get_option( 'display' ),
				'pix_discount'    => $this->pix_discount && ! $disable_discount ? $this->pix_discount / 100 : 0,
			),
			'woocommerce/pagseguro/',
			Virtuaria_Pagseguro::get_templates_path()
		);
	}

	/**
	 * Formatter pix validate
	 *
	 * @param string $validate the time of pix validate.
	 * @return string
	 */
	private function format_pix_validate( $validate ) {
		$format = $validate / 3600;
		switch ( $format ) {
			case 0.5:
				$format = '30 minutos';
				break;
			case 1:
				$format = '1 hora';
				break;
			case 1.5:
				$format = '1 hora e 30 minutos';
				break;
			case 2:
				$format = '2 horas';
				break;
			case 2.5:
				$format = '2 horas e 30 minutos';
				break;
			default:
				$format = '3 horas';
				break;
		}
		return $format;
	}

	/**
	 * Get installment value with tax.
	 *
	 * @param float $total       the total from cart.
	 * @param int   $installment the installment selected.
	 */
	public function get_installment_value( $total, $installment ) {
		// $subtotal  = ( $total_fees * ( $tax / ( 1 - ( 1 / pow( 1 + $tax, $installments ) ) ) ) ); // Valor da Parcela.
		$tax        = floatval( $this->tax ) / 100;
		$subtotal   = $total;
		$n_parcelas = range( 1, $installment );
		foreach ( $n_parcelas as $installment ) {
			$subtotal += ( $subtotal * $tax );
		}
		return $subtotal;
	}

	/**
	 * Metabox to additional charge.
	 *
	 * @param WP_Post $post the post.
	 */
	public function additional_charge_metabox( $post ) {
		$options = get_option( 'woocommerce_virt_pagseguro_settings' );
		$order   = wc_get_order( sanitize_text_field( wp_unslash( $post->ID ) ) );
		$credit  = get_user_meta( $order->get_customer_id(), '_pagseguro_credit_info_store_' . get_current_blog_id(), true );

		if ( ! $order
			|| 'BOLETO' === $order->get_meta( '_payment_mode' )
			|| ( 'CREDIT_CARD' === $order->get_meta( '_payment_mode' ) && $this->get_option( 'payment_status' ) !== $order->get_status() )
			|| ( 'PIX' === $order->get_meta( '_payment_mode' ) && ! in_array( $order->get_status(), array( 'on-hold', $this->get_option( 'payment_status' ) ), true ) )
			|| 'virt_pagseguro' !== $order->get_payment_method()
			|| ( ! isset( $options['enabled'] ) || 'yes' !== $options['enabled'] )
			|| ( ( ! isset( $credit['token'] ) || ! $credit['token'] ) && 'PIX' !== $order->get_meta( '_payment_mode' ) ) ) {
			return;
		}

		$title = $this->get_option( 'payment_status' ) === $order->get_status()
			? __( 'Cobrança Adicional', 'virtuaria-pagseguro' ) : __( 'Nova Cobrança', 'virtuaria-pagseguro' );
		add_meta_box(
			'pagseguro-additional-charge',
			$title,
			array( $this, 'display_additional_charge_content' ),
			'shop_order',
			'side',
			'high'
		);
	}

	/**
	 * Content to additional charge metabox.
	 *
	 * @param WP_Post $post the post.
	 */
	public function display_additional_charge_content( $post ) {
		?>
		<label for="additional-value">Informe o valor a ser cobrado (R$):</label>
		<input type="number" style="width:calc(100% - 36px)" name="additional_value" id="additional-value" step="0.01" min="0.1"/>
		<button id="submit-additional-charge" style="padding: 3px 4px;vertical-align:middle;color:green;cursor:pointer">
			<span class="dashicons dashicons-money-alt"></span>
		</button>
		<label for="reason-charge" style="margin-top: 5px;">Motivo:</label>
		<input type="text" name="credit_charge_reason" id="reason-charge" style="display:block;max-width:219px;">
		<style>
			#submit-additional-charge {
				border-color: #0071a1;
				color: #0071a1;
				font-size: 16px;
				border-width: 1px;
				border-radius: 5px;
			}
		</style>
		<?php
		wp_nonce_field( 'do_additional_charge', 'additional_charge_nonce' );
	}

	/**
	 * Do additional charge.
	 *
	 * @param int $order_id the order id.
	 */
	public function do_additional_charge( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order || ! in_array( $order->get_status(), array( 'on-hold', $this->get_option( 'payment_status' ) ), true ) ) {
			return;
		}

		if ( isset( $_POST['additional_value'] )
			&& isset( $_POST['additional_charge_nonce'] )
			&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['additional_charge_nonce'] ) ), 'do_additional_charge' )
			&& floatval( $_POST['additional_value'] ) > 0 ) {
			$amount = number_format(
				sanitize_text_field( wp_unslash( $_POST['additional_value'] ) ),
				2,
				'',
				''
			);

			$resp = $this->api->additional_charge(
				$order,
				$amount,
				isset( $_POST['credit_charge_reason'] ) ? sanitize_text_field( wp_unslash( $_POST['credit_charge_reason'] ) ) : ''
			);

			if ( 'PIX' === $order->get_meta( '_payment_mode' ) && $resp ) {
				$qr_code     = get_post_meta( $order_id, '_pagseguro_additional_qrcode', true );
				$qr_code_png = get_post_meta( $order_id, '_pagseguro_additional_qrcode_png', true );

				if ( $qr_code && $qr_code_png ) {
					$this->add_qrcode_in_note( $order, $qr_code );
					$validate     = $this->format_pix_validate( $this->pix_validate );
					$amount      /= 100;
					$charge_title = $amount == $order->get_total() ? 'Nova Cobrança' : 'Cobrança Extra';
					ob_start();
					echo '<p>Olá, ' . esc_html( $order->get_billing_first_name() ) . '.</p>';
					echo '<p><strong>Uma ' . esc_html( mb_strtolower( $charge_title ) ) . ' está disponível para seu pedido.</strong></p>';
					remove_action(
						'woocommerce_email_after_order_table',
						array( $this, 'email_instructions' ),
						10,
						3
					);
					wc_get_template(
						'emails/email-order-details.php',
						array(
							'order'         => $order,
							'sent_to_admin' => false,
							'plain_text'    => false,
							'email'         => '',
						)
					);
					add_action(
						'woocommerce_email_after_order_table',
						array( $this, 'email_instructions' ),
						10,
						3
					);
					if ( $amount != $order->get_total() ) {
						echo '<p style="color:green"><strong style="display:block;">Valor da Cobrança Extra: R$ '
						. number_format( $amount, 2, ',', '.' ) . '.</strong>';
					}
					if ( isset( $_POST['charge_reason'] ) && ! empty( $_POST['charge_reason'] ) ) {
						$reason = 'Motivo: ' . esc_html( sanitize_text_field( wp_unslash( $_POST['charge_reason'] ) ) ) . '.';
					}
					echo wp_kses_post( $reason ) . '</p>';
					require_once plugin_dir_path( __FILE__ ) . '../templates/payment-instructions.php';
					$message = ob_get_clean();

					$this->send_email(
						$order->get_billing_email(),
						'[' . get_bloginfo( 'name' ) . '] ' . $charge_title . ' PIX no Pedido #' . $order_id,
						'Novo Código de Pagamento Disponível para seu Pedido ',
						$message
					);
				}
			}
		}
	}

	/**
	 * Add QR Code in order note.
	 *
	 * @param wc_order $order   the order.
	 * @param string   $qr_code the qr code.
	 */
	private function add_qrcode_in_note( $order, $qr_code ) {
		if ( function_exists( '\\order\\limit_characters_order_note' ) ) {
			remove_filter( 'woocommerce_new_order_note_data', '\\order\\limit_characters_order_note' );
			$order->add_order_note( 'PagSeguro Pix Copia e Cola: <div class="pix">' . $qr_code . '</div><a href="#" id="copy-qr" class="button button-primary">Copiar</a>' );
			add_filter( 'woocommerce_new_order_note_data', '\\order\\limit_characters_order_note' );
		} else {
			$order->add_order_note( 'PagSeguro Pix Copia e Cola: <div class="pix">' . $qr_code . '</div><a href="#" id="copy-qr" class="button button-primary">Copiar</a>' );
		}
	}

	/**
	 * Check status from order. If unpaid cancel order.
	 *
	 * @param int $order_id the args.
	 */
	public function check_order_paid( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( $order && ! get_post_meta( $order_id, '_charge_id', true ) ) {
			$order->add_order_note( 'Pagseguro PIX: o limite de tempo para pagamento deste pedido expirou.' );
			$order->update_status( 'cancelled' );
			if ( 'yes' === $this->debug ) {
				$this->log->add( 'virt_pagseguro', 'Pedido #' . $order->get_order_number() . ' mudou para o status cancelado.', WC_Log_Levels::INFO );
			}
		}
	}

	/**
	 * Add scripts to dash.
	 */
	public function admin_checkout_scripts() {
		if ( isset( $_GET['post'] ) && 'shop_order' === get_post_type( sanitize_text_field( wp_unslash( $_GET['post'] ) ) ) ) {
			wp_enqueue_script(
				'copy-qr',
				VIRTUARIA_PAGSEGURO_URL . 'public/js/copy-code.js',
				array( 'jquery' ),
				filemtime( VIRTUARIA_PAGSEGURO_DIR . 'public/js/copy-code.js' ),
				true
			);

			wp_enqueue_style(
				'copy-qr',
				VIRTUARIA_PAGSEGURO_URL . 'public/css/pix-code.css',
				array(),
				filemtime( VIRTUARIA_PAGSEGURO_DIR . 'public/css/pix-code.css' )
			);
		}
	}

	/**
	 * Add meta box.
	 *
	 * @param wp_post $post the post.
	 */
	public function pix_payment_metabox( $post ) {
		$order = wc_get_order( $post->ID );
		if ( $order
			&& 'sandbox' === $this->environment
			&& 'PIX' === $order->get_meta( '_payment_mode' )
			&& $order->get_meta( '_qrcode_id' )
			&& $order->has_status( 'on-hold' ) ) {
			add_meta_box(
				'pix-payment',
				'Pagamento Pix',
				array( $this, 'pix_payment_content' ),
				'shop_order',
				'side'
			);
		}
	}

	/**
	 * Meta box content.
	 */
	public function pix_payment_content() {
		?>
		<button class="button make_payment button-primary">Simular pagamento do pix</button>
		<input type="hidden" name="pix_button_clicked" class="pix_input"/>
		<script>
			jQuery(document).ready(function($) {
				$('.make_payment').on('click', function() {
					$('.pix_input').val('yes');
				});
			});
		</script>
		<?php
		wp_nonce_field(
			'pix-payment',
			'pix_nonce'
		);
	}

	/**
	 * Do pix payment.
	 *
	 * @param int $order_id the order id.
	 */
	public function make_pix_payment( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order || 'on-hold' !== $order->get_status() ) {
			return;
		}

		if ( isset( $_POST['pix_nonce'] )
			&& isset( $_POST['pix_button_clicked'] )
			&& 'yes' === $_POST['pix_button_clicked']
			&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pix_nonce'] ) ), 'pix-payment' ) ) {

			$paid = $this->api->simulate_payment(
				$order->get_meta( '_qrcode_id' )
			);

			if ( $paid ) {
				$order->add_order_note(
					__( 'PagSeguro: Simulação de pagamento Pix realizada com sucesso.', 'virtuaria-pagseguro' ),
					0,
					true
				);
			} else {
				$order->add_order_note(
					__( 'PagSeguro: A simulação de pagamento Pix falhou.', 'virtuaria-pagseguro' ),
					0,
					true
				);
			}
		}
	}

	/**
	 * Process schedule order status.
	 *
	 * @param int    $order_id the order id.
	 * @param string $status the status scheduled.
	 */
	public function process_order_status( $order_id, $status ) {
		$order = wc_get_order( $order_id );

		if ( $order ) {
			if ( 'on-hold' === $status ) {
				if ( $order->has_status( 'pending' ) ) {
					$order->update_status( 'on-hold', __( 'PagSeguro: Aguardando confirmação de pagamento.', 'virtuaria-pagseguro' ) );
				}
			} else {
				$order->update_status( $this->get_option( 'payment_status' ), __( 'PagSeguro: Pagamento aprovado.', 'virtuaria-pagseguro' ) );
			}
		}
	}

	/**
	 * Display auth field.
	 *
	 * @param string $key  the name from field.
	 * @param array  $data the data.
	 */
	public function generate_auth_html( $key, $data ) {
		$field_key = $this->get_field_key( $key );
		$defaults  = array(
			'title'             => '',
			'disabled'          => false,
			'class'             => '',
			'css'               => '',
			'placeholder'       => '',
			'type'              => 'text',
			'desc_tip'          => false,
			'description'       => '',
			'custom_attributes' => array(),
		);

		$data = wp_parse_args( $data, $defaults );

		$data['id']    = 'woocommerce_' . $this->id . '_autorization';
		$data['value'] = $this->get_option( 'autorization' );

		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $data['id'] ); ?>">
					<?php echo esc_html( $data['title'] ); ?>
					<span class="woocommerce-help-tip" data-tip="<?php echo esc_html( $data['description'] ); ?>"></span>
				</label>
			</th>
			<td class="forminp forminp-<?php echo esc_attr( $data['type'] ); ?>">
				<?php
				$token = null;
				$auth  = '';
				if ( 'sandbox' === $this->environment ) {
					$token = $this->get_option( 'token_sanbox' );
					$auth  = 'sandbox.';
				} else {
					$token = $this->get_option( 'token_production' );
				}

				$origin = str_replace( array( 'https://', 'http://' ), '', home_url() );

				$auth  = 'https://connect.' . $auth . 'pagseguro.uol.com.br/oauth2/authorize';
				$auth .= '?response_type=code&client_id=' . $this->app_id . '&redirect_uri=' . $this->app_url;
				$auth .= '&scope=payments.read+payments.create+payments.refund+accounts.read&state=' . $origin;

				if ( $token ) {
					echo '<span class="connected"><strong>Status: <span class="status">Conectado.</span></strong></span>';
					echo '<a href="' . esc_url( $this->app_revoke ) . '?state=' . $origin . '" class="auth button-primary">Desconectar com PagSeguro <img src="' . esc_url( VIRTUARIA_PAGSEGURO_URL ) . 'public/images/conectado.svg" alt="Desconectar" /></a>';
					echo '<span class="expire-info">A conexão tem duração <strong>média de 1 ano</strong>, após esse período é necessário reconectar para atualizar as permissões junto ao PagSeguro.</span>';
				} else {
					echo '<span class="disconnected"><strong>Status: <span class="status">Desconectado.</span></strong></span>';
					echo '<a href="' . esc_url( $auth ) . '" class="auth button-primary">Conectar com PagSeguro <img src="' . esc_url( VIRTUARIA_PAGSEGURO_URL ) . 'public/images/conectar.png" alt="Conectar" /></a>';
					echo '<span class="expire-info">A conexão tem duração <strong>média de 1 ano</strong>, após esse período é necessário reconectar para atualizar as permissões junto ao PagSeguro.</span>';
				}
				?>
				<style>
					.auth img {
						display: inline-block;
						vertical-align: middle;
						max-width: 30px;
						margin-left: 5px;
					}
					.forminp-auth .auth {
						margin-left: 10px;
						padding: 4px 10px;
						box-shadow: none;
					}
					.wp-core-ui .auth.button-primary:hover {
						box-shadow: none;
						font-weight: normal;
					}
					.expire-info {
						display: block;
						margin-top: 5px;
					}
					.forminp-auth .connected .status{
						color: green;
					}
					.woocommerce_page_wc-settings h3.wc-settings-sub-title {
						font-size: 1.2em;
						border-top: 1px solid #ccc;
						padding-top: 30px;
						font-weight: bold;
					}
				</style>
				<script>
					function getUrlParameter(name) {
						name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
						var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
						results = regex.exec(location.search);
						return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
					}
					jQuery(document).ready(function($) {
						$('#woocommerce_virt_pagseguro_environment').on('change', function() {
							$('.woocommerce-save-button').click();
						});

						let connected = $('.forminp-auth > .connected' ).length > 0;
						if ( ( getUrlParameter( 'token' ) != '' && ! connected ) || ( getUrlParameter( 'access_revoked' ) != '' && connected ) ) {
							alert( 'Para efetivar a conexão/desconexão clique em "Salvar Alterações".' );
							$([document.documentElement, document.body]).animate({
								scrollTop: $("#woocommerce_virt_pagseguro_tecvirtuaria").offset().top
							}, 2000);
						}
					});
				</script>
			</td>
		</tr>  

		<?php
		return ob_get_clean();
	}

	/**
	 * Save store token.
	 */
	public function save_store_token() {
		if ( isset( $_GET['section'] )
			&& 'virt_pagseguro' === $_GET['section'] ) {
			if ( isset( $_GET['token'] ) ) {
				if ( 'sandbox' === $this->environment ) {
					$this->update_option( 'token_sanbox', sanitize_text_field( wp_unslash( $_GET['token'] ) ) );
				} else {
					$this->update_option( 'token_production', sanitize_text_field( wp_unslash( $_GET['token'] ) ) );
				}
				add_action( 'admin_notices', array( $this, 'virtuaria_pagseguro_connected' ) );
				delete_option( 'virtuaria_pagseguro_not_authorized' );
			} elseif ( isset( $_GET['access_revoked'] ) && 'success' === $_GET['access_revoked'] ) {
				if ( 'sandbox' === $this->environment ) {
					$this->update_option( 'token_sanbox', null );
				} else {
					$this->update_option( 'token_production', null );
				}
				add_action( 'admin_notices', array( $this, 'virtuaria_pagseguro_disconnected' ) );
				delete_option( 'virtuaria_pagseguro_not_authorized' );
			} elseif ( isset( $_GET['proccess'] ) && 'failed' === $_GET['proccess'] ) {
				$this->update_option( 'token_sanbox', null );
				$this->update_option( 'token_production', null );
				delete_option( 'virtuaria_pagseguro_not_authorized' );
				add_action( 'admin_notices', array( $this, 'virtuaria_pagseguro_failed' ) );
			}
		}
	}

	/**
	 * Message from token generate success.
	 */
	public function virtuaria_pagseguro_connected() {
		?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_attr_e( 'Virtuaria PagSeguro Conectado!', 'virtuaria-pagseguro' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Message from token revoked success.
	 */
	public function virtuaria_pagseguro_disconnected() {
		?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_attr_e( 'Virtuaria PagSeguro Desconectado!', 'virtuaria-pagseguro' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Message from fail.
	 */
	public function virtuaria_pagseguro_failed() {
		?>
		<div class="notice notice-error is-dismissible">
			<p><?php esc_attr_e( 'Virtuaria PagSeguro - Falha ao processar operação!', 'virtuaria-pagseguro' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Message from fail.
	 */
	public function virtuaria_pagseguro_not_authorized() {
		if ( get_option( 'virtuaria_pagseguro_not_authorized' ) ) {
			?>
			<div class="notice notice-warning is-dismissible">
				<p>
					Virtuaria PagSeguro - Sua conexão com a API do PagSeguro está sendo negada, impedindo a concretização das transações (pagamento, reembolso, etc). Tente reconectar o plugin via página de <a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=virt_pagseguro' ) ); ?>">configuração</a> para renovar a autorização. Para mais detalhes, consulte o log do plugin.
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Required billing_neighborhood.
	 *
	 * @param array $fields the fields.
	 */
	public function billing_neighborhood_required( $fields ) {
		if ( isset( $fields['billing_neighborhood'] ) ) {
			$fields['billing_neighborhood']['required'] = true;
		}
		return $fields;
	}

	/**
	 * Display ignore discount field.
	 *
	 * @param string $key  the name from field.
	 * @param array  $data the data.
	 */
	public function generate_ignore_discount_html( $key, $data ) {
		$field_key = $this->get_field_key( $key );

		if ( isset( $_POST['woocommerce_virt_pagseguro_pix_discount_ignore'] ) ) {
			$ignored = sanitize_text_field( wp_unslash( $_POST['woocommerce_virt_pagseguro_pix_discount_ignore'] ) );
			$ignored = explode( ',', $ignored );
			$this->update_option(
				'pix_discount_ignore',
				$ignored
			);
		}
		$selected_cats = $this->get_option( 'pix_discount_ignore' );
		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field_key ); ?>">
					<?php echo esc_html( $data['title'] ); ?>
					<span class="woocommerce-help-tip" data-tip="<?php echo esc_html( $data['description'] ); ?>"></span>
				</label>
			</th>
			<td class="forminp forminp-<?php echo esc_attr( $data['type'] ); ?>">
				<input type="hidden" name="<?php echo esc_attr( $field_key ); ?>" id="<?php echo esc_attr( $field_key ); ?>" />
				<div id="product_cat-all" class="tabs-panel">
					<ul id="product_catchecklist" data-wp-lists="list:product_cat" class="categorychecklist form-no-clear">
						<?php
						wp_terms_checklist(
							0,
							array(
								'taxonomy'      => 'product_cat',
								'selected_cats' => $selected_cats,
							)
						);
						?>
					</ul>
				</div>
			</td>
		</tr>
		<script>
			jQuery(document).ready(function($){
				$('.woocommerce-save-button').on('click', function() {
					let selected_cats = [];
					$('#product_catchecklist input[type="checkbox"]:checked').each(function(i, v){
						selected_cats.push($(v).val());
					});
					$('#<?php echo esc_attr( $field_key ); ?>').val(selected_cats);
				})
			});
		</script>
		<style>
			#product_cat-all {
				background-color: #fff;
				max-width: 400px;
			}
			#product_cat-all .categorychecklist {
				margin: 10px;
				max-height: 145px;
				overflow: auto;
			}
			#product_cat-all .children {
				margin-left: 20px;
			}
		</style>
		<?php
		return ob_get_clean();
	}

	/**
	 * Ignore product from categorie to pix discount.
	 *
	 * @param boolean    $disable true if disable item otherwise false.
	 * @param wc_product $product the itens.
	 */
	public function disable_discount_by_product_categoria( $disable, $product ) {
		$ignored_categories = $this->get_option( 'pix_discount_ignore', '' );
		if ( $ignored_categories && count( $product->get_category_ids() ) > 0 ) {
			foreach ( $product->get_category_ids() as $category_id ) {
				if ( in_array( $category_id, $ignored_categories ) ) {
					$disable = true;
					break;
				}
			}
		}
		return $disable;
	}

	/**
	 * Display discount pix text.
	 *
	 * @param string $title      the gateway title.
	 * @param string $gateway_id the gateway id.
	 */
	public function discount_pix_text( $title, $gateway_id ) {
		if ( 'yes' === $this->pix_enable
			&& is_checkout()
			&& isset( $_REQUEST['wc-ajax'] )
			&& 'update_order_review' === $_REQUEST['wc-ajax']
			&& $this->pix_discount > 0
			&& $this->id === $gateway_id
			&& ( ! $this->pix_discount_coupon || count( WC()->cart->get_applied_coupons() ) === 0 ) ) {
			$title .= '<span class="pix-discount">(desconto de <b>' . str_replace( '.', ',', $this->pix_discount ) . '%</b> no Pix)</span>';
		}
		return $title;
	}

	/**
	 * Text about categories disable to pix discount.
	 *
	 * @param array $itens the cart itens.
	 */
	public function info_about_categories( $itens ) {
		$ignored_categories = $this->get_option( 'pix_discount_ignore', '' );

		if ( is_array( $ignored_categories ) ) {
			$ignored_categories = array_filter( $ignored_categories );
		}

		if ( 'yes' === $this->pix_enable
			&& $this->pix_discount > 0
			&& is_array( $ignored_categories )
			&& $ignored_categories ) {

			$category_disabled = array();
			foreach ( $ignored_categories as $index => $category ) {
				$term = get_term( $category );
				if ( $term && ! is_wp_error( $term ) ) {
					$category_disabled[] = ucwords( mb_strtolower( $term->name ) );
				}
			}

			if ( $category_disabled ) {
				echo '<div class="info-category">' . wp_kses_post(
					sprintf(
						/* translators: %s: categories */
						_nx(
							'O desconto pix não é válido para produtos da categoria <span class="categories">%s</span>.',
							'O desconto pix não é válido para produtos das categorias <span class="categories">%s</span>.',
							count( $category_disabled ),
							'Checkout',
							'virtuaria-pagseguro'
						),
						implode( ', ', $category_disabled )
					)
				) . '</div>';
			}
		}
	}

	/**
	 * Get store order status.
	 */
	private function get_payment_status() {
		$status = array();
		foreach ( wc_get_order_statuses() as $key => $text ) {
			if ( ! in_array( $key, array( 'wc-pending', 'wc-cancelled', 'wc-refunded', 'wc-failed' ), true ) ) {
				$status[ str_replace( 'wc-', '', $key ) ] = $text;
			}
		}
		return $status;
	}

	/**
	 * Create box to fetch order status.
	 *
	 * @param wc_order $order the order.
	 */
	public function fetch_order_status_metabox( $order ) {
		if ( is_a( $order, 'WP_Post' ) && get_post_meta( $order->ID, '_charge_id', true ) ) {
			add_meta_box(
				'fetch-status',
				__( 'Consultar PagSeguro', 'virtuaria-pagseguro' ),
				array( $this, 'fetch_order_status_content' ),
				'shop_order',
				'side'
			);
		}
	}

	/**
	 * Fetch order status box callback.
	 */
	public function fetch_order_status_content() {
		global $post;
		?>
		<small>Clique para checar o status de pagamento deste pedido no painel do PagSeguro.</small>
		<small>O resultado da consulta será exibido nas notas(histórico) do pedido.</small>
		<button id="fetch-order-payment" class="button-primary button">
			Verificar Status<span class="dashicons dashicons-money-alt" style="vertical-align: middle;margin-left:5px"></span>
		</button>
		<input type="hidden" name="fetch_order_payment">
		<script>
			jQuery(document).ready(function($){
				$('#fetch-order-payment').on('click', function(){
					$('input[name="fetch_order_payment"]').val('<?php echo esc_html( $post->ID ); ?>');
				});
			});
		</script>
		<style>
			#fetch-status small {
				display: block;
				margin-bottom: 10px;
			}
			#fetch-order-payment {
				display: table;
				margin: 0 auto;
			}
		</style>
		<?php
		wp_nonce_field( 'search_order_payment_status', 'fetch_payment_nonce' );
	}

	/**
	 * Search payment status.
	 */
	public function search_order_payment_status() {
		if ( isset( $_POST['fetch_order_payment'] )
			&& isset( $_POST['fetch_payment_nonce'] )
			&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['fetch_payment_nonce'] ) ), 'search_order_payment_status' ) ) {
			$order = wc_get_order(
				sanitize_text_field(
					wp_unslash(
						$_POST['fetch_order_payment']
					)
				)
			);

			if ( $order ) {
				$status = $this->api->fetch_payment_status(
					get_post_meta(
						$order->get_id(),
						'_charge_id',
						true
					)
				);

				if ( $status ) {
					$translated = $status;

					switch ( $status ) {
						case 'AUTHORIZED':
							$translated = __(
								'Pré-autorizada. O total do pedido está reservado no cartão de crédito do cliente.',
								'virtuaria-pagseguro'
							);
							break;
						case 'PAID':
							$translated = __(
								'Paga.',
								'virtuaria-pagseguro'
							);
							break;
						case 'IN_ANALYSIS':
							$translated = __(
								'Em análise. O comprador optou por pagar com um cartão de crédito e o PagSeguro está analisando o risco da transação.',
								'virtuaria-pagseguro'
							);
							break;
						case 'DECLINED':
							$translated = __(
								'Negada pelo PagSeguro ou Emissor do Cartão de Crédito.',
								'virtuaria-pagseguro'
							);
							break;
						case 'CANCELED':
							$translated = __(
								'Cancelada.',
								'virtuaria-pagseguro'
							);
							break;
						case 'WAITING':
							$translated = __(
								'Aguardando Pagamento.',
								'virtuaria-pagseguro'
							);
							break;
					}
					$order->add_order_note(
						'Consulta PagSeguro: Transação ' . $translated,
						0,
						true
					);
					return;
				}
			}

			if ( ! $status && $order ) {
				$order->add_order_note(
					'PagSeguro: Não possível consultar o status de pagamento do pedido. Consulte o log para mais detalhes.',
					0,
					true
				);
			}
		}
	}
}

add_action( 'wp_ajax_fetch_payment_order', 'fetch_payment_order' );
add_action( 'wp_ajax_nopriv_fetch_payment_order', 'fetch_payment_order' );
/**
 * Check order status.
 */
function fetch_payment_order() {
	if ( isset( $_POST['order_id'] )
		&& isset( $_POST['payment_nonce'] )
		&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['payment_nonce'] ) ), 'fecth_order_status' ) ) {
		$options = get_option( 'woocommerce_virt_pagseguro_settings' );
		if ( 'wc-' . $options['payment_status'] === get_post_status( sanitize_text_field( wp_unslash( $_POST['order_id'] ) ) ) ) {
			echo 'success';
		}
	}
	wp_die();
}
