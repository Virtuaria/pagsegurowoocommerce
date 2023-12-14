<?php
/**
 * Plugin Name: Virtuaria - Pagseguro Crédito, Pix e Boleto
 * Plugin URI: https://virtuaria.com.br/virtuaria-pagseguro-plugin/
 * Description: Adiciona o método de pagamento PagSeguro a sua loja virtual.
 * Author: Virtuaria
 * Author URI: https://virtuaria.com.br/
 * Version: 3.0.0
 * License: GPLv2 or later
 *
 * @package virtuaria
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Virtuaria_Pagseguro' ) ) :
	define( 'VIRTUARIA_PAGSEGURO_DIR', plugin_dir_path( __FILE__ ) );
	define( 'VIRTUARIA_PAGSEGURO_URL', plugin_dir_url( __FILE__ ) );
	/**
	 * Class definition.
	 */
	class Virtuaria_Pagseguro {
		/**
		 * Instance of this class.
		 *
		 * @var object
		 */
		protected static $instance = null;

		/**
		 * Settings.
		 *
		 * @var array
		 */
		private $settings;

		/**
		 * Return an instance of this class.
		 *
		 * @return object A single instance of this class.
		 */
		public static function get_instance() {
			// If the single instance hasn't been set, set it now.
			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Singleton constructor.
		 *
		 * @throws Exception Corrupted plugin.
		 */
		private function __construct() {
			if ( ! class_exists( 'Extra_Checkout_Fields_For_Brazil' ) ) {
				add_action( 'admin_notices', array( $this, 'missing_extra_checkout_fields' ) );
				return;
			}

			add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
			if ( class_exists( 'WC_Payment_Gateway' ) ) {
				$this->settings = get_option( 'woocommerce_virt_pagseguro_settings' );
				$this->load_dependecys();
				add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateway' ) );
			} else {
				add_action( 'admin_notices', array( $this, 'missing_dependency' ) );
			}
		}

		/**
		 * Display warning about missing dependency.
		 */
		public function missing_dependency() {
			?>
			<div class="notice notice-error is-dismissible">
				<p><?php esc_attr_e( 'Virtuaria PagSeguro precisa do Woocommerce 4.0+ para funcionar!', 'virtuaria-pagseguro' ); ?></p>
			</div>
			<?php
		}

		/**
		 * Display warning about conflict in module.
		 */
		public function conflict_module() {
			?>
			<div class="notice notice-error is-dismissible">
				<p><?php esc_attr_e( 'Virtuaria PagSeguro não pode ser usado ao mesmo tempo que "Claudio Sanches - PagSeguro for WooCommerce"', 'virtuaria-pagseguro' ); ?></p>
			</div>
			<?php
		}

		/**
		 * Load file dependencys.
		 */
		private function load_dependecys() {
			require_once 'includes/traits/trait-virtuaria-pagseguro-common.php';
			require_once 'includes/traits/trait-virtuaria-pagseguro-credit.php';
			require_once 'includes/traits/trait-virtuaria-pagseguro-pix.php';
			require_once 'includes/traits/trait-virtuaria-pagseguro-ticket.php';

			if ( isset( $this->settings['payment_form'] )
				&& 'separated' === $this->settings['payment_form'] ) {
				require_once 'includes/class-wc-virtuaria-pagseguro-gateway-credit.php';
				require_once 'includes/class-wc-virtuaria-pagseguro-gateway-pix.php';
				require_once 'includes/class-wc-virtuaria-pagseguro-gateway-ticket.php';
			} else {
				require_once 'includes/class-wc-virtuaria-pagseguro-gateway.php';
			}

			require_once 'includes/class-virtuaria-pagseguro-handle-notifications.php';
			require_once 'includes/class-wc-virtuaria-pagseguro-api.php';
			require_once 'includes/class-virtuaria-pagseguro-settings.php';
			require_once 'includes/class-virtuaria-pagseguro-events.php';

			if ( ! function_exists( 'get_plugin_data' )
				&& file_exists( ABSPATH . '/wp-admin/includes/plugin.php' ) ) {
				require_once ABSPATH . '/wp-admin/includes/plugin.php';
			}
			$plugin_data = get_plugin_data( __FILE__ );
			require_once 'includes/integrity-check.php';
		}

		/**
		 * Add Payment method.
		 *
		 * @param array $methods the current methods.
		 */
		public function add_gateway( $methods ) {
			if ( isset( $this->settings['payment_form'] )
				&& 'separated' === $this->settings['payment_form'] ) {
				$methods[] = 'WC_Virtuaria_PagSeguro_Gateway_Credit';
				$methods[] = 'WC_Virtuaria_PagSeguro_Gateway_Pix';
				$methods[] = 'WC_Virtuaria_PagSeguro_Gateway_Ticket';
			} else {
				$methods[] = 'WC_Virtuaria_PagSeguro_Gateway';
			}
			return $methods;
		}

		/**
		 * Get templates path.
		 *
		 * @return string
		 */
		public static function get_templates_path() {
			return plugin_dir_path( __FILE__ ) . 'templates/';
		}

		/**
		 * Load the plugin text domain for translation.
		 */
		public function load_plugin_textdomain() {
			load_plugin_textdomain( 'virtuaria-pagseguro', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		}

		/**
		 * Endpoint to homolog file.
		 */
		public function register_endpoint() {
			add_rewrite_rule( 'virtuaria-pagseguro(/)?', 'index.php?virtuaria-pagseguro=sim', 'top' );
		}

		/**
		 * Add query vars.
		 *
		 * @param array $query_vars the query vars.
		 * @return array
		 */
		public function add_query_vars( $query_vars ) {
			$query_vars[] = 'virtuaria-pagseguro';
			return $query_vars;
		}

		/**
		 * Redirect access to confirm page.
		 *
		 * @param string $template the template path.
		 * @return string
		 */
		public function redirect_to_homolog_page( $template ) {
			if ( false == get_query_var( 'virtuaria-pagseguro' ) ) {
				return $template;
			}

			return plugin_dir_path( __FILE__ ) . '/includes/endpoint-homolog.php';
		}

		/**
		 * Display warning about missing dependency.
		 */
		public function missing_extra_checkout_fields() {
			?>
			<div class="notice notice-error is-dismissible">
				<p>
					<?php
					echo wp_kses_post(
						sprintf(
							/* translators: %s: plugin link */
							__(
								'Virtuaria PagSeguro precisa do plugin Brazilian Market on WooCommerce 3.7 ou superior para funcionar! O plugin pode ser obtido clicando <a href="%s" target="_blank">aqui</a>.',
								'virtuaria-pagseguro'
							),
							'https://wordpress.org/plugins/woocommerce-extra-checkout-fields-for-brazil/'
						)
					);
					?>
				</p>
			</div>
			<?php
		}

		/**
		 * Init Payment gateway instance.
		 */
		public function initialize_payment_gateway() {
			WC()->payment_gateways();
		}
	}

	add_action( 'plugins_loaded', array( 'Virtuaria_Pagseguro', 'get_instance' ) );

endif;
