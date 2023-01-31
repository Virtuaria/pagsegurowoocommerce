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
	 * Constructor for the gateway.
	 */
	public function __construct() {
		$this->id                 = 'virt_pagseguro';
		$this->icon               = apply_filters( 'woocommerce_pagseguro_virt_icon', VIRTUARIA_PAGSEGURO_URL . '/public/images/pagseguro.png' );
		$this->has_fields         = false;
		$this->method_title       = __( 'PagSeguro', 'virtuaria-pagseguro' );
		$this->method_description = __( 'Pague com cartão de crédito e boleto.', 'virtuaria-pagseguro' );

		$this->supports = array( 'products', 'refunds' );

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Define user set variables.
		$this->title           = $this->get_option( 'title' );
		$this->description     = $this->get_option( 'description' );
		$this->environment     = $this->get_option( 'environment' );
		$this->token           = $this->get_option( 'token' );
		$this->email           = $this->get_option( 'email' );
		$this->debug           = $this->get_option( 'debug' );
		$this->installments    = $this->get_option( 'installments' );
		$this->tax             = $this->get_option( 'tax' );
		$this->soft_descriptor = $this->get_option( 'soft_descriptor' );
		$this->tiket_validate  = $this->get_option( 'tiket_validate' );
		$this->debug           = $this->get_option( 'debug' );

		// Active logs.
		if ( 'yes' === $this->debug ) {
			if ( function_exists( 'wc_get_logger' ) ) {
				$this->log = wc_get_logger();
			} else {
				$this->log = new WC_Logger();
			}
		}

		// Set the API.
		$this->api = new WC_PagSeguro_API( $this );

		// // Main actions.
		add_action( 'woocommerce_api_wc_virtuaria_pagseguro_gateway', array( $this, 'ipn_handler' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

		// Transparent checkout actions.
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
		add_action( 'woocommerce_email_after_order_table', array( $this, 'email_instructions' ), 10, 3 );
		add_action( 'wp_enqueue_scripts', array( $this, 'checkout_scripts' ) );

		// Additional charge.
		add_action( 'add_meta_boxes_shop_order', array( $this, 'additional_charge_metabox' ), 10 );
		add_action( 'save_post_shop_order', array( $this, 'do_additional_charge' ) );
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
		if ( is_checkout() && $this->is_available() && ! get_query_var( 'order-received' ) ) {
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
		}
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'         => array(
				'title'   => __( 'Habilitar', 'virtuaria-pagseguro' ),
				'type'    => 'checkbox',
				'label'   => __( 'Habilita o método de Pagamento Virtuaria PagSeguro', 'virtuaria-pagseguro' ),
				'default' => 'yes',
			),
			'title'           => array(
				'title'       => __( 'Título', 'virtuaria-pagseguro' ),
				'type'        => 'text',
				'description' => __( 'Isto controla o título exibido ao usuário durante o checkout.', 'virtuaria-pagseguro' ),
				'desc_tip'    => true,
				'default'     => __( 'PagSeguro', 'virtuaria-pagseguro' ),
			),
			'description'     => array(
				'title'       => __( 'Descrição', 'virtuaria-pagseguro' ),
				'type'        => 'textarea',
				'description' => __( 'Controla a descrição exibida ao usuário durante o checkout.', 'virtuaria-pagseguro' ),
				'default'     => __( 'Pague com PagSeguro.', 'virtuaria-pagseguro' ),
			),
			'integration'     => array(
				'title'       => __( 'Integração', 'virtuaria-pagseguro' ),
				'type'        => 'title',
				'description' => '',
			),
			'environment'     => array(
				'title'       => __( 'Ambiente', 'virtuaria-pagseguro' ),
				'type'        => 'select',
				'description' => __( 'Selecione Sanbox para testes ou Produção para vendas reais.', 'virtuaria-pagseguro' ),
				'options'     => array(
					'sandbox'    => 'Sandbox',
					'production' => 'Produção',
				),
			),
			'token'           => array(
				'title'       => __( 'Token', 'virtuaria-pagseguro' ),
				'type'        => 'text',
				'description' => __( 'Informe seu token do Pagseguro. Isto é necessário para o processamento do pagamento.', 'virtuaria-pagseguro' ),
				'default'     => '',
			),
			'email'           => array(
				'title'       => __( 'E-mail', 'virtuaria-pagseguro' ),
				'type'        => 'text',
				'description' => __( 'Informe seu e-mail utilizado na conta do Pagseguro. Isto é necessário para a confirmação do pagamento.', 'virtuaria-pagseguro' ),
				'default'     => '',
			),
			'credit'          => array(
				'title'       => __( 'Cartão de crédito', 'virtuaria-pagseguro' ),
				'type'        => 'title',
				'description' => '',
			),
			'installments'    => array(
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
			'tax'             => array(
				'title'       => __( 'Taxa de juros (%)', 'virtuaria-pagseguro' ),
				'type'        => 'number',
				'description' => __( 'Define o percentual de juros aplicado ao parcelamento.', 'virtuaria-pagseguro' ),
			),
			'soft_descriptor' => array(
				'title'       => __( 'Nome na fatura', 'virtuaria-pagseguro' ),
				'type'        => 'text',
				'description' => 'Texto exibido na fatura do cartão para identificar a loja.',
			),
			'save_card_info'  => array(
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
			'tiket'           => array(
				'title'       => __( 'Boleto', 'virtuaria-pagseguro' ),
				'type'        => 'title',
				'description' => '',
			),
			'tiket_validate'  => array(
				'title'       => 'Validade',
				'type'        => 'number',
				'description' => 'Define o limite de dias onde o boleto pode ser pago.',
				'default'     => '5',
			),
		);

		if ( current_user_can( 'install_themes' ) ) {
			$this->form_fields['testing'] = array(
				'title'       => __( 'Testes', 'virtuaria-pagseguro' ),
				'type'        => 'title',
				'description' => '',
			);
			$this->form_fields['debug']   = array(
				'title'       => __( 'Debug Log', 'virtuaria-pagseguro' ),
				'type'        => 'checkbox',
				'label'       => __( 'Habilitar registro de log', 'virtuaria-pagseguro' ),
				'default'     => 'yes',
				/* translators: %s: log page link */
				'description' => __( 'Registra eventos de comunição com a API e erros', 'virtuaria-pagseguro' ),
			);
		}

		$this->form_fields['testing'] = array(
			'title'       => __( 'Tecnologia Virtuaria', 'virtuaria-pagseguro' ),
			'type'        => 'title',
			'description' => '',
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
		if ( isset( $_POST['new_charge_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['new_charge_nonce'] ) ), 'do_new_charge' ) ) {
			$order = wc_get_order( $order_id );

			$paid = $this->api->new_charge( $order, $_POST );

			if ( ! isset( $paid['error'] ) ) {
				$charge_id = get_post_meta( $order_id, '_charge_id', true );
				$order->set_transaction_id( $charge_id );
				if ( $paid ) {
					$charge_amount = get_post_meta( $order_id, '_charge_amount', true );
					$order->update_status( 'processing', __( 'PagSeguro: Pagamento aprovado.', 'virtuaria-pagseguro' ) );
					$order->add_order_note( 'PagSeguro: cobrança recebida R$' . number_format( $charge_amount / 100, 2, ',', '.' ) );
					if ( $this->tax ) {
						$fee = new WC_Order_Item_Fee();
						$fee->set_name( __( 'Parcelamento pagseguro', 'virtuaria-pagseguro' ) );
						$fee->set_total( ( $charge_amount / 100 ) - $order->get_total() );

						$order->add_item( $fee );
						$order->calculate_totals();
						$order->save();
					}
				} else {
					$order->update_status( 'on-hold', __( 'PagSeguro: Aguardando confirmação de pagamento.', 'virtuaria-pagseguro' ) );
				}

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
		$request = array();
		parse_str( $body, $request );
		$this->log->add(
			$this->id,
			'Request to order ' . $body,
			WC_Log_Levels::INFO
		);
		if ( 'transaction' === $request['notificationType'] && isset( $request['notificationCode'] ) ) {
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
			if ( false === strpos( $order->get_transaction_id(), (string) $transaction->code ) ) {
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
							$order->update_status( 'processing', __( 'PagSeguro: Pagamento aprovado.', 'virtuaria-pagseguro' ) );
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
							$order->update_status( 'processing', __( 'PagSeguro: Pagamento aprovado.', 'virtuaria-pagseguro' ) );
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

				// $order->save();
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
		}
	}

	/**
	 * Display ticket info.
	 *
	 * @param int $order_id the order id.
	 */
	private function get_ticket_info( $order_id ) {
		echo '<div class="tiket-info">';
		echo '<h3 style="margin: 0;">' . esc_html_e( 'Utilize o código de barras abaixo para efetuar o pagamento em lotéricas, instituições financeiras ou internet banking.', 'virtuaria-pagseguro' ) . '</h3>';
		echo '<strong style="display:block;margin: 15px 0;">' . esc_html( get_post_meta( $order_id, '_formatted_barcode', true ) ) . '</strong>';
		echo '<a class="pdf-link" target="_blank" href="' . esc_url( get_post_meta( $order_id, '_pdf_link', true ) ) . '">';
		echo '<img class="barcode-icon" src="' . esc_url( home_url( 'wp-content/plugins/virtuaria-pagseguro/public/images/codigo-de-barras.png' ) ) . '" alt="Boleto"/>';
		echo 'Imprimir Boleto Bancário</a>';
		echo '</div>';
		echo '<style>
		.tiket-info {
			border: 1px solid #ddd;
			padding: 20px;
			max-width: 600px;
		}
		.tiket-info > .pdf-link {
			background-color: green;
			color: #fff;
			padding: 5px 15px;
			border-radius: 6px;
			display: table;
			margin-top: 10px;
			transition: filter .2s;
			text-decoration: none;
		}
		div.tiket-info > .pdf-link:hover {
			color: #fff;
			filter: brightness(1.3);
		}
		.tiket-info > .pdf-link .barcode-icon {
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

		if ( $amount && 'processing' === $order->get_status() ) {
			if ( $this->api->refund_order( $order_id, $amount ) ) {
				$order->add_order_note( 'PagSeguro: Reembolso de R$' . $amount . ' bem sucedido.', 0, true );
				return true;
			}
		} else {
			$order->add_order_note( 'PagSeguro: Não foi possível reembolsar R$' . $amount . '. Verifique o status da transação e o valor a ser reembolsado e tente novamente.', 0, true );
		}

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

		$cart_total = $this->get_order_total();

		$combo_installments = array();
		foreach ( range( 1, $this->installments ) as $installment ) {
			if ( 1 === $installment ) {
				$combo_installments[] = $cart_total;
				continue;
			}

			$combo_installments[] = $this->get_installment_value(
				$cart_total,
				$installment
			);
		}

		wc_get_template(
			'transparent-checkout.php',
			array(
				'cart_total'   => $cart_total,
				'flag'         => plugins_url( 'assets/images/brazilian-flag.png', plugin_dir_path( __FILE__ ) ),
				'installments' => $combo_installments,
				'has_tax'      => floatval( $this->tax ) > 0,
			),
			'woocommerce/pagseguro/',
			Virtuaria_Pagseguro::get_templates_path()
		);
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
		$credit  = get_user_meta( $order->get_customer_id(), '_pagseguro_credit_info', true );

		if ( ! $order
			|| 'processing' !== $order->get_status()
			|| 'virt_pagseguro' !== $order->get_payment_method()
			|| 'yes' !== $options['enabled']
			|| ! $credit['token'] ) {
			return;
		}

		add_meta_box(
			'pagseguro-additional-charge',
			__( 'Cobrança Adicional', 'woocommerce-pagseguro' ),
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
		<input type="number" style="width:calc(100% - 36px)" name="additional_value" id="additional-value" step="0.01"/>
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
		if ( ! $order || ! in_array( $order->get_status(), array( 'on-hold', 'processing' ), true ) ) {
			return;
		}

		if ( isset( $_POST['additional_value'] )
			&& isset( $_POST['additional_charge_nonce'] )
			&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['additional_charge_nonce'] ) ), 'do_additional_charge' )
			&& floatval( $_POST['additional_value'] ) > 0 ) {
			$amount = number_format( sanitize_text_field( wp_unslash( $_POST['additional_value'] ) ), 2, '.', '' );

			if ( $amount <= 0 ) {
				if ( 'yes' === $this->debug ) {
					$order->add_order_note( 'PagSeguro: Cobrança Adicional com valor da inválido.', 0, true );
					$this->log->add( $this->id, 'Valor inválido ou pedido não encontrado para cobrança adicional.', WC_Log_Levels::ERROR );
				}
				return;
			}

			$pagseguro_card_info = get_user_meta( $order->get_customer_id(), '_pagseguro_credit_info', true );

			if ( ! $pagseguro_card_info || ! isset( $pagseguro_card_info['token'] ) ) {
				if ( 'yes' === $this->debug ) {
					$order->add_order_note( 'PagSeguro: Cobrança Adicional, método de pagamento do cliente ausente.', 0, true );
					$this->log->add( $this->id, 'Cobrança Adicional: método de pagamento do cliente ausente', WC_Log_Levels::ERROR );
				}
				return;
			}
			$data = array(
				'headers' => array(
					'Authorization' => $this->token,
					'Content-Type'  => 'application/json',
				),
				'body'    => array(
					'reference_id'      => strval( $order->get_id() ),
					'description'       => substr( get_bloginfo( 'name' ), 0, 63 ),
					'amount'            => array(
						'value'    => $amount * 100,
						'currency' => 'BRL',
					),
					'notification_urls' => array( home_url( 'wc-api/WC_Virtuaria_PagSeguro_Gateway' ) ),
					'payment_method'    => array( 'type' => 'CREDIT_CARD' ),
				),
				'timeout' => 120,
			);

			$data['body']['payment_method']['installments']    = 1;
			$data['body']['payment_method']['capture']         = true;
			$data['body']['payment_method']['soft_descriptor'] = $this->soft_descriptor;
			$data['body']['payment_method']['card']['id']      = $pagseguro_card_info['token'];

			if ( 'yes' === $this->debug ) {
				$this->log->add( $this->id, 'Send new additional charge: ' . wp_json_encode( $data ), WC_Log_Levels::ERROR );
			}
			$data['body'] = wp_json_encode( $data['body'] );

			if ( 'sandbox' === $this->environment ) {
				$endpoint = 'https://sandbox.api.pagseguro.com/';
			} else {
				$endpoint = 'https://api.pagseguro.com/';
			}

			$request = wp_remote_post(
				$endpoint . 'charges',
				$data
			);

			if ( is_wp_error( $request ) ) {
				if ( 'yes' === $this->debug ) {
					$this->log->add(
						$this->id,
						'New charge error: ' . $request->get_error_message(),
						WC_Log_Levels::ERROR
					);
				}
				$order->add_order_note( 'PagSeguro: Não foi possível criar cobrança adicional.', 0, true );
				return;
			}

			if ( 'yes' === $this->debug ) {
				$this->log->add(
					$this->id,
					'Server response in additional charge: ' . wp_json_encode( $request ),
					WC_Log_Levels::INFO
				);
			}

			$response  = json_decode( wp_remote_retrieve_body( $request ), true );
			$resp_code = intval( wp_remote_retrieve_response_code( $request ) );
			$note_resp = '';
			if ( 201 !== $resp_code ) {
				if ( 401 === $resp_code ) {
					$note_resp = __( 'Pagamento não autorizado.', 'virtuaria-pagseguro' );
				} elseif ( in_array( $resp_code, array( 400, 409 ), true ) ) {
					$msg = $response['error_messages'][0]['description'];
					if ( 'invalid_parameter' === $response['error_messages'][0]['description'] ) {
						$msg = 'Verifique os dados enviados e tente novamente.';
					}
					$note_resp = $msg;
				} else {
					$note_resp = __( 'Não foi possível processar a sua cobrança. Por favor, tente novamente mais tarde.', 'virtuaria-pagseguro' );
				}
			}

			if ( $note_resp ) {
				$order->add_order_note( 'PagSeguro: ' . $note_resp, 0, true );
				return;
			}

			if ( isset( $_POST['credit_charge_reason'] ) && ! empty( $_POST['credit_charge_reason'] ) ) {
				$reason = '<br>Motivo: ' . sanitize_text_field( wp_unslash( $_POST['credit_charge_reason'] ) ) . '.';
			}
			$order->add_order_note( 'PagSeguro: Nova cobrança enviada R$' . number_format( $amount, 2, ',', '.' ) . '.' . $reason, 0, true );

			if ( 'PAID' === $response['status'] ) {
				$order->add_order_note(
					sprintf(
						/* translators: %s: amount value */
						__( 'PagSeguro: Cobrança recebida R$ %s', 'virtuaria-pagseguro' ),
						number_format( $response['amount']['value'] / 100, 2, ',', '.' )
					),
					0,
					true
				);
			} elseif ( 'DECLINED' === $response['status'] ) {
				$order->add_order_note(
					sprintf(
						/* translators: %s: payment response */
						__( 'PagSeguro: Não autorizado, %s.', 'virtuaria-pagseguro' ),
						$response['payment_response']['message']
					),
					0,
					true
				);
			}
		}
	}
}
