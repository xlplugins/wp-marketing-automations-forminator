<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


/**
 * Optin Forms Detection
 */
if ( ! function_exists( 'bwfan_is_forminator_forms_active' ) ) {
	function bwfan_is_forminator_forms_active() {
		$active_plugins =  (array) get_option( 'active_plugins', array() );
		if ( class_exists( 'Forminator' ) ) {
			return true;
		}

		return in_array( 'forminator/forminator.php', $active_plugins, true ) || array_key_exists( 'forminator/forminator.php', $active_plugins );
	}
}
