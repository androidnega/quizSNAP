/**
 * QuizSnap live support — student/public chat widget with images and optional screen share.
 */
(function () {
    'use strict';

    var STORAGE_KEY = 'quizsnap_live_support';
    var panel = document.getElementById('qs-live-support-panel');
    var messagesEl = document.getElementById('qs-live-support-messages');
    var inputEl = document.getElementById('qs-live-support-input');
    var statusEl = document.getElementById('qs-live-support-status');
    var shareWrap = document.getElementById('qs-live-support-share');
    var shareBtn = document.getElementById('qs-live-support-share-btn');
    var sendBtn = document.getElementById('qs-live-support-send');
    var closeBtn = document.getElementById('qs-live-support-close');
    var imageInput = document.getElementById('qs-live-support-image-input');
    var imageBtn = document.getElementById('qs-live-support-image-btn');
    var agentBar = document.getElementById('qs-live-support-agent');

    var state = {
        uuid: null,
        token: null,
        lastId: 0,
        pollTimer: null,
        echoChannel: null,
        pc: null,
        localStream: null,
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

    function defaultContext() {
        var cfg = window.QuizSnapSupportConfig || {};
        return cfg.defaultContext || {};
    }

    function loadStored() {
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

    function setOpen(open) {
        if (!panel) return;
        panel.classList.toggle('is-open', open);
        panel.setAttribute('aria-hidden', open ? 'false' : 'true');
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

    function renderMessage(msg) {
        if (!messagesEl || !msg || !msg.id) return;
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
        if (msg.id > state.lastId) state.lastId = msg.id;
        scrollBottom();
    }

    function applySession(session) {
        if (!session) return;
        if (session.status === 'active' && session.assigned_admin) {
            setStatus('Connected');
            setAgent('Agent: ' + (session.assigned_admin.name || 'Support'));
        } else if (session.status === 'waiting') {
            setStatus('Waiting for an agent…');
            setAgent('');
        } else if (session.status === 'closed') {
            setStatus('Chat closed');
            setAgent('');
        }
        if (session.screen_share_active && shareWrap) shareWrap.classList.add('is-visible');
    }

    function ingestMessages(list) {
        if (!Array.isArray(list)) return;
        list.forEach(function (m) {
            renderMessage(m);
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
                if (data.success) ingestMessages(data.messages);
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

    function bindEcho() {
        if (!window.QuizSnapReverb || !state.uuid || !state.token) return;
        var channelName = 'private-support-session.' + state.uuid;
        state.echoChannel = window.QuizSnapReverb.subscribePrivate(channelName, {
            'X-Support-Session-Token': state.token,
        });
        if (!state.echoChannel) return;
        state.echoChannel.bind('SupportMessageSent', function (payload) {
            if (payload && payload.message) {
                renderMessage(payload.message);
                if (payload.message.message_type === 'webrtc' && payload.message.meta) {
                    handleRemoteSignal(payload.message.meta);
                }
            }
        });
        state.echoChannel.bind('SupportSessionUpdated', function (payload) {
            if (payload && payload.session) applySession(payload.session);
        });
    }

    function createSession(opts) {
        opts = opts || {};
        var ctx = defaultContext();
        setStatus('Starting chat…');
        setAgent('');
        if (messagesEl) messagesEl.innerHTML = '';
        state.lastId = 0;
        return fetch('/support/sessions', {
            method: 'POST',
            headers: headers(),
            body: JSON.stringify({
                student_index: opts.student_index || ctx.index_number || null,
                student_name: opts.student_name || ctx.name || null,
                student_phone: opts.student_phone || ctx.phone || null,
                student_email: opts.student_email || ctx.email || null,
                page_url: opts.page_url || ctx.page || window.location.pathname,
                issue_category: opts.issue_category || 'general',
                initial_message: opts.initial_message || null,
            }),
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success) throw new Error(data.message || 'Could not start chat');
                state.uuid = data.session.uuid;
                state.token = data.client_token;
                saveStored();
                applySession(data.session);
                startPolling();
                bindEcho();
                return data;
            });
    }

    function resumeSession() {
        if (!state.uuid) return Promise.reject();
        return fetch('/support/sessions/' + encodeURIComponent(state.uuid), { headers: headers() })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success) {
                    state.uuid = null;
                    state.token = null;
                    sessionStorage.removeItem(STORAGE_KEY);
                    return createSession({});
                }
                applySession(data.session);
                startPolling();
                bindEcho();
                return fetch('/support/sessions/' + encodeURIComponent(state.uuid) + '/messages', { headers: headers() })
                    .then(function (r) { return r.json(); })
                    .then(function (m) { if (m.success) ingestMessages(m.messages); });
            });
    }

    function sendText(text) {
        if (!state.uuid || !text) return;
        fetch('/support/sessions/' + encodeURIComponent(state.uuid) + '/messages', {
            method: 'POST',
            headers: headers(),
            body: JSON.stringify({ body: text, message_type: 'text' }),
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success && data.message) renderMessage(data.message);
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
                if (data.success && data.message) renderMessage(data.message);
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

    if (sendBtn && inputEl) {
        sendBtn.addEventListener('click', function () {
            var text = inputEl.value.trim();
            if (!text) return;
            inputEl.value = '';
            sendText(text);
        });
        inputEl.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendBtn.click(); }
        });
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
            setOpen(true);
            var p = state.uuid ? resumeSession() : createSession(opts || {});
            p.catch(function () { setStatus('Could not connect. Try again.'); });
        },
        close: function () { setOpen(false); },
    };
})();
