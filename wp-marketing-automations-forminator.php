<?php

/**
 * Plugin Name: FunnelKit Automations - Forminator Addon
 * Plugin URI: https://funnelkit.com/wordpress-marketing-automation-autonami/
 * Description: Formination Integration. Works with FunnelKit Automations for WordPress
 * Version: 2.0.0
 * Author: FunnelKit
 * Author URI: https://funnelkit.com
 * License: GPLv3 or later
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: wp-marketing-automations
 */

final class BWFAN_Forminator {
	// instance
	private static $_instance = null;

	private function __construct() {
		add_action( 'bwfan_loaded', [ $this, 'init_forminator' ] );
		add_action( 'bwfan_before_automations_loaded', [ $this, 'add_modules' ] );
		add_action( 'bwfan_merge_tags_loaded', [ $this, 'load_merge_tags' ] );
	}

	/**
	 * @return void
	 */
	public static function get_instance() {
		if ( null === self::$_instance ) {
			self::$_instance = new self;
		}

		return self::$_instance;
	}

	/**
	 * initializing forminator
	 * @return void
	 */
	public function init_forminator() {

		define( 'BWFAN_FORMINTOR_VERSION', '1.0.0' );
		define( 'BWFAN_FORMINTOR_FULL_NAME', 'FunnelKit Automations - Forminator Addon' );
		define( 'BWFAN_FORMINTOR_PLUGIN_FILE', __FILE__ );
		define( 'BWFAN_FORMINTOR_PLUGIN_DIR', __DIR__ );
		define( 'BWFAN_FORMINTOR_PLUGIN_URL', untrailingslashit( plugin_dir_url( BWFAN_FORMINTOR_PLUGIN_FILE ) ) );

		require_once BWFAN_FORMINTOR_PLUGIN_DIR . '/includes/bwfan-forminator-functions.php';
		$this->load_rules();
	}

	public function load_rules() {
		include_once BWFAN_FORMINTOR_PLUGIN_DIR . '/rules/class-bwfan-rules.php';
	}

	public function add_modules() {
		$integration_dir = BWFAN_FORMINTOR_PLUGIN_DIR . '/autonami';
		foreach ( glob( $integration_dir . '/class-*.php' ) as $_field_filename ) {
			require_once $_field_filename;
		}
	}

	/**
	 * Include Merge Tags files
	 */
	public function load_merge_tags() {
		/** Merge tags in root folder */
		$dir = BWFAN_FORMINTOR_PLUGIN_DIR . '/merge_tags';
		foreach ( glob( $dir . '/class-*.php' ) as $_field_filename ) {
			require_once( $_field_filename );
		}
	}

}

BWFAN_Forminator::get_instance();

