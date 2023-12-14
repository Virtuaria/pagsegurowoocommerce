<?php
/**
 * Template form ticket.
 *
 * @package Virtuaria/Payments/PagSeguro.
 */

defined( 'ABSPATH' ) || exit;
?>
<div id="virt-pagseguro-banking-ticket-form" class="virt-pagseguro-method-form payment-details">
	<div class="ticket-text">
		<p>
			<?php esc_html_e( 'O pedido será confirmado apenas após a confirmação do pagamento.', 'virtuaria-pagseguro' ); ?>
		</p>
		<p><?php esc_html_e( '* Depois de clicar em "Realizar pagamento", você terá acesso ao boleto bancário, podendo imprimir e pagar via internet banking ou rede bancária credenciada.', 'virtuaria-pagseguro' ); ?></p>
	</div>
	<i id="pagseguro-icon-ticket"></i>
	<div class="clear"></div>
</div>
