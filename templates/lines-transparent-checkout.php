<?php
/**
 * Transparent checkout.
 *
 * @package Virtuaria/Payments/Pagseguro
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit;

?>
<fieldset id="virt-pagseguro-payment" data-cart_total="<?php echo esc_attr( number_format( $cart_total, 2, '.', '' ) ); ?>" class="<?php echo $card_loaded ? 'card-loaded' : ''; ?>">
	<div class="payment-methods">
		<?php
		if ( $methods_enabled['credit'] ) :
			?>
			<!-- Cartão de Crédito -->
			<div class="payment-method">
				<label class="payment-option">
					<input id="credit-card" type="radio" name="payment_mode" value="credit" <?php echo isset( $_POST['payment_mode'] ) && 'credit' === $_POST['payment_mode'] ? 'checked' : ''; ?> />
					<span class="indicator"></span>
					Cartão de Crédito
				</label>
				<?php require_once 'credit-checkout.php'; ?>
			</div>
			<?php
		endif;
		?>

		<?php
		if ( $methods_enabled['pix'] ) :
			?>
			<!-- PIX -->
			<div class="payment-method">
				<label class="payment-option">
					<input id="pix" type="radio" name="payment_mode" value="pix" <?php echo isset( $_POST['payment_mode'] ) && 'pix' === $_POST['payment_mode'] ? 'checked' : ''; ?>/>
					<span class="indicator"></span>
					<?php echo wp_kses_post( $pix_offer_text ); ?>
				</label>
				<?php require_once 'pix-checkout.php'; ?>
			</div>
			<?php
		endif;
		?>

		<?php
		if ( $methods_enabled['ticket'] ) :
			?>
			<!-- Boleto -->
			<div class="payment-method">
				<label class="payment-option">
					<input id="banking-ticket" type="radio" name="payment_mode" value="ticket" <?php echo isset( $_POST['payment_mode'] ) && 'ticket' === $_POST['payment_mode'] ? 'checked' : ''; ?>>
					<span class="indicator"></span>
					Boleto 
				</label>
				<?php require_once 'ticket-checkout.php'; ?>
			</div>
			<?php
		endif;
		?>
	</div>
	<?php wp_nonce_field( 'do_new_charge', 'new_charge_nonce' ); ?>
</fieldset>
