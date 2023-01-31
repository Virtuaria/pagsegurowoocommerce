<?php
/**
 * Plugin Name: Virtuaria - Pagseguro para Woocommerce
 * Plugin URI: https://virtuaria.com.br/virtuaria-pagseguro-plugin/
 * Description: Adiciona o método de pagamento PagSeguro a sua loja virtual.
 * Author: Virtuaria
 * Author URI: https://virtuaria.com.br/
 * Version: 1.1.0
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
		 */
		private function __construct() {
			if ( class_exists( 'WC_PagSeguro' ) ) {
				add_action( 'admin_notices', array( $this, 'conflict_module' ) );
				return;
			}

			add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
			if ( class_exists( 'WC_Payment_Gateway' ) ) {
				$this->load_dependecys();
				add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateway' ) );
				add_action( 'admin_menu', array( $this, 'add_submenu_pagseguro' ) );
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
				<p><?php esc_attr_e( 'Virtuaria Pagseguro need Woocommerce 4.0+ to work!', 'virtuaria-pagseguro' ); ?></p>
			</div>
			<?php
		}

		/**
		 * Display warning about conflict in module.
		 */
		public function conflict_module() {
			?>
			<div class="notice notice-error is-dismissible">
				<p><?php esc_attr_e( 'Virtuaria Pagseguro não pode ser usado ao mesmo tempo que "Claudio Sanches - PagSeguro for WooCommerce"', 'virtuaria-pagseguro' ); ?></p>
			</div>
			<?php
		}

		/**
		 * Load file dependencys.
		 */
		private function load_dependecys() {
			require_once 'includes/class-wc-virtuaria-pagseguro-gateway.php';
			require_once 'includes/class-wc-virtuaria-pagseguro-api.php';
		}

		/**
		 * Add Payment method.
		 *
		 * @param array $methods the current methods.
		 */
		public function add_gateway( $methods ) {
			$methods[] = 'WC_Virtuaria_PagSeguro_Gateway';
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
		 * Add submenu pagseguro.
		 */
		public function add_submenu_pagseguro() {
			add_submenu_page(
				'pagamentos',
				'Pagseguro',
				'Pagseguro',
				'remove_users',
				'admin.php?page=wc-settings&tab=checkout&section=virt_pagseguro'
			);
		}

		/**
		 * Load the plugin text domain for translation.
		 */
		public function load_plugin_textdomain() {
			load_plugin_textdomain( 'virtuaria-pagseguro', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		}
	}

	add_action( 'plugins_loaded', array( 'Virtuaria_Pagseguro', 'get_instance' ) );

endif;
