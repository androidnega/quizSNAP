/**
 * StudentQuiz: Timer, auto-save, tab blur (two-strike tab switch policy), offline-safe saves.
 * No auto-submit on refresh or network failure. Tab switch warns once, auto-submits on second confirmed leave.
 */
(function () {
    const c = window.QuizSnapQuiz || {};
    // Global proctoring state shared between object and face detectors
    window.QuizSnapProctorState = window.QuizSnapProctorState || { lastPhoneDetectedAt: 0 };
    const saveAnswerUrl = c.saveAnswerUrl;
    const saveAnswersBatchUrl = c.saveAnswersBatchUrl;
    const violationUrl = c.violationUrl;
    const heartbeatUrl = c.heartbeatUrl;
    const finalPhotoUrl = c.finalPhotoUrl;
    const finalizeUrl = c.finalizeUrl;
    const timeSyncUrl = c.timeSyncUrl;
    const csrfToken = c.csrfToken;
    const storagePrefix = c.storagePrefix || 'quizsnap_quiz';
    const cameraRequired = c.cameraRequired !== false;
    let remainingSeconds = c.remainingSeconds || 0;
    let endTimeMs = null;
    let timerInterval = null;
    let timeSyncInterval = null;
    const BLUR_RECORD_DELAY_MS = 0;
    let blurRecordTimer = null;
    let isUnloading = false;
    let cameraStream = null;
    let cameraCheckInterval = null;
    let wakeLock = null;
    let cameraProtectionInterval = null;
    let cameraWarningShown = false;
    var periodicHeartbeatInterval = null;
    let lastUserInputSample = '';
    const AI_KEYWORDS = ['chatgpt', 'openai', 'deepseek', 'gemini', 'google', 'ngrok', 'claude', 'copilot', 'perplexity'];

    function isConstrainedDevice() {
        try {
            var cores = navigator.hardwareConcurrency || 8;
            var conn = navigator.connection;
            if (conn && conn.saveData === true) {
                return true;
            }
            var narrowTouch = (navigator.maxTouchPoints > 0 || 'ontouchstart' in window) && window.innerWidth < 900;
            return cores <= 4 || narrowTouch;
        } catch (e) {
            return false;
        }
    }
    var constrainedDevice = isConstrainedDevice();
    var SAVE_BATCH_CHUNK = 25;
    var TIME_SYNC_INTERVAL_MS = constrainedDevice ? 45000 : 30000;

    function notifyStudentError(kind) {
        if (window.QuizSnapStudentFeedback) {
            if (kind === 'save' && window.QuizSnapStudentFeedback.saveError) {
                window.QuizSnapStudentFeedback.saveError();
            } else if (kind === 'violation' && window.QuizSnapStudentFeedback.violationError) {
                window.QuizSnapStudentFeedback.violationError();
            } else if (window.QuizSnapStudentFeedback.connectionError) {
                window.QuizSnapStudentFeedback.connectionError();
            }
        }
    }

    /**
     * Request screen wake lock to prevent dimming
     */
    async function requestWakeLock() {
        if ('wakeLock' in navigator) {
            try {
                wakeLock = await navigator.wakeLock.request('screen');
                console.log('Screen wake lock acquired');
                wakeLock.addEventListener('release', function() {
                    console.log('Screen wake lock released, re-requesting...');
                    // Re-request if released (e.g., user switches tabs)
                    setTimeout(requestWakeLock, 1000);
                });
            } catch (err) {
                console.warn('Wake lock request failed:', err);
            }
        }
    }

    /**
     * Release screen wake lock
     */
    function releaseWakeLock() {
        if (wakeLock) {
            wakeLock.release().then(function() {
                wakeLock = null;
                console.log('Screen wake lock released');
            }).catch(function(err) {
                console.warn('Wake lock release failed:', err);
            });
        }
    }

    /**
     * Protect camera stream from being canceled
     */
    function startCameraProtection() {
        if (cameraProtectionInterval) return;
        cameraProtectionInterval = setInterval(function() {
            if (!cameraStream) return;
            const videoTrack = cameraStream.getVideoTracks()[0];
            if (!videoTrack || videoTrack.readyState === 'ended') {
                console.warn('Camera stream ended during quiz.');
                if (cameraRequired && typeof showCameraOffOverlay === 'function') {
                    showCameraOffOverlay();
                } else if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                    var constraints = { video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } }, audio: false };
                    navigator.mediaDevices.getUserMedia(constraints)
                        .then(function(newStream) {
                            cameraStream = newStream;
                            if (typeof hideCameraOffOverlay === 'function') hideCameraOffOverlay();
                            const monitorVideo = document.getElementById('face-monitor-video');
                            if (monitorVideo) monitorVideo.srcObject = newStream;
                        })
                        .catch(function(err) {
                            console.error('Failed to restart camera:', err);
                            if (cameraRequired && typeof showCameraOffOverlay === 'function') showCameraOffOverlay();
                        });
                }
            }
        }, 2000);
    }

    /**
     * Stop camera protection monitoring
     */
    function stopCameraProtection() {
        if (cameraProtectionInterval) {
            clearInterval(cameraProtectionInterval);
            cameraProtectionInterval = null;
        }
    }

    function showCameraReconnectWarning() {
        if (cameraWarningShown) return;
        cameraWarningShown = true;
        setTimeout(function () {
            cameraWarningShown = false;
        }, 5000);
        alert('Camera connection was interrupted. Please keep camera on and face centered.');
    }

    function showCameraOffOverlay() {
        var el = document.getElementById('camera-off-overlay');
        if (el) {
            el.classList.remove('hidden');
            el.setAttribute('aria-hidden', 'false');
        }
    }

    function hideCameraOffOverlay() {
        var el = document.getElementById('camera-off-overlay');
        if (el) {
            el.classList.add('hidden');
            el.setAttribute('aria-hidden', 'true');
        }
    }

    const timerEl = document.getElementById('quiz-timer');
    const timerStickyEl = document.getElementById('quiz-timer-sticky');
    const quizForm = document.getElementById('quiz-form');
    const postFaceBtn = document.getElementById('post-face-btn');
    const blurWarning = document.getElementById('blur-warning');

    function csrf() {
        return csrfToken || (document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').content) || '';
    }

    function captureCurrentMonitorFrame() {
        var video = document.getElementById('face-monitor-video');
        if (!video || video.readyState < 2 || video.videoWidth <= 0 || video.videoHeight <= 0) return null;
        try {
            var canvas = document.createElement('canvas');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            var ctx = canvas.getContext('2d');
            if (!ctx) return null;
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            return canvas.toDataURL('image/jpeg', 0.8);
        } catch (e) {
            return null;
        }
    }

    function sendCriticalEvidenceSnapshot(type, metadata) {
        if (!c.violationCaptureUrl || !c.sessionId) return;
        var imageBase64 = captureCurrentMonitorFrame();
        if (!imageBase64) return;
        fetch(c.violationCaptureUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf(),
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                session_id: c.sessionId,
                violation_type: type,
                image_base64: imageBase64,
                metadata: metadata || {},
            }),
        }).catch(function () {
            notifyStudentError('violation');
        });
    }

    function formatTime(sec) {
        sec = Math.max(0, Math.floor(sec));
        const minutes = Math.floor(sec / 60);
        const seconds = sec % 60;
        return minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
    }

    function applyTimerColor(sec) {
        var els = [timerEl, timerStickyEl].filter(Boolean);
        els.forEach(function (el) {
            if (!el) return;
            el.classList.remove('quiz-timer-green', 'quiz-timer-blue', 'quiz-timer-red');
            if (sec <= 30) {
                el.classList.add('quiz-timer-red');
            } else if (sec <= 120) {
                el.classList.add('quiz-timer-blue');
            } else {
                el.classList.add('quiz-timer-green');
            }
        });
    }

    function playTimeUpSound() {
        try {
            var ctx = new (window.AudioContext || window.webkitAudioContext)();
            var osc = ctx.createOscillator();
            var gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.frequency.value = 440;
            osc.type = 'sine';
            gain.gain.setValueAtTime(0.3, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.8);
            osc.start(ctx.currentTime);
            osc.stop(ctx.currentTime + 0.8);
        } catch (e) {}
    }

    function updateTimer() {
        if (endTimeMs !== null) {
            remainingSeconds = Math.max(0, Math.ceil((endTimeMs - Date.now()) / 1000));
        } else {
            remainingSeconds = Math.max(0, remainingSeconds - 1);
        }
        if (remainingSeconds <= 0) {
            if (timerInterval) clearInterval(timerInterval);
            if (timeSyncInterval) clearInterval(timeSyncInterval);
            if (periodicHeartbeatInterval) {
                clearInterval(periodicHeartbeatInterval);
                periodicHeartbeatInterval = null;
            }
            playTimeUpSound();
            submitQuiz(true);
            return;
        }
        var text = formatTime(remainingSeconds);
        if (timerEl) timerEl.textContent = text;
        if (timerStickyEl) timerStickyEl.textContent = text;
        applyTimerColor(remainingSeconds);
    }

    function syncTimeFromServer() {
        if (!timeSyncUrl || remainingSeconds <= 0) return;
        fetch(timeSyncUrl, { method: 'GET', headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (data) {
                if (data && typeof data.remaining_seconds === 'number') {
                    remainingSeconds = Math.max(0, data.remaining_seconds);
                    endTimeMs = Date.now() + remainingSeconds * 1000;
                    var text = formatTime(remainingSeconds);
                    if (timerEl) timerEl.textContent = text;
                    if (timerStickyEl) timerStickyEl.textContent = text;
                    applyTimerColor(remainingSeconds);
                    if (remainingSeconds <= 0) {
                        if (timerInterval) clearInterval(timerInterval);
                        if (timeSyncInterval) clearInterval(timeSyncInterval);
                        if (periodicHeartbeatInterval) {
                            clearInterval(periodicHeartbeatInterval);
                            periodicHeartbeatInterval = null;
                        }
                        submitQuiz(true);
                    }
                }
            })
            .catch(function () {
                notifyStudentError('connection');
            });
    }

    var savePending = {};
    var saveDebounceTimer = null;
    var saveFlushInFlight = false;
    var saveFlushQueued = false;
    var SAVE_DEBOUNCE_MS = constrainedDevice ? 2800 : 1600;
    var offlineBanner = null;

    function fetchWithRetry(url, options, retriesLeft) {
        retriesLeft = retriesLeft == null ? 2 : retriesLeft;
        return fetch(url, options).then(function (r) {
            if (!r.ok && retriesLeft > 0 && (r.status >= 500 || r.status === 429)) {
                return new Promise(function (resolve) {
                    setTimeout(resolve, 400 * (3 - retriesLeft));
                }).then(function () {
                    return fetchWithRetry(url, options, retriesLeft - 1);
                });
            }
            return r;
        }).catch(function (err) {
            if (retriesLeft > 0) {
                return new Promise(function (resolve) {
                    setTimeout(resolve, 400 * (3 - retriesLeft));
                }).then(function () {
                    return fetchWithRetry(url, options, retriesLeft - 1);
                });
            }
            throw err;
        });
    }

    function showOfflineBanner(show) {
        if (!show) {
            if (offlineBanner) { offlineBanner.remove(); offlineBanner = null; }
            return;
        }
        if (offlineBanner) return;
        offlineBanner = document.createElement('div');
        offlineBanner.setAttribute('role', 'status');
        offlineBanner.className = 'fixed bottom-4 left-4 right-4 sm:left-auto sm:right-4 sm:max-w-sm z-50 px-3 py-2 rounded-lg bg-amber-100 border border-amber-300 text-amber-800 text-sm font-medium shadow';
        offlineBanner.textContent = 'Offline. Answers saved locally and will sync when back online.';
        document.body.appendChild(offlineBanner);
    }

    function persistPendingToStorage() {
        var list = [];
        for (var id in savePending) { list.push(savePending[id]); }
        if (list.length === 0) return;
        try {
            localStorage.setItem(storagePrefix + '_pending', JSON.stringify(list));
        } catch (e) {}
    }

    function flushSavePending() {
        if (saveDebounceTimer) clearTimeout(saveDebounceTimer);
        saveDebounceTimer = null;
        if (saveFlushInFlight) {
            saveFlushQueued = true;
            return Promise.resolve();
        }
        saveFlushInFlight = true;
        var list = [];
        for (var id in savePending) { list.push(savePending[id]); }
        if (list.length === 0) {
            saveFlushInFlight = false;
            if (saveFlushQueued) {
                saveFlushQueued = false;
                return flushSavePending();
            }
            try { localStorage.removeItem(storagePrefix + '_pending'); } catch (e) {}
            showOfflineBanner(false);
            return Promise.resolve();
        }
        var h = { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' };
        if (!navigator.onLine) {
            persistPendingToStorage();
            showOfflineBanner(true);
            saveFlushInFlight = false;
            return Promise.resolve();
        }
        var fail = function () {
            persistPendingToStorage();
            showOfflineBanner(true);
            notifyStudentError('save');
        };
        var finishFlush = function () {
            saveFlushInFlight = false;
            if (saveFlushQueued) {
                saveFlushQueued = false;
                return flushSavePending();
            }
            return Promise.resolve();
        };
        var tryClearAllDone = function () {
            var still = false;
            for (var k in savePending) { still = true; break; }
            if (!still) {
                try { localStorage.removeItem(storagePrefix + '_pending'); } catch (e) {}
                showOfflineBanner(false);
            }
        };
        if (saveAnswersBatchUrl && list.length > 0) {
            var start = 0;
            function sendNextBatch() {
                var chunk = list.slice(start, start + SAVE_BATCH_CHUNK);
                if (chunk.length === 0) {
                    tryClearAllDone();
                    return finishFlush();
                }
                var payload = chunk.map(function (p) { return { question_id: p.questionId, answer: p.answer }; });
                return fetchWithRetry(saveAnswersBatchUrl, { method: 'POST', headers: h, body: JSON.stringify({ answers: payload }) })
                    .then(function (r) {
                        if (!r.ok) {
                            fail();
                            return finishFlush();
                        }
                        chunk.forEach(function (p) { delete savePending[p.questionId]; });
                        start += SAVE_BATCH_CHUNK;
                        return sendNextBatch();
                    })
                    .catch(function () {
                        fail();
                        return finishFlush();
                    });
            }
            return sendNextBatch();
        }
        var anyFail = false;
        var parallel = list.slice(0, 8);
        return Promise.all(parallel.map(function (p) {
            return fetchWithRetry(saveAnswerUrl, { method: 'POST', headers: h, body: JSON.stringify({ question_id: p.questionId, answer: p.answer }) })
                .then(function (r) {
                    if (r.ok) delete savePending[p.questionId];
                    else { anyFail = true; }
                })
                .catch(function () { anyFail = true; });
        })).then(function () {
            if (anyFail) fail();
            else tryClearAllDone();
            return finishFlush();
        });
    }

    function saveAnswer(questionId, answer) {
        savePending[questionId] = { questionId: questionId, answer: answer };
        if (saveDebounceTimer) clearTimeout(saveDebounceTimer);
        saveDebounceTimer = setTimeout(flushSavePending, SAVE_DEBOUNCE_MS);
    }

    function loadPendingFromStorageAndFlush() {
        try {
            var raw = localStorage.getItem(storagePrefix + '_pending');
            if (!raw) return;
            var list = JSON.parse(raw);
            if (!Array.isArray(list) || list.length === 0) return;
            list.forEach(function (p) {
                if (p && p.questionId != null) savePending[p.questionId] = { questionId: p.questionId, answer: p.answer || '' };
            });
            flushSavePending();
        } catch (e) {}
    }

    function showPhoneDetectedSubmitted(redirectUrl) {
        var modal = document.getElementById('phone-detected-modal');
        var target = (modal && modal.getAttribute('data-dashboard-url')) || '/dashboard';
        if (modal) {
            modal.classList.remove('hidden');
            var btn = document.getElementById('phone-detected-dashboard-btn');
            if (btn) {
                btn.disabled = false;
                btn.textContent = 'Return to dashboard';
                btn.onclick = function () {
                    window.location.href = target;
                };
            }
            return;
        }
        showNeutralPageThenRedirect(target);
    }

    function showNeutralPageThenRedirect(redirectUrl) {
        try {
            document.body.innerHTML = '';
            var wrap = document.createElement('div');
            wrap.style.cssText = 'min-height:100vh;display:flex;align-items:center;justify-content:center;background:#111;color:#e5e5e5;font-family:system-ui,sans-serif;padding:1.5rem;text-align:center;';
            wrap.setAttribute('role', 'alert');
            var msg = document.createElement('p');
            msg.style.cssText = 'font-size:1.125rem;max-width:28rem;line-height:1.6;';
            msg.textContent = 'Your quiz has been submitted due to a policy violation. Thanks for participating.';
            wrap.appendChild(msg);
            var emoji = document.createElement('p');
            emoji.style.cssText = 'font-size:1.5rem;margin-top:0.75rem;';
            emoji.textContent = '\uD83D\uDE1C';
            wrap.appendChild(emoji);
            document.body.appendChild(wrap);
            if (typeof history.replaceState === 'function') {
                history.replaceState(null, '', window.location.href);
            }
        } catch (e) {}
        if (redirectUrl) {
            setTimeout(function () { window.location.replace(redirectUrl); }, 350);
        }
    }

    var criticalTypes = [
        'phone_detected',
        'screenshot_attempt',
        'window_resize',
        'copy_paste',
        'multiple_ip'
    ];

    var tabSwitchStrikeTypes = ['tab_switch', 'blur'];

    function addViolationMessage(text, isError) {
        var list = document.getElementById('live-camera-violations-list');
        if (!list) return;
        var li = document.createElement('li');
        li.className = isError ? 'text-red-700' : 'text-amber-800';
        li.textContent = text;
        list.appendChild(li);
        list.scrollTop = list.scrollHeight;
    }

    function setCriticalViolationDisplay(count) {
        var el = document.getElementById('quiz-critical-violation-number');
        if (el) el.textContent = count + '/1';
    }

    /**
     * Record proctoring violation. Only auto-submit when the server explicitly returns auto_submitted
     * (i.e. user broke proctoring rules). Never auto-submit on network failure or when offline.
     */
    function recordViolation(type, metadata) {
        var body = { type: type };
        if (metadata) body.metadata = typeof metadata === 'string' ? metadata : JSON.stringify(metadata);
        var label = type.replace(/_/g, ' ');
        var isTabSwitchStrike = tabSwitchStrikeTypes.indexOf(type) !== -1;
        addViolationMessage(label, criticalTypes.indexOf(type) !== -1 || isTabSwitchStrike);
        if (criticalTypes.indexOf(type) !== -1) {
            setCriticalViolationDisplay(1);
            sendCriticalEvidenceSnapshot(type, metadata || {});
        }
        fetch(violationUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf(),
                'Accept': 'application/json',
            },
            body: JSON.stringify(body),
        })
            .then(function (r) {
                if (!r.ok) return null;
                var ct = r.headers.get('content-type') || '';
                if (ct.indexOf('application/json') === -1) return null;
                return r.json();
            })
            .then(function (data) {
                if (!data) return;
                if (data.auto_submitted) {
                    var redirect = data.redirect || null;
                    if (type === 'phone_detected') {
                        showPhoneDetectedSubmitted(redirect);
                    } else if (redirect) {
                        showNeutralPageThenRedirect(redirect);
                    } else {
                        showNeutralPageThenRedirect(null);
                    }
                } else if (data.show_major_warning) {
                    if (isTabSwitchStrike && window.QuizSnapQuiz && typeof window.QuizSnapQuiz.showTabSwitchWarning === 'function') {
                        window.QuizSnapQuiz.showTabSwitchWarning(data.tab_switch_strikes, data.tab_switch_remaining);
                    } else if (window.QuizSnapQuiz && typeof window.QuizSnapQuiz.showTabSwitchWarning === 'function' && (data.tab_switch_strikes || 0) > 0) {
                        window.QuizSnapQuiz.showTabSwitchWarning(data.tab_switch_strikes, data.tab_switch_remaining);
                    } else {
                        var el = document.getElementById('blur-warning');
                        if (el) el.classList.remove('hidden');
                    }
                }
            })
            .catch(function () {
                notifyStudentError('violation');
            });
    }

    /** Push all current form answers into savePending so they are included in the next flush. */
    function pushAllFormAnswersToSavePending() {
        if (!quizForm) return;
        quizForm.querySelectorAll('input[type="radio"], textarea').forEach(function (el) {
            var questionId = el.dataset.questionId || (el.name && el.name.replace('q_', ''));
            if (!questionId) return;
            var val = '';
            if (el.type === 'radio') {
                var r = quizForm.querySelector('input[name="' + el.name + '"]:checked');
                val = r ? r.value : '';
            } else {
                val = el.value || '';
            }
            saveAnswer(questionId, val);
        });
    }

    /** Redirect to final photo page (separate screen). Photo required before submission. Do not redirect when offline. */
    function goToFinalPhoto() {
        if (!navigator.onLine) {
            showOfflineBanner(true);
            if (offlineBanner) offlineBanner.textContent = 'Offline. Connect to the internet, then click Finish quiz again.';
            return;
        }
        if (!cameraRequired) {
            pushAllFormAnswersToSavePending();
            flushSavePending();
            fetch(finalizeUrl || '/quiz/finalize', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                    'Accept': 'application/json',
                },
                body: JSON.stringify({}),
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data && data.redirect) {
                        window.location.href = data.redirect;
                    } else if (data && data.success) {
                        window.location.href = '/quiz/complete';
                    } else {
                        showOfflineBanner(true);
                        if (offlineBanner) offlineBanner.textContent = (data && data.message) ? data.message : 'Could not submit quiz. Please try again.';
                    }
                })
                .catch(function () {
                    showOfflineBanner(true);
                    if (offlineBanner) offlineBanner.textContent = 'Network error. Please try again.';
                    notifyStudentError('connection');
                });
            return;
        }
        if (window.QuizSnapQuiz) window.QuizSnapQuiz.navigatingToFinalPhoto = true;
        pushAllFormAnswersToSavePending();
        if (saveDebounceTimer) clearTimeout(saveDebounceTimer);
        saveDebounceTimer = null;
        flushSavePending().then(function () {
            if (finalPhotoUrl) {
                window.location.href = finalPhotoUrl;
            }
        });
    }

    function submitQuiz(doPostFace) {
        // Release wake lock and stop camera protection when quiz ends
        releaseWakeLock();
        stopCameraProtection();
        if (doPostFace) {
            goToFinalPhoto();
        } else {
            goToFinalPhoto();
        }
    }

    if (timerEl && remainingSeconds > 0) {
        endTimeMs = Date.now() + remainingSeconds * 1000;
        var text = formatTime(remainingSeconds);
        timerEl.textContent = text;
        if (timerStickyEl) timerStickyEl.textContent = text;
        applyTimerColor(remainingSeconds);
        timerInterval = setInterval(updateTimer, 1000);
        if (heartbeatUrl) {
            periodicHeartbeatInterval = setInterval(function () {
                if (remainingSeconds <= 0 || isUnloading) return;
                if (document.visibilityState !== 'visible') return;
                sendHeartbeat();
            }, 45000);
        }
        if (timeSyncUrl) {
            syncTimeFromServer();
            timeSyncInterval = setInterval(syncTimeFromServer, TIME_SYNC_INTERVAL_MS);
            document.addEventListener('visibilitychange', function () {
                if (document.visibilityState === 'visible') syncTimeFromServer();
            });
            window.addEventListener('pageshow', function (e) {
                if (e.persisted) syncTimeFromServer();
            });
        }
    }

    window.addEventListener('pagehide', function () { isUnloading = true; });
    // Cleanup on quiz end
    window.addEventListener('beforeunload', function (e) {
        releaseWakeLock();
        stopCameraProtection();
        isUnloading = true;
        if (periodicHeartbeatInterval) {
            clearInterval(periodicHeartbeatInterval);
            periodicHeartbeatInterval = null;
        }
        if (window.QuizSnapQuiz && window.QuizSnapQuiz.navigatingToFinalPhoto) return;
        flushSavePending();
        e.preventDefault();
        e.returnValue = '';
    });
    window.addEventListener('online', loadPendingFromStorageAndFlush);
    if (document.readyState === 'complete') loadPendingFromStorageAndFlush();
    else window.addEventListener('load', loadPendingFromStorageAndFlush);

    if (quizForm) {
        quizForm.querySelectorAll('input[type="radio"], textarea').forEach(function (el) {
            const questionId = el.dataset.questionId || (el.name && el.name.replace('q_', ''));
            const getVal = function () {
                if (el.type === 'radio') {
                    const r = quizForm.querySelector('input[name="' + el.name + '"]:checked');
                    return r ? r.value : '';
                }
                return el.value;
            };
            el.addEventListener('change', function () {
                saveAnswer(questionId, getVal());
            });
            el.addEventListener('blur', function () {
                if (el.type !== 'radio') saveAnswer(questionId, getVal());
            });
            el.addEventListener('input', function () {
                if (el.type === 'radio') return;
                // Persist typed answers continuously so full scripts are not lost if submit/connection changes.
                saveAnswer(questionId, getVal());
                const sample = (el.value || '').trim();
                if (sample !== '') lastUserInputSample = sample.slice(-300);
            });
            el.addEventListener('change', function () {
                if (el.type !== 'radio') return;
                const selected = quizForm.querySelector('input[name="' + el.name + '"]:checked');
                if (selected && selected.value) lastUserInputSample = String(selected.value);
            });
        });
    }

    function collectTabSwitchEvidence() {
        const sample = (lastUserInputSample || '').trim();
        const sampleLower = sample.toLowerCase();
        const matchedKeywords = AI_KEYWORDS.filter(function (keyword) {
            return sampleLower.indexOf(keyword) !== -1;
        });
        return {
            occurred_at: new Date().toISOString(),
            visibility_state: document.visibilityState || null,
            page_url: window.location.href,
            page_referrer: document.referrer || null,
            viewport: {
                width: window.innerWidth || null,
                height: window.innerHeight || null,
            },
            local_input_sample: sample ? sample.slice(0, 300) : null,
            ai_related_keywords_detected: matchedKeywords,
            external_url_capture_supported: false,
            capture_note: 'Browser security prevents reading exact URL/text from other tabs or external applications.'
        };
    }

    function recordBlurAfterDelay() {
        if (isUnloading || remainingSeconds <= 0) return;
        if (c.proctoringTabSwitch === false) return;
        recordViolation('tab_switch', collectTabSwitchEvidence());
    }

    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            if (isUnloading) return;
            if (blurRecordTimer) clearTimeout(blurRecordTimer);
            blurRecordTimer = setTimeout(function () {
                blurRecordTimer = null;
                if (!document.hidden || isUnloading) return;
                recordBlurAfterDelay();
            }, BLUR_RECORD_DELAY_MS);
        } else {
            if (blurRecordTimer) { clearTimeout(blurRecordTimer); blurRecordTimer = null; }
            sendHeartbeat();
        }
    });
    
    // Do NOT record violation on window blur (e.g. user moved to tab bar but did not switch). Only actual tab switch (visibilitychange → document.hidden) triggers violation.
    window.addEventListener('focus', function () {
        if (blurRecordTimer) { clearTimeout(blurRecordTimer); blurRecordTimer = null; }
        sendHeartbeat();
    });

    var lastHeartbeatAt = 0;
    var HEARTBEAT_THROTTLE_MS = 25000;
    function sendHeartbeat() {
        if (!heartbeatUrl) return;
        var now = Date.now();
        if (now - lastHeartbeatAt < HEARTBEAT_THROTTLE_MS) return;
        lastHeartbeatAt = now;
        fetch(heartbeatUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
            body: JSON.stringify({}),
        })
            .then(function (r) {
                if (!r.ok) return null;
                var ct = r.headers.get('content-type') || '';
                if (ct.indexOf('application/json') === -1) return null;
                return r.json();
            })
            .then(function (data) {
                if (!data || !data.show_tab_switch_warning) return;
                if (window.QuizSnapQuiz && typeof window.QuizSnapQuiz.showTabSwitchWarning === 'function') {
                    window.QuizSnapQuiz.showTabSwitchWarning(data.tab_switch_strikes, data.tab_switch_remaining);
                }
            })
            .catch(function () {
                notifyStudentError('connection');
            });
    }

    document.addEventListener('copy', function (e) {
        if (c.proctoringBlockCopyPaste === false) return;
        e.preventDefault();
        recordViolation('copy_paste');
    });
    document.addEventListener('cut', function (e) {
        if (c.proctoringBlockCopyPaste === false) return;
        e.preventDefault();
        recordViolation('copy_paste');
    });
    document.addEventListener('paste', function (e) {
        if (c.proctoringBlockCopyPaste === false) return;
        e.preventDefault();
        recordViolation('copy_paste');
    });

    document.addEventListener('keydown', function (e) {
        if (c.proctoringTabSwitch === false) return;
        var key = e.keyCode || e.which;
        var meta = e.metaKey || e.ctrlKey;
        var shift = e.shiftKey;
        if (key === 44) {
            e.preventDefault();
            recordViolation('screenshot_attempt');
            return;
        }
        if (meta && shift && (key === 51 || key === 52)) {
            e.preventDefault();
            recordViolation('screenshot_attempt');
            return;
        }
        if (e.ctrlKey && shift && (key === 73 || key === 74 || key === 67)) {
            e.preventDefault();
            recordViolation('screenshot_attempt');
            return;
        }
        if ((e.ctrlKey || e.metaKey) && key === 85) {
            e.preventDefault();
            recordViolation('screenshot_attempt');
        }
    }, true);

    if (postFaceBtn) {
        postFaceBtn.addEventListener('click', function () {
            goToFinalPhoto();
        });
    }

    // --- Window / fullscreen enforcement (resize, exit fullscreen) ---
    var resizeBlurOverlay = document.getElementById('resize-blur-overlay');
    var resizeBlurTitle = document.getElementById('resize-blur-title');
    var resizeBlurMessage = document.getElementById('resize-blur-message');
    var resizeBlurWarning = document.getElementById('resize-blur-warning');
    var resizeBlurFinalWarning = document.getElementById('resize-blur-final-warning');
    var enterFsBtn = document.getElementById('resize-blur-enter-fs-btn');
    var windowResizeLimit = (window.QuizSnapQuiz && window.QuizSnapQuiz.windowResizeLimit) || 3;
    var fullscreenEnforced = c.fullscreenEnforcement !== false;
    var ws = window.QuizSnapWindowState;
    if (!ws || !ws.isFullscreenOrMaximized || !ws.requestFullscreen) {
        console.warn('[QuizSnap] quiz-window-state.js must load before quiz-proctoring.js');
    }
    var fsDebug = (ws && ws.fsDebug) ? ws.fsDebug : function () {};
    var isFullscreenOrMaximized = (ws && ws.isFullscreenOrMaximized)
        ? ws.isFullscreenOrMaximized.bind(ws)
        : function () {
            return !!(document.fullscreenElement || document.webkitFullscreenElement || document.mozFullScreenElement);
        };
    var fullscreenDeniedMessage = (ws && ws.getFullscreenDeniedMessage)
        ? ws.getFullscreenDeniedMessage()
        : 'Could not enter full screen.';
    var wasFullscreenOrMaximized = isFullscreenOrMaximized();
    var invalidStateTimer = null;
    var INVALID_PERSISTENCE_MS = 1500;
    // Track how many times the user has left fullscreen / maximized state during this quiz.
    // First time: show strong warning that next time will auto-submit.
    // Second time: record critical window_resize violation (server will auto-submit).
    var windowResizeExitCount = 0;
    var fsEnterGraceUntil = 0;
    var FS_ENTER_GRACE_MS = 2500;
    var cameraStartDeferred = false;
    var onFullscreenReadyCallback = null;

    function markFullscreenEntered() {
        wasFullscreenOrMaximized = isFullscreenOrMaximized();
        fsEnterGraceUntil = Date.now() + FS_ENTER_GRACE_MS;
        fsDebug('fullscreen entered / confirmed', {
            active: wasFullscreenOrMaximized,
            graceUntil: fsEnterGraceUntil
        });
        if (wasFullscreenOrMaximized) {
            hideResizeBlur();
            tryStartCameraWhenAllowed('after-fullscreen-entered');
        }
    }

    function tryStartCameraWhenAllowed(reason) {
        if (!cameraRequired || cameraStream) return;
        if (fullscreenEnforced && !isFullscreenOrMaximized()) {
            cameraStartDeferred = true;
            fsDebug('camera start deferred (waiting for fullscreen)', { reason: reason });
            return;
        }
        cameraStartDeferred = false;
        fsDebug('starting camera', { reason: reason });
        if (typeof onFullscreenReadyCallback === 'function') {
            onFullscreenReadyCallback(reason);
        }
    }

    function clearInvalidStateTimer() {
        if (invalidStateTimer) {
            clearTimeout(invalidStateTimer);
            invalidStateTimer = null;
        }
    }

    function showResizeBlur(showFinalWarning, isInitialGate) {
        if (!resizeBlurOverlay) return;
        resizeBlurOverlay.classList.remove('hidden');
        resizeBlurOverlay.classList.add('flex');
        resizeBlurOverlay.setAttribute('aria-hidden', 'false');
        document.body.classList.add('quiz-fs-blocked');
        if (enterFsBtn) enterFsBtn.classList.remove('hidden');
        if (resizeBlurWarning) {
            if (isInitialGate) {
                resizeBlurWarning.classList.add('hidden');
            } else {
                resizeBlurWarning.classList.remove('hidden');
                resizeBlurWarning.textContent = 'Repeated violations will result in auto-submission of your quiz.';
            }
        }
        if (resizeBlurFinalWarning) {
            if (showFinalWarning) {
                resizeBlurFinalWarning.classList.remove('hidden');
            } else {
                resizeBlurFinalWarning.classList.add('hidden');
            }
        }
    }

    function hideResizeBlur() {
        if (!resizeBlurOverlay) return;
        resizeBlurOverlay.classList.add('hidden');
        resizeBlurOverlay.classList.remove('flex');
        resizeBlurOverlay.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('quiz-fs-blocked');
        if (resizeBlurWarning) resizeBlurWarning.classList.add('hidden');
        if (resizeBlurFinalWarning) resizeBlurFinalWarning.classList.add('hidden');
    }

    function onWindowResizeOrExitFullscreen() {
        if (c.proctoringTabSwitch === false) return;
        if (remainingSeconds <= 0) return;
        if (!wasFullscreenOrMaximized) return;

        // Mark that we have left fullscreen/maximized at least once
        wasFullscreenOrMaximized = false;
        windowResizeExitCount++;

        var isSecondExit = windowResizeExitCount >= 2;

        // Show overlay every time, but on the first exit, also show the final warning
        // so the message matches behaviour: "One more resize will auto-submit your quiz."
        if (resizeBlurTitle) resizeBlurTitle.textContent = 'Window resized or left full screen';
        if (resizeBlurMessage) resizeBlurMessage.textContent = 'Return to full screen to continue. The timer is still running.';
        showResizeBlur(!isSecondExit); // first exit => showFinalWarning=true, second => already warned

        // First exit: warn only (no violation yet).
        if (!isSecondExit) {
            return;
        }

        // Second (or later) exit: record critical violation; backend auto-submits.
        var timestamp = new Date().toISOString();
        recordViolation('window_resize', { timestamp: timestamp });
        if (resizeBlurWarning) resizeBlurWarning.classList.remove('hidden');
    }

    function checkWindowState() {
        if (remainingSeconds <= 0) return;
        var nowOk = isFullscreenOrMaximized();
        if (nowOk) {
            clearInvalidStateTimer();
            wasFullscreenOrMaximized = true;
            hideResizeBlur();
        }
    }

    var cameraOffOverlay = document.getElementById('camera-off-overlay');
    function isBlockingOverlayVisible() {
        return (resizeBlurOverlay && !resizeBlurOverlay.classList.contains('hidden')) ||
            (cameraOffOverlay && !cameraOffOverlay.classList.contains('hidden'));
    }
    if (resizeBlurOverlay || cameraOffOverlay) {
        document.addEventListener('keydown', function (e) {
            if (isBlockingOverlayVisible()) {
                e.preventDefault();
                e.stopPropagation();
            }
        }, true);
        document.addEventListener('keypress', function (e) {
            if (isBlockingOverlayVisible()) {
                e.preventDefault();
                e.stopPropagation();
            }
        }, true);
    }

    function showProctorMessage(message, useAlert) {
        if (useAlert && !isFullscreenOrMaximized()) {
            alert(message);
            return;
        }
        if (resizeBlurOverlay && fullscreenEnforced && !isFullscreenOrMaximized()) {
            if (resizeBlurTitle) resizeBlurTitle.textContent = 'Action required';
            if (resizeBlurMessage) resizeBlurMessage.textContent = message;
            showResizeBlur(false);
            return;
        }
        if (cameraOffOverlay && cameraRequired) {
            showCameraOffOverlay();
        }
        fsDebug('proctor message (no alert — would exit fullscreen)', { message: message });
    }

    function handleFullscreenChange(source) {
        if (remainingSeconds <= 0) return;
        var active = isFullscreenOrMaximized();
        fsDebug('fullscreen state change', {
            source: source || 'fullscreenchange',
            active: active,
            wasActive: wasFullscreenOrMaximized,
            graceMsLeft: Math.max(0, fsEnterGraceUntil - Date.now())
        });
        if (active) {
            clearInvalidStateTimer();
            markFullscreenEntered();
            return;
        }
        if (!wasFullscreenOrMaximized) return;
        if (Date.now() < fsEnterGraceUntil) {
            fsDebug('ignored fullscreen exit during grace (likely camera/layout churn)', { source: source });
            setTimeout(checkWindowState, 400);
            return;
        }
        fsDebug('confirmed fullscreen exit', { source: source });
        showResizeBlur(false);
        if (invalidStateTimer) return;
        invalidStateTimer = setTimeout(function () {
            invalidStateTimer = null;
            if (!isFullscreenOrMaximized()) {
                onWindowResizeOrExitFullscreen();
            }
        }, INVALID_PERSISTENCE_MS);
    }

    function handleWindowResizeForDebug() {
        fsDebug('window resize (ignored for fullscreen enforcement)', {
            w: window.innerWidth,
            h: window.innerHeight,
            fsActive: isFullscreenOrMaximized()
        });
    }

    var windowStateCheckMs = constrainedDevice ? 1200 : 500;
    if (c.proctoringTabSwitch !== false) {
        // Only fullscreenchange ends fullscreen — NOT window resize (resize fired on FS enter,
        // camera attach, and viewport reflow; old maximized-window detection caused false exits).
        document.addEventListener('fullscreenchange', function () { handleFullscreenChange('fullscreenchange'); });
        document.addEventListener('webkitfullscreenchange', function () { handleFullscreenChange('webkitfullscreenchange'); });
        window.addEventListener('resize', handleWindowResizeForDebug);
        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'visible') checkWindowState();
        });
        window.addEventListener('focus', checkWindowState);
        setInterval(checkWindowState, windowStateCheckMs);
    } else if (fullscreenEnforced && ws && ws.bindFullscreenSync) {
        ws.bindFullscreenSync(checkWindowState);
        window.addEventListener('focus', checkWindowState);
        setInterval(checkWindowState, windowStateCheckMs);
    }

    if (c && typeof c === 'object' && ws && ws.requestFullscreen) {
        c.requestFullscreen = ws.requestFullscreen.bind(ws);
    }

    if (ws && ws.bindKnownFullscreenButtons) {
        ws.bindKnownFullscreenButtons();
    }

    document.addEventListener('quizsnap:fullscreen-entered', function () {
        markFullscreenEntered();
    });

    // Block the quiz until the student is in browser full screen (fullscreen is lost on redirect from quiz-ready).
    if (fullscreenEnforced && !isFullscreenOrMaximized()) {
        wasFullscreenOrMaximized = false;
        cameraStartDeferred = true;
        showResizeBlur(false, true);
        if (resizeBlurTitle) resizeBlurTitle.textContent = 'Full screen required';
        if (resizeBlurMessage) resizeBlurMessage.textContent = 'Your quiz runs in browser full screen so tabs and the address bar are hidden. Click below and choose Allow when your browser asks.';
        fsDebug('quiz load: waiting for fullscreen before camera');
    } else if (fullscreenEnforced && isFullscreenOrMaximized()) {
        markFullscreenEntered();
    } else if (!fullscreenEnforced && c.proctoringTabSwitch === false) {
        hideResizeBlur();
    }

    // --- Camera monitoring during quiz (single background camera stream) ---
    if (cameraRequired) {
        function handleCameraDisconnection() {
            if (remainingSeconds <= 0 || isUnloading) return;
            var panelBadge = document.getElementById('ai-invigilator-badge-panel');
            if (panelBadge) panelBadge.classList.remove('visible');
            showCameraOffOverlay();
        }

        function checkCameraStatus() {
            if (remainingSeconds <= 0 || isUnloading) return;
            if (!cameraStream) {
                handleCameraDisconnection();
                return;
            }
            const videoTrack = cameraStream.getVideoTracks()[0];
            if (!videoTrack || videoTrack.readyState === 'ended') {
                handleCameraDisconnection();
            }
        }

        function requestCameraAndContinue() {
            // Check if page is loaded over HTTPS or localhost (required for camera access)
            if (window.location.protocol !== 'https:' && window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
                showCameraOffOverlay();
                showProctorMessage('Camera access requires HTTPS. Please access this page using https:// or contact your administrator.', true);
                return;
            }

            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                showCameraOffOverlay();
                showProctorMessage('Camera is not supported in this browser. Please use a modern browser like Chrome, Firefox, or Safari.', true);
                return;
            }
            
            console.log('Requesting camera access for quiz monitoring...');
            
            // Request with explicit constraints so browser shows permission prompt
            var constraints = { video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } }, audio: false };
            
            navigator.mediaDevices.getUserMedia(constraints)
                .catch(function (err) {
                    console.warn('Initial camera request failed:', err.name, err.message);
                    // If specific constraints fail, try with basic video
                    if (err && (err.name === 'OverconstrainedError' || err.name === 'NotFoundError')) {
                        console.log('Retrying with basic video constraints...');
                        return navigator.mediaDevices.getUserMedia({ video: true, audio: false });
                    }
                    throw err;
                })
                .then(function (stream) {
                    console.log('Camera access granted successfully');
                    hideCameraOffOverlay();
                    setupMonitoringWithStream(stream);
                })
                .catch(function (err) {
                    console.error('Camera access error:', err);
                    showCameraOffOverlay();
                    var msg = 'Could not access camera. Please allow camera permission when your browser asks, then click "Allow camera & continue" below.';
                    if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                        msg = 'Camera permission was denied. Click "Allow camera & continue" below, then click "Allow" in the browser prompt.';
                    } else if (err.name === 'NotFoundError') {
                        msg = 'No camera found. Please connect a camera and try again.';
                    } else if (err.name === 'NotReadableError') {
                        msg = 'Camera is in use by another app. Close it and click "Allow camera & continue" below.';
                    }
                    if (resizeBlurMessage && fullscreenEnforced && isFullscreenOrMaximized()) {
                        resizeBlurMessage.textContent = msg;
                    }
                    showProctorMessage(msg, !isFullscreenOrMaximized());
                });
        }

        function setupMonitoringWithStream(stream) {
            hideCameraOffOverlay();
            var panelBadge = document.getElementById('ai-invigilator-badge-panel');
            if (panelBadge) panelBadge.classList.add('visible');
            cameraStream = stream;
            const videoTrack = stream.getVideoTracks()[0];
            if (videoTrack) {
                videoTrack.onended = function () {
                    handleCameraDisconnection();
                };
            }

            // Single live camera frame: use first slot only, ensure only one video in the slot
            var frameSlot = document.querySelector('#live-camera-video-slot');
            if (frameSlot) {
                frameSlot.querySelectorAll('video').forEach(function (v) {
                    v.remove();
                });
            }
            var monitorVideo = document.getElementById('face-monitor-video');
            if (!monitorVideo) {
                monitorVideo = document.createElement('video');
                monitorVideo.id = 'face-monitor-video';
                monitorVideo.autoplay = true;
                monitorVideo.playsinline = true;
                monitorVideo.muted = true;
                monitorVideo.width = 640;
                monitorVideo.height = 480;
                if (frameSlot) {
                    var overlay = frameSlot.querySelector('#live-camera-guide-overlay');
                    var kids = Array.prototype.slice.call(frameSlot.children);
                    kids.forEach(function (el) {
                        if (el.id !== 'live-camera-guide-overlay') el.remove();
                    });
                    frameSlot.insertBefore(monitorVideo, overlay || null);
                } else {
                    document.body.appendChild(monitorVideo);
                }
            } else if (frameSlot && !frameSlot.contains(monitorVideo)) {
                var ov = frameSlot.querySelector('#live-camera-guide-overlay');
                frameSlot.insertBefore(monitorVideo, ov || frameSlot.firstChild);
            }
            monitorVideo.classList.add('w-full', 'h-full', 'object-cover');
            monitorVideo.style.display = 'block';
            monitorVideo.setAttribute('playsinline', '');
            monitorVideo.srcObject = stream;

            function startTfMonitoring() {
                if (monitorVideo.readyState < 2 || monitorVideo.videoWidth <= 0) {
                    setTimeout(startTfMonitoring, 400);
                    return;
                }

                if (c.proctoringFaceMonitor !== false && window.QuizSnapIntelligentFaceMonitor) {
                    window.QuizSnapIntelligentFaceMonitor.config = window.QuizSnapIntelligentFaceMonitor.config || {};
                    window.QuizSnapIntelligentFaceMonitor.config.videoElement = monitorVideo;
                    window.QuizSnapIntelligentFaceMonitor.config.violationUrl = violationUrl;
                    window.QuizSnapIntelligentFaceMonitor.config.violationCaptureUrl = c.violationCaptureUrl || '/quiz/violation/capture';
                    window.QuizSnapIntelligentFaceMonitor.config.csrfToken = csrfToken;
                    window.QuizSnapIntelligentFaceMonitor.config.sessionId = c.sessionId || 0;
                    if (window.QuizSnapIntelligentFaceMonitor.start) {
                        window.QuizSnapIntelligentFaceMonitor.start();
                    }
                    if (window.QuizSnapIntelligentFaceMonitor.startQuizMonitoring) {
                        window.QuizSnapIntelligentFaceMonitor.startQuizMonitoring();
                    }
                }

                if (c.proctoringObjectDetect !== false && window.QuizSnapObjectMonitor) {
                    window.QuizSnapObjectMonitor.config = window.QuizSnapObjectMonitor.config || {};
                    window.QuizSnapObjectMonitor.config.videoElement = monitorVideo;
                    window.QuizSnapObjectMonitor.config.violationCaptureUrl = c.violationCaptureUrl || '/quiz/violation/capture';
                    window.QuizSnapObjectMonitor.config.csrfToken = csrfToken;
                    window.QuizSnapObjectMonitor.config.sessionId = c.sessionId || 0;
                    window.QuizSnapObjectMonitor.config.onViolation = function (violation) {
                        if (violation.type === 'phone_detected') {
                            showPhoneDetectedSubmitted(null);
                            if (window.QuizSnapProctorState) {
                                window.QuizSnapProctorState.lastPhoneDetectedAt = Date.now();
                            }
                        }
                        recordViolation(violation.type || 'other', violation.metadata || {});
                    };
                    if (window.QuizSnapObjectMonitor.start) {
                        window.QuizSnapObjectMonitor.start();
                    }
                }

            }

            monitorVideo.play().then(startTfMonitoring).catch(function () {
                setTimeout(startTfMonitoring, 800);
            });
            monitorVideo.addEventListener('loadeddata', startTfMonitoring, { once: true });
            monitorVideo.addEventListener('canplay', startTfMonitoring, { once: true });

            startCameraProtection();
            requestWakeLock();
            cameraCheckInterval = setInterval(checkCameraStatus, 2000);
        }

        // Start camera only after fullscreen is active (getUserMedia can exit fullscreen on some browsers).
        var cameraOffAllowBtn = document.getElementById('camera-off-allow-btn');
        if (cameraOffAllowBtn) {
            cameraOffAllowBtn.addEventListener('click', function () {
                requestCameraAndContinue();
            });
        }
        onFullscreenReadyCallback = function (reason) {
            if (cameraStream) return;
            fsDebug('onFullscreenReadyCallback', { reason: reason });
            requestCameraAndContinue();
        };
        if (!fullscreenEnforced || isFullscreenOrMaximized()) {
            tryStartCameraWhenAllowed('quiz-load-no-fs-gate');
        } else {
            fsDebug('camera deferred on load until user enters fullscreen');
        }

        if (navigator.mediaDevices && navigator.mediaDevices.ondevicechange !== undefined) {
            navigator.mediaDevices.addEventListener('devicechange', checkCameraStatus);
        }

        window.addEventListener('beforeunload', function () {
            var panelBadge = document.getElementById('ai-invigilator-badge-panel');
            if (panelBadge) panelBadge.classList.remove('visible');
            releaseWakeLock();
            stopCameraProtection();
            if (cameraCheckInterval) clearInterval(cameraCheckInterval);
            if (window.QuizSnapObjectMonitor && window.QuizSnapObjectMonitor.stop) {
                window.QuizSnapObjectMonitor.stop();
            }
            if (window.QuizSnapIntelligentFaceMonitor && window.QuizSnapIntelligentFaceMonitor.stop) {
                window.QuizSnapIntelligentFaceMonitor.stop();
            }
            if (cameraStream) {
                cameraStream.getTracks().forEach(function (track) {
                    track.stop();
                });
            }
        });
    }

    if (window.QuizSnapQuiz) {
        window.QuizSnapQuiz.addViolationMessage = addViolationMessage;
        window.QuizSnapQuiz.setCriticalViolationDisplay = setCriticalViolationDisplay;
    }
})();
