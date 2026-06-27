/**
 * QuizSnap live support — student/public chat widget with images, typing, and sounds.
 */
(function () {
    'use strict';

    var STORAGE_KEY = 'quizsnap_live_support';
    var HIDDEN_CLOSE_MS = 15 * 60 * 1000;
    var panel = document.getElementById('qs-live-support-panel');
    var messagesEl = document.getElementById('qs-live-support-messages');
    var intakeEl = document.getElementById('qs-live-support-intake');
    var composeEl = document.getElementById('qs-live-support-compose');
    var inputEl = document.getElementById('qs-live-support-input');
    var statusEl = document.getElementById('qs-live-support-status');
    var shareWrap = document.getElementById('qs-live-support-share');
    var shareBtn = document.getElementById('qs-live-support-share-btn');
    var sendBtn = document.getElementById('qs-live-support-send');
    var closeBtn = document.getElementById('qs-live-support-close');
    var imageInput = document.getElementById('qs-live-support-image-input');
    var imageBtn = document.getElementById('qs-live-support-image-btn');
    var agentBar = document.getElementById('qs-live-support-agent');
    var headerAvatarEl = document.getElementById('qs-live-support-header-avatar');
    var audioBtn = document.getElementById('qs-live-support-audio-btn');
    var typingEl = document.getElementById('qs-live-support-typing');
    var statusTextEl = document.getElementById('qs-live-support-status-text');
    var statusDotEl = document.getElementById('qs-live-support-status-dot');
    var intakeStartBtn = document.getElementById('qs-live-intake-start');
    var intakeErrorEl = document.getElementById('qs-live-intake-error');
    var intakeLeadEl = document.getElementById('qs-live-support-intake-lead');
    var emojiBarEl = document.getElementById('qs-live-support-emoji-bar');
    var recordingWaveEl = document.getElementById('qs-live-support-recording-wave');
    var recordingBarsEl = document.getElementById('qs-live-support-recording-bars');

    var state = {
        uuid: null,
        token: null,
        lastId: 0,
        pollTimer: null,
        echoChannel: null,
        pc: null,
        localStream: null,
        typingTimer: null,
        typingStopTimer: null,
        isTyping: false,
        inIntake: false,
        pendingOpts: null,
        agentsOnline: null,
        sessionStatus: null,
        agentDongPlayed: false,
        hiddenSince: null,
        hiddenCloseTimer: null,
        audioRecorder: null,
        recordingWaveform: null,
        pendingRemoteIce: [],
    };

    function csrf() {
        var m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute('content') : '';
    }

    function headers(json) {
        var h = {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrf(),
        };
        if (json !== false) h['Content-Type'] = 'application/json';
        if (state.token) h['X-Support-Session-Token'] = state.token;
        return h;
    }

    function config() {
        return window.QuizSnapSupportConfig || {};
    }

    function defaultContext() {
        return config().defaultContext || {};
    }

    function requiresGuestDetails() {
        return !!config().requiresGuestDetails;
    }

    function sounds() {
        return window.QuizSnapSupportSounds || null;
    }

    function media() {
        return window.QuizSnapSupportMedia || null;
    }

    function loadStored() {
        if (requiresGuestDetails()) return;
        try {
            var raw = sessionStorage.getItem(STORAGE_KEY);
            if (!raw) return;
            var data = JSON.parse(raw);
            if (data && data.uuid && data.token) {
                state.uuid = data.uuid;
                state.token = data.token;
            }
        } catch (e) {}
    }

    function saveStored() {
        if (!state.uuid || !state.token) return;
        sessionStorage.setItem(STORAGE_KEY, JSON.stringify({ uuid: state.uuid, token: state.token }));
    }

    function setStatus(text, tone) {
        if (statusTextEl) statusTextEl.textContent = text || '';
        else if (statusEl) statusEl.textContent = text || '';
        if (statusDotEl) {
            statusDotEl.classList.remove('is-online', 'is-waiting');
            if (tone === 'online') statusDotEl.classList.add('is-online');
            if (tone === 'waiting') statusDotEl.classList.add('is-waiting');
        }
    }

    function setAgent(text) {
        if (!agentBar) return;
        if (!text) {
            agentBar.textContent = '';
            agentBar.hidden = true;
            return;
        }
        agentBar.textContent = text;
        agentBar.hidden = false;
    }

    function updateAgentPresentation(admin) {
        if (!admin) {
            setAgent('');
            return;
        }
        var label = 'Agent: ' + agentChatName(admin);
        if (agentBar) {
            agentBar.hidden = false;
            if (media() && admin.avatar) {
                agentBar.innerHTML = media().renderAvatarHtml(admin.avatar, 'qs-live-support-agent__avatar') +
                    '<span>' + label + '</span>';
            } else {
                agentBar.textContent = label;
            }
        }
        if (headerAvatarEl && media() && admin.avatar) {
            headerAvatarEl.innerHTML = media().renderAvatarHtml(admin.avatar, '');
        }
    }

    function setTyping(text) {
        if (!typingEl) return;
        var show = !!(text && String(text).trim());
        var labelEl = typingEl.querySelector('.qs-typing-label');
        if (labelEl) labelEl.textContent = show ? text : '';
        typingEl.hidden = !show;
        typingEl.setAttribute('aria-hidden', show ? 'false' : 'true');
    }

    function showIntake(show) {
        state.inIntake = !!show;
        if (panel) panel.classList.toggle('is-intake', !!show);
        if (intakeEl) intakeEl.hidden = !show;
        if (messagesEl) messagesEl.hidden = show;
        if (composeEl) composeEl.hidden = show;
        if (emojiBarEl) emojiBarEl.hidden = show;
        if (statusEl) statusEl.hidden = show;
        if (agentBar && show) agentBar.hidden = true;
        if (shareWrap) {
            if (show) shareWrap.classList.remove('is-visible');
            shareWrap.hidden = show;
        }
        if (recordingWaveEl) recordingWaveEl.classList.remove('is-active');
        if (show) {
            setTyping('');
            state.isTyping = false;
            if (state.typingStopTimer) {
                clearTimeout(state.typingStopTimer);
                state.typingStopTimer = null;
            }
        }
    }

    function setOpen(open) {
        if (!panel) return;
        panel.classList.toggle('is-open', open);
        panel.setAttribute('aria-hidden', open ? 'false' : 'true');
        document.body.classList.toggle('qs-live-chat-open', open);
        if (open) {
            document.body.dataset.qsChatScroll = document.body.style.overflow || '';
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = document.body.dataset.qsChatScroll || '';
            delete document.body.dataset.qsChatScroll;
        }
    }

    function showIntakeError(msg) {
        if (!intakeErrorEl) return;
        intakeErrorEl.textContent = msg || '';
        intakeErrorEl.hidden = !msg;
    }

    function isValidPhone(raw) {
        if (!raw || !String(raw).trim()) return false;
        var trimmed = String(raw).trim();
        if (/[a-zA-Z]/.test(trimmed)) return false;
        if (!/^[\d\s+\-().]+$/.test(trimmed)) return false;
        var digits = trimmed.replace(/\D/g, '');
        return digits.length >= 9 && digits.length <= 15;
    }

    function scrollBottom() {
        if (messagesEl) messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    function formatTime(iso) {
        if (!iso) return '';
        try {
            return new Date(iso).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        } catch (e) {
            return '';
        }
    }

    function renderMessage(msg, fromEcho) {
        if (!messagesEl || !msg || !msg.id) return;
        if (msg.sender_type === 'system') return;
        if (document.querySelector('[data-live-msg-id="' + msg.id + '"]')) return;
        var div = document.createElement('div');
        var type = msg.sender_type === 'student' ? 'student' : (msg.sender_type === 'system' ? 'system' : 'admin');
        div.className = 'qs-live-msg qs-live-msg--' + type;
        div.setAttribute('data-live-msg-id', String(msg.id));
        var bubble = document.createElement('div');
        bubble.className = 'qs-live-msg__bubble';

        if (msg.message_type === 'image' && msg.meta && msg.meta.url && media()) {
            media().appendMessageMedia(bubble, msg);
        } else if (msg.message_type === 'audio' && msg.meta && msg.meta.url && media()) {
            media().appendMessageMedia(bubble, msg);
        } else {
            bubble.textContent = msg.body || '';
        }

        var time = document.createElement('span');
        time.className = 'qs-live-msg__time';
        time.textContent = formatTime(msg.created_at);
        div.appendChild(bubble);
        div.appendChild(time);
        messagesEl.appendChild(div);
        if (typeof msg.id === 'number' && msg.id > state.lastId) state.lastId = msg.id;
        scrollBottom();

        if (fromEcho && msg.sender_type === 'admin' && sounds()) {
            if (!panel || !panel.classList.contains('is-open')) {
                sounds().startMessageAlert(state.uuid);
            } else {
                sounds().stopMessageAlert();
                sounds().playMessageOnce();
            }
        }
    }

    function playAgentDongOnce() {
        if (state.agentDongPlayed || !sounds()) return;
        state.agentDongPlayed = true;
        sounds().playAgentAvailable();
    }

    function agentChatName(admin) {
        if (!admin) return 'Support';
        return admin.chat_name || admin.name || 'Support';
    }

    function applySession(session) {
        if (!session) return;
        var prevStatus = state.sessionStatus;
        state.sessionStatus = session.status;
        if (session.status === 'active' && session.assigned_admin) {
            setStatus('Connected with support', 'online');
            updateAgentPresentation(session.assigned_admin);
            if (prevStatus === 'waiting') playAgentDongOnce();
        } else if (session.status === 'waiting') {
            setStatus('Waiting for an agent…', 'waiting');
            setAgent('');
        } else if (session.status === 'closed') {
            setStatus('Chat closed');
            setAgent('');
        }
        if (session.screen_share_active && shareWrap) shareWrap.classList.add('is-visible');
    }

    function ingestMessages(list, fromEcho) {
        if (!Array.isArray(list)) return;
        list.forEach(function (m) {
            renderMessage(m, fromEcho);
            if (m.message_type === 'webrtc' && m.meta) {
                if (m.meta.signal === 'request_screen') {
                    if (shareWrap) shareWrap.classList.add('is-visible');
                } else {
                    handleRemoteSignal(m.meta);
                }
            }
        });
    }

    function pollMessages() {
        if (!state.uuid) return;
        fetch('/support/sessions/' + encodeURIComponent(state.uuid) + '/messages?since=' + state.lastId, { headers: headers() })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) ingestMessages(data.messages, true);
            })
            .catch(function () {});
    }

    function startPolling() {
        stopPolling();
        pollMessages();
        state.pollTimer = setInterval(pollMessages, 2500);
    }

    function stopPolling() {
        if (state.pollTimer) clearInterval(state.pollTimer);
        state.pollTimer = null;
    }

    function sendTypingSignal(typing) {
        if (!state.uuid || state.inIntake) return;
        fetch('/support/sessions/' + encodeURIComponent(state.uuid) + '/typing', {
            method: 'POST',
            headers: headers(),
            body: JSON.stringify({ typing: !!typing }),
        }).catch(function () {});
    }

    function onInputTyping() {
        if (!state.uuid || state.inIntake) return;
        if (!state.isTyping) {
            state.isTyping = true;
            sendTypingSignal(true);
        }
        if (state.typingStopTimer) clearTimeout(state.typingStopTimer);
        state.typingStopTimer = setTimeout(function () {
            state.isTyping = false;
            sendTypingSignal(false);
        }, 1400);
        if (sounds()) sounds().playTypingLocal();
    }

    function bindEcho() {
        if (!window.QuizSnapReverb || !state.uuid || !state.token) return;
        var channelName = 'private-support-session.' + state.uuid;
        state.echoChannel = window.QuizSnapReverb.subscribePrivate(channelName, {
            'X-Support-Session-Token': state.token,
        });
        if (!state.echoChannel) return;
        state.echoChannel.bind('SupportMessageSent', function (payload) {
            if (payload && payload.message) {
                renderMessage(payload.message, true);
                if (payload.message.message_type === 'webrtc' && payload.message.meta) {
                    if (payload.message.meta.signal === 'request_screen') {
                        if (shareWrap) shareWrap.classList.add('is-visible');
                    } else {
                        handleRemoteSignal(payload.message.meta);
                    }
                }
            }
        });
        state.echoChannel.bind('SupportSessionUpdated', function (payload) {
            if (payload && payload.session) applySession(payload.session);
        });
        state.echoChannel.bind('SupportTyping', function (payload) {
            if (!payload || payload.sender_type === 'student' || state.inIntake) return;
            if (payload.is_typing === true) {
                setTyping((payload.sender_label || 'Agent') + ' is typing');
                if (sounds()) sounds().playTyping();
            } else {
                setTyping('');
            }
        });
    }

    function fetchAvailability() {
        return fetch('/support/availability', { headers: headers() })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) state.agentsOnline = !!data.agents_online;
                return state.agentsOnline;
            })
            .catch(function () { return null; });
    }

    function clearHiddenCloseTimer() {
        if (state.hiddenCloseTimer) {
            clearTimeout(state.hiddenCloseTimer);
            state.hiddenCloseTimer = null;
        }
        state.hiddenSince = null;
    }

    function scheduleHiddenClose() {
        clearHiddenCloseTimer();
        if (!state.uuid || !document.hidden) return;
        state.hiddenSince = Date.now();
        state.hiddenCloseTimer = setTimeout(function () {
            if (document.hidden && state.uuid) endSession(true);
        }, HIDDEN_CLOSE_MS);
    }

    function endSession(fromIdle) {
        var uuid = state.uuid;
        stopPolling();
        stopScreenShare();
        setOpen(false);
        setTyping('');
        if (uuid) {
            fetch('/support/sessions/' + encodeURIComponent(uuid) + '/close', { method: 'POST', headers: headers() }).catch(function () {});
        }
        if (fromIdle || requiresGuestDetails()) {
            state.uuid = null;
            state.token = null;
            state.lastId = 0;
            state.sessionStatus = null;
            sessionStorage.removeItem(STORAGE_KEY);
            if (messagesEl) messagesEl.innerHTML = '';
        }
        clearHiddenCloseTimer();
    }

    function onVisibilityChange() {
        if (document.hidden) scheduleHiddenClose();
        else clearHiddenCloseTimer();
    }

    document.addEventListener('visibilitychange', onVisibilityChange);

    function createSession(opts) {
        opts = opts || {};
        var ctx = defaultContext();
        setStatus('Starting chat…', 'waiting');
        setAgent('');
        setTyping('');
        showIntake(false);
        if (messagesEl) messagesEl.innerHTML = '';
        state.lastId = 0;
        return fetch('/support/sessions', {
            method: 'POST',
            headers: headers(),
            body: JSON.stringify({
                student_index: opts.student_index || ctx.index_number || null,
                student_name: opts.student_name || ctx.name || null,
                student_phone: opts.student_phone || ctx.phone || null,
                page_url: opts.page_url || ctx.page || window.location.pathname,
                issue_category: opts.issue_category || 'general',
                initial_message: opts.initial_message || null,
            }),
        })
            .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, data: d }; }); })
            .then(function (res) {
                if (!res.ok || !res.data.success) {
                    var msg = res.data.message || 'Could not start chat';
                    if (res.data.errors) {
                        var keys = Object.keys(res.data.errors);
                        if (keys.length && res.data.errors[keys[0]][0]) {
                            msg = res.data.errors[keys[0]][0];
                        }
                    }
                    throw new Error(msg);
                }
                state.uuid = res.data.session.uuid;
                state.token = res.data.client_token;
                saveStored();
                applySession(res.data.session);
                startPolling();
                bindEcho();
                return res.data;
            });
    }

    function resumeSession() {
        if (!state.uuid) return Promise.reject();
        showIntake(false);
        return fetch('/support/sessions/' + encodeURIComponent(state.uuid), { headers: headers() })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success) {
                    state.uuid = null;
                    state.token = null;
                    sessionStorage.removeItem(STORAGE_KEY);
                    return beginChat(state.pendingOpts || {});
                }
                applySession(data.session);
                startPolling();
                bindEcho();
                return fetch('/support/sessions/' + encodeURIComponent(state.uuid) + '/messages', { headers: headers() })
                    .then(function (r) { return r.json(); })
                    .then(function (m) { if (m.success) ingestMessages(m.messages, false); });
            });
    }

    function prefillIntake(opts) {
        var ctx = defaultContext();
        var nameEl = document.getElementById('qs-live-intake-name');
        var phoneEl = document.getElementById('qs-live-intake-phone');
        var indexEl = document.getElementById('qs-live-intake-index');
        if (nameEl) nameEl.value = opts.student_name || ctx.name || '';
        if (phoneEl) phoneEl.value = opts.student_phone || ctx.phone || '';
        if (indexEl) indexEl.value = opts.student_index || ctx.index_number || '';
    }

    function beginChat(opts) {
        opts = opts || {};
        if (state.uuid) return resumeSession();
        if (requiresGuestDetails() && !opts._intakeComplete) {
            state.uuid = null;
            state.token = null;
            state.lastId = 0;
            state.sessionStatus = null;
            sessionStorage.removeItem(STORAGE_KEY);
            state.pendingOpts = opts;
            showIntake(true);
            prefillIntake(opts);
            showIntakeError('');
            setStatus('Enter your details to start', 'waiting');
            return fetchAvailability().then(function (online) {
                if (online === true && sounds()) sounds().playAgentAvailable();
                if (intakeLeadEl) {
                    intakeLeadEl.textContent = online === false
                        ? 'Agents are away. Enter your name, index, and phone, then leave your message.'
                        : 'An agent is available. Enter your name, index, and phone to start.';
                }
            });
        }
        return createSession(opts);
    }

    function sendText(text) {
        if (!state.uuid || !text) return;
        var tempId = 'pending-' + Date.now();
        renderMessage({
            id: tempId,
            sender_type: 'student',
            body: text,
            created_at: new Date().toISOString(),
            message_type: 'text',
        }, false);
        fetch('/support/sessions/' + encodeURIComponent(state.uuid) + '/messages', {
            method: 'POST',
            headers: headers(),
            body: JSON.stringify({ body: text, message_type: 'text' }),
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                var pending = document.querySelector('[data-live-msg-id="' + tempId + '"]');
                if (pending) pending.remove();
                if (data.success && data.message) renderMessage(data.message, false);
            })
            .catch(function () {
                var pending = document.querySelector('[data-live-msg-id="' + tempId + '"]');
                if (pending) pending.remove();
            });
        state.isTyping = false;
        sendTypingSignal(false);
    }

    function uploadAudio(blob) {
        if (!state.uuid || !blob) return;
        var fd = new FormData();
        fd.append('audio', blob, 'voice-message.webm');
        fetch('/support/sessions/' + encodeURIComponent(state.uuid) + '/upload-audio', {
            method: 'POST',
            headers: headers(false),
            body: fd,
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success && data.message) renderMessage(data.message, false);
            });
    }

    function hideRecordingWave() {
        if (recordingWaveEl) recordingWaveEl.classList.remove('is-active');
        if (state.recordingWaveform) {
            state.recordingWaveform.reset();
            state.recordingWaveform.destroy();
            state.recordingWaveform = null;
        }
    }

    function showRecordingWave(rec) {
        if (!media() || !recordingWaveEl || !recordingBarsEl) return;
        hideRecordingWave();
        state.recordingWaveform = media().createWaveform(recordingBarsEl, 18);
        recordingWaveEl.classList.add('is-active');
        rec.onLevels(function (levels) {
            if (state.recordingWaveform) state.recordingWaveform.update(levels);
        });
    }

    function toggleAudioRecording() {
        if (!media() || !state.uuid) return;
        if (!state.audioRecorder) state.audioRecorder = media().createRecorder();
        var rec = state.audioRecorder;
        if (rec.isRecording()) {
            if (audioBtn) audioBtn.classList.remove('is-recording');
            hideRecordingWave();
            rec.stop().then(function (blob) {
                if (blob && blob.size > 0) uploadAudio(blob);
            });
            return;
        }
        rec.start().then(function () {
            if (audioBtn) audioBtn.classList.add('is-recording');
            showRecordingWave(rec);
        }).catch(function () {
            hideRecordingWave();
            alert('Microphone access is required to send a voice message.');
        });
    }

    function uploadImage(file) {
        if (!state.uuid || !file) return;
        var fd = new FormData();
        fd.append('image', file);
        fetch('/support/sessions/' + encodeURIComponent(state.uuid) + '/upload-image', {
            method: 'POST',
            headers: headers(false),
            body: fd,
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success && data.message) renderMessage(data.message, false);
            });
    }

    function sendSignal(meta) {
        if (!state.uuid) return;
        var packed = media() && media().packRtcMeta ? media().packRtcMeta(meta) : meta;
        fetch('/support/sessions/' + encodeURIComponent(state.uuid) + '/messages', {
            method: 'POST',
            headers: headers(),
            body: JSON.stringify({ message_type: 'webrtc', meta: packed }),
        });
    }

    function flushRemoteIce() {
        if (!state.pc || !state.pendingRemoteIce.length) return;
        state.pendingRemoteIce.forEach(function (candidate) {
            state.pc.addIceCandidate(candidate).catch(function () {});
        });
        state.pendingRemoteIce = [];
    }

    function queueRemoteIce(candidate) {
        if (!candidate) return;
        var ice = candidate instanceof RTCIceCandidate ? candidate : new RTCIceCandidate(candidate);
        if (state.pc && state.pc.remoteDescription && state.pc.remoteDescription.type) {
            state.pc.addIceCandidate(ice).catch(function () {});
        } else {
            state.pendingRemoteIce.push(ice);
        }
    }

    function stopScreenShare() {
        state.pendingRemoteIce = [];
        if (state.localStream) {
            state.localStream.getTracks().forEach(function (t) { t.stop(); });
            state.localStream = null;
        }
        if (state.pc) {
            state.pc.close();
            state.pc = null;
        }
    }

    function startScreenShare() {
        if (!state.uuid) return;
        if (!navigator.mediaDevices || !navigator.mediaDevices.getDisplayMedia) {
            alert('Screen sharing is not supported in this browser.');
            return;
        }
        stopScreenShare();
        navigator.mediaDevices.getDisplayMedia({ video: true, audio: false })
            .then(function (stream) {
                state.localStream = stream;
                var videoTrack = stream.getVideoTracks()[0];
                if (videoTrack) {
                    videoTrack.onended = function () {
                        stopScreenShare();
                        setStatus('Screen share ended.');
                        if (shareWrap) shareWrap.classList.add('is-visible');
                    };
                }
                state.pc = new RTCPeerConnection({ iceServers: [{ urls: 'stun:stun.l.google.com:19302' }] });
                stream.getTracks().forEach(function (track) {
                    state.pc.addTrack(track, stream);
                });
                state.pc.onicecandidate = function (ev) {
                    if (ev.candidate) sendSignal({ signal: 'ice', candidate: ev.candidate });
                };
                return state.pc.createOffer();
            })
            .then(function (offer) {
                return state.pc.setLocalDescription(offer).then(function () { return offer; });
            })
            .then(function (offer) {
                sendSignal({ signal: 'offer', sdp: offer });
                setStatus('Sharing your screen…');
                if (shareWrap) shareWrap.classList.remove('is-visible');
            })
            .catch(function () {
                stopScreenShare();
                setStatus('Screen share cancelled.');
            });
    }

    function handleRemoteSignal(meta) {
        if (!meta || !meta.signal) return;
        if (meta.signal === 'answer' && state.pc && meta.sdp) {
            state.pc.setRemoteDescription(new RTCSessionDescription(meta.sdp))
                .then(flushRemoteIce)
                .catch(function () {});
        }
        if (meta.signal === 'ice') {
            queueRemoteIce(meta.candidate);
        }
    }

    loadStored();

    var intakePhoneEl = document.getElementById('qs-live-intake-phone');
    if (intakePhoneEl) {
        intakePhoneEl.addEventListener('input', function () {
            var cleaned = intakePhoneEl.value.replace(/[a-zA-Z]/g, '');
            if (cleaned !== intakePhoneEl.value) intakePhoneEl.value = cleaned;
        });
    }

    if (intakeStartBtn) {
        intakeStartBtn.addEventListener('click', function () {
            var name = (document.getElementById('qs-live-intake-name') || {}).value || '';
            var phone = (document.getElementById('qs-live-intake-phone') || {}).value || '';
            var index = (document.getElementById('qs-live-intake-index') || {}).value || '';
            name = name.trim();
            phone = phone.trim();
            index = index.trim();
            if (!name || name.length < 2) { showIntakeError('Please enter your full name.'); return; }
            if (!index) { showIntakeError('Please enter your index number.'); return; }
            if (!phone) { showIntakeError('Please enter your phone number.'); return; }
            if (!isValidPhone(phone)) {
                showIntakeError('Please enter a valid phone number using digits only (e.g. 0241234567).');
                return;
            }
            showIntakeError('');
            var opts = Object.assign({}, state.pendingOpts || {}, {
                student_name: name,
                student_index: index,
                student_phone: phone,
                _intakeComplete: true,
            });
            createSession(opts).catch(function (err) {
                showIntake(true);
                showIntakeError(err.message || 'Could not start chat.');
            });
        });
    }

    if (sendBtn && inputEl) {
        sendBtn.addEventListener('click', function () {
            var text = inputEl.value.trim();
            if (!text) return;
            inputEl.value = '';
            if (window.QuizSnapSupportCompose) QuizSnapSupportCompose.autoGrow(inputEl);
            sendText(text);
        });
        if (window.QuizSnapSupportCompose) {
            QuizSnapSupportCompose.bindTextarea(inputEl, sendBtn);
            QuizSnapSupportCompose.mountEmojiBar(document.getElementById('qs-live-support-emoji-bar'), inputEl);
        } else {
            inputEl.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendBtn.click(); }
                else onInputTyping();
            });
            inputEl.addEventListener('input', onInputTyping);
        }
        inputEl.addEventListener('input', onInputTyping);
    }

    if (imageBtn && imageInput) {
        imageBtn.addEventListener('click', function () { imageInput.click(); });
        imageInput.addEventListener('change', function () {
            if (imageInput.files && imageInput.files[0]) {
                uploadImage(imageInput.files[0]);
                imageInput.value = '';
            }
        });
    }
    if (audioBtn) audioBtn.addEventListener('click', toggleAudioRecording);

    if (shareBtn) shareBtn.addEventListener('click', startScreenShare);
    if (closeBtn) closeBtn.addEventListener('click', function () { endSession(false); });

    window.QuizSnapLiveSupport = {
        open: function (opts) {
            opts = opts || {};
            setOpen(true);
            if (sounds()) sounds().stopMessageAlert();
            clearHiddenCloseTimer();
            state.agentDongPlayed = false;
            if (requiresGuestDetails() && !opts._intakeComplete) {
                state.uuid = null;
                state.token = null;
                sessionStorage.removeItem(STORAGE_KEY);
            }
            if (sounds()) sounds().unlock();
            var p = beginChat(opts);
            if (p && p.catch) {
                p.catch(function (err) { setStatus(err.message || 'Could not connect. Try again.'); });
            }
        },
        close: function () { endSession(false); },
    };
})();
