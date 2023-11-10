<?php
/**
 * Transparent checkout.
 *
 * @package Virtuaria/Payments/Pagseguro
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit;

$settings    = get_option( 'woocommerce_virt_pagseguro_settings' );
$card_loaded = false;
if ( is_user_logged_in() && 'do_not_store' !== $settings['save_card_info'] ) {
	$pagseguro_card_info = get_user_meta( get_current_user_id(), '_pagseguro_credit_info_store_' . get_current_blog_id(), true );
	if ( isset( $pagseguro_card_info['token'] ) ) {
		$card_loaded = true;
	}
}

if ( ! function_exists( 'pagseguro_form_class' ) ) {
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
}
?>
<fieldset id="pagseguro-payment" data-cart_total="<?php echo esc_attr( number_format( $cart_total, 2, '.', '' ) ); ?>" class="<?php echo $card_loaded ? 'card-loaded' : ''; ?>">
	<div class="payment-methods">
		<!-- Cartão de Crédito -->
		<div class="payment-method">
			<label class="payment-option">
				<input id="credit-card" type="radio" name="payment_mode" value="credit" <?php echo isset( $_POST['payment_mode'] ) && 'credit' === $_POST['payment_mode'] ? 'checked' : ''; ?> />
				<span class="indicator"></span>
				Cartão de Crédito
			</label>
			<?php require_once 'credit-checkout.php'; ?>
		</div>

		<!-- PIX -->
		<div class="payment-method">
			<label class="payment-option">
				<input id="pix" type="radio" name="payment_mode" value="pix" <?php echo isset( $_POST['payment_mode'] ) && 'pix' === $_POST['payment_mode'] ? 'checked' : ''; ?>/>
				<span class="indicator"></span>
				<?php echo wp_kses_post( $pix_offer_text ); ?>
			</label>
			<?php require_once 'pix-checkout.php'; ?>
		</div>

		<!-- Boleto -->
		<div class="payment-method">
			<label class="payment-option">
				<input id="banking-ticket" type="radio" name="payment_mode" value="ticket" <?php echo isset( $_POST['payment_mode'] ) && 'ticket' === $_POST['payment_mode'] ? 'checked' : ''; ?>>
				<span class="indicator"></span>
				Boleto 
			</label>
			<?php require_once 'ticket-checkout.php'; ?>
		</div>
	</div>
	<?php wp_nonce_field( 'do_new_charge', 'new_charge_nonce' ); ?>
</fieldset>
