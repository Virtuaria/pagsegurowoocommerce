<?php
/**
 * Template main screen setting.
 *
 * @package Virtuaria/Payments/PagSeguro.
 */

defined( 'ABSPATH' ) || exit;

do_action( 'virtuaria_pagseguro_save_settings' );

if ( get_transient( 'virtuaria_pagseguro_main_setting_saved' ) ) {
	echo '<div id="message" class="updated inline"><p><strong>Suas configurações foram salvas.</strong></p></div>';
	delete_transient(
		'virtuaria_pagseguro_main_setting_saved'
	);
}

$options = get_option( 'woocommerce_virt_pagseguro_settings' );

if ( isset( $options['environment'] ) && 'sandbox' === $options['environment'] ) {
	$app_id     = 'a2c55b69-d66f-4bf0-80f9-21d504ebf559';
	$app_url    = 'pagseguro.virtuaria.com.br/auth/pagseguro-sandbox';
	$app_revoke = 'https://pagseguro.virtuaria.com.br/revoke/pagseguro-sandbox';
	$token      = isset( $options['token_sanbox'] ) ? $options['token_sanbox'] : '';
	$fee_setup  = '';
} else {
	$fee_setup = isset( $options['fee_setup'] ) ? $options['fee_setup'] : '';

	if ( 'd14' === $fee_setup ) {
		$app_id = 'f7aa07e1-5368-45cd-9372-67db6777b4b0';
	} elseif ( 'd30' === $fee_setup ) {
		$app_id = 'a59bb94a-2e78-43bc-a497-30447bdf1a3e';
	} else {
		$app_id = '7acbe665-76c3-4312-afd5-29c263e8fb93';
	}
	$app_url    = 'pagseguro.virtuaria.com.br/auth/pagseguro';
	$app_revoke = 'https://pagseguro.virtuaria.com.br/revoke/pagseguro';
	$token      = isset( $options['token_production'] ) ? $options['token_production'] : '';

	$options['environment'] = 'production';
}

if ( ! isset( $options['payment_form'] ) ) {
	$options['payment_form'] = 'unified';
}
?>
<h1 class="main-title">Virtuaria PagSeguro</h1>
<form action="" method="post" id="mainform" class="main-setting">
	<table class="form-table">
		<tbody>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="woocommerce_virt_pagseguro_payment_form">Modo de Funcionamento</label>
				</th>
				<td class="forminp">
					<fieldset>
						<legend class="screen-reader-text"><span>Modo de Funcionamento</span></legend>
						<select class="select " name="woocommerce_virt_pagseguro_payment_form" id="woocommerce_virt_pagseguro_payment_form">
							<option value="unified" <?php selected( 'unified', $options['payment_form'] ); ?>>Unificado</option>
							<option value="separated" <?php selected( 'separated', $options['payment_form'] ); ?>>Separado</option>
						</select>
						<p class="description">
							Define o modo de configuração e exibição dos métodos de pagamento disponibilizados pelo PagSeguro.
							<span href="#" class="read-more">Saiba mais</span>
							<span class="tip-desc" style="display: none;">
								<b>- Unificado:</b> Exibe unicamente o método de pagamento PagSeguro com configurações para Crédito, Pix e Boleto. Essa abordagem simplifica a experiência do usuário no checkout e painel, agrupando todas as opções de pagamento do PagSeguro.
								<br><br><b>- Separado:</b> Exibe três métodos de pagamento distintos, PagSeguro Crédito, PagSeguro Pix e PagSeguro Boleto. Cada um deles aparece como uma opção independente dentro da interface do painel e checkout do WooCommerce, permitindo que os clientes selecionem diretamente o método de pagamento de sua preferência. Essa abordagem ajuda nas integrações com outros sistemas (ERP, CRM, etc) e também na compatibilidade com plugins de desconto por método de pagamento.
							</span>
						</p>
					</fieldset>
				</td>
			</tr>
			<?php
			if ( 'production' === $options['environment'] ) :
				?>
				<tr valign="top">
					<th scope="row" class="titledesc">
						<label for="woocommerce_virt_pagseguro_fee_setup">Taxas </label>
					</th>
					<td class="forminp">
						<fieldset>
							<legend class="screen-reader-text"><span>Taxas</span></legend>
							<select class="select" name="woocommerce_virt_pagseguro_fee_setup" id="woocommerce_virt_pagseguro_fee_setup">
								<option <?php selected( 'd30', $options['fee_setup'] ); ?> value="d30">Especial Virtuaria 01: Crédito 3,79% (recebimento em 30 dias) | Pix 0,99% | Boleto R$ 2,99</option>
								<option <?php selected( 'd14', $options['fee_setup'] ); ?> value="d14">Especial Virtuaria 02: Crédito 4,39% (recebimento em 14 dias) | Pix 0,99% | Boleto R$ 2,99</option>
								<option <?php selected( 'default', $options['fee_setup'] ); ?> value="default">Padrão do PagSeguro</option>
								<option <?php selected( 'custom', $options['fee_setup'] ); ?> value="custom">Negociada PagSeguro (caso tenha negociado com o PagSeguro uma taxa personalizada)</option>
							</select>
							<p class="description">Define a taxa utilizada na integração com PagSeguro. O percentual especial pode ser redefinido a critério do PagSeguro.</p>
						</fieldset>
					</td>
				</tr>
				<?php
			endif;
			?>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="woocommerce_virt_pagseguro_environment">Ambiente </label>
				</th>
				<td class="forminp">
					<fieldset>
						<legend class="screen-reader-text"><span>Ambiente</span></legend>
						<select class="select " name="woocommerce_virt_pagseguro_environment" id="woocommerce_virt_pagseguro_environment">
							<option value="sandbox" <?php selected( 'sandbox', $options['environment'] ); ?>>Sandbox</option>
							<option value="production" <?php selected( 'production', $options['environment'] ); ?>>Produção</option>
						</select>
						<p class="description">Selecione Sandbox para testes ou Produção para vendas reais. O ambiente de sandbox é instável e comumente apresenta problemas. Consulte o item 12 da FAQ na <a href="https://wordpress.org/plugins/virtuaria-pagseguro/#faq" target="_blank">página do plugin</a> para mais informações.</p>
					</fieldset>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="woocommerce_virt_pagseguro_email">E-mail </label>
				</th>
				<td class="forminp">
					<fieldset>
						<legend class="screen-reader-text"><span>E-mail</span></legend>
						<input class="input-text regular-input " type="text" name="woocommerce_virt_pagseguro_email" id="woocommerce_virt_pagseguro_email" value="<?php echo isset( $options['email'] ) ? esc_attr( $options['email'] ) : ''; ?>" >
						<p class="description">Informe seu e-mail utilizado na conta do Pagseguro. Isto é necessário para a confirmação do pagamento.</p>
					</fieldset>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="woocommerce_virt_pagseguro_autorization">
						Autorização <span class="woocommerce-help-tip"></span>
					</label>
				</th>
				<td class="forminp forminp-auth">
					<?php
					$auth = '';
					if ( 'sandbox' === $options['environment'] ) {
						$auth = 'sandbox.';
					}

					$origin = str_replace( array( 'https://', 'http://' ), '', home_url() );

					$auth  = 'https://connect.' . $auth . 'pagseguro.uol.com.br/oauth2/authorize';
					$auth .= '?response_type=code&client_id=' . $app_id . '&redirect_uri=' . $app_url;
					$auth .= '&scope=payments.read+payments.create+payments.refund+accounts.read&state=' . $origin;
					if ( $fee_setup ) {
						$auth .= '--' . $fee_setup;
					}
					$auth .= '--' . str_replace( '@', 'aN', $options['email'] );

					if ( $token ) {
						$revoke_url = $app_revoke . '?state=' . $origin . ( $fee_setup ? '--' . $fee_setup : '' ) . '--' . str_replace( '@', 'aN', $options['email'] );
						echo '<span class="connected"><strong>Status: <span class="status">Conectado.</span></strong></span>';
						echo '<a href="' . esc_url( $revoke_url ) . '" class="auth button-primary">Desconectar com PagSeguro <img src="' . esc_url( VIRTUARIA_PAGSEGURO_URL ) . 'public/images/conectado.svg" alt="Desconectar" /></a>';
					} else {
						echo '<span class="disconnected"><strong>Status: <span class="status">Desconectado.</span></strong></span>';
						echo '<a href="' . esc_url( $auth ) . '" class="auth button-primary">Conectar com PagSeguro <img src="' . esc_url( VIRTUARIA_PAGSEGURO_URL ) . 'public/images/conectar.png" alt="Conectar" /></a>';
					}
					echo '<span class="expire-info">A conexão tem duração <strong>média de 1 ano</strong>. Após esse período, é necessário reconectar para atualizar as permissões junto ao PagSeguro. O plugin exibirá um alerta, caso ocorra algum problema recorrente com a conexão.</span>';
					?>
				</td>
			</tr>

			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="woocommerce_virt_pagseguro_process_mode">Modo de processamento </label>
				</th>
				<td class="forminp">
					<fieldset>
						<legend class="screen-reader-text"><span>Modo de processamento</span></legend>
						<select class="select " name="woocommerce_virt_pagseguro_process_mode" id="woocommerce_virt_pagseguro_process_mode">
							<option value="sync" <?php selected( 'sync', $options['process_mode'] ); ?>>Síncrono</option>
							<option value="async" <?php selected( 'async', $options['process_mode'] ); ?>>Assíncrono</option>
						</select>
						<p class="description">
							A mudança de status do pedido dispara uma série de ações, como envio de emails, redução do estoque, eventos em plugins, entre muitas outras. No modo assíncrono, o checkout não precisa esperar pela conclusão destas ações,  consequentemente fica mais rápido. A confirmação do pagamento via Cartão de Crédito ocorre da mesma forma, independente do modo escolhido. Apenas a mudança de status do pedido é afetada, pois passa a ocorrer via agendamento (cron) em até 5 minutos após a finalização da compra pelo cliente.
						</p>
					</fieldset>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="woocommerce_virt_pagseguro_invoice_prefix">Prefixo da transação </label>
				</th>
				<td class="forminp">
					<fieldset>
						<legend class="screen-reader-text"><span>Prefixo da transação</span></legend>
						<input class="input-text regular-input " type="text" name="woocommerce_virt_pagseguro_invoice_prefix" id="woocommerce_virt_pagseguro_invoice_prefix" value="<?php echo isset( $options['invoice_prefix'] ) ? esc_attr( $options['invoice_prefix'] ) : ''; ?>">
						<p class="description">
							Este prefixo é usado para definir o identificador do pedido. Caso precise utilizar a mesma conta PagSeguro em mais de uma loja virtual, será preciso definir um prefixo único para cada loja, pois o PagSeguro não permitirá pedidos com o mesmo identificador.
						</p>
					</fieldset>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="woocommerce_virt_pagseguro_payment_status">Status após confirmação </label>
				</th>
				<td class="forminp">
					<fieldset>
						<legend class="screen-reader-text"><span>Status após confirmação</span></legend>
						<select class="select" name="woocommerce_virt_pagseguro_payment_status" id="woocommerce_virt_pagseguro_payment_status">
							<?php
							foreach ( wc_get_order_statuses() as $key => $text ) {
								if ( ! in_array( $key, array( 'wc-pending', 'wc-cancelled', 'wc-refunded', 'wc-failed' ), true ) ) {
									$method = str_replace( 'wc-', '', $key );
									printf(
										'<option value="%s" %s>%s</option>',
										esc_attr( $method ),
										selected( $options['payment_status'], $method, false ),
										esc_attr( $text )
									);
								}
							}
							?>
						</select>
						<p class="description">
							Define o status que o pedido assumirá após a confirmação de pagamento. O status padrão é processando.
						</p>
					</fieldset>
				</td>
			</tr>
			<?php
			if ( isset( $options['payment_form'] )
				&& 'separated' !== $options['payment_form'] ) :
				?>
				<tr valign="top">
					<th scope="row" class="titledesc">
						<label for="woocommerce_virt_pagseguro_layout_checkout">Layout </label>
					</th>
					<td class="forminp">
						<fieldset>
							<legend class="screen-reader-text"><span>Layout</span></legend>
							<select class="select" name="woocommerce_virt_pagseguro_layout_checkout" id="woocommerce_virt_pagseguro_layout_checkout">
								<option value="lines" <?php selected( 'lines', $options['layout_checkout'] ); ?>>Linhas</option>
								<option value="tabs" <?php selected( 'tabs', $options['layout_checkout'] ); ?>>Abas</option>
							</select>
							<p class="description">
								Define o padrão visual utilizado na página de finalização das compras.
							</p>
						</fieldset>
					</td>
				</tr>
				<?php
			endif;
			?>
			<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="woocommerce_virt_pagseguro_debug">Log de depuração </label>
			</th>
			<td class="forminp">
				<fieldset>
					<legend class="screen-reader-text"><span>Log de depuração</span></legend>
					<label for="woocommerce_virt_pagseguro_debug">
					<input type="checkbox" name="woocommerce_virt_pagseguro_debug" id="woocommerce_virt_pagseguro_debug" value="yes" <?php checked( 'yes', $options['debug'] ); ?>> Habilitar registro de log</label><br>
					<p class="description">
						Registra eventos de comunição com a API e erros. Para visualizar clique <a href="https://modateste.virtuaria.net/wp-admin/admin.php?page=wc-status&amp;tab=logs&amp;source=virt_pagseguro">aqui</a>.
					</p>
				</fieldset>
			</td>
		</tr>
		</tbody>
	</table>

	<button name="save" class="button-primary woocommerce-save-button" type="submit" value="Salvar alterações">Salvar alterações</button>
	<?php wp_nonce_field( 'setup_virtuaria_module', 'setup_nonce' ); ?>
</form>
