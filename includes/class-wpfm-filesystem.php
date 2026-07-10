<?php
/**
 * Filesystem helper – all path handling, sandboxing, and file operations.
 *
 * Every path coming from a request MUST be normalised through
 * WPFM_Filesystem::resolve() before any filesystem call. This prevents
 * directory-traversal (e.g. "../../etc/passwd") and keeps the plugin
 * restricted to the configured root path.
 *
 * @package WP_File_Manager
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class WPFM_Filesystem {

	/**
	 * Absolute, real path of the sandbox root (no trailing slash).
	 *
	 * @var string
	 */
	protected $root;

	/**
	 * Extensions that must never be uploaded / created.
	 *
	 * @var array
	 */
	protected $blocked_extensions;

	public function __construct() {
		$settings                 = get_option( 'wpfm_settings', array() );
		$root                     = isset( $settings['root_path'] ) ? $settings['root_path'] : ABSPATH;
		$this->root               = untrailingslashit( wp_normalize_path( realpath( $root ) ?: $root ) );
		$this->blocked_extensions = isset( $settings['blocked_extensions'] )
			? array_map( 'strtolower', (array) $settings['blocked_extensions'] )
			: array( 'php', 'phtml', 'phar', 'sh', 'exe' );
	}

	/**
	 * Sandbox root (absolute path).
	 */
	public function get_root() {
		return $this->root;
	}

	/**
	 * Resolve a relative path against the sandbox root and make sure the
	 * result stays inside it. Returns absolute path or WP_Error.
	 *
	 * @param string $relative Relative path, "" or "/" for root.
	 * @param bool   $must_exist Whether the target has to already exist.
	 * @return string|WP_Error
	 */
	public function resolve( $relative, $must_exist = true ) {
		$relative = (string) $relative;
		$relative = wp_normalize_path( $relative );
		$relative = ltrim( $relative, '/' );

		// Explicitly reject traversal segments – belt & braces.
		$parts = explode( '/', $relative );
		foreach ( $parts as $part ) {
			if ( '..' === $part ) {
				return new WP_Error( 'wpfm_invalid_path', __( 'Path traversal is not allowed.', 'wp-file-manager' ) );
			}
		}

		$candidate = '' === $relative ? $this->root : $this->root . '/' . $relative;
		$candidate = wp_normalize_path( $candidate );

		if ( $must_exist ) {
			$real = realpath( $candidate );
			if ( false === $real ) {
				return new WP_Error( 'wpfm_not_found', __( 'File or folder not found.', 'wp-file-manager' ) );
			}
			$real = wp_normalize_path( $real );
		} else {
			// Resolve parent, then re-append basename – target may not exist yet.
			$parent = dirname( $candidate );
			$real_parent = realpath( $parent );
			if ( false === $real_parent ) {
				return new WP_Error( 'wpfm_not_found', __( 'Parent folder does not exist.', 'wp-file-manager' ) );
			}
			$real = wp_normalize_path( $real_parent ) . '/' . basename( $candidate );
		}

		if ( 0 !== strpos( $real, $this->root ) ) {
			return new WP_Error( 'wpfm_out_of_sandbox', __( 'Access outside of the allowed root is forbidden.', 'wp-file-manager' ) );
		}

		return $real;
	}

	/**
	 * Return the relative path of an absolute path (for display).
	 */
	public function to_relative( $absolute ) {
		$absolute = wp_normalize_path( $absolute );
		if ( 0 === strpos( $absolute, $this->root ) ) {
			return ltrim( substr( $absolute, strlen( $this->root ) ), '/' );
		}
		return $absolute;
	}

	/**
	 * List a directory. Returns array of entries.
	 */
	public function list_directory( $relative ) {
		$dir = $this->resolve( $relative, true );
		if ( is_wp_error( $dir ) ) {
			return $dir;
		}
		if ( ! is_dir( $dir ) ) {
			return new WP_Error( 'wpfm_not_dir', __( 'Not a directory.', 'wp-file-manager' ) );
		}

		$entries = array();
		$dh      = @opendir( $dir );
		if ( ! $dh ) {
			return new WP_Error( 'wpfm_read_failed', __( 'Unable to read directory.', 'wp-file-manager' ) );
		}
		while ( false !== ( $entry = readdir( $dh ) ) ) {
			if ( '.' === $entry || '..' === $entry ) {
				continue;
			}
			$full     = $dir . '/' . $entry;
			$is_dir   = is_dir( $full );
			$stat     = @stat( $full );
			$entries[] = array(
				'name'     => $entry,
				'path'     => $this->to_relative( $full ),
				'is_dir'   => $is_dir,
				'size'     => $is_dir ? 0 : ( $stat ? (int) $stat['size'] : 0 ),
				'modified' => $stat ? (int) $stat['mtime'] : 0,
				'perms'    => $stat ? substr( sprintf( '%o', $stat['mode'] ), -4 ) : '',
				'ext'      => $is_dir ? '' : strtolower( pathinfo( $entry, PATHINFO_EXTENSION ) ),
			);
		}
		closedir( $dh );

		// Sort: folders first, then alphabetical.
		usort(
			$entries,
			function ( $a, $b ) {
				if ( $a['is_dir'] !== $b['is_dir'] ) {
					return $a['is_dir'] ? -1 : 1;
				}
				return strcasecmp( $a['name'], $b['name'] );
			}
		);

		return $entries;
	}

	/**
	 * Create a directory.
	 */
	public function make_directory( $relative_parent, $name ) {
		$name = $this->sanitize_name( $name );
		if ( is_wp_error( $name ) ) {
			return $name;
		}
		$parent = $this->resolve( $relative_parent, true );
		if ( is_wp_error( $parent ) ) {
			return $parent;
		}
		$target = $parent . '/' . $name;
		if ( file_exists( $target ) ) {
			return new WP_Error( 'wpfm_exists', __( 'A file or folder with that name already exists.', 'wp-file-manager' ) );
		}
		if ( ! wp_mkdir_p( $target ) ) {
			return new WP_Error( 'wpfm_mkdir_failed', __( 'Could not create the folder.', 'wp-file-manager' ) );
		}
		return $this->to_relative( $target );
	}

	/**
	 * Rename or move a file/folder within the sandbox.
	 */
	public function rename( $relative, $new_name ) {
		$new_name = $this->sanitize_name( $new_name );
		if ( is_wp_error( $new_name ) ) {
			return $new_name;
		}
		$source = $this->resolve( $relative, true );
		if ( is_wp_error( $source ) ) {
			return $source;
		}
		if ( $source === $this->root ) {
			return new WP_Error( 'wpfm_root_protected', __( 'The root folder cannot be renamed.', 'wp-file-manager' ) );
		}
		$target = dirname( $source ) . '/' . $new_name;
		if ( file_exists( $target ) ) {
			return new WP_Error( 'wpfm_exists', __( 'Target already exists.', 'wp-file-manager' ) );
		}
		if ( ! @rename( $source, $target ) ) {
			return new WP_Error( 'wpfm_rename_failed', __( 'Rename failed.', 'wp-file-manager' ) );
		}
		return $this->to_relative( $target );
	}

	/**
	 * Delete a file or folder (recursive).
	 */
	public function delete( $relative ) {
		$target = $this->resolve( $relative, true );
		if ( is_wp_error( $target ) ) {
			return $target;
		}
		if ( $target === $this->root ) {
			return new WP_Error( 'wpfm_root_protected', __( 'The root folder cannot be deleted.', 'wp-file-manager' ) );
		}
		return $this->recursive_delete( $target );
	}

	protected function recursive_delete( $path ) {
		if ( is_link( $path ) || is_file( $path ) ) {
			if ( ! @unlink( $path ) ) {
				return new WP_Error( 'wpfm_delete_failed', __( 'Could not delete file.', 'wp-file-manager' ) );
			}
			return true;
		}
		if ( is_dir( $path ) ) {
			$items = scandir( $path );
			foreach ( $items as $item ) {
				if ( '.' === $item || '..' === $item ) {
					continue;
				}
				$res = $this->recursive_delete( $path . '/' . $item );
				if ( is_wp_error( $res ) ) {
					return $res;
				}
			}
			if ( ! @rmdir( $path ) ) {
				return new WP_Error( 'wpfm_delete_failed', __( 'Could not remove folder.', 'wp-file-manager' ) );
			}
			return true;
		}
		return true;
	}

	/**
	 * Read a text file (bounded size).
	 */
	public function read_file( $relative, $max_bytes = 2097152 ) {
		$file = $this->resolve( $relative, true );
		if ( is_wp_error( $file ) ) {
			return $file;
		}
		if ( ! is_file( $file ) ) {
			return new WP_Error( 'wpfm_not_file', __( 'Not a file.', 'wp-file-manager' ) );
		}
		if ( filesize( $file ) > $max_bytes ) {
			return new WP_Error( 'wpfm_too_large', __( 'File too large to edit in the browser.', 'wp-file-manager' ) );
		}
		$contents = file_get_contents( $file );
		if ( false === $contents ) {
			return new WP_Error( 'wpfm_read_failed', __( 'Could not read file.', 'wp-file-manager' ) );
		}
		return $contents;
	}

	/**
	 * Write text to a file (creates if missing).
	 */
	public function write_file( $relative, $contents ) {
		$file = $this->resolve( $relative, false );
		if ( is_wp_error( $file ) ) {
			return $file;
		}
		if ( $this->is_blocked_extension( $file ) ) {
			return new WP_Error( 'wpfm_blocked_ext', __( 'This file type cannot be created or modified.', 'wp-file-manager' ) );
		}
		if ( false === file_put_contents( $file, $contents ) ) {
			return new WP_Error( 'wpfm_write_failed', __( 'Could not write file.', 'wp-file-manager' ) );
		}
		return $this->to_relative( $file );
	}

	/**
	 * Handle an uploaded file (from $_FILES entry).
	 */
	public function upload( $relative_dir, array $file ) {
		$dir = $this->resolve( $relative_dir, true );
		if ( is_wp_error( $dir ) ) {
			return $dir;
		}
		if ( ! is_dir( $dir ) ) {
			return new WP_Error( 'wpfm_not_dir', __( 'Upload destination must be a folder.', 'wp-file-manager' ) );
		}
		if ( empty( $file['name'] ) || ! isset( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
			return new WP_Error( 'wpfm_invalid_upload', __( 'Invalid upload.', 'wp-file-manager' ) );
		}
		if ( isset( $file['error'] ) && UPLOAD_ERR_OK !== (int) $file['error'] ) {
			return new WP_Error( 'wpfm_upload_error', __( 'Upload error.', 'wp-file-manager' ) );
		}

		$name = $this->sanitize_name( $file['name'] );
		if ( is_wp_error( $name ) ) {
			return $name;
		}

		$target = $dir . '/' . $name;
		if ( $this->is_blocked_extension( $target ) ) {
			return new WP_Error( 'wpfm_blocked_ext', __( 'This file type is not allowed.', 'wp-file-manager' ) );
		}
		if ( file_exists( $target ) ) {
			// Add "-1", "-2"… suffix until unique.
			$info = pathinfo( $name );
			$base = $info['filename'];
			$ext  = isset( $info['extension'] ) ? '.' . $info['extension'] : '';
			$i    = 1;
			do {
				$target = $dir . '/' . $base . '-' . $i . $ext;
				$i++;
			} while ( file_exists( $target ) );
		}

		if ( ! @move_uploaded_file( $file['tmp_name'], $target ) ) {
			return new WP_Error( 'wpfm_move_failed', __( 'Could not move uploaded file.', 'wp-file-manager' ) );
		}
		@chmod( $target, 0644 );
		return $this->to_relative( $target );
	}

	/**
	 * Stream a file to the browser as a download.
	 */
	public function stream_download( $relative ) {
		$file = $this->resolve( $relative, true );
		if ( is_wp_error( $file ) ) {
			return $file;
		}
		if ( ! is_file( $file ) ) {
			return new WP_Error( 'wpfm_not_file', __( 'Not a file.', 'wp-file-manager' ) );
		}

		nocache_headers();
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename="' . basename( $file ) . '"' );
		header( 'Content-Length: ' . filesize( $file ) );
		header( 'X-Content-Type-Options: nosniff' );
		@ob_end_clean();
		readfile( $file );
		exit;
	}

	/**
	 * Sanitize a file/folder name.
	 */
	public function sanitize_name( $name ) {
		$name = wp_basename( (string) $name );
		$name = sanitize_file_name( $name );
		if ( '' === $name || '.' === $name || '..' === $name ) {
			return new WP_Error( 'wpfm_invalid_name', __( 'Invalid name.', 'wp-file-manager' ) );
		}
		return $name;
	}

	/**
	 * Check if the path has a blocked extension.
	 */
	public function is_blocked_extension( $path ) {
		$ext = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );
		return in_array( $ext, $this->blocked_extensions, true );
	}

	/**
	 * Breadcrumb array for a relative path.
	 */
	public function breadcrumbs( $relative ) {
		$relative = ltrim( wp_normalize_path( (string) $relative ), '/' );
		$crumbs   = array( array( 'name' => __( 'Root', 'wp-file-manager' ), 'path' => '' ) );
		if ( '' === $relative ) {
			return $crumbs;
		}
		$parts = explode( '/', $relative );
		$acc   = '';
		foreach ( $parts as $part ) {
			$acc     = '' === $acc ? $part : $acc . '/' . $part;
			$crumbs[] = array( 'name' => $part, 'path' => $acc );
		}
		return $crumbs;
	}
}
