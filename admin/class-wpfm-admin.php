<?php
/**
 * Admin side: menus, assets, and page rendering.
 *
 * @package WP_File_Manager
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class WPFM_Admin {

	const MENU_SLUG = 'wp-file-manager';
	const CAPABILITY = 'manage_options';

	public function register_hooks() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'plugin_action_links_' . WPFM_PLUGIN_BASENAME, array( $this, 'action_links' ) );
	}

	public function add_menu() {
		add_menu_page(
			__( 'File Manager', 'wp-file-manager' ),
			__( 'File Manager', 'wp-file-manager' ),
			self::CAPABILITY,
			self::MENU_SLUG,
			array( $this, 'render_page' ),
			'dashicons-open-folder',
			76
		);
	}

	public function action_links( $links ) {
		$url  = admin_url( 'admin.php?page=' . self::MENU_SLUG );
		$open = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Open', 'wp-file-manager' ) . '</a>';
		array_unshift( $links, $open );
		return $links;
	}

	public function enqueue_assets( $hook ) {
		if ( 'toplevel_page_' . self::MENU_SLUG !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'wpfm-admin',
			WPFM_PLUGIN_URL . 'admin/css/wpfm-admin.css',
			array( 'dashicons' ),
			WPFM_VERSION
		);

		wp_enqueue_script(
			'wpfm-admin',
			WPFM_PLUGIN_URL . 'admin/js/wpfm-admin.js',
			array( 'jquery' ),
			WPFM_VERSION,
			true
		);

		wp_localize_script(
			'wpfm-admin',
			'WPFM',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'wpfm_nonce' ),
				'i18n'    => array(
					'confirmDelete' => __( 'Are you sure you want to delete "%s"? This cannot be undone.', 'wp-file-manager' ),
					'promptFolder'  => __( 'New folder name:', 'wp-file-manager' ),
					'promptRename'  => __( 'New name:', 'wp-file-manager' ),
					'promptFile'    => __( 'New file name:', 'wp-file-manager' ),
					'empty'         => __( 'This folder is empty.', 'wp-file-manager' ),
					'loading'       => __( 'Loading…', 'wp-file-manager' ),
					'error'         => __( 'Error', 'wp-file-manager' ),
					'saved'         => __( 'Saved.', 'wp-file-manager' ),
					'uploading'     => __( 'Uploading…', 'wp-file-manager' ),
					'name'          => __( 'Name', 'wp-file-manager' ),
					'size'          => __( 'Size', 'wp-file-manager' ),
					'modified'      => __( 'Modified', 'wp-file-manager' ),
					'perms'         => __( 'Perms', 'wp-file-manager' ),
					'actions'       => __( 'Actions', 'wp-file-manager' ),
					'download'      => __( 'Download', 'wp-file-manager' ),
					'edit'          => __( 'Edit', 'wp-file-manager' ),
					'rename'        => __( 'Rename', 'wp-file-manager' ),
					'delete'        => __( 'Delete', 'wp-file-manager' ),
					'cancel'        => __( 'Cancel', 'wp-file-manager' ),
					'save'          => __( 'Save', 'wp-file-manager' ),
					'editing'       => __( 'Editing: %s', 'wp-file-manager' ),
				),
			)
		);
	}

	public function render_page() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'wp-file-manager' ) );
		}
		include WPFM_PLUGIN_DIR . 'admin/partials/wpfm-admin-page.php';
	}
}
