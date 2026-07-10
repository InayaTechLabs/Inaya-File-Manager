<?php
/**
 * Admin page markup.
 *
 * @package Inaya_File_Manager
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}
?>
<div class="wrap wpfm-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'File Manager', 'inaya-file-manager' ); ?></h1>

	<div class="wpfm-toolbar">
		<button type="button" class="button button-primary" id="wpfm-new-folder">
			<span class="dashicons dashicons-plus-alt2"></span>
			<?php esc_html_e( 'New Folder', 'inaya-file-manager' ); ?>
		</button>
		<button type="button" class="button" id="wpfm-new-file">
			<span class="dashicons dashicons-media-text"></span>
			<?php esc_html_e( 'New File', 'inaya-file-manager' ); ?>
		</button>
		<label class="button wpfm-upload-btn" for="wpfm-upload-input">
			<span class="dashicons dashicons-upload"></span>
			<?php esc_html_e( 'Upload', 'inaya-file-manager' ); ?>
		</label>
		<input type="file" id="wpfm-upload-input" multiple style="display:none" />
		<button type="button" class="button" id="wpfm-refresh">
			<span class="dashicons dashicons-update"></span>
			<?php esc_html_e( 'Refresh', 'inaya-file-manager' ); ?>
		</button>
		<span class="wpfm-status" id="wpfm-status" aria-live="polite"></span>
	</div>

	<nav class="wpfm-breadcrumbs" id="wpfm-breadcrumbs" aria-label="<?php esc_attr_e( 'Breadcrumbs', 'inaya-file-manager' ); ?>"></nav>

	<div class="wpfm-panel">
		<table class="widefat striped wpfm-table" id="wpfm-table">
			<thead>
				<tr>
					<th class="wpfm-col-name"><?php esc_html_e( 'Name', 'inaya-file-manager' ); ?></th>
					<th class="wpfm-col-size"><?php esc_html_e( 'Size', 'inaya-file-manager' ); ?></th>
					<th class="wpfm-col-mod"><?php esc_html_e( 'Modified', 'inaya-file-manager' ); ?></th>
					<th class="wpfm-col-perms"><?php esc_html_e( 'Perms', 'inaya-file-manager' ); ?></th>
					<th class="wpfm-col-actions"><?php esc_html_e( 'Actions', 'inaya-file-manager' ); ?></th>
				</tr>
			</thead>
			<tbody id="wpfm-tbody">
				<tr><td colspan="5"><?php esc_html_e( 'Loading…', 'inaya-file-manager' ); ?></td></tr>
			</tbody>
		</table>
	</div>

	<div class="wpfm-editor" id="wpfm-editor" hidden>
		<div class="wpfm-editor-header">
			<strong id="wpfm-editor-title"></strong>
			<span class="wpfm-editor-actions">
				<button type="button" class="button" id="wpfm-editor-undo" title="<?php esc_attr_e( 'Undo', 'inaya-file-manager' ); ?>">
					<span class="dashicons dashicons-undo"></span>
				</button>
				<button type="button" class="button" id="wpfm-editor-redo" title="<?php esc_attr_e( 'Redo', 'inaya-file-manager' ); ?>">
					<span class="dashicons dashicons-redo"></span>
				</button>
				<button type="button" class="button button-primary" id="wpfm-editor-save"><?php esc_html_e( 'Save', 'inaya-file-manager' ); ?></button>
				<button type="button" class="button" id="wpfm-editor-cancel"><?php esc_html_e( 'Cancel', 'inaya-file-manager' ); ?></button>
			</span>
		</div>
		<textarea id="wpfm-editor-textarea" spellcheck="false" wrap="off"></textarea>
	</div>

	<p class="description wpfm-footnote">
		<?php
		printf(
			/* translators: %s: absolute root path */
			esc_html__( 'Root: %s', 'inaya-file-manager' ),
			'<code>' . esc_html( ( new WPFM_Filesystem() )->get_root() ) . '</code>'
		);
		?>
	</p>
</div>