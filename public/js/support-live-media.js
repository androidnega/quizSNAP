/**
 * QuizSnap live support — shared avatar + media rendering and audio recording.
 */
(function () {
    'use strict';

    function escapeHtml(str) {
        var d = document.createElement('div');
        d.textContent = str == null ? '' : String(str);
        return d.innerHTML;
    }

    function renderAvatarHtml(avatar, className) {
        className = className || '';
        avatar = avatar || { type: 'vector', vector: 'headset' };
        if (avatar.type === 'emoji' && avatar.emoji) {
            return '<span class="' + escapeHtml(className) + ' qs-support-avatar qs-support-avatar--emoji" aria-hidden="true">' + escapeHtml(avatar.emoji) + '</span>';
        }
        var viewBox = avatar.viewBox || '0 0 24 24';
        var path = avatar.path || 'M4 12a8 8 0 0116 0v4a3 3 0 01-3 3h-2v-6h4v-2a6 6 0 00-12 0v2h4v6H7a3 3 0 01-3-3v-4z';
        return '<span class="' + escapeHtml(className) + ' qs-support-avatar qs-support-avatar--vector" aria-hidden="true">' +
            '<svg fill="none" viewBox="' + escapeHtml(viewBox) + '" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">' +
            '<path d="' + escapeHtml(path) + '"></path></svg></span>';
    }

    function appendMessageMedia(bubble, msg) {
        if (!bubble || !msg || !msg.meta) return false;
        if (msg.message_type === 'image' && msg.meta.url) {
            var img = document.createElement('img');
            img.src = msg.meta.url;
            img.alt = 'Shared image';
            img.className = 'qs-live-msg__image';
            img.loading = 'lazy';
            var link = document.createElement('a');
            link.href = msg.meta.url;
            link.target = '_blank';
            link.rel = 'noopener noreferrer';
            link.appendChild(img);
            bubble.appendChild(link);
            return true;
        }
        if (msg.message_type === 'audio' && msg.meta.url) {
            var audio = document.createElement('audio');
            audio.controls = true;
            audio.preload = 'metadata';
            audio.className = 'qs-live-msg__audio';
            audio.src = msg.meta.url;
            bubble.appendChild(audio);
            return true;
        }
        return false;
    }

    function createRecorder() {
        var recorder = null;
        var stream = null;
        var chunks = [];
        var recording = false;

        function stopTracks() {
            if (stream) {
                stream.getTracks().forEach(function (t) { t.stop(); });
                stream = null;
            }
        }

        return {
            isRecording: function () { return recording; },
            start: function () {
                if (recording || !navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    return Promise.reject(new Error('Microphone not available.'));
                }
                return navigator.mediaDevices.getUserMedia({ audio: true })
                    .then(function (s) {
                        stream = s;
                        chunks = [];
                        var mime = window.MediaRecorder && MediaRecorder.isTypeSupported('audio/webm;codecs=opus')
                            ? 'audio/webm;codecs=opus'
                            : (window.MediaRecorder && MediaRecorder.isTypeSupported('audio/webm') ? 'audio/webm' : '');
                        recorder = mime ? new MediaRecorder(s, { mimeType: mime }) : new MediaRecorder(s);
                        recorder.ondataavailable = function (e) {
                            if (e.data && e.data.size > 0) chunks.push(e.data);
                        };
                        recorder.start();
                        recording = true;
                    });
            },
            stop: function () {
                if (!recording || !recorder) return Promise.resolve(null);
                return new Promise(function (resolve) {
                    recorder.onstop = function () {
                        recording = false;
                        var type = (recorder && recorder.mimeType) ? recorder.mimeType : 'audio/webm';
                        var blob = chunks.length ? new Blob(chunks, { type: type }) : null;
                        stopTracks();
                        recorder = null;
                        chunks = [];
                        resolve(blob);
                    };
                    recorder.stop();
                });
            },
            cancel: function () {
                recording = false;
                if (recorder && recorder.state !== 'inactive') {
                    try { recorder.stop(); } catch (e) {}
                }
                stopTracks();
                recorder = null;
                chunks = [];
            },
        };
    }

    window.QuizSnapSupportMedia = {
        renderAvatarHtml: renderAvatarHtml,
        appendMessageMedia: appendMessageMedia,
        createRecorder: createRecorder,
    };
})();
