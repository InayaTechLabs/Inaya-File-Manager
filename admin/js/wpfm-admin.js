/* global jQuery, WPFM */
(function ($) {
	'use strict';

	var state = {
		currentPath: '',
		editingPath: null,
		cmEditor: null
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
			if (state.cmEditor) {
				$editorTextarea.val(state.cmEditor.getValue());
			}
			busy(WPFM.i18n.loading);
			ajax('save', { path: state.editingPath, contents: $editorTextarea.val() })
				.done(function () { ok(WPFM.i18n.saved); });
		});

		$('#wpfm-editor-undo').on('click', function () {
			if (state.cmEditor) { state.cmEditor.undo(); }
		});
		$('#wpfm-editor-redo').on('click', function () {
			if (state.cmEditor) { state.cmEditor.redo(); }
		});
	}

	function openEditor(path) {
		busy(WPFM.i18n.loading);
		ajax('read', { path: path }).done(function (resp) {
			state.editingPath = path;
			$editorTitle.text(WPFM.i18n.editing.replace('%s', path));
			$editorTextarea.val(resp.data.contents);
			$editor.prop('hidden', false);

			if (!state.cmEditor && window.wp && window.wp.codeEditor) {
				var settings = window.wp.codeEditor.defaultSettings || {};
				state.cmEditor = window.wp.codeEditor.initialize($editorTextarea[0], settings).codemirror;
			} else if (state.cmEditor) {
				state.cmEditor.setValue(resp.data.contents);
			}

			$('html, body').animate({ scrollTop: $editor.offset().top - 40 }, 250);
			clearStatus();
		});
	}

	function closeEditor() {
		state.editingPath = null;
		$editor.prop('hidden', true);
		$editorTextarea.val('');
	}

	// ... (বাকি কোড অপরিবর্তিত রয়েছে)
}(jQuery));