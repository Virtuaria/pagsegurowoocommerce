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
	<ul id="virt-pagseguro-payment-methods">
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
