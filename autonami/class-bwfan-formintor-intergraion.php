<?php

final class BWFAN_Formintor_Integration extends BWFAN_Integration {

	private static $instance = null;

	/**
	 * BWFAN_Formintor_Integration constructor.
	 */
	private function __construct() {
		$this->action_dir = __DIR__;
		$this->nice_name  = __( 'Formintor', 'autonami-automations-pro' );
		$this->priority   = 50;
		$this->group_name = __( 'Formintor', 'autonami-automations-connectors' );
		$this->group_slug = 'formintor';
	}

	/**
	 * Ensures only one instance of the class is loaded or can be loaded.
	 *
	 * @return BWFAN_Formintor_Integration|null
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}

if ( bwfan_is_forminator_forms_active() ) {
	BWFAN_Load_Integrations::register( 'BWFAN_Formintor_Integration' );
}
