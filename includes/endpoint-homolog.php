<?php
/**
 * Generate file homolog.
 *
 * @package Virtuaria/Payments/Pagseguro.
 */

defined( 'ABSPATH' ) || exit;
$token = get_option( 'woocommerce_virt_pagseguro_settings' )['token'];

if ( isset( $_POST['encriptation_card'] ) && ! empty( $_POST['encriptation_card'] ) ) {
	$dir      = plugin_dir_path( __FILE__ );
	$filename = 'arquivo-homologao.txt';
	$output   = fopen( $dir . $filename, 'w' );
	$card     = sanitize_text_field( wp_unslash( $_POST['encriptation_card'] ) );
	$card2    = sanitize_text_field( wp_unslash( $_POST['encriptation_card2'] ) );

	$endpoint = 'https://sandbox.api.pagseguro.com/';

	$count = intval( get_option( 'homolog_number', 0 ) ) + 1;

	$data = array(
		'headers' => array(
			'Authorization' => $token,
			'Content-Type'  => 'application/json',
		),
		'body'    => array(
			'reference_id'      => 'teste0' . $count,
			'customer'          => array(
				'name'   => 'João da Silva',
				'email'  => 'joaosilva@virtuaria.com.br',
				'tax_id' => '95749391035',
				'phone'  => array(
					'country' => '55',
					'area'    => '011',
					'number'  => '999999999',
					'type'    => 'CELLPHONE',
				),
			),
			'items'             => array(
				array(
					'name'        => 'teste',
					'quantity'    => 1,
					'unit_amount' => 100000,
				),
			),
			'shipping'          => array(
				'address' => array(
					'street'      => 'Rua A Conjunto Z',
					'number'      => '12',
					'complement'  => 'Casa',
					'locality'    => 'Centro',
					'city'        => 'Sao Paulo',
					'region'      => 'SP',
					'region_code' => 'SP',
					'country'     => 'BRA',
					'postal_code' => '17560246',
				),
			),
			'notification_urls' => array( home_url( 'wc-api/WC_Virtuaria_PagSeguro_Gateway' ) ),
		),
		'timeout' => 12,
	);

	fwrite( $output, ">>>>BOLETO<<<<\r\n" );
	fwrite( $output, "\r\n" );
	fwrite( $output, "REQUEST\r\n" );
	$data['body']['charges'][] = array(
		'reference_id'      => 'teste0' . $count,
		'description'       => substr( get_bloginfo( 'name' ), 0, 63 ),
		'amount'            => array(
			'value'    => 100000,
			'currency' => 'BRL',
		),
		'notification_urls' => array( home_url( 'wc-api/WC_Virtuaria_PagSeguro_Gateway' ) ),
		'payment_method'    => array(
			'type'   => 'BOLETO',
			'boleto' => array(
				'due_date' => wp_date( 'Y-m-d', strtotime( '+1 day' ) ),
				'holder'   => array(
					'name'    => 'Joao da Silva',
					'tax_id'  => '95749391035',
					'email'   => 'joaosilva@virtuaria.com.br',
					'address' => array(
						'street'      => 'Rua A Conjunto Z',
						'number'      => '12',
						'complement'  => 'Casa',
						'locality'    => 'Centro',
						'city'        => 'Sao Paulo',
						'region'      => 'SP',
						'region_code' => 'SP',
						'country'     => 'BR',
						'postal_code' => '17560246',
					),
				),
			),
		),
	);

	fwrite( $output, wp_json_encode( $data ) . "\r\n" );
	fwrite( $output, "\r\n" );
	fwrite( $output, "RESPONSE\r\n" );

	$data['body'] = wp_json_encode( $data['body'] );
	$response     = wp_remote_post(
		$endpoint . 'orders',
		$data
	);

	fwrite( $output, wp_json_encode( $response ) . "\r\n" );
	fwrite( $output, "\r\n" );

	update_option( 'homolog_number', ++$count );

	fwrite( $output, ">>>>CRÉDITO<<<<\r\n" );
	fwrite( $output, "\r\n" );
	fwrite( $output, "REQUEST\r\n" );

	$data['body'] = json_decode( $data['body'], true );
	$count++;
	$data['body']['charges'][0]['reference_id']                      = 'teste0' . $count;
	$data['body']['reference_id']                                    = 'teste0' . $count;
	$data['body']['charges'][0]['payment_method']['installments']    = 1;
	$data['body']['charges'][0]['payment_method']['capture']         = true;
	$data['body']['charges'][0]['payment_method']['soft_descriptor'] = 'VIRTUARIA';

	unset( $data['body']['charges'][0]['payment_method']['boleto'] );
	$data['body']['charges'][0]['payment_method']['type'] = 'CREDIT_CARD';
	$data['body']['charges'][0]['payment_method']['card'] = array(
		'encrypted' => $card,
	);

	fwrite( $output, wp_json_encode( $data ) . "\r\n" );
	fwrite( $output, "\r\n" );
	fwrite( $output, "RESPONSE\r\n" );

	$data['body'] = wp_json_encode( $data['body'] );

	$response = wp_remote_post(
		$endpoint . 'orders',
		$data
	);

	fwrite( $output, wp_json_encode( $response ) . "\r\n" );
	if ( ! is_wp_error( $response ) ) {
		$response = json_decode( wp_remote_retrieve_body( $response ), true );
	}

	update_option( 'homolog_number', ++$count );

	fwrite( $output, "\r\n" );
	fwrite( $output, ">>>>REEMBOLSO TOTAL<<<<\r\n" );
	fwrite( $output, "\r\n" );
	fwrite( $output, "REQUEST\r\n" );

	$refund = array(
		'headers' => array(
			'Authorization' => 'Bearer ' . $token,
			'Content-Type'  => 'application/json',
			'x-api-version' => '4.0',
		),
		'body'    => array(
			'amount' => array(
				'value' => '100000',
			),
		),
		'timeout' => 12,
	);

	fwrite( $output, wp_json_encode( $refund ) . "\r\n" );

	$refund['body'] = wp_json_encode( $refund['body'] );
	$request        = wp_remote_post(
		'https://sandbox.api.pagseguro.com/charges/' . $response['charges'][0]['id'] . '/cancel',
		$refund
	);

	fwrite( $output, "RESPONSE\r\n" );
	fwrite( $output, wp_json_encode( $request ) . "\r\n" );

	// New order to new refund partial.
	$data['body'] = json_decode( $data['body'], true );

	$data['body']['reference_id']                         = 'teste0' . $count;
	$data['body']['charges'][0]['reference_id']           = 'teste0' . $count;
	$data['body']['charges'][0]['payment_method']['card'] = array(
		'encrypted' => $card2,
	);

	$data['body'] = wp_json_encode( $data['body'] );

	$response = wp_remote_post(
		$endpoint . 'orders',
		$data
	);

	fwrite( $output, "\r\n" );
	fwrite( $output, "\r\n" );
	fwrite( $output, ">>>>REEMBOLSO PARCIAL<<<<\r\n" );
	fwrite( $output, "\r\n" );
	fwrite( $output, "REQUEST\r\n" );

	$refund['body'] = array(
		'amount' => array(
			'value' => '5000',
		),
	);
	fwrite( $output, wp_json_encode( $refund ) . "\r\n" );

	$refund['body'] = wp_json_encode( $refund['body'] );

	if ( ! is_wp_error( $response ) ) {
		$response = json_decode( wp_remote_retrieve_body( $response ), true );
	}
	$request = wp_remote_post(
		'https://sandbox.api.pagseguro.com/charges/' . $response['charges'][0]['id'] . '/cancel',
		$refund
	);

	fwrite( $output, "RESPONSE\r\n" );
	fwrite( $output, wp_json_encode( $request ) . "\r\n" );
	fwrite( $output, "\r\n" );
	fwrite( $output, ">>>>PIX<<<<\r\n" );
	fwrite( $output, "\r\n" );
	fwrite( $output, "REQUEST\r\n" );

	$data['body'] = json_decode( $data['body'], true );
	unset( $data['body']['charges'] );

	update_option( 'homolog_number', ++$count );
	$expiration = new DateTime(
		wp_date(
			'Y-m-d H:i:s',
			strtotime( '+1800 seconds' )
		),
		new DateTimeZone( 'America/Sao_Paulo' )
	);

	$data['body']['qr_codes'][] = array(
		'amount'          => array(
			'value' => '100000',
		),
		'expiration_date' => $expiration->format( 'c' ),
	);

	$data['body']['reference_id'] = 'teste0' . $count;

	fwrite( $output, wp_json_encode( $data ) . "\r\n" );
	fwrite( $output, "\r\n" );
	fwrite( $output, "RESPONSE\r\n" );

	$data['body'] = wp_json_encode( $data['body'] );

	$response = wp_remote_post(
		$endpoint . 'orders',
		$data
	);

	fwrite( $output, wp_json_encode( $response ) . "\r\n" );
	if ( ! is_wp_error( $response ) ) {
		$response = json_decode( wp_remote_retrieve_body( $response ), true );
	}
	fwrite( $output, "\r\n" );

	ob_get_clean();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Cache-Control: no-store, no-cache' );
	header( 'Content-Disposition: attachment; filename=' . $filename );
	readfile( $dir . $filename );
	unlink( $dir . $filename );
	exit;
} else {
	if ( ! current_user_can( 'remove_users' ) ) {
		echo '<span style="display:block;text-align:center;margin-top:40px">';
		echo 'Sem permissão para acessar esta página. Por favor, faça <a href="' . esc_url( wp_login_url( home_url( 'virtuaria-pagseguro' ) ) ) . '">login</a> e tente novamente.';
		echo '</span>';
		return;
	}
	$request = wp_remote_get(
		'https://sandbox.api.pagseguro.com/public-keys/card',
		array(
			'headers' => array(
				'Authorization' => $token,
				'Content-Type'  => 'application/json',
			),
		)
	);

	if ( is_wp_error( $request ) || 200 !== wp_remote_retrieve_response_code( $request ) ) {
		echo '<span style="display:block;text-align:center;margin-top:40px"> Token inválido, por favor informe o token da conta pagseguro para seguir com a geração do arquivo de homologação</span>';
		return;
	}

	$pub_key = json_decode( wp_remote_retrieve_body( $request ) )->public_key;
	?>
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/purecss@3.0.0/build/base-min.css" />
	<h1 style="margin-top: 30px">Homologação PagSeguro</h1>
	<h4 style="margin-top: 20px">Plugin Virtuaria PagSeguro Woocommerce</h4>
	<p style="margin-top: 20px">
		Para finalizar a integração com o PagSeguro, é necessário passar por um processo de homologação. Durante a homologação, a equipe do PagSeguro verificará se a integração está funcionando da melhor forma possível, evitando assim problemas futuros. Caso os requisitos mínimos sejam cumpridos, você receberá as credenciais para uso da API em produção.
	</p>
	<form action="" method="post" style="margin: 60px auto;max-width: 240px;text-align:center;border: 5px double;padding:40px;background: #c6e3c6;">
		<strong style="font-size: 19px;line-height:24px">Gere seu arquivo de homologação</strong>
		<input type="hidden" name="encriptation_card" id="card"/>
		<input type="hidden" name="encriptation_card2" id="card2"/>
		<input type="submit" value="Gerar arquivo" style="margin-top: 20px;padding:8px 20px;cursor:pointer;" id="btn-main"/>
	</form>
	<script type='text/javascript' src='https://assets.pagseguro.com.br/checkout-sdk-js/rc/dist/browser/pagseguro.min.js?ver=1.1.1' id='pagseguro-sdk-js'></script>
	<script>
		var card = PagSeguro.encryptCard({
			publicKey: '<?php echo esc_html( $pub_key ); ?>',
			holder: 'Homologação crédito',
			number: '4111111111111111',
			expMonth: '12',
			expYear: '2026',
			securityCode: '123'
		});

		var card2 = PagSeguro.encryptCard({
			publicKey: '<?php echo esc_html( $pub_key ); ?>',
			holder: 'Sou crédito',
			number: '4111111111111111',
			expMonth: '11',
			expYear: '2027',
			securityCode: '233'
		});

		document.getElementById( 'card' ).value  = card.encryptedCard;
		document.getElementById( 'card2' ).value = card2.encryptedCard;

		document.getElementById( 'btn-main' ).addEventListener( 'click', disableBtn );

		function disableBtn() {
			let elem = document.getElementById( 'btn-main' );
			elem.form.submit();
			elem.disabled = true;
			this.value='Gerando...';
			setTimeout( function() {
				elem.disabled = false;
				elem.value='Gerar arquivo';
			}, 9000 );
		}
	</script>
	<style>
		body {
			padding: 20px 40px;
			background: #ABC8AB;
			line-height: 25px;
		}
	</style>
	<h2>Passos da Homologação:</h2>
	<ol>
		<li>Solicitar homologação da integração via <a target="_blank" href="https://app.pipefy.com/public/form/2e56YZLK"> formulário do pipefy</a>;</li>
		<li>Preencher formulário conforme o seguinte vídeo: <a target="_blank" href="https://youtu.be/L0iqvf1LL7g">https://youtu.be/L0iqvf1LL7g</a>;</li>
		<li>Anexar arquivo gerado nesta página;</li>
		<li>Aguarda contato do pagseguro sobre a homologação.</li>
	</ol>

	<p style="margin-top: 40px;">
		<strong>Atenção:</strong> Os seguintes problemas podem ocorrer devido a instabilidade no Sandbox do PagSeguro. Nestes casos, aguarde um pouco e tente novamente. Caso o problema continue, entre em contato com o suporte do PagSeguro via email ou formulário (<a target="_blank" href="https://app.pipefy.com/public/form/sBlh9Nq6">pipefy</a>) de contato, reportando "Problemas com a Sandbox".
	</p>
	<ul>
		<li>Internal Server Error;</li>
		<li>Transaction is not found;</li>
		<li>Operation timed out;</li>
		<li>Bad Gateway;</li>
		<li>External service error.</li>
	</ul>

	<a style="margin: 20px auto;display:table;" target="_blank" href="https://wordpress.org/plugins/virtuaria-pagseguro">
		Plugin Virtuaria PagSeguro Woocommerce
	</a>

	<a style="font-weight: bold;display:table;margin:0 auto;" target="_blank" href="https://virtuaria.com.br">
		Tecnologia Virtuaria
	</a>
	<?php
}
