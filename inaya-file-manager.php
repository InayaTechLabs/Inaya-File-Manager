<?php
/**
 * Plugin Name:       WP File Manager
 * Plugin URI:        https://github.com/InayaTechLabs/WP-Files
	
 * Description:       A lightweight, secure file manager for WordPress. Browse, upload, download, rename, and delete files inside your WordPress installation from the admin dashboard.
	
 * Version:           1.0.0
 * Requires at least: 5.6
 * Requires PHP:      7.4
 * Author:            Inaya Tech Labs
 * Author URI:        https://github.com/InayaTechLabs
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-file-manager
 * Domain Path:       /languages
 *
 * @package WP_File_Manager
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Current plugin version.
 */
define( 'WPFM_VERSION', '1.0.0' );
define( 'WPFM_PLUGIN_FILE', __FILE__ );
define( 'WPFM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPFM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WPFM_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * The core plugin class.
 */
require_once WPFM_PLUGIN_DIR . 'includes/class-wpfm-plugin.php';
require_once WPFM_PLUGIN_DIR . 'includes/class-wpfm-filesystem.php';
require_once WPFM_PLUGIN_DIR . 'includes/class-wpfm-ajax.php';
require_once WPFM_PLUGIN_DIR . 'admin/class-wpfm-admin.php';

/**
 * Activation hook.
 */
function wpfm_activate() {
	// Reserve for future setup (options, capabilities, etc.).
	if ( ! get_option( 'wpfm_settings' ) ) {
		add_option(
			'wpfm_settings',
			array(
				'root_path'         => ABSPATH,
				'max_upload_size'   => wp_max_upload_size(),
				'allowed_roles'     => array( 'administrator' ),
				'blocked_extensions' => array( 'php', 'phtml', 'phar', 'sh', 'exe' ),
			)
		);
	}
}
register_activation_hook( __FILE__, 'wpfm_activate' );

/**
 * Deactivation hook.
 */
function wpfm_deactivate() {
	// Intentionally left blank – keep user data on deactivation.
}
register_deactivation_hook( __FILE__, 'wpfm_deactivate' );

/**
 * Load plugin text domain.
 */
function wpfm_load_textdomain() {
	load_plugin_textdomain( 'wp-file-manager', false, dirname( WPFM_PLUGIN_BASENAME ) . '/languages' );
}
add_action( 'plugins_loaded', 'wpfm_load_textdomain' );

/**
 * Bootstrap the plugin.
 */
function wpfm_run() {
	$plugin = new WPFM_Plugin();
	$plugin->run();
}
wpfm_run();
