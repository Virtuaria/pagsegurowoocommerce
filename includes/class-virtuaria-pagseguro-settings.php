<?php
/**
 * Handle unified settings.
 *
 * @package Virtuaria/Payments/PagSeguro.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class definition.
 */
class Virtuaria_PagSeguro_Settings {
	/**
	 * Initialize functions.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_submenu_pagseguro' ) );
		add_action( 'in_admin_footer', array( $this, 'display_review_info' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_checkout_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'add_public_styles_scripts' ), 20 );
		add_action( 'init', array( $this, 'save_main_settings' ) );
		add_action( 'admin_init', array( $this, 'redirect_old_save_token' ), 9 );
		add_action( 'admin_init', array( $this, 'save_store_token' ) );
		add_action( 'admin_notices', array( $this, 'virtuaria_pagseguro_not_authorized' ) );
		add_action( 'admin_init', array( $this, 'fee_setup_update' ), 20 );
	}

	/**
	 * Add submenu pagseguro.
	 */
	public function add_submenu_pagseguro() {
		add_menu_page(
			'Virtuaria PagSeguro',
			'Virtuaria PagSeguro',
			'remove_users',
			'virtuaria_pagseguro',
			array( $this, 'main_setting_screen' ),
			plugin_dir_url( __FILE__ ) . '../admin/images/virtuaria.png'
		);

		add_submenu_page(
			'virtuaria_pagseguro',
			'Integração',
			'Integração',
			'remove_users',
			'virtuaria_pagseguro'
		);

		$options = get_option( 'woocommerce_virt_pagseguro_settings' );
		if ( isset( $options['payment_form'] ) && 'separated' === $options['payment_form'] ) {
			add_submenu_page(
				'virtuaria_pagseguro',
				'Crédito',
				'Crédito',
				'remove_users',
				admin_url( 'admin.php?page=wc-settings&tab=checkout&section=virt_pagseguro_credit' )
			);
			add_submenu_page(
				'virtuaria_pagseguro',
				'Pix',
				'Pix',
				'remove_users',
				admin_url( 'admin.php?page=wc-settings&tab=checkout&section=virt_pagseguro_pix' )
			);
			add_submenu_page(
				'virtuaria_pagseguro',
				'Boleto',
				'Boleto',
				'remove_users',
				admin_url( 'admin.php?page=wc-settings&tab=checkout&section=virt_pagseguro_ticket' )
			);
		} else {
			add_submenu_page(
				'virtuaria_pagseguro',
				'Crédito, Pix e Boleto',
				'Crédito, Pix e Boleto',
				'remove_users',
				admin_url( 'admin.php?page=wc-settings&tab=checkout&section=virt_pagseguro' )
			);
		}
	}

	/**
	 * Template main screen.
	 */
	public function main_setting_screen() {
		require_once Virtuaria_PagSeguro::get_templates_path() . 'main-screen-settings.php';
	}

	/**
	 * Add scripts to dash.
	 *
	 * @param string $page the idendifier page.
	 */
	public function admin_checkout_scripts( $page ) {
		if ( isset( $_GET['post'] )
			&& 'shop_order' === get_post_type( sanitize_text_field( wp_unslash( $_GET['post'] ) ) ) ) {
			wp_enqueue_script(
				'copy-qr',
				VIRTUARIA_PAGSEGURO_URL . 'admin/js/copy-code.js',
				array( 'jquery' ),
				filemtime( VIRTUARIA_PAGSEGURO_DIR . 'admin/js/copy-code.js' ),
				true
			);

			wp_enqueue_style(
				'copy-qr',
				VIRTUARIA_PAGSEGURO_URL . 'admin/css/pix-code.css',
				array(),
				filemtime( VIRTUARIA_PAGSEGURO_DIR . 'admin/css/pix-code.css' )
			);
		}

		$allowed_screens = array(
			'virt_pagseguro',
			'virt_pagseguro_credit',
			'virt_pagseguro_pix',
			'virt_pagseguro_ticket',
		);
		if ( 'toplevel_page_virtuaria_pagseguro' === $page
			|| ( 'woocommerce_page_wc-settings' === $page
			&& isset( $_GET['section'] )
			&& in_array( $_GET['section'], $allowed_screens, true ) ) ) {
			wp_enqueue_script(
				'setup',
				VIRTUARIA_PAGSEGURO_URL . 'admin/js/setup.js',
				array( 'jquery' ),
				filemtime( VIRTUARIA_PAGSEGURO_DIR . 'admin/js/setup.js' ),
				true
			);

			wp_enqueue_style(
				'setup',
				VIRTUARIA_PAGSEGURO_URL . 'admin/css/setup.css',
				array(),
				filemtime( VIRTUARIA_PAGSEGURO_DIR . 'admin/css/setup.css' )
			);

			$options = get_option( 'woocommerce_virt_pagseguro_settings' );
			$section = sanitize_text_field(
				wp_unslash( $_GET['section'] )
			);
			$page    = isset( $_GET['page'] ) ?
				sanitize_text_field(
					wp_unslash( $_GET['page'] )
				)
				: '';

			if ( ( 'virtuaria_pagseguro' === $page
					|| 'virt_pagseguro' !== $_GET['section'] )
				&& isset( $options['payment_form'] )
				&& 'separated' === $options['payment_form'] ) {
				wp_localize_script(
					'setup',
					'navigation',
					array(
						'<div class="navigation-tab">
							<a class="tablinks ' . ( 'virtuaria_pagseguro' === $page ? 'active' : '' ) . '" href="' . admin_url( 'admin.php?page=virtuaria_pagseguro' ) . '">Integração</a>
							<a class="tablinks ' . ( 'virt_pagseguro_credit' === $section ? 'active' : '' ) . '" href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=virt_pagseguro_credit' ) . '">Crédito</a>
							<a class="tablinks ' . ( 'virt_pagseguro_pix' === $section ? 'active' : '' ) . '" href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=virt_pagseguro_pix' ) . '">Pix</a>
							<a class="tablinks ' . ( 'virt_pagseguro_ticket' === $section ? 'active' : '' ) . '" href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=virt_pagseguro_ticket' ) . '">Boleto</a>
						</div>',
					)
				);
			} else {
				wp_localize_script(
					'setup',
					'navigation',
					array(
						'<div class="navigation-tab">
							<a class="tablinks ' . ( 'virtuaria_pagseguro' === $page ? 'active' : '' ) . '" href="' . admin_url( 'admin.php?page=virtuaria_pagseguro' ) . '">Integração</a>
							<a class="tablinks ' . ( 'virt_pagseguro' === $section ? 'active' : '' ) . '" href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=virt_pagseguro' ) . '">Pagamento</a>
						</div>',
					)
				);
			}
		}
	}

	/**
	 * Review info.
	 */
	public function display_review_info() {
		global $hook_suffix;

		$methods = array(
			'virt_pagseguro',
			'virt_pagseguro_credit',
			'virt_pagseguro_pix',
			'virt_pagseguro_ticket',
		);

		if ( 'toplevel_page_virtuaria_pagseguro' === $hook_suffix
			|| ( 'woocommerce_page_wc-settings' === $hook_suffix
			&& isset( $_GET['section'] )
			&& in_array( $_GET['section'], $methods, true ) ) ) {
			echo '<style>#wpfooter{display: block;}</style>';
			echo '<h4 class="stars">Avalie nosso trabalho ⭐</h4>';
			echo '<p class="review-us">Apoie o nosso trabalho. Se gostou do plugin, deixe uma avaliação positiva clicando <a href="https://wordpress.org/support/plugin/virtuaria-pagseguro/reviews?rate=5#new-post " target="_blank">aqui</a>. Desde já, nossos agradecimentos.</p>';
			echo '<h4 class="pagbank">Suporte PagBank 🤝</h4>';
			echo '<p class="pagbank">Deseja negociar taxas? Use este link para ser atendido por um especialista do PagBank: <a target="_blank" href="https://pagseguro.uol.com.br/campanhas/contato/?parceiro=virtuaria#rmcl">Solicitar Contato do PagBank</a>.</p>';
			echo '<h4 class="stars">Privacidade ✅</h4>';
			echo '<p class="disclaimer">Email e domínio do site serão armazenados durante o processo de autorização, para contato e suporte caso necessário. O campo email é opcional.</p>';
			echo '<h4 class="stars">Tecnologia Virtuaria ✨</h4>';
			echo '<p class="disclaimer">Desenvolvimento, implantação e manutenção de e-commerces e marketplaces para atacado e varejo. Soluções personalizadas para cada cliente. <a target="_blank" href="https://virtuaria.com.br">Saiba mais</a>.</p>';
		}
	}

	/**
	 * Update main settings.
	 */
	public function save_main_settings() {
		if ( isset( $_POST['setup_nonce'] )
			&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['setup_nonce'] ) ), 'setup_virtuaria_module' ) ) {
			$options = get_option( 'woocommerce_virt_pagseguro_settings' );
			foreach ( $_POST as $index => $fields ) {
				if ( strpos( $index, 'woocommerce_virt_pagseguro_' ) !== false ) {
					$options[ str_replace( 'woocommerce_virt_pagseguro_', '', $index ) ] = sanitize_text_field(
						wp_unslash(
							$fields
						)
					);
				}
			}

			if ( ! isset( $_POST['woocommerce_virt_pagseguro_debug'] ) ) {
				unset( $options['debug'] );
			}

			update_option(
				'woocommerce_virt_pagseguro_settings',
				$options
			);

			set_transient(
				'virtuaria_pagseguro_main_setting_saved',
				true,
				15
			);
		}
	}

	/**
	 * Save store token.
	 */
	public function save_store_token() {
		if ( isset( $_GET['page'] )
			&& ! isset( $_POST['fee_setup_updated'] )
			&& 'virtuaria_pagseguro' === $_GET['page'] ) {
			$settings = get_option(
				'woocommerce_virt_pagseguro_settings'
			);

			if ( isset( $_GET['token'] ) ) {
				$this->update_token(
					$settings,
					sanitize_text_field(
						wp_unslash( $_GET['token'] )
					)
				);

				add_action(
					'admin_notices',
					array( $this, 'virtuaria_pagseguro_connected' )
				);
				delete_option( 'virtuaria_pagseguro_not_authorized' );
			} elseif ( isset( $_GET['access_revoked'] )
				&& 'success' === $_GET['access_revoked'] ) {

				$this->update_token(
					$settings,
					null
				);
				add_action(
					'admin_notices',
					array( $this, 'virtuaria_pagseguro_disconnected' )
				);
				delete_option( 'virtuaria_pagseguro_not_authorized' );
			} elseif ( isset( $_GET['proccess'] )
				&& 'failed' === $_GET['proccess'] ) {

				$this->update_token(
					$settings,
					null
				);

				delete_option( 'virtuaria_pagseguro_not_authorized' );
				add_action(
					'admin_notices',
					array( $this, 'virtuaria_pagseguro_failed' )
				);
			}
		}
	}

	/**
	 * Update token from main settins.
	 *
	 * @param array $current current settings.
	 * @param mixed $token token.
	 */
	private function update_token( $current, $token ) {
		if ( isset( $current['environment'] )
			&& 'sandbox' === $current['environment'] ) {
			$current['token_sanbox'] = $token;
		} else {
			$current['token_production'] = $token;
		}

		update_option(
			'woocommerce_virt_pagseguro_settings',
			$current
		);
	}

	/**
	 * Message from token generate success.
	 */
	public function virtuaria_pagseguro_connected() {
		?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_attr_e( 'Virtuaria PagSeguro Conectado!', 'virtuaria-pagseguro' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Message from token revoked success.
	 */
	public function virtuaria_pagseguro_disconnected() {
		?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_attr_e( 'Virtuaria PagSeguro Desconectado!', 'virtuaria-pagseguro' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Message from fail.
	 */
	public function virtuaria_pagseguro_failed() {
		?>
		<div class="notice notice-error is-dismissible">
			<p><?php esc_attr_e( 'Virtuaria PagSeguro - Falha ao processar operação!', 'virtuaria-pagseguro' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Message from fail.
	 */
	public function virtuaria_pagseguro_not_authorized() {
		if ( get_option( 'virtuaria_pagseguro_not_authorized' ) ) {
			?>
			<div class="notice notice-warning is-dismissible">
				<p>
					Virtuaria PagSeguro - Sua conexão com a API do PagSeguro está sendo negada, impedindo a concretização das transações (pagamento, reembolso, etc). Tente reconectar o plugin via página de <a href="<?php echo esc_url( admin_url( 'admin.php?page=virtuaria_pagseguro' ) ); ?>">configuração</a> para renovar a autorização. Para mais detalhes, consulte o log do plugin.
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Fee setup change.
	 */
	public function fee_setup_update() {
		if ( isset( $_POST['fee_setup_updated'] ) ) {
			$settings = get_option(
				'woocommerce_virt_pagseguro_settings'
			);

			$settings['token_production'] = null;

			update_option(
				'woocommerce_virt_pagseguro_settings',
				$settings
			);
		}
	}

	/**
	 * Redirect store token to new main settings page.
	 */
	public function redirect_old_save_token() {
		$token_update = isset( $_GET['token'] )
			|| isset( $_GET['proccess'] )
			|| isset( $_GET['access_revoked'] );

		if ( isset( $_GET['section'], $_GET['page'] )
			&& ! isset( $_POST['fee_setup_updated'] )
			&& 'virt_pagseguro' === $_GET['section']
			&& $token_update
			&& 'virtuaria_pagseguro' !== $_GET['page'] ) {
			unset( $_GET['page'] );

			if ( wp_safe_redirect(
				admin_url(
					'admin.php?page=virtuaria_pagseguro&'
						. http_build_query( $_GET )
				)
			) ) {
				exit;
			}
		}
	}

	/**
	 * Separated payment style and scripts.
	 */
	public function add_public_styles_scripts() {
		$settings = get_option(
			'woocommerce_virt_pagseguro_settings'
		);
		if ( isset( $settings['payment_form'] )
			&& 'separated' === $settings['payment_form'] ) {
			wp_enqueue_style(
				'pagseguro-separated-methods',
				VIRTUARIA_PAGSEGURO_URL . 'public/css/separated-methods.css',
				'',
				filemtime( VIRTUARIA_PAGSEGURO_DIR . 'public/css/separated-methods.css' )
			);
		}
	}
}

new Virtuaria_PagSeguro_Settings();
