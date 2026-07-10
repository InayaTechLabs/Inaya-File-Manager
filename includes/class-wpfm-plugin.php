<?php
/**
 * Main plugin bootstrap class.
 *
 * @package Inaya_File_Manager
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class WPFM_Plugin {

	public function run() {
		$admin = new WPFM_Admin();
		$admin->register_hooks();

		$ajax = new WPFM_Ajax();
		$ajax->register_hooks();
	}
}