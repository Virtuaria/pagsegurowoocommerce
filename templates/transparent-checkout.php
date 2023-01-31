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
	$pagseguro_card_info = get_user_meta( get_current_user_id(), '_pagseguro_credit_info', true );
	if ( isset( $pagseguro_card_info['token'] ) ) {
		$card_loaded = true;
	}
}
?>
<span class="pagseguro-info">
	Na área "Detalhes de Faturamento", recomendamos inserir os dados do titular do cartão. Caso a compra seja para outra pessoa, escolha "Entregar para um endereço diferente".
</span>
<fieldset id="pagseguro-payment" data-cart_total="<?php echo esc_attr( number_format( $cart_total, 2, '.', '' ) ); ?>">
	<ul id="pagseguro-payment-methods">
		<li class="active">
			<label>
				<input id="credit-card" type="radio" name="payment_mode" value="credit" <?php echo 'ticket' !== $_POST['payment_mode'] ? 'checked' : ''; ?> /> <?php esc_html_e( 'Cartão de Crédito', 'virtuaria-pagseguro' ); ?>
			</label>
		</li>
		<li>
			<label>
				<input id="banking-ticket" type="radio" name="payment_mode" value="ticket" <?php echo 'ticket' === $_POST['payment_mode'] ? 'checked' : ''; ?>/> <?php esc_html_e( 'Boleto', 'virtuaria-pagseguro' ); ?>
			</label>
		</li>
	</ul>
	<div class="clear"></div>

	<?php $class_card_loaded = $card_loaded ? 'card-loaded' : ''; ?>
	<div id="pagseguro-credit-card-form" class="pagseguro-method-form">
		<p id="pagseguro-card-holder-name-field" class="form-row form-row-first <?php echo esc_attr( $class_card_loaded ); ?>">
			<label for="pagseguro-card-holder-name"><?php esc_html_e( 'Titular', 'virtuaria-pagseguro' ); ?> <small>(<?php esc_html_e( 'como no cartão', 'virtuaria-pagseguro' ); ?>)</small> <span class="required">*</span></label>
			<input id="pagseguro-card-holder-name" name="pagseguro_holder_name" class="input-text" type="text" autocomplete="off" style="font-size: 1.5em; padding: 8px;" value="<?php echo isset( $_POST['pagseguro_holder_name'] ) ? esc_html( $_POST['pagseguro_holder_name'] ) : ''; ?>"/>
		</p>
		<p id="pagseguro-card-number-field" class="form-row form-row-last <?php echo esc_attr( $class_card_loaded ); ?>">
			<label for="pagseguro-card-number"><?php esc_html_e( 'Número do cartão', 'virtuaria-pagseguro' ); ?> <span class="required">*</span></label>
			<input id="pagseguro-card-number" name="pagseguro_card_number" maxlength="16" class="input-text wc-credit-card-form-card-number" type="tel" maxlength="20" autocomplete="off" placeholder="&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;" style="font-size: 1.5em; padding: 8px;"  value="<?php echo isset( $_POST['pagseguro_card_number'] ) ? esc_html( $_POST['pagseguro_card_number'] ) : ''; ?>"/>
		</p>
		<div class="clear"></div>
		<p id="pagseguro-card-expiry-field" class="form-row form-row-first <?php echo esc_attr( $class_card_loaded ); ?>">
			<label for="pagseguro-card-expiry"><?php esc_html_e( 'Validade (MM/AAAA)', 'virtuaria-pagseguro' ); ?> <span class="required">*</span></label>
			<input id="pagseguro-card-expiry" name="pagseguro_card_validate" class="input-text wc-credit-card-form-card-expiry" type="tel" autocomplete="off" placeholder="<?php esc_html_e( 'MM / AAAA', 'virtuaria-pagseguro' ); ?>" style="font-size: 1.5em; padding: 8px;"  value="<?php echo isset( $_POST['pagseguro_card_validate'] ) ? esc_html( $_POST['pagseguro_card_validate'] ) : ''; ?>"/>
		</p>
		<p id="pagseguro-card-cvc-field" class="form-row form-row-last <?php echo esc_attr( $class_card_loaded ); ?>">
			<label for="pagseguro-card-cvc"><?php esc_html_e( 'Código de segurança', 'virtuaria-pagseguro' ); ?> <span class="required">*</span></label>
			<input id="pagseguro-card-cvc" name="pagseguro_card_cvc" class="input-text wc-credit-card-form-card-cvc" type="tel" autocomplete="off" placeholder="<?php esc_html_e( 'CVV', 'virtuaria-pagseguro' ); ?>" style="font-size: 1.5em; padding: 8px;"  value="<?php echo isset( $_POST['pagseguro_card_cvc'] ) ? esc_html( $_POST['pagseguro_card_cvc'] ) : ''; ?>"/>
		</p>
		<div class="clear"></div>
		<p id="pagseguro-card-installments-field" class="form-row form-row-first">
			<label for="pagseguro-card-installments"><?php esc_html_e( 'Parcelas', 'virtuaria-pagseguro' ); ?> <small>(<?php esc_html_e( 'mínimo de R$ 5,00', 'virtuaria-pagseguro' ); ?>)</small> <span class="required">*</span></label>
			<select id="pagseguro-card-installments" name="pagseguro_installments" style="font-size: 1.5em; padding: 4px; width: 100%;">
				<?php
				foreach ( $installments as $index => $installment ) {
					if ( 0 !== $index && $installment < 5 ) {
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
					} else {
						printf(
							'<option value="%d">%dx de %s %s</option>',
							esc_attr( $aux ),
							esc_attr( $aux ),
							wp_kses_post( wc_price( $installment / $aux ) ),
							$has_tax ? '(' . wp_kses_post( wc_price( $installment ) ) . ')' : ' sem juros'
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
								__( '<span class="card-brand"><i class="fa fa-credit-card-alt" aria-hidden="true"></i>%1$s</span><span class="number">**** **** **** %2$s</span><span class="holder">%3$s</span>', 'virtuaria-pagseguro' ),
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
		<div class="clear"></div>
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