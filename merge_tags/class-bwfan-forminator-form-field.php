<?php

class BWFAN_Forminator_Form_Field extends BWFAN_Merge_Tag {

	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'forminator_form_field';
		$this->tag_description = __( 'Form Field', 'autonami-automations-pro' );
		add_shortcode( 'bwfan_forminator_form_field', array( $this, 'parse_shortcode' ) );
		add_action( 'wp_ajax_bwfan_get_automation_forminator_form_fields', array( $this, 'bwfan_get_automation_forminator_form_fields' ) );
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	/**
	 * Parse the merge tag and return its value.
	 *
	 * @param $attr
	 *
	 * @return mixed|string|void
	 */
	public function parse_shortcode( $attr ) {
		if ( true === BWFAN_Merge_Tag_Loader::get_data( 'is_preview' ) ) {
			return $this->get_dummy_preview();
		}
        $field_value = [];
        $key= $attr['field'];
		$entries = BWFAN_Merge_Tag_Loader::get_data( 'entry' );
      
        $field_value = $entries[$key];
        if ( is_array( $field_value ) ) {
			$field_value = isset( $field_value['first'] ) ? implode( ' ', $field_value ) : implode( ', ', $field_value );
		}
		return $this->parse_shortcode_output( $field_value, $attr );
	}

	/**
	 * Show dummy value of the current merge tag.
	 *
	 * @return string
	 *
	 * @todo:Hard values shouldn't be passed
	 */
	public function get_dummy_preview() {
		return 'Test';
	}

	public function bwfan_get_automation_forminator_form_fields() {

        $finalarr = [];
		$automationId = absint( sanitize_text_field( $_POST['automationId'] ) );

		/** Check Automation */
		$automation_obj = BWFAN_Automation_V2::get_instance( $automationId );
		/** Check for automation exists */
		if ( empty( $automation_obj->error ) ) {
			$automation_meta = $automation_obj->get_automation_meta_data();

			if ( isset( $automation_meta[ 'event_meta' ] ) && isset( $automation_meta['event_meta']['bwfan-forminator_form_submit_form_id'] ) ) {
				$form_id = sanitize_text_field( $automation_meta['event_meta']['bwfan-forminator_form_submit_form_id'] );
				$fields  = [];
               
                if ( ! empty( $form_id ) ) {
					$obj = BWFAN_FORMINATOR_Form_Submit::get_instance();
					$fields = $obj->get_form_fields( $form_id );
				}
                $finalarr = [];
                foreach ( $fields as $key => $value ) {
                    $finalarr[] = [
                        'key'   => $value->__get( 'element_id' ),
                        'value' => $value->__get( 'field_label' )
                    ];
                }
			}
		}
		wp_send_json( array(
			'results' => $finalarr
		) );
	}

	/**
	 * Returns merge tag schema
	 *
	 * @return array[]
	 */
	public function get_setting_schema() {
		return [
			[
				'id'          => 'field',
				'type'        => 'ajax',
				'label'       => __( 'Select Field', 'wp-marketing-automations' ),
				"class"       => 'bwfan-input-wrapper',
				"required"    => true,
				'placeholder' => 'Select',
				"description" => "",
				"ajax_cb"     => 'bwfan_get_automation_forminator_form_fields',
			]
		];
	}
}

/**
 * Register this merge tag to a group.
 */

 if ( function_exists( 'bwfan_is_forminator_forms_active' ) ) {
	BWFAN_Merge_Tag_Loader::register( 'forminator_forms', 'BWFAN_Forminator_Form_Field', null, 'Forminator Forms' );
}
