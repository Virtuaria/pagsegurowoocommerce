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
	<ul id="pagseguro-payment-methods">
		<?php
		if ( $methods_enabled['credit'] ) :
			?>
			<li class="active">
				<label>
					<input id="credit-card" type="radio" name="payment_mode" value="credit" <?php echo isset( $_POST['payment_mode'] ) && 'credit' === $_POST['payment_mode'] ? 'checked' : ''; ?> /> <?php esc_html_e( 'Cartão de Crédito', 'virtuaria-pagseguro' ); ?>
				</label>
			</li>	
				<?php
		endif;
		if ( $methods_enabled['pix'] ) :
			?>
			<li>
				<label>
					<input id="pix" type="radio" name="payment_mode" value="pix" <?php echo isset( $_POST['payment_mode'] ) && 'pix' === $_POST['payment_mode'] ? 'checked' : ''; ?>/> <?php esc_html_e( 'PIX', 'virtuaria-pagseguro' ); ?>
				</label>
			</li>
			<?php
		endif;
		if ( $methods_enabled['ticket'] ) :
			?>
			<li>
				<label>
					<input id="banking-ticket" type="radio" name="payment_mode" value="ticket" <?php echo isset( $_POST['payment_mode'] ) && 'ticket' === $_POST['payment_mode'] ? 'checked' : ''; ?>/> <?php esc_html_e( 'Boleto', 'virtuaria-pagseguro' ); ?>
				</label>
			</li>
			<?php
		endif;
		?>
	</ul>
	<div class="clear"></div>	
	<?php
	require_once 'credit-checkout.php';
	require_once 'pix-checkout.php';
	require_once 'ticket-checkout.php';
	?>
	<?php wp_nonce_field( 'do_new_charge', 'new_charge_nonce' ); ?>
</fieldset>
