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
			$title .= '<span class="pix-discount">(desconto de <span class="percentage">' . str_replace( '.', ',', $this->pix_discount ) . '%</span>)</span>';
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
	 * Check payment pix.
	 *
	 * @param wc_order $order the order.
	 */
	public function check_payment_pix( $order ) {
		$qr_code = $order->get_meta( '_pagseguro_qrcode' );
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
