=== WP File Manager ===
Contributors: inayatechlabs
Tags: file manager, files, filesystem, admin, upload, editor
Requires at least: 5.6
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A lightweight, secure file manager for WordPress. Browse, upload, edit, download, rename, and delete files from the admin dashboard.

== Description ==

WP File Manager gives administrators a simple interface for managing files inside the WordPress installation directory without needing FTP or SSH access.

Features:

* Browse folders with a breadcrumb navigation.
* Upload files via button or drag-and-drop (multiple files supported).
* Create new folders and text files.
* Rename and delete files/folders (recursive delete for folders).
* In-browser text editor for common text file types (up to 2 MB).
* Direct download for any file.
* Sandboxed to the WordPress root — all paths are validated against directory traversal.
* Blocks creation/upload of dangerous executable extensions (`php`, `phtml`, `phar`, `sh`, `exe`) by default.
* Restricted to users with the `manage_options` capability.
* All AJAX actions are nonce-protected.

== Installation ==

1. Upload the `wp-file-manager` folder to `/wp-content/plugins/`.
2. Activate the plugin through the "Plugins" menu in WordPress.
3. Open **File Manager** in the admin sidebar.

== Frequently Asked Questions ==

= Who can use the file manager? =

Only users with the `manage_options` capability (typically administrators).

= Can I change the root folder? =

Yes. Update the `wpfm_settings` option in the database — set `root_path` to any absolute path on the server. Everything the plugin does is confined to that folder.

= Is it safe? =

The plugin enforces several safeguards: capability checks, WordPress nonces on every request, path resolution with `realpath` plus explicit traversal rejection, and a blocklist of dangerous extensions. That said, any file manager is powerful — install it only if you trust the site's administrators.

== Changelog ==

= 1.0.0 =
* Initial release.
