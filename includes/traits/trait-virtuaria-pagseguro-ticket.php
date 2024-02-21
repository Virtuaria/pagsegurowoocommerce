<?php
/**
 * Reused ticket code.
 *
 * @package Virtuaria/Payments/Pagseguro.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Definition.
 */
trait Virtuaria_PagSeguro_Ticket {
	/**
	 * Thank You page message.
	 *
	 * @param int $order_id Order ID.
	 */
	public function ticket_thankyou_page( $order_id ) {
		$this->get_ticket_info( $order_id );
	}

	/**
	 * Display ticket info.
	 *
	 * @param int $order_id the order id.
	 */
	private function get_ticket_info( $order_id ) {
		$formatted_barcode = get_post_meta(
			$order_id,
			'_formatted_barcode',
			true
		);

		if ( ! $formatted_barcode ) {
			return;
		}
		echo '<div class="ticket-info">';
		echo '<h3 style="margin: 0;">' . esc_html_e( 'Utilize o código de barras abaixo para efetuar o pagamento em lotéricas, instituições financeiras ou internet banking.', 'virtuaria-pagseguro' ) . '</h3>';
		echo '<strong style="display:block;margin: 15px 0;">' . esc_html( $formatted_barcode ) . '</strong>';
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
	public function ticket_email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		if ( $sent_to_admin
			|| 'on-hold' !== $order->get_status()
			|| $this->id !== $order->get_payment_method() ) {
			return;
		}
		$this->get_ticket_info( $order->get_id() );
	}

	/**
	 * Default settings.
	 */
	public function get_ticket_default_settings() {
		$settings = array(
			'ticket_validate'    => array(
				'title'             => __( 'Validade', 'virtuaria-pagseguro' ),
				'type'              => 'number',
				'description'       => __( 'Define o limite de dias onde o boleto pode ser pago.', 'virtuaria-pagseguro' ),
				'default'           => '5',
				'custom_attributes' => array(
					'min' => 1,
				),
			),
			'instruction_line_1'     => array(
				'title'             => __( '1º Linha de Instrução', 'virtuaria-pagseguro' ),
				'type'              => 'text',
				'description'       => __( 'Define a primeira linha de instruções sobre o pagamento do Boleto. Deixe em branco para desativar.', 'virtuaria-pagseguro' ),
				'default'           => __( '* Pagável em qualquer instituição bancária e lotéricas.', 'virtuaria-pagseguro' ),
				'custom_attributes' => array(
					'maxlength' => '75',
				),
			),
			'instruction_line_2'     => array(
				'title'             => __( '2º Linha de Instrução', 'virtuaria-pagseguro' ),
				'type'              => 'text',
				'description'       => __( 'Define a segunda linha de instruções sobre o pagamento do Boleto. Deixe em branco para desativar.', 'virtuaria-pagseguro' ),
				'default'           => __( '* Não receber após o vencimento.', 'virtuaria-pagseguro' ),
				'custom_attributes' => array(
					'maxlength' => '75',
				),
			),
			'ticket_discount'        => array(
				'title'             => __( 'Desconto (%)', 'virtuaria-pagseguro' ),
				'type'              => 'number',
				'description'       => __( 'Define um percentual de desconto a ser aplicado ao total do pedido, caso o pagamento seja realizado com Boleto. O desconto não incide sobre o valor do frete.', 'virtuaria-pagseguro' ),
				'custom_attributes' => array(
					'min'  => 0,
					'step' => '0.01',
				),
			),
			'ticket_discount_coupon' => array(
				'title'       => __( 'Desabilitar desconto em cupons', 'virtuaria-pagseguro' ),
				'type'        => 'checkbox',
				'label'       => __( 'Desabilita o desconto do Boleto em conjunto com cupons', 'virtuaria-pagseguro' ),
				'description' => __( 'Desabilita o desconto do Boleto, caso um cupom seja aplicado ao pedido.', 'virtuaria-pagseguro' ),
				'default'     => '',
			),
			'ticket_discount_ignore' => array(
				'title'       => __( 'Desabilitar desconto em produtos das seguintes categorias', 'virtuaria-pagseguro' ),
				'type'        => 'ignore_discount',
				'description' => __( 'Define as categorias que serão ignoradas para o cálculo do desconto ticket.', 'virtuaria-pagseguro' ),
				'default'     => '',
			),
		);

		if ( isset( $this->global_settings['payment_form'] )
			&& 'separated' !== $this->global_settings['payment_form'] ) {
			$settings = array(
				'ticket'        => array(
					'title'       => __( 'Boleto', 'virtuaria-pagseguro' ),
					'type'        => 'title',
					'description' => '',
				),
				'ticket_enable' => array(
					'title'       => __( 'Habilitar', 'virtuaria-pagseguro' ),
					'type'        => 'checkbox',
					'description' => __( 'Define se a opção de pagamento Boleto deve estar disponível durante o checkout.', 'virtuaria-pagseguro' ),
					'default'     => 'yes',
				),
			) + $settings;
		}
		return $settings;
	}

	/**
	 * Register in order note, pdf link.
	 *
	 * @param wc_order $order the order.
	 */
	public function register_pdf_link_note( $order ) {
		$link     = get_post_meta( $order->get_id(), '_pdf_link', true );
		$bar_code = get_post_meta( $order->get_id(), '_formatted_barcode', true );

		if ( $link && $bar_code ) {
			if ( function_exists( '\\order\\limit_characters_order_note' ) ) {
				remove_filter(
					'woocommerce_new_order_note_data',
					'\\order\\limit_characters_order_note'
				);
			}
			$order->add_order_note(
				sprintf(
					'%1$s:<br> PDF 📁: <a href="%2$s" target="_blank" style="font-weight: bold">%3$s</a>.<br><br><b>Código de barras 📦:</b> <div class="barcode" style="display:block">%4$s</div><a href="#" id="copy-barcode" style="display:table;margin: 10px auto 0;" class="button button-primary">Copiar</a>',
					__( 'PagSeguro Boleto', 'virtuaria-pagseguro' ),
					esc_url( $link ),
					__( 'Imprimir o boleto bancário', 'virtuaria-pagseguro' ),
					esc_html( $bar_code )
				)
			);
			if ( function_exists( '\\order\\limit_characters_order_note' ) ) {
				add_filter(
					'woocommerce_new_order_note_data',
					'\\order\\limit_characters_order_note'
				);
			}

			$args = array( $order->get_id() );
			if ( ! wp_next_scheduled( 'pagseguro_ticket_check_payment', $args ) ) {
				wp_schedule_single_event(
					strtotime( '23:59:59' ) + ( DAY_IN_SECONDS * ( $this->ticket_validate + 2 ) ),
					'pagseguro_ticket_check_payment',
					$args,
					true
				);
			}
		}
	}

	/**
	 * A function to check and process payment for Pagseguro ticket.
	 *
	 * @param int $order_id The ID of the order to be processed.
	 */
	public function check_payment_ticket( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( $order && ! get_post_meta( $order->get_id(), '_paid_webhook', true ) ) {
			$order->add_order_note(
				__( 'PagSeguro Boleto: o limite de tempo para pagamento deste pedido expirou.', 'virtuaria-pagseguro' )
			);

			$order->update_status( 'cancelled' );
			if ( 'yes' === $this->debug ) {
				$this->log->add(
					$this->tag,
					'Pedido #' . $order->get_order_number() . ' mudou para o status cancelado.',
					WC_Log_Levels::INFO
				);
			}
		}
	}
}
