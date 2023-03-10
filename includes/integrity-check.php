<?php
/**
 * Check plugin integrity.
 *
 * @package Virtuaria/Integrations/Pagseguro.
 */

defined( 'ABSPATH' ) || exit;

if ( ! is_plugin_active( 'virtuaria-pagseguro/virtuaria-pagseguro.php' )
	|| ( 'Virtuaria - Pagseguro para Woocommerce' !== $plugin_data['Name']
	&& 'Virtuaria - Pagseguro Crédito, Pix e Boleto' !== $plugin_data['Name'] )
	|| '<a href="https://virtuaria.com.br/">Virtuaria</a>' !== $plugin_data['Author'] ) {
	wp_die( 'Erro: Plugin corrompido. Favor baixar novamente o código e reinstalar o plugin.' );
}
