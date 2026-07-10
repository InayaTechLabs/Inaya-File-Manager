<?php
/**
 * Uninstall handler – runs when the plugin is deleted from the admin UI.
 *
 * @package WP_File_Manager
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

delete_option( 'wpfm_settings' );
