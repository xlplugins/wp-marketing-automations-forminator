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
final class BWFAN_Forminator {
    // instance 
    private static $_instance = null;
    private function __construct() {
		// initializing lms
		add_action( 'bwfan_loaded', [ $this, 'init_formintor' ] );
    }
        
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
	public function init_formintor() {
		/** return if autonami pro is not active as this addon is dependent on autonami pro */
		if ( ! function_exists( 'bwfan_is_autonami_pro_active' ) || ! bwfan_is_autonami_pro_active() ) {
			return;
		}
		$this->define_plugin_properties();
        $this->loads_files();
	}
    // how include class (events, merges , intigratiojn classes)
    //plugins\wp-marketing-automations-lms/wp-marketing-automations-lms.php
    // there is co0de but cant find how you loads these extraction classes 
    public function loads_files(){
        
       //include_once BWFAN_FORMINTOR_PLUGIN_DIR . '/formintor/autonami/class-formintor-intergraion.php';
       include_once BWFAN_FORMINTOR_PLUGIN_DIR . '/formintor/autonami/class-forminator-source.php';
       include_once BWFAN_FORMINTOR_PLUGIN_DIR . '/formintor/autonami/class-bwfan-forminator-events.php';

	   
	}

    
    	/**
	 * defining lms properties
	 * @return void
	 */
	public function define_plugin_properties() {
		define( 'BWFAN_FORMINTOR_VERSION', '1.0.0' );
		define( 'BWFAN_FORMINTOR_FULL_NAME', 'Autonami Marketing Automations FORMINTOR' );
		define( 'BWFAN_FORMINTOR_PLUGIN_FILE', __FILE__ );
		define( 'BWFAN_FORMINTOR_PLUGIN_DIR', __DIR__ );
		define( 'BWFAN_FORMINTOR_PLUGIN_URL', untrailingslashit( plugin_dir_url( BWFAN_FORMINTOR_PLUGIN_FILE ) ) );
    }

}
BWFAN_Forminator::get_instance();

