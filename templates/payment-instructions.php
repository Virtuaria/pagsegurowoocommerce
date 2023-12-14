<?php
/**
 * Template PIX payment instructions.
 *
 * @package virtuaria.
 */

defined( 'ABSPATH' ) || exit;

$is_android = ( isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && 'com.virtuaria.supertem' === $_SERVER['HTTP_X_REQUESTED_WITH'] );
?>
<div class="pix-payment">
	<h3 class="validate-warning" style="color: green;">Pague com PIX. O código de pagamento tem validade de <?php echo esc_html( $validate ); ?>.</h3>

	<h2 class="on-hold-payment" style="display: none;">Aguardando pagamento <img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ); ?>../public/images/loading.png" alt="Spinner" /></h2>
	<strong style="display: block; margin-top: 10px;">
		Escaneie este código para pagar
	</strong>
	<ol class="scan-instructions">
		<li>Acesse seu internet Banking ou app de pagamentos</li>
		<li>Escolha pagar via PIX</li>
		<li>Use o seguinte QR Code:</li>
	</ol>
	<img style="max-width: 150px" src="<?php echo esc_html( $qr_code_png ); ?>" alt="Qr code" />
	<div class="code-area">
		<span class="code-text">
			Ou cole o seguinte código QR para fazer o pagamento ( escolha a opção Pix Copia e Cola no seu Internet Banking ).
		</span>
		<?php
		if ( $is_android ) :
			?>
			<div class="pix">
				<?php echo esc_html( $qr_code ); ?>
			</div>
			<?php
		else :
			?>
			<div id="pix-code">
				<span class="pix"><?php echo esc_html( $qr_code ); ?></span>
			</div>
			<button class="copy-pix">Copiar código</button>
			<div class="pix-copied" style="color:green;"></div>
			<?php
		endif;
		?>
	</div>
</div>
<style>
	.code-area {
		margin: 20px 0;
	}
</style>
<?php
if ( $order ) {
	?>
	<style>
		.copy-pix,
		#pix-code .copy {
			display: none;
		}
		.validate-warning {
			font-size: 18px;
		}
	</style>
	<?php
} else {
	?>
	<style>
		.validate-warning {
			font-size: 16px;
		}
		.code-area > .pix {
			word-break: break-all;
			margin-top: 40px;
		}
		.code-area > .pix,
		#pix-code {
			display: block;
			border: 1px solid;
			color: green;
			padding: 10px;
			margin-top: 10px;
		}
		#pix-code .copy,
		#pix-code .pix {
			display: inline-block;
			vertical-align: middle;
		}
		#pix-code .pix {
			word-break: break-all;
			display: block;
		}
		#pix-code .copy {
			font-size: 20px;
			font-weight: bold;
		}
		.copy-pix:hover {
			background-image: none;
			color: #fff;
			filter: brightness(1.3);
			background-color: green;
		}
		.copy-pix {
			background-color: green;
			background-image: none;
			text-shadow: none;
			font-size: 20px;
			color: #fff;
			font-weight: bold;
			padding: 10px 30px;
			margin-top: 15px;
		}
		.code-text {
			display: block;
			font-weight: bold;
		}
		.scan-instructions {
			margin-bottom: 10px;
			margin-left: 30px;
		}

		@media only screen and (max-width: 479px) {
			#pix-code .copy {
				display: none;
			}
			#pix-code .pix {
				max-width: initial;
				margin-right: 0px;
			}
		}
	</style>
	<?php
}

if ( $is_android ) {
	?>
	<style>
		.copy-pix,
		.pix-copied,
		#pix-code .copy {
			display: none;
		}
	</style>
	<?php
} else {
	?>
	<script>
		jQuery(document).ready(function($) {
		$('.copy-pix').on('click', function(e) {
			e.preventDefault();
			navigator.clipboard.writeText($('#pix-code .pix').html());
			$('.pix-copied').html( 'Código copiado!' );
		});
	});
	</script>
	<?php
}
