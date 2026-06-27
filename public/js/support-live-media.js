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

    function resolveMediaUrl(url) {
        if (!url) return '';
        if (/^(https?:|blob:)/i.test(url)) return url;
        if (url.charAt(0) === '/') return window.location.origin + url;
        return url;
    }

    function blobMime(blob) {
        return (blob && blob.type) ? blob.type : 'audio/webm';
    }

    function audioFilenameForBlob(blob) {
        var mime = blobMime(blob);
        if (mime.indexOf('mp4') !== -1 || mime.indexOf('m4a') !== -1) return 'voice-message.m4a';
        if (mime.indexOf('ogg') !== -1) return 'voice-message.ogg';
        if (mime.indexOf('mpeg') !== -1 || mime.indexOf('mp3') !== -1) return 'voice-message.mp3';
        return 'voice-message.webm';
    }

    function buildAudioElement(src, mime) {
        var audio = document.createElement('audio');
        audio.controls = true;
        audio.preload = 'auto';
        audio.playsInline = true;
        audio.className = 'qs-live-msg__audio';
        if (mime) {
            var source = document.createElement('source');
            source.src = resolveMediaUrl(src);
            source.type = mime;
            audio.appendChild(source);
        } else {
            audio.src = resolveMediaUrl(src);
        }
        return audio;
    }

    function appendMessageMedia(bubble, msg) {
        if (!bubble || !msg || !msg.meta) return false;
        if (msg.message_type === 'image' && msg.meta.url) {
            var img = document.createElement('img');
            img.src = resolveMediaUrl(msg.meta.url);
            img.alt = 'Shared image';
            img.className = 'qs-live-msg__image';
            img.loading = 'lazy';
            var link = document.createElement('a');
            link.href = resolveMediaUrl(msg.meta.url);
            link.target = '_blank';
            link.rel = 'noopener noreferrer';
            link.appendChild(img);
            bubble.appendChild(link);
            return true;
        }
        if (msg.message_type === 'audio' && msg.meta.url) {
            bubble.appendChild(buildAudioElement(msg.meta.url, msg.meta.mime || 'audio/webm'));
            return true;
        }
        return false;
    }

    function pickRecorderMime() {
        if (!window.MediaRecorder) return '';
        var types = [
            'audio/webm;codecs=opus',
            'audio/webm',
            'audio/mp4',
            'audio/ogg;codecs=opus',
            'audio/ogg',
        ];
        for (var i = 0; i < types.length; i++) {
            if (MediaRecorder.isTypeSupported(types[i])) return types[i];
        }
        return '';
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
        var selectedMime = '';

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
            getMimeType: function () { return selectedMime || blobMime(null); },
            onLevels: function (fn) { levelCallback = fn; },
            start: function () {
                if (recording || !navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    return Promise.reject(new Error('Microphone not available.'));
                }
                return navigator.mediaDevices.getUserMedia({ audio: true })
                    .then(function (s) {
                        stream = s;
                        chunks = [];
                        selectedMime = pickRecorderMime();
                        recorder = selectedMime
                            ? new MediaRecorder(s, { mimeType: selectedMime })
                            : new MediaRecorder(s);
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
                        var type = (recorder && recorder.mimeType) ? recorder.mimeType : (selectedMime || 'audio/webm');
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

    function rtcConfig() {
        return {
            iceServers: [
                { urls: 'stun:stun.l.google.com:19302' },
                { urls: 'stun:stun1.l.google.com:19302' },
                { urls: 'stun:stun2.l.google.com:19302' },
            ],
        };
    }

    function normalizeSdp(sdp) {
        if (!sdp) return null;
        if (typeof sdp === 'string') return { type: 'offer', sdp: sdp };
        if (sdp.type && sdp.sdp) return { type: sdp.type, sdp: sdp.sdp };
        return null;
    }

    function attachRemoteTrack(videoEl, wrapEl, ev) {
        if (!videoEl || !ev || !ev.track) return;
        var stream = (ev.streams && ev.streams[0]) ? ev.streams[0] : new MediaStream([ev.track]);
        videoEl.srcObject = stream;
        videoEl.classList.remove('hidden');
        if (wrapEl) wrapEl.classList.remove('hidden');
        videoEl.play().catch(function () {});
    }

    function processWebRtcBatch(messages, handler) {
        if (!Array.isArray(messages) || !handler) return;
        var signals = messages.filter(function (m) {
            return m && m.message_type === 'webrtc' && m.meta && m.meta.signal;
        });
        signals.filter(function (m) { return m.meta.signal === 'offer'; }).forEach(function (m) {
            handler(m.meta, m.id);
        });
        signals.filter(function (m) { return m.meta.signal === 'answer'; }).forEach(function (m) {
            handler(m.meta, m.id);
        });
        signals.filter(function (m) { return m.meta.signal === 'ice'; }).forEach(function (m) {
            handler(m.meta, m.id);
        });
    }

    window.QuizSnapSupportMedia = {
        renderAvatarHtml: renderAvatarHtml,
        appendMessageMedia: appendMessageMedia,
        buildAudioElement: buildAudioElement,
        resolveMediaUrl: resolveMediaUrl,
        audioFilenameForBlob: audioFilenameForBlob,
        blobMime: blobMime,
        createRecorder: createRecorder,
        createWaveform: createWaveform,
        packRtcMeta: packRtcMeta,
        rtcConfig: rtcConfig,
        normalizeSdp: normalizeSdp,
        attachRemoteTrack: attachRemoteTrack,
        processWebRtcBatch: processWebRtcBatch,
    };
})();
