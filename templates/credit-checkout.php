<?php
/**
 * Template form credit.
 *
 * @package Virtuaria/Payments/PagSeguro.
 */

defined( 'ABSPATH' ) || exit;
?>
<div id="pagseguro-credit-card-form" class="pagseguro-method-form payment-details">
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
