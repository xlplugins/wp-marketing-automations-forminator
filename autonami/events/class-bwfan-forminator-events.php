<?php

final class BWFAN_FORMINATOR_Form_Submit extends BWFAN_Event {
	private static $instance = null;
	public $form_id = 0;
	public $form_title = '';
	public $entry = [];
	public $fields = [];
	public $email = '';
	public $entry_id = '';
	public $mark_subscribe = false;
	public $first_name = '';
	public $last_name = '';
	public $contact_phone = '';

	private function __construct() {
		$this->event_merge_tag_groups = array( 'forminator_forms' );// merge tags groups
		$this->event_name             = esc_html__( 'Form Submits', 'autonami-automations-pro' );
		$this->event_desc             = esc_html__( 'This event runs after a form is submitted', 'autonami-automations-pro' );
		$this->event_rule_groups      = array(
			'forminator_forms',
			'bwf_contact_segments',
			'bwf_contact',
			'bwf_contact_fields',
			'bwf_contact_user',
		);
		$this->optgroup_label         = esc_html__( 'Forminator Forms', 'autonami-automations-pro' );
		$this->priority               = 10;
		$this->customer_email_tag     = '';
		// v2 and support_v1 property allows to control the visibility of this event in respective versions
		$this->v2         = true;
		$this->support_v1 = false;

	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function load_hooks() {
		add_filter( 'bwfan_get_form_submit_events', array( $this, 'add_forminator_to_form_submit_events' ), 10, 1 );
		add_action( 'wp_ajax_bwfan_get_forminator_form_fields', array( $this, 'bwfan_get_forminator_form_fields' ) );
		add_action( 'forminator_form_after_save_entry', array( $this, 'process' ), 10);
	}

	public function get_view_data() {
		$options = [];
		$forms   = Forminator_API::get_forms( null, 1, 100, Forminator_Form_Model::STATUS_PUBLISH );

		if ( is_array( $forms ) ) {
			foreach ( $forms as $form ) {
				$options[ $form->id ] = $form->name;
			}
		}

		return $options;
	}

	public function bwfan_get_forminator_form_fields() {
		$form_id = absint( sanitize_text_field( $_POST['id'] ) ); // WordPress.CSRF.NonceVerification.NoNonceVerification
		$fields  = [];
		if ( empty( $form_id ) ) {
			wp_send_json( array(
				'fields' => $fields,
			) );
		}
		$fields = $this->get_form_fields( $form_id );

			$finalarr = [];
			foreach ( $fields as $key => $value ) {
				$finalarr[] = [
					'key'   => $value->__get( 'element_id' ),
					'value' => $value->__get( 'field_label' )
				];
			}

			wp_send_json( array(
				'results' => $finalarr
			) );
			exit;
	}

	public function get_form_fields( $form_id ) {
		return Forminator_API::get_form_fields( $form_id );
	}

	public function process( $form_id ) {

		$formdata = Forminator_API::get_entries( $form_id );
		$fields   = Forminator_API::get_form_fields( $form_id );

		$data               = $this->get_default_data();
		$data['form_id']    = $formdata[0]->form_id;
		$data['form_title'] = isset( $formdata[0]->form_id ) ? get_the_title( $form_id ) : '';
		$data['entry_id']   = $formdata[0]->entry_id;
		$fields_array       = [];
		$entries            = array();
		foreach ( $formdata[0]->meta_data as $key => $field ) {
			$entries[ $key ] = $field['value'];

		}
		foreach ( $fields as $value ) {
			$fields_array[ $value->__get( 'element_id' ) ] = $value->__get( 'field_label' );
		}
		$data['entry']  = $entries;
		$data['fields'] = $fields_array;
		$this->send_async_call( $data );

	}

	/**
	 * Set up rules data
	 *
	 * @param $automation_data
	 */
	public function pre_executable_actions( $automation_data ) {
		$email_map = $automation_data['event_meta']['email_map'];

		$this->email = ( ! empty( $email_map ) && isset( $this->entry['fields'][ $email_map ] ) && is_email( $this->entry['fields'][ $email_map ] ) ) ? $this->entry['fields'][ $email_map ] : '';

		BWFAN_Core()->rules->setRulesData( $this->form_id, 'form_id' );
		BWFAN_Core()->rules->setRulesData( $this->form_title, 'form_title' );
		BWFAN_Core()->rules->setRulesData( $this->entry, 'entry' );
		BWFAN_Core()->rules->setRulesData( $this->entry_id, 'entry_id' );
		BWFAN_Core()->rules->setRulesData( $this->fields, 'fields' );
		BWFAN_Core()->rules->setRulesData( $this->email, 'email' );
		BWFAN_Core()->rules->setRulesData( BWFAN_Common::get_bwf_customer( $this->email, $this->get_user_id_event() ), 'bwf_customer' );
	}

	public function get_user_id_event() {
		if ( is_email( $this->email ) ) {
			$user = get_user_by( 'email', $this->email );

			return ( $user instanceof WP_User ) ? $user->ID : false;
		}

		return false;
	}

	/**
	 * Registers the tasks for current event.
	 *
	 * @param $automation_id
	 * @param $integration_data
	 * @param $event_data
	 */
	public function register_tasks( $automation_id, $integration_data, $event_data ) {
		if ( ! is_array( $integration_data ) ) {
			return;
		}

		$data_to_send = $this->get_event_data();

		$this->create_tasks( $automation_id, $integration_data, $event_data, $data_to_send );
	}

	public function get_event_data() {
		$data_to_send                         = [ 'global' => [] ];
		$data_to_send['global']['form_id']    = $this->form_id;
		$data_to_send['global']['form_title'] = $this->form_title;
		$data_to_send['global']['entry']      = $this->entry;
		$data_to_send['global']['fields']     = $this->fields;
		$data_to_send['global']['entry_id']   = $this->entry_id;
		$data_to_send['global']['email']      = $this->email;

		return $data_to_send;
	}

	/**
	 * Set global data for all the merge tags which are supported by this event.
	 *
	 * @param $task_meta
	 */
	public function set_merge_tags_data( $task_meta ) {
		$get_data = BWFAN_Merge_Tag_Loader::get_data( 'form_id' );
		if ( ( empty( $get_data ) || intval( $get_data ) !== intval( $task_meta['global']['form_id'] ) ) ) {
			$set_data = array(
				'form_id'    => intval( $task_meta['global']['form_id'] ),
				'form_title' => $task_meta['global']['form_title'],
				'entry'      => $task_meta['global']['entry'],
				'fields'     => $task_meta['global']['fields'],
				'entry_id'   => $task_meta['global']['entry_id'],
				'email'      => $task_meta['global']['email'],
			);
			BWFAN_Merge_Tag_Loader::set_data( $set_data );
		}
	}

	public function get_email_event() {
		return is_email( $this->email ) ? $this->email : false;
	}

	/**
	 * v2 Method: Validate event settings
	 *
	 * @param $automation_data
	 *
	 * @return bool
	 */
	public function validate_v2_event_settings( $automation_data ) {

		if ( absint( $automation_data['form_id'] ) !== absint( $automation_data['event_meta']['bwfan-forminator_form_submit_form_id'] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Capture the async data for the current event.
	 * @return array|bool
	 */
	public function capture_v2_data( $automation_data ) {
		$get_email         = $automation_data['event_meta']['bwfan-form-field-map']['bwfan_email_field_map'];
		$get_first_name    = $automation_data['event_meta']['bwfan-form-field-map']['bwfan_first_name_field_map'];
		$get_last_name     = $automation_data['event_meta']['bwfan-form-field-map']['bwfan_last_name_field_map'];
		$get_contact_phone = $automation_data['event_meta']['bwfan-form-field-map']['bwfan_phone_field_map'];


		$email_map            = isset( $automation_data['entry'][ $get_email ] ) ? $automation_data['entry'][ $get_email ] : '';
		$first_name_map       = isset( $automation_data['entry'][ $get_first_name ]['first-name'] ) ? $automation_data['entry'][ $get_first_name ] : '';
		$last_name_map        = isset( $automation_data['entry'][ $get_last_name ]['last-name'] ) ? $automation_data['entry'][ $get_last_name ] : '';
		$phone_map            = isset( $automation_data['entry'][ $get_contact_phone ] ) ? $automation_data['entry'][ $get_contact_phone ] : '';
		$this->mark_subscribe = isset( $automation_data['event_meta']['bwfan-mark-contact-subscribed'] ) ? $automation_data['event_meta']['bwfan-mark-contact-subscribed'] : 0;

		$this->form_id       = BWFAN_Common::$events_async_data['form_id'];
		$this->form_title    = BWFAN_Common::$events_async_data['form_title'];
		$this->entry         = BWFAN_Common::$events_async_data['entry'];
		$this->fields        = BWFAN_Common::$events_async_data['fields'];
		$this->entry_id      = BWFAN_Common::$events_async_data['entry_id'];
		$this->email         = ( ! empty( $email_map ) ) ? $email_map : '';
		$this->first_name    = ( ! empty( $first_name_map['first-name'] ) ) ? $first_name_map['first-name'] : '';
		$this->last_name     = ( ! empty( $last_name_map['last-name'] ) ) ? $last_name_map['last-name'] : '';
		$this->contact_phone = ( ! empty( $phone_map ) ) ? $phone_map : '';

		$automation_data['form_id']       = $this->form_id;
		$automation_data['form_title']    = $this->form_title;
		$automation_data['fields']        = $this->fields;
		$automation_data['email']         = $this->email;
		$automation_data['entry']         = $this->entry;
		$automation_data['entry_id']      = $this->entry;
		$automation_data['first_name']    = $this->first_name;
		$automation_data['last_name']     = $this->last_name;
		$automation_data['contact_phone'] = $this->contact_phone;
		BWFAN_PRO_Common::maybe_create_update_contact( $automation_data );

		return $automation_data;
	}

	/**
	 * v2 Method: Get fields schema
	 * @return array[][]
	 */
	public function get_fields_schema() {
		$forms = array_replace( [ '' => 'Select' ], $this->get_view_data() );
		$forms = BWFAN_PRO_Common::prepared_field_options( $forms );

		return [
			[
				'id'          => 'bwfan-forminator_form_submit_form_id',
				'type'        => 'select',
				'options'     => $forms,
				'label'       => __( 'Select Form', 'wp-marketing-automations' ),
				"class"       => 'bwfan-input-wrapper',
				"placeholder" => 'Select',
				"required"    => true,
				"errorMsg"    => "Form is required.",
				"description" => ""
			],
			[
				'id'          => 'bwfan-form-field-map',
				'type'        => 'bwf_form_submit',
				"class"       => 'bwfan-input-wrapper',
				"required"    => true,
				'placeholder' => 'Select',
				"description" => "",
				"ajax_cb"     => 'bwfan_get_forminator_form_fields',
				"ajax_field"  => [
					'id' => 'bwfan-forminator_form_submit_form_id'
				],
				"fieldChange" => 'bwfan-forminator_form_submit_form_id',
				"toggler"     => [
					'fields'   => array(
						array(
							'id'    => 'bwfan-forminator_form_submit_form_id',
							'value' => '',
						)
					),
					'relation' => 'AND',
				]
			],
			[
				'id'            => 'bwfan-mark-contact-subscribed',
				'type'          => 'checkbox',
				'checkboxlabel' => 'Mark Contact as Subscribed',
				'description'   => '',
				"toggler"       => [
					'fields'   => array(
						array(
							'id'    => 'bwfan-forminator_form_submit_form_id',
							'value' => '',
						),
					),
					'relation' => 'AND',
				]
			]
		];
	}

	public function add_forminator_to_form_submit_events( $events ) {
		$events[] = 'BWFAN_FORMINATOR_Form_Submit';

		return $events;
	}

}


/**
 * Register this event to a source.
 * This will show the current event in dropdown in single automation screen.
 */
if ( bwfan_is_forminator_forms_active() ) {
	return 'BWFAN_FORMINATOR_Form_Submit';
}