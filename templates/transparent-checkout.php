<?php
/**
 * Transparent checkout.
 *
 * @package Virtuaria/Payments/Pagseguro
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit;

$settings = get_option( 'woocommerce_virt_pagseguro_settings' );
if ( is_user_logged_in() && 'do_not_store' !== $settings['save_card_info'] ) {
	$pagseguro_card_info = get_user_meta( get_current_user_id(), '_pagseguro_credit_info_store_' . get_current_blog_id(), true );
	if ( isset( $pagseguro_card_info['token'] ) ) {
		$card_loaded = true;
	}
}

/**
 * Get form class.
 *
 * @param boolean $card_loaded true if card is loaded.
 * @param boolean $full_width  true if one column.
 * @param string  $default default class.
 */
function pagseguro_form_class( $card_loaded, $full_width, $default ) {
	$class = '';
	if ( $card_loaded ) {
		$class .= ' card-loaded';
	}
	if ( $full_width ) {
		$class .= ' form-row-wide';
	} else {
		$class .= ' ' . $default;
	}

	return $class;
}
?>
<fieldset id="pagseguro-payment" data-cart_total="<?php echo esc_attr( number_format( $cart_total, 2, '.', '' ) ); ?>" class="<?php echo $card_loaded ? 'card-loaded' : ''; ?>">
	<ul id="pagseguro-payment-methods">
		<?php
		if ( $methods_enabled['credit'] ) :
			?>
			<li class="active">
				<label>
					<input id="credit-card" type="radio" name="payment_mode" value="credit" <?php echo 'credit' === $_POST['payment_mode'] ? 'checked' : ''; ?> /> <?php esc_html_e( 'Cartão de Crédito', 'virtuaria-pagseguro' ); ?>
				</label>
			</li>	
				<?php
		endif;
		if ( $methods_enabled['pix'] ) :
			?>
			<li>
				<label>
					<input id="pix" type="radio" name="payment_mode" value="pix" <?php echo 'pix' === $_POST['payment_mode'] ? 'checked' : ''; ?>/> <?php esc_html_e( 'PIX', 'virtuaria-pagseguro' ); ?>
				</label>
			</li>
			<?php
		endif;
		if ( $methods_enabled['ticket'] ) :
			?>
			<li>
				<label>
					<input id="banking-ticket" type="radio" name="payment_mode" value="ticket" <?php echo 'ticket' === $_POST['payment_mode'] ? 'checked' : ''; ?>/> <?php esc_html_e( 'Boleto', 'virtuaria-pagseguro' ); ?>
				</label>
			</li>
			<?php
		endif;
		?>
	</ul>
	<div class="clear"></div>	
	<div id="pagseguro-credit-card-form" class="pagseguro-method-form">
		<p id="pagseguro-card-holder-name-field" class="form-row <?php echo esc_attr( pagseguro_form_class( $card_loaded, $full_width, 'form-row-first' ) ); ?>">
			<label for="pagseguro-card-holder-name"><?php esc_html_e( 'Titular', 'virtuaria-pagseguro' ); ?> <small>(<?php esc_html_e( 'como no cartão', 'virtuaria-pagseguro' ); ?>)</small> <span class="required">*</span></label>
			<input id="pagseguro-card-holder-name" name="pagseguro_card_holder_name" class="input-text" type="text" autocomplete="off" style="font-size: 1.5em; padding: 8px;" value="<?php echo isset( $_POST['pagseguro_holder_name'] ) ? esc_html( $_POST['pagseguro_holder_name'] ) : ''; ?>"/>
		</p>
		<p id="pagseguro-card-number-field" class="form-row <?php echo esc_attr( pagseguro_form_class( $card_loaded, $full_width, 'form-row-last' ) ); ?>">
			<label for="pagseguro-card-number"><?php esc_html_e( 'Número do cartão', 'virtuaria-pagseguro' ); ?> <span class="required">*</span></label>
			<input id="pagseguro-card-number" name="pagseguro_card_number" maxlength="16" class="input-text wc-credit-card-form-card-number" type="tel" maxlength="20" autocomplete="off" placeholder="&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;" style="font-size: 1.5em; padding: 8px;"  value="<?php echo isset( $_POST['pagseguro_card_number'] ) ? esc_html( $_POST['pagseguro_card_number'] ) : ''; ?>"/>
		</p>
		<div class="clear"></div>
		<p id="pagseguro-card-expiry-field" class="form-row <?php echo esc_attr( pagseguro_form_class( $card_loaded, $full_width, 'form-row-first' ) ); ?>">
			<label for="pagseguro-card-expiry"><?php esc_html_e( 'Validade (MM / AAAA)', 'virtuaria-pagseguro' ); ?> <span class="required">*</span></label>
			<input id="pagseguro-card-expiry" name="pagseguro_card_validate" class="input-text wc-credit-card-form-card-expiry" type="tel" autocomplete="off" placeholder="<?php esc_html_e( 'MM / AAAA', 'virtuaria-pagseguro' ); ?>" style="font-size: 1.5em; padding: 8px;"  value="<?php echo isset( $_POST['pagseguro_card_validate'] ) ? esc_html( $_POST['pagseguro_card_validate'] ) : ''; ?>" maxlength="9"/>
		</p>
		<p id="pagseguro-card-cvc-field" class="form-row <?php echo esc_attr( pagseguro_form_class( $card_loaded, $full_width, 'form-row-last' ) ); ?>">
			<label for="pagseguro-card-cvc"><?php esc_html_e( 'Código de segurança', 'virtuaria-pagseguro' ); ?> <span class="required">*</span></label>
			<input id="pagseguro-card-cvc" name="pagseguro_card_cvc" class="input-text wc-credit-card-form-card-cvc" type="tel" autocomplete="off" placeholder="<?php esc_html_e( 'CVV', 'virtuaria-pagseguro' ); ?>" style="font-size: 1.5em; padding: 8px;"  value="<?php echo isset( $_POST['pagseguro_card_cvc'] ) ? esc_html( $_POST['pagseguro_card_cvc'] ) : ''; ?>"/>
		</p>
		<div class="clear"></div>
		<p id="pagseguro-card-installments-field" class="form-row <?php echo $full_width ? 'form-row-wide' : 'form-row-first'; ?>">
			<label for="pagseguro-card-installments">
				<?php
				esc_html_e( 'Parcelas', 'virtuaria-pagseguro' );

				if ( $min_installment ) :
					?>
					<small>(
						<?php
						echo esc_html(
							sprintf(
								/* translators: %s: amount */
								__( 'mínima de R$ %s', 'virtuaria-pagseguro' ),
								number_format( $min_installment, 2, ',', '.' )
							)
						);
						?>
						)
					</small>
					<?php
				endif;
				?>
				<span class="required">*</span>
			</label>
			<select id="pagseguro-card-installments" name="pagseguro_installments" style="font-size: 1.5em; padding: 4px; width: 100%;">
				<?php
				foreach ( $installments as $index => $installment ) {
					if ( 0 !== $index && $installment < 5 ) {
						// Mínimo de 5 reais por parcela.
						break;
					}
					$aux = $index + 1;
					if ( 1 === $aux ) {
						printf(
							'<option value="%d">%dx de %s sem juros</option>',
							esc_attr( $aux ),
							esc_attr( $aux ),
							wp_kses_post( wc_price( $installment ) )
						);
					} elseif ( ( $installment / $aux ) > $min_installment ) {
						printf(
							'<option value="%d">%dx de %s %s</option>',
							esc_attr( $aux ),
							esc_attr( $aux ),
							wp_kses_post( wc_price( $installment / $aux ) ),
							$has_tax && $fee_from <= $aux ? '(' . wp_kses_post( wc_price( $installment ) ) . ')' : ' sem juros'
						);
					}
				}
				?>
			</select>
			<?php
			if ( is_user_logged_in()
				&& 'do_not_store' !== $settings['save_card_info']
				&& $card_loaded ) :
				?>
				<div class="card-in-use">
					<?php
					if ( $pagseguro_card_info['card_last'] ) {
						echo wp_kses_post(
							sprintf(
								/* translators: %s: card itens */
								__( '<span class="card-brand"><img src="%1$s" alt="Cartão" /></i>%2$s</span><span class="number">**** **** **** %3$s</span><span class="holder">%4$s</span>', 'virtuaria-pagseguro' ),
								esc_url( VIRTUARIA_PAGSEGURO_URL ) . 'public/images/card.png',
								ucwords( $pagseguro_card_info['card_brand'] ),
								$pagseguro_card_info['card_last'],
								$pagseguro_card_info['name']
							)
						);
					}
					?>
				</div>
				<?php
			endif;
			?>
		</p>
		<div class="clear after-installments"></div>
		<?php
		if ( is_user_logged_in() && 'do_not_store' !== $settings['save_card_info'] ) :
			if ( $card_loaded ) :
				?>
				<p id="pagseguro-load-card" class="form-now form-wide">
					<label for="pagseguro-use-other-card"><?php esc_attr_e( 'Usar outro cartão?', 'virtuaria-pagseguro' ); ?></label>
					<input type="checkbox" name="pagseguro_use_other_card" id="pagseguro-use-other-card" value="yes"/>
					<input type="hidden" name="pagseguro_save_hash_card" id="pagseguro-save-hash-card" value="yes"/>
				</p>
				<?php
			else :
				if ( 'always_store' === $settings['save_card_info'] ) :
					?>
					<p id="pagseguro-save-card" class="form-now form-wide">
						<label for="pagseguro-save-hash-card" style="font-size: 12px;">
							<?php esc_html_e( 'Ao finalizar a compra, permito que a loja memorize esta forma de pagamento.', 'virtuaria-pagseguro' ); ?>
						</label>
						<input type="hidden" name="pagseguro_save_hash_card" id="pagseguro-save-hash-card" value="yes"/>
					</p>
					<?php
				else :
					?>
					<p id="pagseguro-save-card" class="form-now form-wide">
						<label for="pagseguro-save-hash-card"><?php esc_html_e( 'Salvar método de pagamento para compras futuras?', 'virtuaria-pagseguro' ); ?></label>
						<input type="checkbox" name="pagseguro_save_hash_card" id="pagseguro-save-hash-card" value="yes"/>
					</p>
					<?php
				endif;
			endif;
			?>
			<div class="clear"></div>
			<?php
		endif;
		?>
		<input type="hidden" name="pagseguro_encrypted_card" id="pagseguro_encrypted_card" />
	</div>
	<div id="pagseguro-banking-pix-form" class="pagseguro-method-form">
		<i id="pagseguro-icon-pix"></i>
		<div class="pix-desc">
			<?php
			echo '<span>' . esc_html( __( 'O pedido será confirmado apenas após a confirmação do pagamento.', 'virtuaria-pagseguro' ) ) . '</span>';
			echo '<span>' . esc_html(
				sprintf(
					/* translators: %s: pix validate */
					__( 'Pague com PIX. O código de pagamento tem validade de %s.', 'virtuaria-pagseguro' ),
					$pix_validate
				)
			) . '</span>';

			if ( $pix_discount && $pix_discount > 0 ) {
				if ( isset( WC()->cart ) && WC()->cart->get_shipping_total() > 0 ) {
					$cart_total -= WC()->cart->get_shipping_total();
				}
				$discount = $cart_total * $pix_discount;
				echo '<span>Total de desconto: <b style="color:green">R$ ' . esc_html( number_format( $discount, 2, ',', '.' ) ) . '</b>.</span>';
			}
			?>
		</div>
		<div class="clear"></div>
	</div>
	<div id="pagseguro-banking-ticket-form" class="pagseguro-method-form">
		<p>
			<i id="pagseguro-icon-ticket"></i>
			<?php esc_html_e( 'O pedido será confirmado apenas após a confirmação do pagamento.', 'virtuaria-pagseguro' ); ?>
		</p>
		<p><?php esc_html_e( '* Depois de clicar em "Realizar pagamento", você terá acesso ao boleto bancário, podendo imprimir e pagar em via internet ou rede bancária credenciada.', 'virtuaria-pagseguro' ); ?></p>
		<div class="clear"></div>
	</div>
	<?php wp_nonce_field( 'do_new_charge', 'new_charge_nonce' ); ?>
</fieldset>
