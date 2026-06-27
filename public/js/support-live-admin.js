/**
 * QuizSnap live support — staff console (full page + FAB widget).
 */
(function () {
    'use strict';

    var cfg = window.QuizSnapLiveSupportAdmin || {};
    var prefix = cfg.prefix || '';
    var queueEl = document.getElementById(prefix + 'live-support-queue');
    var messagesEl = document.getElementById(prefix + 'live-support-messages');
    var inputEl = document.getElementById(prefix + 'live-support-input');
    var sendBtn = document.getElementById(prefix + 'live-support-send');
    var screenBtn = document.getElementById(prefix + 'live-support-screen-btn');
    var closeBtn = document.getElementById(prefix + 'live-support-close-btn');
    var deleteBtn = document.getElementById(prefix + 'live-support-delete-btn');
    var remoteVideo = document.getElementById(prefix + 'live-support-remote-video');
    var headerEl = document.getElementById(prefix + 'live-support-chat-header');
    var imageInput = document.getElementById(prefix + 'live-support-image-input');
    var imageBtn = document.getElementById(prefix + 'live-support-image-btn');
    var takenNotice = document.getElementById(prefix + 'live-support-taken-notice');

    var activeUuid = null;
    var activeSession = null;
    var lastMessageId = 0;
    var pollTimer = null;
    var messagePollTimer = null;
    var pc = null;
    var currentStaffId = cfg.staffId || null;

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

    function escapeHtml(str) {
        var d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }

    function isTakenByOther(session) {
        if (!session || !session.assigned_admin) return false;
        if (!currentStaffId) return true;
        return String(session.assigned_admin.id) !== String(currentStaffId);
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
            var agent = s.assigned_admin ? (' · ' + s.assigned_admin.name) : '';
            var phone = s.student_phone ? (' · ' + s.student_phone) : '';
            btn.innerHTML =
                '<span class="live-support-queue-item__status live-support-queue-item__status--' + s.status + '">' + status + '</span>' +
                '<strong class="block text-sm text-gray-900 truncate">' + escapeHtml(s.student_index || 'Unknown index') + '</strong>' +
                '<span class="block text-xs text-gray-500 truncate">' + escapeHtml((s.issue_category || 'general') + agent + phone) + '</span>' +
                (isTakenByOther(s) ? '<span class="block text-xs text-amber-700 mt-0.5">Handled by ' + escapeHtml(s.assigned_admin.name) + '</span>' : '');
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

        var bubble = '<div class="live-support-msg__bubble">';
        if (msg.message_type === 'image' && msg.meta && msg.meta.url) {
            bubble += '<a href="' + escapeHtml(msg.meta.url) + '" target="_blank" rel="noopener"><img src="' + escapeHtml(msg.meta.url) + '" alt="Image" class="live-support-msg__image"></a>';
        } else {
            bubble += escapeHtml(msg.body || '');
        }
        bubble += '</div>';
        div.innerHTML = bubble;
        messagesEl.appendChild(div);
        if (msg.id > lastMessageId) lastMessageId = msg.id;
        messagesEl.scrollTop = messagesEl.scrollHeight;
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

    function updateTakenNotice(session) {
        if (!takenNotice) return;
        if (session && isTakenByOther(session)) {
            takenNotice.textContent = session.assigned_admin.name + ' is already handling this chat.';
            takenNotice.hidden = false;
            if (inputEl) inputEl.disabled = true;
            if (sendBtn) sendBtn.disabled = true;
            if (imageBtn) imageBtn.disabled = true;
            if (screenBtn) screenBtn.disabled = true;
        } else {
            takenNotice.hidden = true;
            if (inputEl) inputEl.disabled = false;
            if (sendBtn) sendBtn.disabled = false;
            if (imageBtn) imageBtn.disabled = false;
            if (screenBtn) screenBtn.disabled = false;
        }
    }

    function refreshQueue() {
        return fetch(url('/sessions'), { headers: jsonHeaders() })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    renderQueue(data.sessions);
                    if (cfg.onWaitingCount && typeof data.waiting_count === 'number') {
                        cfg.onWaitingCount(data.waiting_count);
                    }
                }
            });
    }

    function pollActiveMessages() {
        if (!activeUuid) return;
        fetch(url('/sessions/' + encodeURIComponent(activeUuid)), { headers: jsonHeaders() })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success) return;
                activeSession = data.session;
                updateTakenNotice(data.session);
                ingestMessages(data.messages);
            });
    }

    function claimSession(uuid) {
        return fetch(url('/sessions/' + encodeURIComponent(uuid) + '/claim'), {
            method: 'POST',
            headers: jsonHeaders(),
        }).then(function (r) { return r.json(); });
    }

    function openSession(uuid) {
        activeUuid = uuid;
        lastMessageId = 0;
        activeSession = null;
        if (messagePollTimer) clearInterval(messagePollTimer);
        messagePollTimer = setInterval(pollActiveMessages, 2500);
        if (messagesEl) messagesEl.innerHTML = '';
        if (remoteVideo) { remoteVideo.srcObject = null; remoteVideo.classList.add('hidden'); }
        fetch(url('/sessions/' + encodeURIComponent(uuid)), { headers: jsonHeaders() })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success) return;
                activeSession = data.session;
                if (headerEl) {
                    var parts = [data.session.student_index || 'Student'];
                    if (data.session.student_phone) parts.push(data.session.student_phone);
                    if (data.session.page_url) parts.push(data.session.page_url);
                    headerEl.textContent = parts.join(' · ');
                }
                updateTakenNotice(data.session);
                ingestMessages(data.messages);
                refreshQueue();
                if (data.session.status === 'waiting' && !isTakenByOther(data.session)) {
                    claimSession(uuid).then(function (claimData) {
                        if (claimData.success) {
                            activeSession = claimData.session;
                            updateTakenNotice(claimData.session);
                        } else if (claimData.session) {
                            activeSession = claimData.session;
                            updateTakenNotice(claimData.session);
                        }
                    });
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
        ch.bind('SupportSessionUpdated', function (payload) {
            if (payload && payload.session && payload.session.uuid === uuid) {
                activeSession = payload.session;
                updateTakenNotice(payload.session);
                refreshQueue();
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
        if (!activeUuid || !inputEl || inputEl.disabled) return;
        var text = inputEl.value.trim();
        if (!text) return;
        inputEl.value = '';
        fetch(url('/sessions/' + encodeURIComponent(activeUuid) + '/messages'), {
            method: 'POST',
            headers: jsonHeaders(),
            body: JSON.stringify({ body: text }),
        }).then(function (r) { return r.json(); }).then(function (data) {
            if (data.success && data.message) renderMessage(data.message);
            else if (data.message) alert(data.message);
        });
    }

    function uploadImage(file) {
        if (!activeUuid || !file || (inputEl && inputEl.disabled)) return;
        var fd = new FormData();
        fd.append('image', file);
        fetch(url('/sessions/' + encodeURIComponent(activeUuid) + '/upload-image'), {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: fd,
        }).then(function (r) { return r.json(); }).then(function (data) {
            if (data.success && data.message) renderMessage(data.message);
            else if (data.message) alert(data.message);
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
    if (imageBtn && imageInput) {
        imageBtn.addEventListener('click', function () { imageInput.click(); });
        imageInput.addEventListener('change', function () {
            if (imageInput.files && imageInput.files[0]) {
                uploadImage(imageInput.files[0]);
                imageInput.value = '';
            }
        });
    }
    if (screenBtn) screenBtn.addEventListener('click', function () {
        if (!activeUuid || screenBtn.disabled) return;
        fetch(url('/sessions/' + encodeURIComponent(activeUuid) + '/screen-share'), { method: 'POST', headers: jsonHeaders() });
    });
    if (closeBtn) closeBtn.addEventListener('click', function () {
        if (!activeUuid) return;
        fetch(url('/sessions/' + encodeURIComponent(activeUuid) + '/close'), { method: 'POST', headers: jsonHeaders() })
            .then(function () {
                activeUuid = null;
                activeSession = null;
                if (messagesEl) messagesEl.innerHTML = '';
                if (headerEl) headerEl.textContent = 'Select a chat';
                refreshQueue();
            });
    });
    if (deleteBtn) deleteBtn.addEventListener('click', function () {
        if (!activeUuid || !confirm('Permanently delete this chat and all messages?')) return;
        fetch(url('/sessions/' + encodeURIComponent(activeUuid)), { method: 'DELETE', headers: jsonHeaders() })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    activeUuid = null;
                    if (messagesEl) messagesEl.innerHTML = '';
                    if (headerEl) headerEl.textContent = 'Select a chat';
                    refreshQueue();
                }
            });
    });

    refreshQueue();
    bindInbox();
    pollTimer = setInterval(refreshQueue, 8000);

    window.QuizSnapLiveSupportAdminConsole = {
        openSession: openSession,
        refreshQueue: refreshQueue,
    };
})();
