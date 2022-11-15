<?php
if ( function_exists( 'bwfan_is_forminator_forms_active' ) && bwfan_is_forminator_forms_active() ) {
	if ( ! class_exists( 'BWFAN_Forminator_Rules' ) ) {
		class BWFAN_Forminator_Rules {

			private static $ins = null;

			private function __construct() {
				add_action( 'bwfan_rules_included', [ $this, 'include_rules' ] );

				add_filter( 'bwfan_rules_groups', [ $this, 'add_rule_group' ] );
				add_filter( 'bwfan_rule_get_rule_types', [ $this, 'add_rule_type' ] );
			}

			public static function get_instance() {
				if ( is_null( self::$ins ) ) {
					self::$ins = new self();
				}

				return self::$ins;
			}

			public function add_rule_group( $group ) {
				$group['forminator_forms'] = array(
					'title' => __( 'Forminator Forms', 'autonami-automations-pro' ),
				);

				return $group;
			}

			public function add_rule_type( $types ) {
				$types['forminator_forms']['forminator_form_field'] = __( 'Form Field', 'autonami-automations-pro' );

				return $types;
			}

			public function include_rules() {
				include_once BWFAN_FORMINTOR_PLUGIN_DIR . '/rules/forminator.php';
			}
		}

		BWFAN_Forminator_Rules::get_instance();
	}
}