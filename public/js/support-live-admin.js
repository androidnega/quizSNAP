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
    var referWrap = document.getElementById(prefix + 'live-support-refer-wrap');
    var referSelect = document.getElementById(prefix + 'live-support-refer-select');
    var referBtn = document.getElementById(prefix + 'live-support-refer-btn');
    var displayNameInput = document.getElementById(prefix + 'live-support-display-name-input');
    var displayNameSaveBtn = document.getElementById(prefix + 'live-support-display-name-save');
    var displayNameHint = document.getElementById(prefix + 'live-support-display-name-hint');
    var avatarGrid = document.getElementById(prefix + 'live-support-avatar-grid');
    var audioBtn = document.getElementById(prefix + 'live-support-audio-btn');
    var remoteVideo = document.getElementById(prefix + 'live-support-remote-video');
    var headerEl = document.getElementById(prefix + 'live-support-chat-header');
    var imageInput = document.getElementById(prefix + 'live-support-image-input');
    var imageBtn = document.getElementById(prefix + 'live-support-image-btn');
    var takenNotice = document.getElementById(prefix + 'live-support-taken-notice');
    var typingEl = document.getElementById(prefix + 'live-support-typing');
    var recordingWaveEl = document.getElementById(prefix + 'live-support-recording-wave');
    var recordingBarsEl = document.getElementById(prefix + 'live-support-recording-bars');

    var activeUuid = null;
    var activeSession = null;
    var lastMessageId = 0;
    var pollTimer = null;
    var messagePollTimer = null;
    var presenceTimer = null;
    var pc = null;
    var currentStaffId = cfg.staffId || null;
    var isTyping = false;
    var typingStopTimer = null;
    var audioRecorder = null;
    var recordingWaveform = null;
    var pendingRemoteIce = [];
    var selectedAvatar = cfg.supportAvatar || null;

    function sounds() {
        return window.QuizSnapSupportSounds || null;
    }

    function media() {
        return window.QuizSnapSupportMedia || null;
    }

    function isStaffPanelOpen() {
        if (prefix === 'staff-fab-') {
            var wrap = document.getElementById('staff-support-fab-wrap');
            return !!(wrap && wrap.classList.contains('is-open'));
        }
        return true;
    }

    function shouldContinuousAlert(sessionUuid) {
        if (!sessionUuid || activeUuid !== sessionUuid) return true;
        return !isStaffPanelOpen();
    }

    function notifyIncomingStudentMessage(msg, sessionUuid, fromEcho) {
        if (!fromEcho || !msg || msg.sender_type !== 'student' || !sounds()) return;
        if (shouldContinuousAlert(sessionUuid)) {
            sounds().startMessageAlert(sessionUuid);
        } else {
            sounds().stopMessageAlert();
            sounds().playMessageOnce();
        }
    }

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

    function updateDisplayNameHint(resolved) {
        if (displayNameHint) displayNameHint.textContent = 'Students see: ' + (resolved || 'Support');
    }

    function saveDisplayName() {
        if (!displayNameInput) return;
        var value = displayNameInput.value.trim();
        if (displayNameSaveBtn) displayNameSaveBtn.disabled = true;
        fetch(url('/display-name'), {
            method: 'PUT',
            headers: jsonHeaders(),
            body: JSON.stringify({ support_display_name: value }),
        })
            .then(function (r) {
                return r.json().then(function (data) {
                    return { ok: r.ok, data: data };
                });
            })
            .then(function (res) {
                if (res.ok && res.data.success) {
                    if (displayNameInput) displayNameInput.value = res.data.support_display_name || '';
                    updateDisplayNameHint(res.data.resolved_name);
                } else {
                    alert(res.data.message || 'Could not save chat name.');
                }
            })
            .catch(function () {
                alert('Could not save chat name. Check your connection and try again.');
            })
            .finally(function () {
                if (displayNameSaveBtn) displayNameSaveBtn.disabled = false;
            });
    }

    function isAssignedToMe(session) {
        if (!session || !session.assigned_admin || !currentStaffId) return false;
        return String(session.assigned_admin.id) === String(currentStaffId);
    }

    function updateReferControls(session) {
        if (!referWrap || !referSelect) return;
        if (!session || !isAssignedToMe(session) || session.status !== 'active') {
            referWrap.hidden = true;
            referSelect.value = '';
            return;
        }
        fetch(url('/agents/available'), { headers: jsonHeaders() })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success || !Array.isArray(data.agents)) {
                    referWrap.hidden = true;
                    return;
                }
                referSelect.innerHTML = '<option value="">Refer to agent…</option>';
                data.agents.forEach(function (agent) {
                    var opt = document.createElement('option');
                    opt.value = String(agent.id);
                    opt.textContent = (agent.chat_name || agent.name || agent.username || ('Agent #' + agent.id));
                    referSelect.appendChild(opt);
                });
                referWrap.hidden = data.agents.length === 0;
            })
            .catch(function () {
                referWrap.hidden = true;
            });
    }

    function referSession() {
        if (!activeUuid || !referSelect || !referSelect.value) return;
        var agentId = referSelect.value;
        var agentName = referSelect.options[referSelect.selectedIndex].textContent;
        if (!confirm('Refer this chat to ' + agentName + '?')) return;
        if (referBtn) referBtn.disabled = true;
        fetch(url('/sessions/' + encodeURIComponent(activeUuid) + '/refer'), {
            method: 'POST',
            headers: jsonHeaders(),
            body: JSON.stringify({ agent_id: parseInt(agentId, 10) }),
        })
            .then(function (r) {
                return r.json().then(function (data) {
                    return { ok: r.ok, data: data };
                });
            })
            .then(function (res) {
                if (res.ok && res.data.success) {
                    activeSession = res.data.session;
                    updateTakenNotice(res.data.session);
                    updateReferControls(res.data.session);
                    refreshQueue();
                    if (headerEl && res.data.session.assigned_admin) {
                        var referredName = res.data.session.assigned_admin.chat_name || res.data.session.assigned_admin.name;
                        headerEl.textContent = (headerEl.textContent || '') + ' · Referred to ' + referredName;
                    }
                } else {
                    alert(res.data.message || 'Could not refer chat.');
                    if (res.data.session) {
                        activeSession = res.data.session;
                        updateTakenNotice(res.data.session);
                        updateReferControls(res.data.session);
                    }
                }
            })
            .catch(function () {
                alert('Could not refer chat. Check your connection and try again.');
            })
            .finally(function () {
                if (referBtn) referBtn.disabled = false;
                if (referSelect) referSelect.value = '';
            });
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
            btn.innerHTML =
                '<span class="live-support-queue-item__status live-support-queue-item__status--' + s.status + '">' + status + '</span>' +
                '<strong class="block text-sm text-gray-900 truncate">' + escapeHtml(s.student_name || s.student_index || 'Guest') + '</strong>' +
                '<span class="block text-xs text-gray-500 truncate">' + escapeHtml([s.student_index, s.issue_category || 'general', s.student_phone].filter(Boolean).join(' · ') + agent) + '</span>' +
                (isTakenByOther(s) ? '<span class="block text-xs text-amber-700 mt-0.5">Handled by ' + escapeHtml(s.assigned_admin.name) + '</span>' : '');
            btn.addEventListener('click', function () { openSession(s.uuid); });
            queueEl.appendChild(btn);
        });
    }

    function renderMessage(msg, fromEcho) {
        if (!messagesEl || !msg || !msg.id) return;
        if (msg.sender_type === 'system') return;
        if (document.querySelector('[data-admin-msg-id="' + msg.id + '"]')) return;
        if (msg.message_type === 'webrtc') return;

        var div = document.createElement('div');
        var type = msg.sender_type === 'admin' ? 'admin' : (msg.sender_type === 'system' ? 'system' : 'student');
        div.className = 'live-support-msg live-support-msg--' + type;
        div.setAttribute('data-admin-msg-id', String(msg.id));

        var bubbleEl = document.createElement('div');
        bubbleEl.className = 'live-support-msg__bubble';
        if (media() && (msg.message_type === 'image' || msg.message_type === 'audio')) {
            if (!media().appendMessageMedia(bubbleEl, msg) && msg.body) {
                bubbleEl.textContent = msg.body;
            }
        } else {
            bubbleEl.textContent = msg.body || '';
        }
        div.appendChild(bubbleEl);
        if (msg.created_at) {
            var time = document.createElement('span');
            time.className = 'live-support-msg__time';
            try {
                time.textContent = new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            } catch (e) {}
            div.appendChild(time);
        }
        messagesEl.appendChild(div);
        if (msg.id > lastMessageId) lastMessageId = msg.id;
        messagesEl.scrollTop = messagesEl.scrollHeight;

        notifyIncomingStudentMessage(msg, activeUuid, fromEcho);
    }

    function setTyping(text) {
        if (!typingEl) return;
        var show = !!(text && String(text).trim());
        var labelEl = typingEl.querySelector('.qs-typing-label');
        if (labelEl) labelEl.textContent = show ? text : '';
        typingEl.hidden = !show;
        typingEl.setAttribute('aria-hidden', show ? 'false' : 'true');
    }

    function sendTypingSignal(typing) {
        if (!activeUuid) return;
        fetch(url('/sessions/' + encodeURIComponent(activeUuid) + '/typing'), {
            method: 'POST',
            headers: jsonHeaders(),
            body: JSON.stringify({ typing: !!typing }),
        }).catch(function () {});
    }

    function onInputTyping() {
        if (!activeUuid || (inputEl && inputEl.disabled)) return;
        if (!isTyping) {
            isTyping = true;
            sendTypingSignal(true);
        }
        if (typingStopTimer) clearTimeout(typingStopTimer);
        typingStopTimer = setTimeout(function () {
            isTyping = false;
            sendTypingSignal(false);
        }, 1400);
        if (sounds()) sounds().playTypingLocal();
    }

    function pingPresence() {
        fetch(url('/presence'), { method: 'POST', headers: jsonHeaders() }).catch(function () {});
    }

    function processWebRtcMeta(meta) {
        if (!meta || !meta.signal) return;
        if (meta.signal === 'offer' && meta.sdp) {
            handleOffer(meta.sdp);
        } else if (meta.signal === 'ice') {
            queueRemoteIce(meta.candidate);
        }
    }

    function ingestMessages(list, fromEcho) {
        if (!Array.isArray(list)) return;
        list.forEach(function (m) {
            renderMessage(m, fromEcho);
            if (m.message_type === 'webrtc' && m.meta) {
                processWebRtcMeta(m.meta);
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
        if (window.QuizSnapLive && typeof window.QuizSnapLive.isUserInteracting === 'function') {
            var busy = window.QuizSnapLive.isUserInteracting();
            if (busy && inputEl && document.activeElement === inputEl) {
                return Promise.resolve();
            }
        }
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

    var sessionChannelBindings = {};

    function pollActiveMessages() {
        if (!activeUuid) return;
        var sinceQuery = lastMessageId > 0 ? ('?since=' + encodeURIComponent(String(lastMessageId))) : '';
        fetch(url('/sessions/' + encodeURIComponent(activeUuid) + sinceQuery), { headers: jsonHeaders() })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success) return;
                activeSession = data.session;
                updateTakenNotice(data.session);
                if (Array.isArray(data.messages) && data.messages.length) {
                    ingestMessages(data.messages, true);
                }
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
        if (sounds()) sounds().stopMessageAlert();
        updateReferControls(null);
        if (messagePollTimer) clearInterval(messagePollTimer);
        messagePollTimer = setInterval(pollActiveMessages, 2500);
        if (messagesEl) messagesEl.innerHTML = '';
        setTyping('');
        if (remoteVideo) { remoteVideo.srcObject = null; remoteVideo.classList.add('hidden'); }
        if (pc) { pc.close(); pc = null; }
        pendingRemoteIce = [];
        fetch(url('/sessions/' + encodeURIComponent(uuid)), { headers: jsonHeaders() })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success) return;
                activeSession = data.session;
                if (headerEl) {
                    var parts = [data.session.student_name || data.session.student_index || 'Student'];
                    if (data.session.student_name && data.session.student_index) parts.push(data.session.student_index);
                    if (data.session.student_phone) parts.push(data.session.student_phone);
                    if (data.session.page_url) parts.push(data.session.page_url);
                    headerEl.textContent = parts.join(' · ');
                }
                updateTakenNotice(data.session);
                updateReferControls(data.session);
                ingestMessages(data.messages, false);
                refreshQueue();
                if (data.session.status === 'waiting' && !isTakenByOther(data.session)) {
                    claimSession(uuid).then(function (claimData) {
                        if (claimData.success) {
                            activeSession = claimData.session;
                            updateTakenNotice(claimData.session);
                            updateReferControls(claimData.session);
                        } else if (claimData.session) {
                            activeSession = claimData.session;
                            updateTakenNotice(claimData.session);
                            updateReferControls(claimData.session);
                        }
                    });
                }
                bindSessionChannel(uuid);
            });
    }

    function bindSessionChannel(uuid) {
        if (!window.QuizSnapReverb || sessionChannelBindings[uuid]) return;
        sessionChannelBindings[uuid] = true;
        var ch = window.QuizSnapReverb.subscribePrivate('private-support-session.' + uuid);
        if (!ch) return;
        ch.bind('SupportMessageSent', function (payload) {
            if (payload && payload.session_uuid === uuid && payload.message) {
                renderMessage(payload.message, true);
                if (payload.message.message_type === 'webrtc' && payload.message.meta) {
                    processWebRtcMeta(payload.message.meta);
                }
            }
        });
        ch.bind('SupportTyping', function (payload) {
            if (!payload || payload.sender_type === 'admin' || uuid !== activeUuid) return;
            if (payload.is_typing === true) {
                setTyping((payload.sender_label || 'Student') + ' is typing');
                if (sounds()) sounds().playTyping();
            } else {
                setTyping('');
            }
        });
        ch.bind('SupportSessionUpdated', function (payload) {
            if (payload && payload.session && payload.session.uuid === uuid) {
                activeSession = payload.session;
                updateTakenNotice(payload.session);
                updateReferControls(payload.session);
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
            if (!payload || !payload.message) return;
            if (payload.session_uuid === activeUuid) {
                renderMessage(payload.message, true);
                if (payload.message.message_type === 'webrtc' && payload.message.meta) {
                    processWebRtcMeta(payload.message.meta);
                }
                return;
            }
            if (payload.message.sender_type === 'student') {
                notifyIncomingStudentMessage(payload.message, payload.session_uuid, true);
            }
        });
        ch.bind('SupportTyping', function (payload) {
            if (!payload || payload.sender_type !== 'student') return;
            if (payload.session_uuid === activeUuid) {
                if (payload.is_typing === true) {
                    setTyping((payload.sender_label || 'Student') + ' is typing');
                } else {
                    setTyping('');
                }
            }
            if (payload.is_typing === true && payload.session_uuid === activeUuid && sounds()) sounds().playTyping();
        });
    }

    function sendMessage() {
        if (!activeUuid || !inputEl || inputEl.disabled) return;
        var text = inputEl.value.trim();
        if (!text) return;
        inputEl.value = '';
        if (window.QuizSnapSupportCompose) QuizSnapSupportCompose.autoGrow(inputEl);
        isTyping = false;
        sendTypingSignal(false);
        fetch(url('/sessions/' + encodeURIComponent(activeUuid) + '/messages'), {
            method: 'POST',
            headers: jsonHeaders(),
            body: JSON.stringify({ body: text }),
        }).then(function (r) { return r.json(); }).then(function (data) {
            if (data.success && data.message) renderMessage(data.message, false);
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

    function uploadAudio(blob) {
        if (!activeUuid || !blob || (inputEl && inputEl.disabled)) return;
        var fd = new FormData();
        fd.append('audio', blob, 'voice-message.webm');
        fetch(url('/sessions/' + encodeURIComponent(activeUuid) + '/upload-audio'), {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: fd,
        }).then(function (r) { return r.json(); }).then(function (data) {
            if (data.success && data.message) renderMessage(data.message, false);
            else if (data.message) alert(data.message);
        });
    }

    function toggleAudioRecording() {
        if (!media() || !activeUuid || (inputEl && inputEl.disabled)) return;
        if (!audioRecorder) audioRecorder = media().createRecorder();
        if (audioRecorder.isRecording()) {
            if (audioBtn) audioBtn.classList.remove('is-recording');
            hideRecordingWave();
            audioRecorder.stop().then(function (blob) {
                if (blob && blob.size > 0) uploadAudio(blob);
            });
            return;
        }
        audioRecorder.start().then(function () {
            if (audioBtn) audioBtn.classList.add('is-recording');
            showRecordingWave(audioRecorder);
        }).catch(function () {
            hideRecordingWave();
            alert('Microphone access is required to send a voice message.');
        });
    }

    function saveAvatar(value) {
        return fetch(url('/avatar'), {
            method: 'PUT',
            headers: jsonHeaders(),
            body: JSON.stringify({ support_avatar: value }),
        }).then(function (r) { return r.json(); });
    }

    function markSelectedAvatar(value) {
        if (!avatarGrid) return;
        avatarGrid.querySelectorAll('.live-support-avatar-option').forEach(function (btn) {
            btn.classList.toggle('is-selected', btn.dataset.avatarValue === (value || ''));
        });
    }

    function buildAvatarPicker() {
        if (!avatarGrid || !cfg.avatarCatalog) return;
        avatarGrid.innerHTML = '';
        (cfg.avatarCatalog.emojis || []).forEach(function (emoji) {
            var value = 'emoji:' + emoji;
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'live-support-avatar-option';
            btn.dataset.avatarValue = value;
            btn.textContent = emoji;
            btn.title = 'Emoji icon';
            btn.addEventListener('click', function () {
                saveAvatar(value).then(function (res) {
                    if (res.success) {
                        selectedAvatar = res.support_avatar;
                        markSelectedAvatar(selectedAvatar);
                    }
                });
            });
            avatarGrid.appendChild(btn);
        });
        (cfg.avatarCatalog.vectors || []).forEach(function (item) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'live-support-avatar-option';
            btn.dataset.avatarValue = item.id;
            btn.title = item.label || 'Vector icon';
            btn.innerHTML = '<svg fill="none" viewBox="' + escapeHtml(item.viewBox || '0 0 24 24') + '" stroke="currentColor" stroke-width="1.75"><path d="' + escapeHtml(item.path || '') + '"></path></svg>';
            btn.addEventListener('click', function () {
                saveAvatar(item.id).then(function (res) {
                    if (res.success) {
                        selectedAvatar = res.support_avatar;
                        markSelectedAvatar(selectedAvatar);
                    }
                });
            });
            avatarGrid.appendChild(btn);
        });
        markSelectedAvatar(selectedAvatar);
    }

    function sendSignal(meta) {
        if (!activeUuid) return;
        var packed = media() && media().packRtcMeta ? media().packRtcMeta(meta) : meta;
        fetch(url('/sessions/' + encodeURIComponent(activeUuid) + '/messages'), {
            method: 'POST',
            headers: jsonHeaders(),
            body: JSON.stringify({ message_type: 'webrtc', meta: packed }),
        });
    }

    function queueRemoteIce(candidate) {
        if (!candidate || !pc) return;
        var ice = candidate instanceof RTCIceCandidate ? candidate : new RTCIceCandidate(candidate);
        if (pc.remoteDescription && pc.remoteDescription.type) {
            pc.addIceCandidate(ice).catch(function () {});
        } else {
            pendingRemoteIce.push(ice);
        }
    }

    function flushRemoteIce() {
        if (!pc || !pendingRemoteIce.length) return;
        pendingRemoteIce.forEach(function (candidate) {
            pc.addIceCandidate(candidate).catch(function () {});
        });
        pendingRemoteIce = [];
    }

    function handleOffer(sdp) {
        if (!remoteVideo || !sdp) return;
        if (pc) { pc.close(); pc = null; }
        pendingRemoteIce = [];
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
            .then(function (answer) {
                sendSignal({ signal: 'answer', sdp: answer });
                flushRemoteIce();
            })
            .catch(function () {
                if (remoteVideo) {
                    remoteVideo.srcObject = null;
                    remoteVideo.classList.add('hidden');
                }
            });
    }

    function hideRecordingWave() {
        if (recordingWaveEl) recordingWaveEl.classList.remove('is-active');
        if (recordingWaveform) {
            recordingWaveform.reset();
            recordingWaveform.destroy();
            recordingWaveform = null;
        }
    }

    function showRecordingWave(rec) {
        if (!media() || !recordingWaveEl || !recordingBarsEl) return;
        hideRecordingWave();
        recordingWaveform = media().createWaveform(recordingBarsEl, 18);
        recordingWaveEl.classList.add('is-active');
        rec.onLevels(function (levels) {
            if (recordingWaveform) recordingWaveform.update(levels);
        });
    }

    if (sendBtn) sendBtn.addEventListener('click', sendMessage);
    if (inputEl) {
        if (window.QuizSnapSupportCompose) {
            QuizSnapSupportCompose.bindTextarea(inputEl, sendBtn);
            QuizSnapSupportCompose.mountEmojiBar(document.getElementById(prefix + 'live-support-emoji-bar'), inputEl);
        } else {
            inputEl.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') { e.preventDefault(); sendMessage(); }
                else onInputTyping();
            });
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
    if (avatarGrid) buildAvatarPicker();
    if (screenBtn) screenBtn.addEventListener('click', function () {
        if (!activeUuid || screenBtn.disabled) return;
        var originalLabel = screenBtn.textContent;
        screenBtn.disabled = true;
        fetch(url('/sessions/' + encodeURIComponent(activeUuid) + '/screen-share'), { method: 'POST', headers: jsonHeaders() })
            .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, data: d }; }); })
            .then(function (res) {
                if (res.ok && res.data.success) {
                    screenBtn.textContent = 'Screen share requested';
                    if (headerEl) {
                        var note = ' · Waiting for student to share screen';
                        if (headerEl.textContent.indexOf(note) === -1) headerEl.textContent += note;
                    }
                } else {
                    alert((res.data && res.data.message) || 'Could not request screen share.');
                }
            })
            .catch(function () {
                alert('Could not request screen share. Check your connection and try again.');
            })
            .finally(function () {
                screenBtn.disabled = false;
                setTimeout(function () {
                    screenBtn.textContent = originalLabel;
                }, 3500);
            });
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
    if (referBtn) referBtn.addEventListener('click', referSession);
    if (displayNameSaveBtn) displayNameSaveBtn.addEventListener('click', saveDisplayName);
    if (displayNameInput) {
        displayNameInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); saveDisplayName(); }
        });
        if (cfg.resolvedSupportDisplayName) updateDisplayNameHint(cfg.resolvedSupportDisplayName);
    }
    if (prefix === 'staff-fab-') {
        var fabWrap = document.getElementById('staff-support-fab-wrap');
        var fabToggle = document.getElementById('staff-support-fab-toggle');
        if (fabToggle && fabWrap) {
            fabToggle.addEventListener('click', function () {
                if (fabWrap.classList.contains('is-open') && activeUuid && sounds()) {
                    sounds().stopMessageAlert();
                }
            });
        }
    }
    if (deleteBtn) deleteBtn.addEventListener('click', function () {
        if (!activeUuid || !confirm('Permanently delete this chat and all messages?')) return;
        deleteBtn.disabled = true;
        fetch(url('/sessions/' + encodeURIComponent(activeUuid)), { method: 'DELETE', headers: jsonHeaders() })
            .then(function (r) {
                return r.json().then(function (data) {
                    return { ok: r.ok, status: r.status, data: data };
                }).catch(function () {
                    return { ok: false, status: r.status, data: { message: 'Could not delete chat.' } };
                });
            })
            .then(function (res) {
                if (res.ok && res.data.success) {
                    activeUuid = null;
                    activeSession = null;
                    lastMessageId = 0;
                    if (messagesEl) messagesEl.innerHTML = '';
                    if (headerEl) headerEl.textContent = 'Select a chat';
                    setTyping('');
                    refreshQueue();
                } else {
                    alert(res.data.message || ('Delete failed (' + res.status + ').'));
                }
            })
            .catch(function () {
                alert('Could not delete chat. Check your connection and try again.');
            })
            .finally(function () {
                deleteBtn.disabled = false;
            });
    });

    refreshQueue();
    bindInbox();
    pingPresence();
    if (sounds()) sounds().unlock();
    pollTimer = setInterval(refreshQueue, 8000);
    presenceTimer = setInterval(pingPresence, 30000);

    window.QuizSnapLiveSupportAdminConsole = {
        openSession: openSession,
        refreshQueue: refreshQueue,
    };
})();
