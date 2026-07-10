<?php
/**
 * Admin side: menus, assets, and page rendering.
 *
 * @package Inaya_File_Manager
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class WPFM_Admin {

	const MENU_SLUG = 'inaya-file-manager';
	const CAPABILITY = 'manage_options';

	public function register_hooks() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'plugin_action_links_' . WPFM_PLUGIN_BASENAME, array( $this, 'action_links' ) );
	}

	public function add_menu() {
		add_menu_page(
			__( 'File Manager', 'inaya-file-manager' ),
			__( 'File Manager', 'inaya-file-manager' ),
			self::CAPABILITY,
			self::MENU_SLUG,
			array( $this, 'render_page' ),
			'dashicons-open-folder',
			76
		);
	}

	public function action_links( $links ) {
		$url  = admin_url( 'admin.php?page=' . self::MENU_SLUG );
		$open = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Open', 'inaya-file-manager' ) . '</a>';
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
			array( 'jquery', 'wp-codemirror' ),
			WPFM_VERSION,
			true
		);

		// Enqueue built-in CodeMirror (WordPress core)
		wp_enqueue_code_editor( array( 'type' => 'text/plain' ) );

		wp_localize_script(
			'wpfm-admin',
			'WPFM',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'wpfm_nonce' ),
				'i18n'    => array(
					'confirmDelete' => __( 'Are you sure you want to delete \"%s\"? This cannot be undone.', 'inaya-file-manager' ),
					'promptFolder'  => __( 'New folder name:', 'inaya-file-manager' ),
					'promptRename'  => __( 'New name:', 'inaya-file-manager' ),
					'promptFile'    => __( 'New file name:', 'inaya-file-manager' ),
					'empty'         => __( 'This folder is empty.', 'inaya-file-manager' ),
					'loading'       => __( 'Loading…', 'inaya-file-manager' ),
					'error'         => __( 'Error', 'inaya-file-manager' ),
					'saved'         => __( 'Saved.', 'inaya-file-manager' ),
					'uploading'     => __( 'Uploading…', 'inaya-file-manager' ),
					'name'          => __( 'Name', 'inaya-file-manager' ),
					'size'          => __( 'Size', 'inaya-file-manager' ),
					'modified'      => __( 'Modified', 'inaya-file-manager' ),
					'perms'         => __( 'Perms', 'inaya-file-manager' ),
					'actions'       => __( 'Actions', 'inaya-file-manager' ),
					'download'      => __( 'Download', 'inaya-file-manager' ),
					'edit'          => __( 'Edit', 'inaya-file-manager' ),
					'rename'        => __( 'Rename', 'inaya-file-manager' ),
					'delete'        => __( 'Delete', 'inaya-file-manager' ),
					'cancel'        => __( 'Cancel', 'inaya-file-manager' ),
					'save'          => __( 'Save', 'inaya-file-manager' ),
					'editing'       => __( 'Editing: %s', 'inaya-file-manager' ),
				),
			)
		);
	}

	public function render_page() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'inaya-file-manager' ) );
		}
		include WPFM_PLUGIN_DIR . 'admin/partials/wpfm-admin-page.php';
	}
}