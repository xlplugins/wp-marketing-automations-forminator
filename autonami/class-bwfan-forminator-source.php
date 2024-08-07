<?php
if ( ! class_exists( 'BWFAN_FORMINATOR_Source' ) ) {
	class BWFAN_FORMINATOR_Source extends BWFAN_Source {
		private static $instance = null;

		public function __construct() {
			$this->event_dir  = __DIR__;
			$this->nice_name  = __( 'Forminator', 'wp-marketing-automations' );
			$this->group_name = __( 'Forms', 'wp-marketing-automations' );
			$this->group_slug = 'forms';
			$this->priority   = 100;
		}

		/**
		 * Ensures only one instance of the class is loaded or can be loaded.
		 *
		 * @return BWFAN_WPFORMS_Source|null
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

	}

	/**
	 * Register this as a source.
	 */
	if ( bwfan_is_forminator_forms_active() ) {
		BWFAN_Load_Sources::register( 'BWFAN_FORMINATOR_Source' );
	}
}
