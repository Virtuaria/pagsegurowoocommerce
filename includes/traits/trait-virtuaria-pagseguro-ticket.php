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
			'ticket_validate' => array(
				'title'             => __( 'Validade', 'virtuaria-pagseguro' ),
				'type'              => 'number',
				'description'       => __( 'Define o limite de dias onde o boleto pode ser pago.', 'virtuaria-pagseguro' ),
				'default'           => '5',
				'custom_attributes' => array(
					'min' => 1,
				),
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
}
