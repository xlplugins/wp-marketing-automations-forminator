<?php

class BWFCRM_Form_Forminator extends BWFCRM_Form_Base {
	private $total_selections = 1;
	private $source = 'forminator';

	/** Form Submission Captured Data */
	private $form_id = '';
	private $form_title = '';
	private $fields = [];
	private $entry = [];
	private $entry_id = [];
	private $email = [];
	private $autonami_event = '';

	public function get_source() {
		return $this->source;
	}

	/**
	 * @param BWFCRM_Form_Feed $feed
	 *
	 * @return string|void
	 */
	public function get_form_link( $feed ) {
		$url     = '';
		$form_id = $feed->get_data( 'form_id' );
		if ( $form_id ) {
			$url = admin_url( 'admin.php?page=forminator-cform-wizard&id=' . absint( $form_id ) );
		}

		return $url;
	}

	public function capture_async_submission() {
		$this->form_id    = BWFAN_Common::$events_async_data['form_id'];
		$this->form_title = BWFAN_Common::$events_async_data['form_title'];
		$this->entry      = BWFAN_Common::$events_async_data['entry'];
		$this->fields     = BWFAN_Common::$events_async_data['fields'];
		$this->autonami_event = BWFAN_Common::$events_async_data['event'];

		$this->find_feeds_and_create_contacts();
	}

	public function filter_feeds_for_current_entry() {
		return array_filter( array_map( function ( $feed ) {
			$feed_form_id = $feed->get_data( 'form_id' );
			if ( absint( $this->form_id ) !== absint( $feed_form_id ) ) {
				return false;
			}

			return $feed;
		}, $this->feeds ) );
	}

	public function prepare_contact_data_from_feed_entry( $mapped_fields ) {
		$contact_data = [];
		foreach ( $this->entry['fields'] as $key => $item ) {
			/** for first_name and last_name*/
			if ( isset( $mapped_fields[ $key . '.1' ] ) ) {
				$first_name_key                 = $key . '.1';
				$contact_field                  = is_numeric( $mapped_fields[ $first_name_key ] ) ? absint( $mapped_fields[ $first_name_key ] ) : $mapped_fields[ $first_name_key ];
				$field_value                    = isset( $item['first'] ) ? $item['first'] : '';
				$contact_data[ $contact_field ] = $field_value;
			}

			if ( isset( $mapped_fields[ $key . '.2' ] ) ) {
				$last_name_key                  = $key . '.2';
				$contact_field                  = is_numeric( $mapped_fields[ $last_name_key ] ) ? absint( $mapped_fields[ $last_name_key ] ) : $mapped_fields[ $last_name_key ];
				$field_value                    = isset( $item['last'] ) ? $item['last'] : '';
				$contact_data[ $contact_field ] = $field_value;
			}

			if ( isset( $mapped_fields[ $key . '.3' ] ) ) {
				$middle_name_key                = $key . '.3';
				$contact_field                  = is_numeric( $mapped_fields[ $middle_name_key ] ) ? absint( $mapped_fields[ $middle_name_key ] ) : $mapped_fields[ $middle_name_key ];
				$field_value                    = isset( $item['middle'] ) ? $item['middle'] : '';
				$contact_data[ $contact_field ] = $field_value;
			}

			if ( isset( $mapped_fields[ $key ] ) ) {
				$contact_field = is_numeric( $mapped_fields[ $key ] ) ? absint( $mapped_fields[ $key ] ) : $mapped_fields[ $key ];

				/** if value is in array */
				if ( is_array( $item ) ) {
					$item = wp_json_encode( $item );
				}

				$contact_data[ $contact_field ] = $item;
			}
		}

		return $contact_data;
	}

	public function get_form_fields( $feed ) {
		if ( ! $feed instanceof BWFCRM_Form_Feed ) {
			return BWFCRM_Common::crm_error( __( 'Feed  not Exists: ', 'wp-marketing-automations-crm' ) );
		}
		$feed_id = $feed->get_id();
		if ( ! $feed->is_feed_exists() ) {
			return BWFCRM_Common::crm_error( __( 'No Feed Exists: ' . $feed_id, 'wp-marketing-automations-crm' ) );
		}

		$form_id = $feed->get_data( 'form_id' );
		if ( empty( $form_id ) ) {
			return BWFCRM_Common::crm_error( __( 'Form Feed doesn\'t have sufficient data to get fields: ' . $feed_id, 'wp-marketing-automations-crm' ) );
		}

		return $this->get_forminator_form_fields( $form_id );
	}

	public function get_forminator_form_fields( $form_id ) {
		$form         = forminatorforms()->form->get( $form_id );
		$form_content = forminatorforms_decode( $form->post_content );
		$fields       = array();

		if ( ! empty( $form_content['fields'] ) ) {
			foreach ( $form_content['fields'] as $field ) {
				if ( isset( $field['isHidden'] ) && $field['isHidden'] ) {
					continue;
				}

				$fields[ $field['id'] ] = $field['label'];
				if ( ! isset( $field['format'] ) || 'name' !== $field['type'] || 'simple' === $field['format'] ) {
					continue;
				}

				unset( $fields[ $field['id'] ] );
				$fields[ $field['id'] . '.1' ] = $field['label'] . ': First Name';
				$fields[ $field['id'] . '.2' ] = $field['label'] . ': Last Name';

				if ( 'first-middle-last' === $field['format'] ) {
					$fields[ $field['id'] . '.3' ] = $field['label'] . ': Middle Name';
				}
			}
		}

		return $fields;
	}
    //  working on this part for integration 
    // how to get Forminator for selection 
    //  for This I need to spend some time on Forminator form documentation
    /// I am still working on getting form, form ids, and form data from forminator
    // if you have any ideas please share them with me 
	public function get_form_selection( $args, $return_all_available = false ) {
		/** Form ID Handling */
		$forminator      = forminator()->form->get();
		$form_options = [];

		foreach ( $forminator as $form ) {
			$form_options[ $form->ID ] = $form->post_title;
		}

		$form_options = array( 'default' => $form_options );
		$form_options = $this->get_step_selection_array( 'Form', 'form_id', 1, $form_options );

		return $form_options;
	}

	public function get_total_selection_steps() {
		return $this->total_selections;
	}

	public function get_meta() {
		return array(
			'form_selection_fields' => array(
				'form_id' => 'Form ID'
			)
		);
	}

	/**
	 * @param $args
	 * @param $feed_id
	 *
	 * @return bool|WP_Error
	 */
	public function update_form_selection( $args, $feed_id ) {
		if ( empty( $feed_id ) ) {
			return BWFCRM_Common::crm_error( __( 'Empty Feed ID provided', 'wp-marketing-automations-crm' ) );
		}

		$form_id = isset( $args['form_id'] ) && ! empty( $args['form_id'] ) ? $args['form_id'] : false;
		$feed    = new BWFCRM_Form_Feed( $feed_id );
		if ( ! $feed->is_feed_exists() ) {
			return BWFCRM_Common::crm_error( __( 'Feed with ID not exists: ' . $feed_id, 'wp-marketing-automations-crm' ) );
		}

		if ( empty( $form_id ) && $this->source === $feed->get_source() ) {
			return false;
		}

		$feed->unset_data( 'form_id' );
		$feed->get_source() !== $this->source && $feed->set_source( $this->source );
		! empty( $form_id ) && $feed->set_data( 'form_id', $form_id );

		return ! ! $feed->save( true );
	}
}


	BWFCRM_Core()->forms->register( 'forminator', 'BWFCRM_Form_Forminator', 'Forminator', array(
		'forminator_form_submit'
	) );
