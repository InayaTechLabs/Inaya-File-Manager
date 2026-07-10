// openEditor ফাংশনের ভিতরে CodeMirror ইনিশিয়ালাইজ করার অংশ
if (!state.cmEditor && window.wp && window.wp.codeEditor) {
    var settings = window.wp.codeEditor.defaultSettings || {};
    state.cmEditor = window.wp.codeEditor.initialize($editorTextarea[0], settings).codemirror;
} else if (state.cmEditor) {
    state.cmEditor.setValue(resp.data.contents);
}