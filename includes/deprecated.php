<?php
/**
 * Includes deprecated functions
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! function_exists( 'qtsndtps_update_old_options' ) ) {
	/**
	 * Upgrade plugin options
	 *
	 * @deprecated since 1.3.5
	 * @todo remove after 20.03.2018
	 */
	function qtsndtps_update_old_options() {
		global $qtsndtps_options;

		$qtsndtps_options['custom_background_image'] = $qtsndtps_options['background_image'];

		$qtsndtps_options['additional_options'] = ( 1 === $qtsndtps_options['additional_options'] ) ? 0 : 1;
		$qtsndtps_options['background_image'] = ( 0 === $qtsndtps_options['background_image_use'] ) ? 'default' : 'custom';
		unset( $qtsndtps_options['background_image_use'] );
	}
}
