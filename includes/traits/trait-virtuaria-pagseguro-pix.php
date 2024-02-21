<?php
/**
 * Reused pix code.
 *
 * @package Virtuaria/Payments/Pagseguro.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Definition.
 */
trait Virtuaria_PagSeguro_Pix {
	/**
	 * Thank You page message.
	 *
	 * @param int $order_id Order ID.
	 */
	public function pix_thankyou_page( $order_id ) {
		$qr_code     = get_post_meta( $order_id, '_pagseguro_qrcode', true );
		$qr_code_png = get_post_meta( $order_id, '_pagseguro_qrcode_png', true );

		if ( $qr_code && $qr_code_png ) {
			$validate = $this->format_pix_validate( $this->pix_validate );
			require plugin_dir_path( __FILE__ ) . '../../templates/payment-instructions.php';
		}
	}

	/**
	 * Checkout scripts.
	 */
	public function public_pix_scripts_styles() {
		if ( is_checkout()
			&& $this->is_available()
			&& get_query_var( 'order-received' ) ) {
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

	/**
	 * Default settings.
	 */
	public function get_pix_default_settings() {
		$settings = array(
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
				'description' => __( 'Define a mensagem que será exibida na tela de pedido após o pagamento do Pix. O pagamento é identificado automaticamente e a tela muda exibindo esta mensagem.', 'virtuaria-pagseguro' ),
				'default'     => 'Seu pagamento foi aprovado!',
			),
			'pix_discount'        => array(
				'title'             => __( 'Desconto (%)', 'virtuaria-pagseguro' ),
				'type'              => 'number',
				'description'       => __( 'Define um percentual de desconto a ser aplicado ao total do pedido, caso o pagamento seja realizado com Pix. O desconto não incide sobre o valor do frete.', 'virtuaria-pagseguro' ),
				'custom_attributes' => array(
					'min'  => 0,
					'step' => '0.01',
				),
			),
			'pix_discount_coupon' => array(
				'title'       => __( 'Desabilitar desconto em cupons', 'virtuaria-pagseguro' ),
				'type'        => 'checkbox',
				'label'       => __( 'Desabilita o desconto Pix em conjunto com cupons', 'virtuaria-pagseguro' ),
				'description' => __( 'Desabilita o desconto Pix, caso um cupom seja aplicado ao pedido.', 'virtuaria-pagseguro' ),
				'default'     => '',
			),
			'pix_discount_ignore' => array(
				'title'       => __( 'Desabilitar desconto em produtos das seguintes categorias', 'virtuaria-pagseguro' ),
				'type'        => 'ignore_discount',
				'description' => __( 'Define as categorias que serão ignoradas para o cálculo do desconto pix.', 'virtuaria-pagseguro' ),
				'default'     => '',
			),
		);

		if ( isset( $this->global_settings['payment_form'] )
			&& 'separated' !== $this->global_settings['payment_form'] ) {
			$settings = array(
				'pix'        => array(
					'title' => __( 'PIX', 'virtuaria-pagseguro' ),
					'type'  => 'title',
				),
				'pix_enable' => array(
					'title'       => __( 'Habilitar', 'virtuaria-pagseguro' ),
					'type'        => 'checkbox',
					'description' => __( 'Define se a opção de pagamento Pix deve estar disponível durante o checkout.', 'virtuaria-pagseguro' ),
					'default'     => 'yes',
				),
			) + $settings;
		}
		return $settings;
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
	 * Add content to the WC emails.
	 *
	 * @param  WC_Order $order         Order object.
	 * @param  bool     $sent_to_admin Send to admin.
	 * @param  bool     $plain_text    Plain text or HTML.
	 * @return string
	 */
	public function pix_email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		if ( $sent_to_admin
			|| 'on-hold' !== $order->get_status()
			|| $this->id !== $order->get_payment_method() ) {
			return;
		}

		$qr_code     = get_post_meta( $order->get_id(), '_pagseguro_qrcode', true );
		$qr_code_png = get_post_meta( $order->get_id(), '_pagseguro_qrcode_png', true );

		if ( $qr_code && $qr_code_png ) {
			$validate = $this->format_pix_validate( $this->pix_validate );
			require_once plugin_dir_path( __FILE__ ) . '../../templates/payment-instructions.php';
		}
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
	 * Add QR Code in order note.
	 *
	 * @param wc_order $order   the order.
	 * @param string   $qr_code the qr code.
	 */
	private function add_qrcode_in_note( $order, $qr_code ) {
		if ( function_exists( '\\order\\limit_characters_order_note' ) ) {
			remove_filter(
				'woocommerce_new_order_note_data',
				'\\order\\limit_characters_order_note'
			);
			$order->add_order_note(
				'PagSeguro Pix Copia e Cola: <div class="pix">'
				. $qr_code
				. '</div><a href="#" id="copy-qr" class="button button-primary">Copiar</a>'
			);
			add_filter(
				'woocommerce_new_order_note_data',
				'\\order\\limit_characters_order_note'
			);
		} else {
			$order->add_order_note(
				'PagSeguro Pix Copia e Cola: <div class="pix">'
				. $qr_code
				. '</div><a href="#" id="copy-qr" class="button button-primary">Copiar</a>'
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
	 * Check payment pix.
	 *
	 * @param wc_order $order the order.
	 */
	public function check_payment_pix( $order ) {
		$qr_code = get_post_meta(
			$order->get_id(),
			'_pagseguro_qrcode',
			true
		);
		if ( $qr_code ) {
			$this->add_qrcode_in_note( $order, $qr_code );

			$args = array( $order->get_id() );
			if ( ! wp_next_scheduled( 'pagseguro_pix_check_payment', $args ) ) {
				wp_schedule_single_event(
					strtotime( 'now' ) + $this->pix_validate + 1800,
					'pagseguro_pix_check_payment',
					$args
				);
			}
		}
	}
}
