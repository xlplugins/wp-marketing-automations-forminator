<?php
if ( function_exists( 'bwfan_is_forminator_forms_active' ) && bwfan_is_forminator_forms_active() ) {
	if ( ! class_exists( 'BWFAN_Rule_Forminator_Form_Field' ) ) {
		class BWFAN_Rule_Forminator_Form_Field extends BWFAN_Rule_Base {

			public function __construct() {
				$this->v2 = true;
				$this->v1 = false;
				parent::__construct( 'forminator_form_field' );
			}

			public function get_possible_rule_operators() {
				return array(
					'is'           => __( 'is', 'wp-marketing-automations' ),
					'is_not'       => __( 'is not', 'wp-marketing-automations' ),
					'contains'     => __( 'contains', 'wp-marketing-automations' ),
					'not_contains' => __( 'does not contain', 'wp-marketing-automations' ),
					'starts_with'  => __( 'starts with', 'wp-marketing-automations' ),
					'ends_with'    => __( 'ends with', 'wp-marketing-automations' ),
				);
			}

			/** v2 Methods: START */

			public function get_options( $term = '' ) {
				$finalarr = [];
				$meta     = $this->event_automation_meta;
				$form_id  = isset( $meta['bwfan-forminator_form_submit_form_id'] ) ? $meta['bwfan-forminator_form_submit_form_id'] : 0;
				if ( empty( $form_id ) ) {
					return array();
				}
				/** @var BWFAN_Forminator_Form_Submit $ins */
				$ins = BWFAN_Forminator_Form_Submit::get_instance();

				$fields = $ins->get_form_fields( $form_id );
				foreach ( $fields as $value ) {
					$finalarr[ $value->__get( 'element_id' ) ] = $value->__get( 'field_label' );
				}

				return $finalarr;
			}

			public function get_rule_type() {
				return 'key-value';
			}

			public function is_match_v2( $automation_data, $rule_data ) {
				if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
					return $this->return_is_match( false, $rule_data );
				}

				$entry = isset( $automation_data['global']['entry'] ) ? $automation_data['global']['entry'] : [];

				$type        = $rule_data['rule'];
				$data        = $rule_data['data'];
				$key         = isset( $data[0] ) ? $data[0] : '';
				$saved_value = isset( $data[1] ) ? $data[1] : '';
				$value       = isset( $entry[ $key ] ) ? $entry[ $key ] : '';

				$value = BWFAN_Pro_Rules::make_value_as_array( $value );

				$value           = array_map( 'strtolower', $value );
				$condition_value = strtolower( trim( $saved_value ) );

				/** checking if condition value contains comma */
				if ( strpos( $condition_value, ',' ) !== false ) {
					$condition_value = explode( ',', $condition_value );
					$condition_value = array_map( 'trim', $condition_value );
				}

				switch ( $type ) {
					case 'is':
						if ( is_array( $condition_value ) && is_array( $value ) ) {
							$result = count( array_intersect( $condition_value, $value ) ) > 0;
						} else {
							$result = in_array( $condition_value, $value );
						}
						break;
					case 'is_not':
						if ( is_array( $condition_value ) && is_array( $value ) ) {
							$result = count( array_intersect( $condition_value, $value ) ) === 0;
						} else {
							$result = ! in_array( $condition_value, $value );
						}
						break;
					case 'contains':
						if ( is_array( $value ) ) {
							$result = ! empty( array_filter( $value, function ( $element ) use ( $condition_value ) {
								return strpos( $element, $condition_value ) !== false;
							} ) );
							break;
						}

						$result = strpos( $value, $condition_value ) !== false;
						break;
					case 'not_contains':
						if ( is_array( $value ) ) {
							$result = ! empty( array_filter( $value, function ( $element ) use ( $condition_value ) {
								return strpos( $element, $condition_value ) === false;
							} ) );
							break;
						}

						$result = strpos( $value, $condition_value ) === false;
						break;
					case 'starts_with':
						$value  = isset( $value[0] ) && ! empty( $value[0] ) ? $value[0] : '';
						$length = strlen( $condition_value );
						$result = substr( $value, 0, $length ) === $condition_value;
						break;
					case 'ends_with':
						$value  = is_array( $value ) ? end( $value ) : $value;
						$length = strlen( $condition_value );
						if ( 0 === $length || ( $length > strlen( $value ) ) ) {
							$result = false;
							break;
						}
						$result = substr( $value, - $length ) === $condition_value;
						break;
					case 'is_blank':
						$result = empty( $value[0] );
						break;
					case 'is_not_blank':
						$result = ! empty( $value[0] );
						break;
					default:
						$result = false;
						break;
				}

				return $this->return_is_match( $result, $rule_data );
			}

			/** v2 Methods: END */
		}
	}
}
