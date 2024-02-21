<?php
/**
 * Handle common code to credit.
 *
 * @package virtuaria/Payments/PagSeguro.
 */

defined( 'ABSPATH' ) || exit;

trait Virtuaria_PagSeguro_Credit {
	/**
	 * Checkout scripts.
	 */
	public function public_credit_scripts_styles() {
		if ( is_checkout()
			&& $this->is_available()
			&& ! get_query_var( 'order-received' ) ) {
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

			wp_localize_script(
				'pagseguro-virt',
				'is_separated',
				( isset( $this->global_settings['payment_form'] )
				&& 'separated' === $this->global_settings['payment_form'] )
			);

			if ( 'one' === $this->get_option( 'display' )
				&& ( isset( $this->global_settings['layout_checkout'] )
				&& 'lines' !== $this->global_settings['layout_checkout'] ) ) {
				wp_enqueue_style(
					'checkout-fields',
					VIRTUARIA_PAGSEGURO_URL . 'public/css/full-width.css',
					'',
					filemtime( VIRTUARIA_PAGSEGURO_DIR . 'public/css/full-width.css' )
				);
			}

			if ( 'yes' !== $this->credit_enable
				&& ( isset( $this->global_settings['layout_checkout'] )
				&& 'lines' !== $this->global_settings['layout_checkout'] ) ) {
				wp_enqueue_style(
					'form-height',
					VIRTUARIA_PAGSEGURO_URL . 'public/css/form-height.css',
					'',
					filemtime( VIRTUARIA_PAGSEGURO_DIR . 'public/css/form-height.css' )
				);
			}

			if ( ( isset( $this->global_settings['layout_checkout'] )
				&& 'tabs' !== $this->global_settings['layout_checkout'] )
				&& ( isset( $this->global_settings['payment_form'] )
				&& 'separated' !== $this->global_settings['payment_form'] ) ) {
				wp_enqueue_script(
					'pagseguro-virt-new-checkout',
					VIRTUARIA_PAGSEGURO_URL . 'public/js/new-checkout.js',
					array( 'jquery' ),
					filemtime( VIRTUARIA_PAGSEGURO_DIR . 'public/js/new-checkout.js' ),
					true
				);

				wp_enqueue_style(
					'pagseguro-virt-new-checkout',
					VIRTUARIA_PAGSEGURO_URL . 'public/css/new-checkout.css',
					'',
					filemtime( VIRTUARIA_PAGSEGURO_DIR . 'public/css/new-checkout.css' )
				);
			}

			$min_value_to_3ds = $this->get_option( '3ds_min_value' );
			if ( 'yes' === $this->get_option( '3ds' )
				&& ( ! $min_value_to_3ds || $this->get_order_total() >= $min_value_to_3ds ) ) {
				wp_enqueue_script(
					'3ds-autentication',
					VIRTUARIA_PAGSEGURO_URL . 'public/js/3ds.js',
					array( 'jquery' ),
					filemtime( VIRTUARIA_PAGSEGURO_DIR . 'public/js/3ds.js' ),
					true
				);

				$session_3d          = $this->api->get_3ds_session();
				$confirm_sell_no_3ds = $this->get_option( 'confirm_sell', 'no' );
				wp_localize_script(
					'3ds-autentication',
					'auth_3ds',
					array(
						'order_total' => $this->get_order_total() * 100,
						'session'     => $session_3d,
						'allow_sell'  => $confirm_sell_no_3ds,
						'environment' => ( isset( $this->global_settings['environment'] )
							&& 'sandbox' === $this->global_settings['environment'] )
							? 'SANDBOX'
							: 'PROD',
						'card_id'     => $this->get_card_id(),
					)
				);

				if ( ! $session_3d && 'yes' !== $confirm_sell_no_3ds ) {
					wc_add_notice(
						__( 'Ocorreu uma falha na comunicação com o PagBank. Pagamento com cartão de crédito foi desativado. Por favor recarregue a página.', 'virtuaria-pagseguro' ),
						'error'
					);
				}
			}
		}

		wp_enqueue_style(
			'pagseguro-installmnets',
			VIRTUARIA_PAGSEGURO_URL . 'public/css/installments.css',
			'',
			filemtime( VIRTUARIA_PAGSEGURO_DIR . 'public/css/installments.css' )
		);
	}

	/**
	 * Default settings.
	 */
	public function get_credit_default_settings() {
		$settings = array(
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
			'display_installment' => array(
				'title'       => __( 'Exibir parcelamento?', 'virtuaria-pagseguro' ),
				'type'        => 'select',
				'description' => __( 'Selecione a forma de exibição do parcelamento na listagem de produtos.', 'virtuaria-pagseguro' ),
				'options'     => array(
					'no-display' => __( 'Não exibir', 'virtuaria-pagseguro' ),
					'with-fee'   => __( 'Exibir todas as parcelas', 'virtuaria-pagseguro' ),
					'no-fee'     => __( 'Exibir somente as parcelas sem juros', 'virtuaria-pagseguro' ),
				),
				'default'     => 'no-display',
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
				'description'       => __( 'Texto exibido na fatura do cartão para identificar a loja (máximo de <b>17 caracteres</b>, não deve conter caracteres especiais ou espaços em branco).', 'virtuaria-pagseguro' ),
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
			'erase_cards'         => array(
				'title'       => __( 'Limpar cartões (tokens)', 'virtuaria-pagseguro' ),
				'type'        => 'erase_cards',
				'description' => __( 'Remove os métodos de pagamento armazenados. <b>Atenção:</b> É recomendada a criação de um backup, pois, essa opção não pode ser desfeita.', 'virtuaria-pagseguro' ),
			),
			'3ds'                 => array(
				'title'       => __( 'Autenticação 3DS', 'virtuaria-pagseguro' ),
				'label'       => __( 'Habilitar autenticação 3DS', 'virtuaria-pagseguro' ),
				'type'        => 'checkbox',
				'description' => __(
					'Ative a autenticação 3D Secure para transações de cartão de crédito,
					implementando um protocolo de segurança avançado que reforça a proteção em compras online.
					Este mecanismo previne chargebacks resultantes de transações não autorizadas, protegendo o lojista contra possíveis fraudes. Para detalhes adicionais, consulte a <a href="https://dev.pagbank.uol.com.br/reference/criar-pagar-pedido-com-3ds-validacao-pagbank" target="_blank">documentação</a>.',
					'virtuaria-pagseguro'
				),
				'default'     => 'no',
			),
			'confirm_sell'        => array(
				'title'       => __( 'Permitir a venda quando o 3DS não for suportado?', 'virtuaria-pagseguro' ),
				'label'       => __( 'Habilite a conclusão da venda em casos de incompatibilidade com o 3DS', 'virtuaria-pagseguro' ),
				'type'        => 'checkbox',
				'description' => __(
					'Alguns cartões não oferecem suporte à autenticação 3DS, por isso, recomendamos ativar esta configuração para não perder vendas.
					Ao selecionar esta opção, o cliente terá a possibilidade de finalizar a compra,
					mesmo que o cartão não suporte esse recurso ou se a obtenção da sessão 3D Secure junto ao PagBank não for bem-sucedida.',
					'virtuaria-pagseguro'
				),
				'default'     => 'no',
			),
			'3ds_min_value'       => array(
				'title'             => __( 'Valor mínimo (R$) do pedido para que a autenticação 3DS seja aplicada', 'virtuaria-pagseguro' ),
				'label'             => __( 'Informe o valor mínimo do carrinho para que autenticação 3DS seja utilizada.', 'virtuaria-pagseguro' ),
				'type'              => 'number',
				'description'       => __(
					'Use para evitar uma etapa extra na finalização da compra de pedidos de menor valor. Deixe o campo em branco para desativar esta verificação e aplicar 3DS a pedidos de qualquer valor.',
					'virtuaria-pagseguro'
				),
				'custom_attributes' => array(
					'step' => '0.01',
				),
				'default'           => '',
			),
		);

		if ( isset( $this->global_settings['payment_form'] )
			&& 'separated' !== $this->global_settings['payment_form'] ) {
			$settings = array(
				'credit'        => array(
					'title'       => __( 'Cartão de crédito', 'virtuaria-pagseguro' ),
					'type'        => 'title',
					'description' => '',
				),
				'credit_enable' => array(
					'title'       => __( 'Habilitar', 'virtuaria-pagseguro' ),
					'type'        => 'checkbox',
					'description' => __( 'Define se a opção de pagamento Crédito deve estar disponível durante o checkout.', 'virtuaria-pagseguro' ),
					'default'     => 'yes',
				),
			) + $settings;
		}
		return $settings;
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
	 * Erase cards option.
	 *
	 * @param string $key  the name from field.
	 * @param array  $data the data.
	 */
	public function generate_erase_cards_html( $key, $data ) {
		$defaults = array(
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

		$data['id'] = 'woocommerce_' . $this->id . '_erase_cards';
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
				<input type="hidden" name="erase_cards" id="erase-cards" />
				<button class="button-primary erase-card-option">Remover TODOS os cartões</button>
				<p class="description">
					<?php echo wp_kses_post( $data['description'] ); ?>
				</p>
			</td>
		</tr>  

		<?php
		return ob_get_clean();
	}

	/**
	 * Do erase cards.
	 */
	public function erase_cards() {
		if ( isset( $_POST['erase_cards'] )
			&& 'CONFIRMED' === $_POST['erase_cards'] ) {
			global $wpdb;

			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM wp_usermeta WHERE meta_key = '_pagseguro_credit_info_store_%d'",
					get_current_blog_id()
				)
			);
		}
	}

	/**
	 * Display pagseguro installments to product.
	 */
	public function display_product_installments() {
		global $product;

		if ( 'yes' === $this->credit_enable
			&& $product
			&& $product->is_type( 'simple' )
			&& 'no-display' !== $this->get_option( 'display_installment' )
			&& $this->installments > 1 ) {
			$this->show_installment_html( $product );
		}
	}

	/**
	 * Get installment html.
	 *
	 * @param wc_product $product product.
	 */
	private function show_installment_html( $product ) {
		$has_tax         = floatval( $this->tax ) > 0;
		$max_installment = $this->installments;
		$min_installment = $this->min_installment;
		$fee_from        = intval( $this->fee_from );

		if ( 2 === $fee_from ) {
			echo wp_kses_post(
				$this->display_max_installments(
					$product->get_price(),
					$has_tax,
					'with-fee'
				),
			);
		} else {
			echo wp_kses_post(
				$this->display_max_installments(
					$product->get_price(),
					$has_tax,
					'no-fee'
				),
			);
		}

		if ( $product->get_price() > $min_installment ) {
			require plugin_dir_path( __FILE__ ) . '../../templates/credit-installments-table.php';
		}
	}

	/**
	 * Get max installments to credit.
	 *
	 * @param float   $total   total to buy.
	 * @param boolean $has_tax true if tax should applied otherwise false.
	 * @param string  $display setting from display.
	 */
	private function display_max_installments( $total, $has_tax, $display = '' ) {
		$installment = 1;
		$subtotal    = 0;
		$calc_total  = 0;
		$tax_applied = false;

		if ( $total < $this->min_installment ) {
			$subtotal = $total;
		} else {
			while ( $installment <= $this->installments ) {
				if ( $has_tax
					&& $this->fee_from <= $installment
					&& 1 !== $installment ) {
					$calc_total = $this->get_installment_value(
						$total,
						$installment
					) / $installment;
				} else {
					$calc_total = $total / $installment;
				}

				if ( $this->min_installment > $calc_total
					|| ( $has_tax && $this->fee_from <= $installment && 'no-fee' === $display ) ) {
					-- $installment;
					break;
				}

				if ( $has_tax && $this->fee_from <= $installment && 'with-fee' === $display ) {
					$tax_applied = true;
				}

				$subtotal = $calc_total;
				$installment++;
			}
		}

		if ( $installment > $this->installments ) {
			$installment = $this->installments;
		}

		return sprintf(
			'<div class="virt-pagseguro-installments">Em <span class="installment">%dx</span> de <span class="subtotal">R$%s</span> <span class="notax">%s</span></div>',
			esc_html( $installment ),
			floatval( $subtotal ) > 0 ? esc_html(
				number_format(
					$subtotal,
					2,
					',',
					'.'
				)
			) : '0,00',
			$tax_applied ? '' : 'sem juros'
		);
	}

	/**
	 * Display based in variation installment price and discount.
	 *
	 * @param array      $params    parameters.
	 * @param wc_product $parent    the product parent.
	 * @param wc_product $variation the variation.
	 */
	public function variation_discount_and_installment( $params, $parent, $variation ) {
		if ( $variation
			&& 'yes' === $this->credit_enable
			&& 'no-display' !== $this->get_option( 'display_installment' )
			&& $this->installments > 1 ) {
			ob_start();
			$this->show_installment_html(
				$variation
			);
			$params['price_html'] .= ob_get_clean();
		}
		return $params;
	}

	/**
	 * Display installment in loop products.
	 */
	public function loop_products_installment() {
		global $product;

		$option_display = $this->get_option( 'display_installment' );
		if ( 'yes' === $this->credit_enable
			&& $product
			&& 'no-display' !== $option_display
			&& $this->installments > 1 ) {
			echo wp_kses_post(
				$this->display_max_installments(
					$product->get_price(),
					floatval( $this->tax ) > 0,
					$option_display
				)
			);
		}
	}

	/**
	 * Add fee to installment with tax.
	 *
	 * @param wc_order $order order.
	 */
	public function add_installment_fee( $order ) {
		$charge_amount = get_post_meta(
			$order->get_id(),
			'_charge_amount',
			true
		);
		if ( $this->tax
			&& ( ( $charge_amount / 100 ) - $order->get_total() ) > 0 ) {
			$fee = new WC_Order_Item_Fee();
			$fee->set_name(
				__( 'Parcelamento pagseguro', 'virtuaria-pagseguro' )
			);
			$fee->set_total(
				( $charge_amount / 100 ) - $order->get_total()
			);

			$order->add_item( $fee );
			$order->calculate_totals();
			$order->save();
		}
	}

	/**
	 * Get the card ID if the user is logged in and the card info should be stored.
	 *
	 * @return mixed false if user is not logged in or card info should not be stored, otherwise the card ID token.
	 */
	public function get_card_id() {
		if ( ! is_user_logged_in() || 'do_not_store' === $this->save_card_info ) {
			return false;
		}

		$pagseguro_card_info = get_user_meta(
			get_current_user_id(),
			'_pagseguro_credit_info_store_' . get_current_blog_id(),
			true
		);
		if ( isset( $pagseguro_card_info['token'] ) ) {
			return $pagseguro_card_info['token'];
		}

		return false;
	}
}
