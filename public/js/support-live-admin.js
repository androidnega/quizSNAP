/**
 * QuizSnap live support — admin console.
 */
(function () {
    'use strict';

    var cfg = window.QuizSnapLiveSupportAdmin || {};
    var queueEl = document.getElementById('live-support-queue');
    var messagesEl = document.getElementById('live-support-messages');
    var inputEl = document.getElementById('live-support-input');
    var sendBtn = document.getElementById('live-support-send');
    var screenBtn = document.getElementById('live-support-screen-btn');
    var closeBtn = document.getElementById('live-support-close-btn');
    var remoteVideo = document.getElementById('live-support-remote-video');
    var headerEl = document.getElementById('live-support-chat-header');

    var activeUuid = null;
    var lastMessageId = 0;
    var pollTimer = null;
    var messagePollTimer = null;
    var pc = null;

    function csrf() {
        var m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute('content') : '';
    }

    function jsonHeaders() {
        return {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf(),
            'X-Requested-With': 'XMLHttpRequest',
        };
    }

    function url(path) {
        return (cfg.baseUrl || '/dashboard/live-support') + path;
    }

    function renderQueue(sessions) {
        if (!queueEl) return;
        queueEl.innerHTML = '';
        if (!sessions || !sessions.length) {
            queueEl.innerHTML = '<p class="text-sm text-gray-500 p-4">No open chats.</p>';
            return;
        }
        sessions.forEach(function (s) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'live-support-queue-item' + (s.uuid === activeUuid ? ' is-active' : '');
            btn.dataset.uuid = s.uuid;
            var status = s.status === 'waiting' ? 'Waiting' : 'Active';
            btn.innerHTML =
                '<span class="live-support-queue-item__status live-support-queue-item__status--' + s.status + '">' + status + '</span>' +
                '<strong class="block text-sm text-gray-900 truncate">' + (s.student_index || 'Unknown index') + '</strong>' +
                '<span class="block text-xs text-gray-500 truncate">' + (s.issue_category || 'general') + '</span>';
            btn.addEventListener('click', function () { openSession(s.uuid); });
            queueEl.appendChild(btn);
        });
    }

    function renderMessage(msg) {
        if (!messagesEl || !msg || !msg.id) return;
        if (document.querySelector('[data-admin-msg-id="' + msg.id + '"]')) return;
        if (msg.message_type === 'webrtc') return;
        var div = document.createElement('div');
        var type = msg.sender_type === 'admin' ? 'admin' : (msg.sender_type === 'system' ? 'system' : 'student');
        div.className = 'live-support-msg live-support-msg--' + type;
        div.setAttribute('data-admin-msg-id', String(msg.id));
        div.innerHTML = '<div class="live-support-msg__bubble">' + escapeHtml(msg.body || '') + '</div>';
        messagesEl.appendChild(div);
        if (msg.id > lastMessageId) lastMessageId = msg.id;
        messagesEl.scrollTop = messagesEl.scrollHeight;

        if (msg.message_type === 'webrtc' && msg.meta && msg.meta.signal === 'offer' && msg.meta.sdp) {
            handleOffer(msg.meta.sdp);
        }
    }

    function escapeHtml(str) {
        var d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }

    function ingestMessages(list) {
        if (!Array.isArray(list)) return;
        list.forEach(function (m) {
            renderMessage(m);
            if (m.message_type === 'webrtc' && m.meta && m.meta.signal === 'offer' && m.meta.sdp) {
                handleOffer(m.meta.sdp);
            }
        });
    }

    function refreshQueue() {
        fetch(url('/sessions'), { headers: jsonHeaders() })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) renderQueue(data.sessions);
            });
    }

    function pollActiveMessages() {
        if (!activeUuid) return;
        fetch(url('/sessions/' + encodeURIComponent(activeUuid)), { headers: jsonHeaders() })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success) return;
                ingestMessages(data.messages);
            });
    }

    function openSession(uuid) {
        activeUuid = uuid;
        lastMessageId = 0;
        if (messagePollTimer) clearInterval(messagePollTimer);
        messagePollTimer = setInterval(pollActiveMessages, 2500);
        if (messagesEl) messagesEl.innerHTML = '';
        if (remoteVideo) { remoteVideo.srcObject = null; remoteVideo.classList.add('hidden'); }
        fetch(url('/sessions/' + encodeURIComponent(uuid)), { headers: jsonHeaders() })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success) return;
                if (headerEl) {
                    headerEl.textContent = (data.session.student_index || 'Student') +
                        (data.session.page_url ? ' · ' + data.session.page_url : '');
                }
                ingestMessages(data.messages);
                refreshQueue();
                if (data.session.status === 'waiting') {
                    fetch(url('/sessions/' + encodeURIComponent(uuid) + '/claim'), { method: 'POST', headers: jsonHeaders() });
                }
                bindSessionChannel(uuid);
            });
    }

    function bindSessionChannel(uuid) {
        if (!window.QuizSnapReverb) return;
        var ch = window.QuizSnapReverb.subscribePrivate('private-support-session.' + uuid);
        if (!ch) return;
        ch.bind('SupportMessageSent', function (payload) {
            if (payload && payload.session_uuid === uuid && payload.message) {
                renderMessage(payload.message);
                if (payload.message.message_type === 'webrtc' && payload.message.meta && payload.message.meta.signal === 'offer') {
                    handleOffer(payload.message.meta.sdp);
                }
            }
        });
    }

    function bindInbox() {
        if (!window.QuizSnapReverb) return;
        var ch = window.QuizSnapReverb.subscribePrivate('private-support-inbox');
        if (!ch) return;
        ch.bind('SupportSessionUpdated', function () { refreshQueue(); });
        ch.bind('SupportMessageSent', function (payload) {
            refreshQueue();
            if (payload && payload.session_uuid === activeUuid && payload.message) {
                renderMessage(payload.message);
            }
        });
    }

    function sendMessage() {
        if (!activeUuid || !inputEl) return;
        var text = inputEl.value.trim();
        if (!text) return;
        inputEl.value = '';
        fetch(url('/sessions/' + encodeURIComponent(activeUuid) + '/messages'), {
            method: 'POST',
            headers: jsonHeaders(),
            body: JSON.stringify({ body: text }),
        }).then(function (r) { return r.json(); }).then(function (data) {
            if (data.success && data.message) renderMessage(data.message);
        });
    }

    function sendSignal(meta) {
        if (!activeUuid) return;
        fetch(url('/sessions/' + encodeURIComponent(activeUuid) + '/messages'), {
            method: 'POST',
            headers: jsonHeaders(),
            body: JSON.stringify({ message_type: 'webrtc', meta: meta }),
        });
    }

    function handleOffer(sdp) {
        if (!remoteVideo) return;
        if (pc) { pc.close(); pc = null; }
        pc = new RTCPeerConnection({ iceServers: [{ urls: 'stun:stun.l.google.com:19302' }] });
        pc.ontrack = function (ev) {
            remoteVideo.srcObject = ev.streams[0];
            remoteVideo.classList.remove('hidden');
        };
        pc.onicecandidate = function (ev) {
            if (ev.candidate) sendSignal({ signal: 'ice', candidate: ev.candidate });
        };
        pc.setRemoteDescription(new RTCSessionDescription(sdp))
            .then(function () { return pc.createAnswer(); })
            .then(function (answer) { return pc.setLocalDescription(answer).then(function () { return answer; }); })
            .then(function (answer) { sendSignal({ signal: 'answer', sdp: answer }); });
    }

    if (sendBtn) sendBtn.addEventListener('click', sendMessage);
    if (inputEl) inputEl.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); sendMessage(); }
    });
    if (screenBtn) screenBtn.addEventListener('click', function () {
        if (!activeUuid) return;
        fetch(url('/sessions/' + encodeURIComponent(activeUuid) + '/screen-share'), { method: 'POST', headers: jsonHeaders() });
    });
    if (closeBtn) closeBtn.addEventListener('click', function () {
        if (!activeUuid) return;
        fetch(url('/sessions/' + encodeURIComponent(activeUuid) + '/close'), { method: 'POST', headers: jsonHeaders() })
            .then(function () {
                activeUuid = null;
                if (messagesEl) messagesEl.innerHTML = '';
                if (headerEl) headerEl.textContent = 'Select a chat';
                refreshQueue();
            });
    });

    refreshQueue();
    bindInbox();
    pollTimer = setInterval(refreshQueue, 8000);

    window.QuizSnapLiveSupportAdminConsole = { openSession: openSession, refreshQueue: refreshQueue };
})();
