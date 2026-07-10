/* global jQuery, WPFM */
(function ($) {
	'use strict';

	var state = {
		currentPath: '',
		editingPath: null
	};

	var $tbody, $crumbs, $status, $editor, $editorTitle, $editorTextarea, $uploadInput, $panel;

	$(function () {
		$tbody          = $('#wpfm-tbody');
		$crumbs         = $('#wpfm-breadcrumbs');
		$status         = $('#wpfm-status');
		$editor         = $('#wpfm-editor');
		$editorTitle    = $('#wpfm-editor-title');
		$editorTextarea = $('#wpfm-editor-textarea');
		$uploadInput    = $('#wpfm-upload-input');
		$panel          = $('.wpfm-panel');

		bindToolbar();
		bindEditor();
		bindDropZone();
		loadDir('');
	});

	// ---------- Toolbar ----------

	function bindToolbar() {
		$('#wpfm-refresh').on('click', function () { loadDir(state.currentPath); });

		$('#wpfm-new-folder').on('click', function () {
			var name = window.prompt(WPFM.i18n.promptFolder, '');
			if (!name) { return; }
			ajax('mkdir', { path: state.currentPath, name: name })
				.done(function () { loadDir(state.currentPath); ok(); });
		});

		$('#wpfm-new-file').on('click', function () {
			var name = window.prompt(WPFM.i18n.promptFile, 'new-file.txt');
			if (!name) { return; }
			var newPath = state.currentPath ? state.currentPath + '/' + name : name;
			ajax('save', { path: newPath, contents: '' })
				.done(function () { loadDir(state.currentPath); ok(); });
		});

		$uploadInput.on('change', function (e) {
			var files = e.target.files;
			if (!files || !files.length) { return; }
			uploadFiles(files);
			$uploadInput.val('');
		});
	}

	// ---------- Editor ----------

	function bindEditor() {
		$('#wpfm-editor-cancel').on('click', closeEditor);
		$('#wpfm-editor-save').on('click', function () {
			if (state.editingPath === null) { return; }
			busy(WPFM.i18n.loading);
			ajax('save', { path: state.editingPath, contents: $editorTextarea.val() })
				.done(function () { ok(WPFM.i18n.saved); });
		});
	}

	function openEditor(path) {
		busy(WPFM.i18n.loading);
		ajax('read', { path: path }).done(function (resp) {
			state.editingPath = path;
			$editorTitle.text(WPFM.i18n.editing.replace('%s', path));
			$editorTextarea.val(resp.data.contents);
			$editor.prop('hidden', false);
			$('html, body').animate({ scrollTop: $editor.offset().top - 40 }, 250);
			clearStatus();
		});
	}

	function closeEditor() {
		state.editingPath = null;
		$editor.prop('hidden', true);
		$editorTextarea.val('');
	}

	// ---------- Drag & Drop ----------

	function bindDropZone() {
		var el = $panel[0];
		['dragenter', 'dragover'].forEach(function (ev) {
			el.addEventListener(ev, function (e) {
				e.preventDefault(); e.stopPropagation();
				$panel.addClass('wpfm-dropzone-active');
			});
		});
		['dragleave', 'drop'].forEach(function (ev) {
			el.addEventListener(ev, function (e) {
				e.preventDefault(); e.stopPropagation();
				$panel.removeClass('wpfm-dropzone-active');
			});
		});
		el.addEventListener('drop', function (e) {
			if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length) {
				uploadFiles(e.dataTransfer.files);
			}
		});
	}

	// ---------- Directory listing ----------

	function loadDir(path) {
		busy(WPFM.i18n.loading);
		ajax('list', { path: path }).done(function (resp) {
			state.currentPath = resp.data.path;
			renderCrumbs(resp.data.breadcrumbs);
			renderEntries(resp.data.entries);
			clearStatus();
		});
	}

	function renderCrumbs(crumbs) {
		$crumbs.empty();
		crumbs.forEach(function (c, i) {
			if (i > 0) { $crumbs.append('<span class="sep">/</span>'); }
			var $a = $('<a href="#"></a>').text(c.name).on('click', function (e) {
				e.preventDefault();
				loadDir(c.path);
			});
			$crumbs.append($a);
		});
	}

	function renderEntries(entries) {
		$tbody.empty();
		if (!entries.length) {
			$tbody.append('<tr><td colspan="5"><em>' + escapeHtml(WPFM.i18n.empty) + '</em></td></tr>');
			return;
		}
		entries.forEach(function (e) {
			$tbody.append(renderRow(e));
		});
	}

	function renderRow(entry) {
		var $tr = $('<tr></tr>').data('entry', entry);

		// Name cell
		var $name = $('<button type="button" class="wpfm-entry-name"></button>');
		var icon = entry.is_dir ? 'dashicons-portfolio' : iconForExt(entry.ext);
		$name.append('<span class="dashicons ' + icon + ' wpfm-icon ' + (entry.is_dir ? 'is-dir' : 'is-file') + '"></span>');
		$name.append(document.createTextNode(entry.name));
		if (entry.is_dir) {
			$name.on('click', function () { loadDir(entry.path); });
		} else {
			$name.addClass('is-file');
		}
		$('<td class="wpfm-col-name"></td>').append($name).appendTo($tr);

		$('<td class="wpfm-col-size"></td>').text(entry.is_dir ? '—' : formatSize(entry.size)).appendTo($tr);
		$('<td class="wpfm-col-mod"></td>').text(formatDate(entry.modified)).appendTo($tr);
		$('<td class="wpfm-col-perms"></td>').text(entry.perms || '').appendTo($tr);

		// Actions
		var $actions = $('<td class="wpfm-col-actions wpfm-row-actions"></td>');
		if (!entry.is_dir) {
			if (isTextExt(entry.ext)) {
				$('<button type="button" class="button-link"></button>')
					.text(WPFM.i18n.edit)
					.on('click', function () { openEditor(entry.path); })
					.appendTo($actions);
			}
			$('<a class="button-link"></a>')
				.attr('href', downloadUrl(entry.path))
				.text(WPFM.i18n.download)
				.appendTo($actions);
		}
		$('<button type="button" class="button-link"></button>')
			.text(WPFM.i18n.rename)
			.on('click', function () {
				var name = window.prompt(WPFM.i18n.promptRename, entry.name);
				if (!name || name === entry.name) { return; }
				ajax('rename', { path: entry.path, new_name: name })
					.done(function () { loadDir(state.currentPath); ok(); });
			}).appendTo($actions);
		$('<button type="button" class="button-link is-danger"></button>')
			.text(WPFM.i18n.delete)
			.on('click', function () {
				if (!window.confirm(WPFM.i18n.confirmDelete.replace('%s', entry.name))) { return; }
				ajax('delete', { path: entry.path })
					.done(function () {
						if (state.editingPath === entry.path) { closeEditor(); }
						loadDir(state.currentPath);
						ok();
					});
			}).appendTo($actions);
		$actions.appendTo($tr);

		return $tr;
	}

	// ---------- Upload ----------

	function uploadFiles(fileList) {
		var files = Array.prototype.slice.call(fileList);
		var total = files.length;
		var done = 0;
		busy(WPFM.i18n.uploading + ' (0/' + total + ')');
		(function next() {
			if (!files.length) { loadDir(state.currentPath); ok(); return; }
			var file = files.shift();
			var fd = new FormData();
			fd.append('action', 'wpfm_upload');
			fd.append('nonce', WPFM.nonce);
			fd.append('path', state.currentPath);
			fd.append('file', file);
			$.ajax({
				url: WPFM.ajaxUrl,
				method: 'POST',
				data: fd,
				processData: false,
				contentType: false
			}).done(function (resp) {
				if (!resp || !resp.success) {
					err((resp && resp.data && resp.data.message) || WPFM.i18n.error);
				}
			}).fail(function (xhr) {
				err(readErr(xhr));
			}).always(function () {
				done++;
				busy(WPFM.i18n.uploading + ' (' + done + '/' + total + ')');
				next();
			});
		})();
	}

	// ---------- AJAX helper ----------

	function ajax(action, data) {
		var payload = $.extend({ action: 'wpfm_' + action, nonce: WPFM.nonce }, data || {});
		return $.post(WPFM.ajaxUrl, payload)
			.fail(function (xhr) { err(readErr(xhr)); })
			.then(function (resp) {
				if (!resp || !resp.success) {
					var msg = (resp && resp.data && resp.data.message) || WPFM.i18n.error;
					err(msg);
					return $.Deferred().reject(msg).promise();
				}
				return resp;
			});
	}

	function readErr(xhr) {
		try {
			var j = JSON.parse(xhr.responseText);
			return (j && j.data && j.data.message) || WPFM.i18n.error;
		} catch (e) { return WPFM.i18n.error; }
	}

	function downloadUrl(path) {
		return WPFM.ajaxUrl + '?action=wpfm_download&nonce=' + encodeURIComponent(WPFM.nonce) + '&path=' + encodeURIComponent(path);
	}

	// ---------- Status ----------

	function busy(msg) { $status.removeClass('is-error is-ok').text(msg || ''); }
	function ok(msg)   { $status.removeClass('is-error').addClass('is-ok').text(msg || ''); setTimeout(clearStatus, 2000); }
	function err(msg)  { $status.removeClass('is-ok').addClass('is-error').text(msg || WPFM.i18n.error); }
	function clearStatus() { $status.removeClass('is-error is-ok').text(''); }

	// ---------- Utilities ----------

	function formatSize(bytes) {
		if (bytes < 1024) { return bytes + ' B'; }
		var units = ['KB', 'MB', 'GB', 'TB'];
		var i = -1;
		do { bytes /= 1024; i++; } while (bytes >= 1024 && i < units.length - 1);
		return bytes.toFixed(1) + ' ' + units[i];
	}

	function formatDate(ts) {
		if (!ts) { return ''; }
		var d = new Date(ts * 1000);
		var pad = function (n) { return n < 10 ? '0' + n : '' + n; };
		return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) +
			' ' + pad(d.getHours()) + ':' + pad(d.getMinutes());
	}

	function iconForExt(ext) {
		if (['jpg','jpeg','png','gif','webp','svg','bmp','ico'].indexOf(ext) !== -1) { return 'dashicons-format-image'; }
		if (['mp4','mov','avi','mkv','webm'].indexOf(ext) !== -1) { return 'dashicons-format-video'; }
		if (['mp3','wav','ogg','flac','m4a'].indexOf(ext) !== -1) { return 'dashicons-format-audio'; }
		if (['zip','tar','gz','rar','7z'].indexOf(ext) !== -1) { return 'dashicons-media-archive'; }
		if (['pdf'].indexOf(ext) !== -1) { return 'dashicons-pdf'; }
		if (isTextExt(ext)) { return 'dashicons-media-text'; }
		return 'dashicons-media-default';
	}

	function isTextExt(ext) {
		return [
			'txt','md','markdown','html','htm','css','scss','less','js','jsx','ts','tsx',
			'json','xml','yml','yaml','ini','conf','log','csv','tsv','sql','htaccess',
			'sh','bash','py','rb','java','c','cpp','h','hpp','go','rs','swift','kt',
			'vue','svelte','env'
		].indexOf((ext || '').toLowerCase()) !== -1;
	}

	function escapeHtml(s) {
		return String(s).replace(/[&<>"']/g, function (c) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
		});
	}
}(jQuery));
