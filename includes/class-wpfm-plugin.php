<?php
/**
 * Main plugin bootstrap class.
 *
 * @package WP_File_Manager
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class WPFM_Plugin
 */
class WPFM_Plugin {

	/**
	 * Register hooks.
	 */
	public function run() {
		$admin = new WPFM_Admin();
		$admin->register_hooks();

		$ajax = new WPFM_Ajax();
		$ajax->register_hooks();
	}
}
