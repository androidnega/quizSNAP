/**
 * QuizSnap live support — student/public chat widget with images, typing, and sounds.
 */
(function () {
    'use strict';

    var STORAGE_KEY = 'quizsnap_live_support';
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
    var typingEl = document.getElementById('qs-live-support-typing');
    var intakeStartBtn = document.getElementById('qs-live-intake-start');
    var intakeErrorEl = document.getElementById('qs-live-intake-error');
    var intakeLeadEl = document.getElementById('qs-live-support-intake-lead');

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
        pendingOpts: null,
        agentsOnline: null,
        sessionStatus: null,
        agentDongPlayed: false,
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

    function setStatus(text) {
        if (statusEl) statusEl.textContent = text || '';
    }

    function setAgent(text) {
        if (agentBar) {
            agentBar.textContent = text || '';
            agentBar.hidden = !text;
        }
    }

    function setTyping(text) {
        if (typingEl) {
            typingEl.textContent = text || '';
            typingEl.hidden = !text;
        }
    }

    function setOpen(open) {
        if (!panel) return;
        panel.classList.toggle('is-open', open);
        panel.setAttribute('aria-hidden', open ? 'false' : 'true');
    }

    function showIntake(show) {
        if (intakeEl) intakeEl.hidden = !show;
        if (messagesEl) messagesEl.hidden = show;
        if (composeEl) composeEl.hidden = show;
        if (shareWrap && show) shareWrap.classList.remove('is-visible');
    }

    function showIntakeError(msg) {
        if (!intakeErrorEl) return;
        intakeErrorEl.textContent = msg || '';
        intakeErrorEl.hidden = !msg;
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

        if (msg.message_type === 'image' && msg.meta && msg.meta.url) {
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
            sounds().playMessage();
        }
    }

    function playAgentDongOnce() {
        if (state.agentDongPlayed || !sounds()) return;
        state.agentDongPlayed = true;
        sounds().playAgentAvailable();
    }

    function applySession(session) {
        if (!session) return;
        var prevStatus = state.sessionStatus;
        state.sessionStatus = session.status;
        if (session.status === 'active' && session.assigned_admin) {
            setStatus('Connected');
            setAgent('Agent: ' + (session.assigned_admin.name || 'Support'));
            if (prevStatus === 'waiting') playAgentDongOnce();
        } else if (session.status === 'waiting') {
            setStatus('Waiting for an agent to join…');
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
            if (m.message_type === 'webrtc' && m.meta && m.meta.signal === 'request_screen') {
                if (shareWrap) shareWrap.classList.add('is-visible');
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
        if (!state.uuid) return;
        fetch('/support/sessions/' + encodeURIComponent(state.uuid) + '/typing', {
            method: 'POST',
            headers: headers(),
            body: JSON.stringify({ typing: !!typing }),
        }).catch(function () {});
    }

    function onInputTyping() {
        if (!state.uuid) return;
        if (!state.isTyping) {
            state.isTyping = true;
            sendTypingSignal(true);
        }
        if (state.typingStopTimer) clearTimeout(state.typingStopTimer);
        state.typingStopTimer = setTimeout(function () {
            state.isTyping = false;
            sendTypingSignal(false);
        }, 1400);
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
                    handleRemoteSignal(payload.message.meta);
                }
            }
        });
        state.echoChannel.bind('SupportSessionUpdated', function (payload) {
            if (payload && payload.session) applySession(payload.session);
        });
        state.echoChannel.bind('SupportTyping', function (payload) {
            if (!payload || payload.sender_type === 'student') return;
            if (payload.is_typing) {
                setTyping((payload.sender_label || 'Agent') + ' is typing…');
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

    function createSession(opts) {
        opts = opts || {};
        var ctx = defaultContext();
        setStatus('Starting chat…');
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
                if (!res.data.success) throw new Error(res.data.message || 'Could not start chat');
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
        var phoneEl = document.getElementById('qs-live-intake-phone');
        var indexEl = document.getElementById('qs-live-intake-index');
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
            setStatus('Enter your details to start');
            return fetchAvailability().then(function (online) {
                if (online === true && sounds()) sounds().playAgentAvailable();
                if (intakeLeadEl) {
                    intakeLeadEl.textContent = online === false
                        ? 'Agents are away. Enter your index and phone, then leave your message.'
                        : 'An agent is available. Enter your index and phone to start.';
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
        fetch('/support/sessions/' + encodeURIComponent(state.uuid) + '/messages', {
            method: 'POST',
            headers: headers(),
            body: JSON.stringify({ message_type: 'webrtc', meta: meta }),
        });
    }

    function stopScreenShare() {
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
        if (!navigator.mediaDevices || !navigator.mediaDevices.getDisplayMedia) {
            alert('Screen sharing is not supported in this browser.');
            return;
        }
        navigator.mediaDevices.getDisplayMedia({ video: true, audio: false })
            .then(function (stream) {
                state.localStream = stream;
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
                setStatus('Screen share cancelled.');
            });
    }

    function handleRemoteSignal(meta) {
        if (!meta || !meta.signal) return;
        if (meta.signal === 'answer' && state.pc && meta.sdp) {
            state.pc.setRemoteDescription(new RTCSessionDescription(meta.sdp));
        }
        if (meta.signal === 'ice' && state.pc && meta.candidate) {
            state.pc.addIceCandidate(new RTCIceCandidate(meta.candidate)).catch(function () {});
        }
    }

    loadStored();

    if (intakeStartBtn) {
        intakeStartBtn.addEventListener('click', function () {
            var phone = (document.getElementById('qs-live-intake-phone') || {}).value || '';
            var index = (document.getElementById('qs-live-intake-index') || {}).value || '';
            phone = phone.trim();
            index = index.trim();
            if (!index) { showIntakeError('Please enter your index number.'); return; }
            if (!phone) { showIntakeError('Please enter your phone number.'); return; }
            showIntakeError('');
            var opts = Object.assign({}, state.pendingOpts || {}, {
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
            sendText(text);
        });
        inputEl.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendBtn.click(); }
            else onInputTyping();
        });
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

    if (shareBtn) shareBtn.addEventListener('click', startScreenShare);
    if (closeBtn) closeBtn.addEventListener('click', function () {
        setOpen(false);
        if (state.uuid) {
            fetch('/support/sessions/' + encodeURIComponent(state.uuid) + '/close', { method: 'POST', headers: headers() });
        }
        stopScreenShare();
    });

    window.QuizSnapLiveSupport = {
        open: function (opts) {
            opts = opts || {};
            setOpen(true);
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
        close: function () { setOpen(false); },
    };
})();
