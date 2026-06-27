/**
 * QuizSnap live support — shared avatar, media rendering, audio recording, and waveforms.
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

    function createWaveform(mountEl, barCount) {
        barCount = barCount || 18;
        var bars = [];
        if (!mountEl) {
            return { update: function () {}, reset: function () {}, destroy: function () {} };
        }
        mountEl.innerHTML = '';
        for (var i = 0; i < barCount; i++) {
            var bar = document.createElement('span');
            bar.className = 'qs-live-recording-wave__bar';
            bar.style.height = '18%';
            mountEl.appendChild(bar);
            bars.push(bar);
        }
        return {
            update: function (levels) {
                if (!levels || !levels.length) return;
                var step = Math.max(1, Math.floor(levels.length / barCount));
                for (var i = 0; i < barCount; i++) {
                    var v = levels[i * step] || 0;
                    var h = Math.max(12, Math.round((v / 255) * 100));
                    bars[i].style.height = h + '%';
                }
            },
            reset: function () {
                bars.forEach(function (b) { b.style.height = '18%'; });
            },
            destroy: function () {
                mountEl.innerHTML = '';
                bars = [];
            },
        };
    }

    function cleanupAudioMonitor(ctx, analyser, source, rafId) {
        if (rafId) cancelAnimationFrame(rafId);
        if (source) {
            try { source.disconnect(); } catch (e) {}
        }
        if (analyser) {
            try { analyser.disconnect(); } catch (e) {}
        }
        if (ctx) {
            ctx.close().catch(function () {});
        }
    }

    function createRecorder() {
        var recorder = null;
        var stream = null;
        var chunks = [];
        var recording = false;
        var audioContext = null;
        var analyser = null;
        var sourceNode = null;
        var rafId = null;
        var levelCallback = null;
        var freqData = null;

        function stopTracks() {
            if (stream) {
                stream.getTracks().forEach(function (t) { t.stop(); });
                stream = null;
            }
        }

        function stopMonitor() {
            cleanupAudioMonitor(audioContext, analyser, sourceNode, rafId);
            audioContext = null;
            analyser = null;
            sourceNode = null;
            rafId = null;
            freqData = null;
        }

        function startMonitor(mediaStream) {
            stopMonitor();
            var AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (!AudioCtx || !mediaStream) return;
            audioContext = new AudioCtx();
            analyser = audioContext.createAnalyser();
            analyser.fftSize = 128;
            analyser.smoothingTimeConstant = 0.72;
            analyser.minDecibels = -90;
            analyser.maxDecibels = -10;
            sourceNode = audioContext.createMediaStreamSource(mediaStream);
            sourceNode.connect(analyser);
            freqData = new Uint8Array(analyser.frequencyBinCount);
            function tick() {
                if (!recording || !analyser) return;
                analyser.getByteFrequencyData(freqData);
                if (levelCallback) levelCallback(freqData);
                rafId = requestAnimationFrame(tick);
            }
            if (audioContext.state === 'suspended') {
                audioContext.resume().catch(function () {});
            }
            tick();
        }

        return {
            isRecording: function () { return recording; },
            onLevels: function (fn) { levelCallback = fn; },
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
                        startMonitor(s);
                    });
            },
            stop: function () {
                if (!recording || !recorder) return Promise.resolve(null);
                return new Promise(function (resolve) {
                    recorder.onstop = function () {
                        recording = false;
                        stopMonitor();
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
                stopMonitor();
                if (recorder && recorder.state !== 'inactive') {
                    try { recorder.stop(); } catch (e) {}
                }
                stopTracks();
                recorder = null;
                chunks = [];
            },
        };
    }

    function packRtcMeta(meta) {
        if (!meta) return {};
        var out = { signal: meta.signal };
        if (meta.sdp) {
            out.sdp = {
                type: meta.sdp.type,
                sdp: meta.sdp.sdp,
            };
        }
        if (meta.candidate) {
            out.candidate = meta.candidate.toJSON ? meta.candidate.toJSON() : meta.candidate;
        }
        return out;
    }

    window.QuizSnapSupportMedia = {
        renderAvatarHtml: renderAvatarHtml,
        appendMessageMedia: appendMessageMedia,
        createRecorder: createRecorder,
        createWaveform: createWaveform,
        packRtcMeta: packRtcMeta,
    };
})();
