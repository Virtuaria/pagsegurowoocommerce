<?php
/**
 * Template form pix.
 *
 * @package Virtuaria/Payments/PagSeguro.
 */

defined( 'ABSPATH' ) || exit;
?>
<div id="virt-pagseguro-banking-pix-form" class="virt-pagseguro-method-form payment-details">
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
			$shipping = 0;
			if ( isset( WC()->cart ) && WC()->cart->get_shipping_total() > 0 ) {
				$shipping = WC()->cart->get_shipping_total();
			}
			$discount_reduce = 0;
			$discount        = ( $cart_total - $shipping );
			foreach ( WC()->cart->get_cart() as $item ) {
				$product = wc_get_product( $item['product_id'] );
				if ( $product && apply_filters( 'virtuaria_pagseguro_disable_discount', false, $product ) ) {
					$discount_reduce += $product->get_price() * $item['quantity'];
				}
			}
			$discount -= $discount_reduce;
			$discount  = $discount * $pix_discount;
			if ( $discount > 0 ) {
				echo '<span class="discount">Desconto: <b style="color:green;">R$ ' . esc_html( number_format( $discount, 2, ',', '.' ) ) . '</b></span>';
				echo '<span class="total">Novo total: <b style="color:green">R$ ' . esc_html( number_format( $cart_total - $discount, 2, ',', '.' ) ) . '</b></span>';
			}
		}

		do_action( 'after_virtuaria_pix_validate_text', WC()->cart );
		?>
	</div>
	<i id="pagseguro-icon-pix"></i>
	<div class="clear"></div>
</div>
