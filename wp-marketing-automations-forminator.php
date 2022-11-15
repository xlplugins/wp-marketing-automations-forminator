<?php

/**
 * Plugin Name: Autonami Marketing Automations FORMINTOR
 * Plugin URI: https://buildwoofunnels.com
 * Description:Formination with Autonami Marketing Automations
 * Version: 1.0.0
 * Author: WooFunnels
 * Author URI: https://buildwoofunnels.com
 * License: GPLv3 or later
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: autonami-automations-pro
 */

//return;
final class BWFAN_Forminator {
	// instance
	private static $_instance = null;

	private function __construct() {
		add_action( 'bwfan_loaded', [ $this, 'init_forminator' ] );
		add_action( 'bwfan_before_automations_loaded', [ $this, 'add_modules' ] );
		add_action( 'bwfan_merge_tags_loaded', [ $this, 'load_merge_tags' ] );	}
	/**
	 * @return void
	 */
	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
	}

	/**
	 * initializing lms
	 * @return void
	 */
	public function init_forminator() {

		define( 'BWFAN_FORMINTOR_VERSION', '1.0.0' );
		define( 'BWFAN_FORMINTOR_FULL_NAME', 'Autonami Marketing Automations FORMINTOR' );
		define( 'BWFAN_FORMINTOR_PLUGIN_FILE', __FILE__ );
		define( 'BWFAN_FORMINTOR_PLUGIN_DIR', __DIR__ );
		define( 'BWFAN_FORMINTOR_PLUGIN_URL', untrailingslashit( plugin_dir_url( BWFAN_FORMINTOR_PLUGIN_FILE ) ) );

		require_once BWFAN_FORMINTOR_PLUGIN_DIR . '/includes/bwfan-forminator-functions.php';
	}

	public function add_modules() {
		$integration_dir = BWFAN_FORMINTOR_PLUGIN_DIR . '/autonami';
		foreach ( glob( $integration_dir . '/class-*.php' ) as $_field_filename ) {
			require_once  $_field_filename ;
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

