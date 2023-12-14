<?php
/**
 * Transparent checkout.
 *
 * @package Virtuaria/Payments/Pagseguro
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit;

?>
<fieldset id="pagseguro-payment-<?php echo esc_attr( $instance->id ); ?>" data-cart_total="<?php echo esc_attr( number_format( $cart_total, 2, '.', '' ) ); ?>" class="<?php echo $card_loaded ? 'card-loaded' : ''; ?>">
	<?php
	switch ( $instance->id ) {
		case 'virt_pagseguro_credit':
			require_once 'credit-checkout.php';
			break;
		case 'virt_pagseguro_pix':
			require_once 'pix-checkout.php';
			break;
		case 'virt_pagseguro_ticket':
			require_once 'ticket-checkout.php';
			break;
	}
	?>
	<?php wp_nonce_field( 'do_new_charge', "{$instance->id}_nonce" ); ?>
</fieldset>
