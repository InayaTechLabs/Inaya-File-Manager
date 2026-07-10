<?php
/**
 * AJAX handlers for WP File Manager.
 *
 * All actions are nonce-checked (nonce action: "wpfm_nonce") and require
 * the "manage_options" capability by default.
 *
 * @package WP_File_Manager
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class WPFM_Ajax {

	/**
	 * Required capability to use the plugin.
	 */
	const CAPABILITY = 'manage_options';

	public function register_hooks() {
		$actions = array(
			'list', 'mkdir', 'rename', 'delete', 'read', 'save', 'upload', 'download',
		);
		foreach ( $actions as $action ) {
			add_action( 'wp_ajax_wpfm_' . $action, array( $this, 'handle_' . $action ) );
		}
	}

	/**
	 * Common auth / nonce gate.
	 */
	protected function guard() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to do that.', 'wp-file-manager' ) ), 403 );
		}
		check_ajax_referer( 'wpfm_nonce', 'nonce' );
	}

	protected function fs() {
		return new WPFM_Filesystem();
	}

	protected function bail( $error ) {
		if ( is_wp_error( $error ) ) {
			wp_send_json_error( array( 'message' => $error->get_error_message() ), 400 );
		}
	}

	public function handle_list() {
		$this->guard();
		$path = isset( $_POST['path'] ) ? sanitize_text_field( wp_unslash( $_POST['path'] ) ) : '';
		$fs   = $this->fs();
		$list = $fs->list_directory( $path );
		$this->bail( $list );
		wp_send_json_success(
			array(
				'path'        => $path,
				'breadcrumbs' => $fs->breadcrumbs( $path ),
				'entries'     => $list,
			)
		);
	}

	public function handle_mkdir() {
		$this->guard();
		$path = isset( $_POST['path'] ) ? sanitize_text_field( wp_unslash( $_POST['path'] ) ) : '';
		$name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$res  = $this->fs()->make_directory( $path, $name );
		$this->bail( $res );
		wp_send_json_success( array( 'path' => $res ) );
	}

	public function handle_rename() {
		$this->guard();
		$path     = isset( $_POST['path'] ) ? sanitize_text_field( wp_unslash( $_POST['path'] ) ) : '';
		$new_name = isset( $_POST['new_name'] ) ? sanitize_text_field( wp_unslash( $_POST['new_name'] ) ) : '';
		$res      = $this->fs()->rename( $path, $new_name );
		$this->bail( $res );
		wp_send_json_success( array( 'path' => $res ) );
	}

	public function handle_delete() {
		$this->guard();
		$path = isset( $_POST['path'] ) ? sanitize_text_field( wp_unslash( $_POST['path'] ) ) : '';
		$res  = $this->fs()->delete( $path );
		$this->bail( $res );
		wp_send_json_success();
	}

	public function handle_read() {
		$this->guard();
		$path = isset( $_POST['path'] ) ? sanitize_text_field( wp_unslash( $_POST['path'] ) ) : '';
		$res  = $this->fs()->read_file( $path );
		$this->bail( $res );
		wp_send_json_success( array( 'contents' => $res ) );
	}

	public function handle_save() {
		$this->guard();
		$path     = isset( $_POST['path'] ) ? sanitize_text_field( wp_unslash( $_POST['path'] ) ) : '';
		// Deliberately do NOT sanitize file contents – it's raw text.
		$contents = isset( $_POST['contents'] ) ? wp_unslash( $_POST['contents'] ) : '';
		$res      = $this->fs()->write_file( $path, $contents );
		$this->bail( $res );
		wp_send_json_success( array( 'path' => $res ) );
	}

	public function handle_upload() {
		$this->guard();
		$path = isset( $_POST['path'] ) ? sanitize_text_field( wp_unslash( $_POST['path'] ) ) : '';
		if ( empty( $_FILES['file'] ) ) {
			wp_send_json_error( array( 'message' => __( 'No file uploaded.', 'wp-file-manager' ) ), 400 );
		}
		$res = $this->fs()->upload( $path, $_FILES['file'] );
		$this->bail( $res );
		wp_send_json_success( array( 'path' => $res ) );
	}

	public function handle_download() {
		// Download uses GET so the browser can navigate to it.
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You are not allowed to do that.', 'wp-file-manager' ), 403 );
		}
		check_admin_referer( 'wpfm_nonce', 'nonce' );
		$path = isset( $_GET['path'] ) ? sanitize_text_field( wp_unslash( $_GET['path'] ) ) : '';
		$res  = $this->fs()->stream_download( $path );
		if ( is_wp_error( $res ) ) {
			wp_die( esc_html( $res->get_error_message() ) );
		}
	}
}
