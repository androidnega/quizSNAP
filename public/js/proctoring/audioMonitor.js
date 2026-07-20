/**
 * Audio Monitor: volume detection + short evidence clips on sustained sound.
 * Clips upload with the same retention as violation photos (violations/).
 */
(function () {
    'use strict';

    const config = window.QuizSnapAudioMonitor || {};
    const violationCaptureUrl = config.violationCaptureUrl || '/quiz/violation/capture';
    const csrfToken = config.csrfToken || (document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').content) || '';
    const sessionId = config.sessionId || 0;
    let videoElement = config.videoElement || null;
    const onViolation = config.onViolation || null;

    const AUDIO_THRESHOLD = 0.7;
    const SUSTAINED_DURATION_MS = 3000;
    const CHECK_INTERVAL_MS = 500;
    const SMOOTHING_TIME_CONSTANT = 0.8;
    const CLIP_MS = 8000;
    const CLIP_COOLDOWN_MS = 45000;

    let audioContext = null;
    let analyser = null;
    let microphone = null;
    let isRunning = false;
    let audioCheckInterval = null;
    let sustainedAudioStartTime = null;
    let dataArray = null;
    let bufferLength = 0;
    let mediaRecorder = null;
    let recordingInFlight = false;
    let lastClipAt = 0;

    function csrf() {
        return csrfToken;
    }

    function captureFrame() {
        if (!videoElement) return null;

        const canvas = document.createElement('canvas');
        canvas.width = videoElement.videoWidth || 640;
        canvas.height = videoElement.videoHeight || 480;
        const ctx = canvas.getContext('2d');

        try {
            ctx.drawImage(videoElement, 0, 0, canvas.width, canvas.height);
            return canvas.toDataURL('image/jpeg', 0.85);
        } catch (err) {
            return null;
        }
    }

    function pickRecorderMime() {
        if (typeof MediaRecorder === 'undefined') return '';
        const candidates = [
            'audio/webm;codecs=opus',
            'audio/webm',
            'audio/mp4',
            'audio/ogg;codecs=opus',
        ];
        for (let i = 0; i < candidates.length; i++) {
            if (MediaRecorder.isTypeSupported && MediaRecorder.isTypeSupported(candidates[i])) {
                return candidates[i];
            }
        }
        return '';
    }

    function blobToDataUrl(blob) {
        return new Promise(function (resolve, reject) {
            const reader = new FileReader();
            reader.onloadend = function () {
                resolve(typeof reader.result === 'string' ? reader.result : null);
            };
            reader.onerror = reject;
            reader.readAsDataURL(blob);
        });
    }

    /**
     * Record a short clip from the live mic stream (same retention path as photos).
     */
    function recordEvidenceClip() {
        if (!microphone || typeof MediaRecorder === 'undefined' || recordingInFlight) {
            return Promise.resolve(null);
        }
        if (Date.now() - lastClipAt < CLIP_COOLDOWN_MS) {
            return Promise.resolve(null);
        }

        const mime = pickRecorderMime();
        let recorder;
        try {
            recorder = mime
                ? new MediaRecorder(microphone, { mimeType: mime })
                : new MediaRecorder(microphone);
        } catch (err) {
            console.warn('MediaRecorder unavailable:', err);
            return Promise.resolve(null);
        }

        recordingInFlight = true;
        const chunks = [];

        return new Promise(function (resolve) {
            recorder.ondataavailable = function (e) {
                if (e.data && e.data.size > 0) {
                    chunks.push(e.data);
                }
            };
            recorder.onerror = function () {
                recordingInFlight = false;
                resolve(null);
            };
            recorder.onstop = function () {
                recordingInFlight = false;
                lastClipAt = Date.now();
                if (!chunks.length) {
                    resolve(null);
                    return;
                }
                const type = (recorder.mimeType || mime || 'audio/webm').split(';')[0];
                const blob = new Blob(chunks, { type: type });
                blobToDataUrl(blob).then(resolve).catch(function () {
                    resolve(null);
                });
            };
            try {
                recorder.start(250);
                setTimeout(function () {
                    try {
                        if (recorder.state !== 'inactive') {
                            recorder.stop();
                        }
                    } catch (e) {
                        recordingInFlight = false;
                        resolve(null);
                    }
                }, CLIP_MS);
            } catch (err) {
                recordingInFlight = false;
                resolve(null);
            }
        });
    }

    function sendViolationCapture(violationType, imageBase64, audioBase64) {
        if (!violationCaptureUrl || !sessionId) return;
        if (!imageBase64 && !audioBase64) return;

        const body = {
            session_id: sessionId,
            violation_type: violationType,
        };
        if (imageBase64) body.image_base64 = imageBase64;
        if (audioBase64) body.audio_base64 = audioBase64;

        fetch(violationCaptureUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf(),
                'Accept': 'application/json',
            },
            body: JSON.stringify(body),
        }).catch(function () {});
    }

    function triggerViolation(type, severity, imageBase64, audioBase64) {
        showAudioWarning();

        if (imageBase64 || audioBase64) {
            sendViolationCapture(type, imageBase64, audioBase64);
        }

        if (onViolation && typeof onViolation === 'function') {
            onViolation({
                type: type,
                severity: severity,
                image_base64: imageBase64,
                audio_base64: audioBase64 || null,
            });
        }
    }

    function showAudioWarning() {
        const existingWarning = document.getElementById('audio-detection-warning');
        if (existingWarning) {
            existingWarning.remove();
        }

        const warning = document.createElement('div');
        warning.id = 'audio-detection-warning';
        warning.className = 'fixed top-20 left-4 right-4 sm:left-auto sm:right-4 sm:max-w-md z-[60] px-4 py-3 rounded-lg shadow-lg border bg-orange-50 border-orange-400 text-orange-800';
        warning.innerHTML = '<p class="text-sm font-bold">External audio detected: sustained sound is a major violation.</p>';

        document.body.appendChild(warning);

        setTimeout(function () {
            if (warning.parentNode) {
                warning.style.transition = 'opacity 0.3s';
                warning.style.opacity = '0';
                setTimeout(function () {
                    if (warning.parentNode) warning.remove();
                }, 300);
            }
        }, 8000);
    }

    function getAudioLevel() {
        if (!analyser || !dataArray) return 0;

        analyser.getByteFrequencyData(dataArray);

        let sum = 0;
        for (let i = 0; i < bufferLength; i++) {
            sum += dataArray[i];
        }

        const average = sum / bufferLength;
        return average / 255;
    }

    function checkAudio() {
        if (!isRunning || !analyser) return;

        const level = getAudioLevel();
        const now = Date.now();

        if (level > AUDIO_THRESHOLD) {
            if (sustainedAudioStartTime === null) {
                sustainedAudioStartTime = now;
            } else if (now - sustainedAudioStartTime >= SUSTAINED_DURATION_MS) {
                sustainedAudioStartTime = null;
                const imageBase64 = captureFrame();
                recordEvidenceClip().then(function (audioBase64) {
                    triggerViolation('external_audio', 'major', imageBase64, audioBase64);
                });
            }
        } else {
            sustainedAudioStartTime = null;
        }
    }

    function requestMicrophone() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            console.warn('getUserMedia not supported');
            return Promise.reject('getUserMedia not supported');
        }

        return navigator.mediaDevices.getUserMedia({ audio: true, video: false })
            .then(function (stream) {
                microphone = stream;
                return stream;
            })
            .catch(function (err) {
                console.warn('Microphone access denied:', err);
                throw err;
            });
    }

    function initAudioContext(stream) {
        try {
            audioContext = new (window.AudioContext || window.webkitAudioContext)();
            analyser = audioContext.createAnalyser();
            analyser.smoothingTimeConstant = SMOOTHING_TIME_CONSTANT;
            analyser.fftSize = 256;

            const source = audioContext.createMediaStreamSource(stream);
            source.connect(analyser);

            bufferLength = analyser.frequencyBinCount;
            dataArray = new Uint8Array(bufferLength);

            return true;
        } catch (err) {
            console.error('Failed to initialize audio context:', err);
            return false;
        }
    }

    function start() {
        if (isRunning) return;

        requestMicrophone()
            .then(function (stream) {
                if (initAudioContext(stream)) {
                    isRunning = true;
                    audioCheckInterval = setInterval(checkAudio, CHECK_INTERVAL_MS);
                }
            })
            .catch(function (err) {
                console.warn('Audio monitoring not available:', err);
            });
    }

    function stop() {
        isRunning = false;

        if (audioCheckInterval) {
            clearInterval(audioCheckInterval);
            audioCheckInterval = null;
        }

        if (mediaRecorder && mediaRecorder.state !== 'inactive') {
            try { mediaRecorder.stop(); } catch (e) { /* ignore */ }
        }
        mediaRecorder = null;
        recordingInFlight = false;

        if (microphone) {
            microphone.getTracks().forEach(function (track) {
                track.stop();
            });
            microphone = null;
        }

        if (audioContext && audioContext.state !== 'closed') {
            audioContext.close().catch(function () {});
            audioContext = null;
        }

        analyser = null;
        dataArray = null;
        sustainedAudioStartTime = null;
    }

    function init() {
        if (!videoElement) {
            const videoEl = document.getElementById('face-monitor-video') ||
                           document.querySelector('video[autoplay]');
            if (videoEl) {
                videoElement = videoEl;
                config.videoElement = videoEl;
            }
        }
    }

    window.QuizSnapAudioMonitor = window.QuizSnapAudioMonitor || {};
    window.QuizSnapAudioMonitor.start = start;
    window.QuizSnapAudioMonitor.stop = stop;
    window.QuizSnapAudioMonitor.triggerViolation = triggerViolation;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    window.addEventListener('beforeunload', stop);
})();
