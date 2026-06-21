/**
 * Proctor Engine Feed Monitor — traces live detection decisions and video feed health.
 * Shows WHY the banner says "Face not detected" / "Out of frame too long", not just camera permission.
 *
 * Enable: ?pdebug=1, localStorage proctorDebug=1, or panel auto-expands on red/yellow banner.
 */
(function () {
    'use strict';

    var lastTrace = null;
    var panelExpanded = false;

    function isDebugEnabled() {
        try {
            if (window.QuizSnapProctorDebug === true) return true;
            if (window.localStorage && localStorage.getItem('proctorDebug') === '1') return true;
            if (window.location && String(window.location.search).indexOf('pdebug=1') !== -1) return true;
        } catch (e) { /* ignore */ }
        return false;
    }

    function getVideoEl() {
        var cfg = window.QuizSnapIntelligentFaceMonitor && window.QuizSnapIntelligentFaceMonitor.config;
        if (cfg && cfg.videoElement) return cfg.videoElement;
        return document.getElementById('face-monitor-video');
    }

    function sampleVideoHealth(videoEl) {
        var health = {
            hasElement: !!videoEl,
            hasSrcObject: !!(videoEl && videoEl.srcObject),
            readyState: videoEl ? videoEl.readyState : -1,
            paused: videoEl ? !!videoEl.paused : true,
            videoWidth: videoEl ? (videoEl.videoWidth || 0) : 0,
            videoHeight: videoEl ? (videoEl.videoHeight || 0) : 0,
            trackState: 'unknown',
            trackMuted: false,
            frozen: false,
            dark: false,
            avgLuminance: null,
        };
        if (!videoEl || !videoEl.srcObject) return health;
        try {
            var tracks = videoEl.srcObject.getVideoTracks();
            if (tracks && tracks[0]) {
                health.trackState = tracks[0].readyState || 'unknown';
                health.trackMuted = !!tracks[0].muted;
            }
        } catch (e) { /* ignore */ }

        if (videoEl && videoEl.readyState >= 2 && videoEl.videoWidth > 0 && videoEl.videoHeight > 0) {
            try {
                var canvas = document.createElement('canvas');
                canvas.width = 32;
                canvas.height = 32;
                var ctx = canvas.getContext('2d');
                if (ctx) {
                    ctx.drawImage(videoEl, 0, 0, 32, 32);
                    var data = ctx.getImageData(0, 0, 32, 32).data;
                    var sum = 0;
                    var n = 0;
                    for (var i = 0; i < data.length; i += 4) {
                        sum += (data[i] + data[i + 1] + data[i + 2]) / 3;
                        n++;
                    }
                    health.avgLuminance = n ? Math.round(sum / n) : 0;
                    health.dark = health.avgLuminance < 12;
                }
            } catch (e) {
                health.dark = false;
            }
        }

        if (window.QuizSnapProctorFeedState) {
            health.frozen = !!window.QuizSnapProctorFeedState.frozen;
        }
        return health;
    }

    function ensurePanel() {
        var panel = document.getElementById('proctor-engine-feed-panel');
        if (panel) return panel;

        panel = document.createElement('div');
        panel.id = 'proctor-engine-feed-panel';
        panel.className = 'hidden mt-2 rounded-lg border border-slate-600 bg-slate-900/95 text-left overflow-hidden';
        panel.innerHTML =
            '<button type="button" id="proctor-engine-feed-toggle" class="w-full flex items-center justify-between px-2 py-1.5 text-[10px] font-semibold uppercase tracking-wide text-slate-300 hover:bg-slate-800">' +
            '<span>Engine feed trace</span>' +
            '<span id="proctor-engine-feed-toggle-icon">▼</span></button>' +
            '<pre id="proctor-engine-feed-body" class="hidden px-2 py-2 text-[10px] leading-snug text-emerald-300 font-mono whitespace-pre-wrap break-words max-h-48 overflow-y-auto border-t border-slate-700"></pre>';
        var bars = document.getElementById('live-camera-bars');
        if (bars && bars.parentNode) {
            bars.parentNode.insertBefore(panel, bars.nextSibling);
        } else {
            document.body.appendChild(panel);
        }
        var toggle = document.getElementById('proctor-engine-feed-toggle');
        if (toggle) {
            toggle.addEventListener('click', function () {
                panelExpanded = !panelExpanded;
                var body = document.getElementById('proctor-engine-feed-body');
                var icon = document.getElementById('proctor-engine-feed-toggle-icon');
                if (body) body.classList.toggle('hidden', !panelExpanded);
                if (icon) icon.textContent = panelExpanded ? '▲' : '▼';
            });
        }
        return panel;
    }

    function formatTrace(trace) {
        if (!trace) return 'Waiting for engine data…';
        var v = trace.videoHealth || {};
        var lines = [
            'BANNER: ' + (trace.banner || '—'),
            'SOURCE: ' + (trace.source || '—'),
            'REASON: ' + (trace.reason || '—'),
            '',
            'DETECTION',
            '  model loaded: ' + (trace.modelLoaded ? 'yes' : 'NO'),
            '  monitor running: ' + (trace.isRunning ? 'yes' : 'no'),
            '  raw predictions: ' + (trace.rawPredictions != null ? trace.rawPredictions : '—'),
            '  kept faces: ' + (trace.keptFaces != null ? trace.keptFaces : '—'),
            '  top score: ' + (trace.topScore != null ? trace.topScore.toFixed(3) : '—'),
            '  filter drop: ' + (trace.filterDrop || 'none'),
            '',
            'VIDEO FEED',
            '  track: ' + (v.trackState || '?') + (v.trackMuted ? ' (muted)' : ''),
            '  size: ' + v.videoWidth + 'x' + v.videoHeight,
            '  readyState: ' + v.readyState + '  paused: ' + v.paused,
            '  srcObject: ' + (v.hasSrcObject ? 'yes' : 'NO'),
            '  luminance: ' + (v.avgLuminance != null ? v.avgLuminance : '—') + (v.dark ? ' (DARK FRAME)' : ''),
            '  frozen: ' + (v.frozen ? 'YES' : 'no'),
        ];
        if (trace.lastDetectionError) {
            lines.push('', 'LAST ERROR: ' + trace.lastDetectionError);
        }
        if (trace.hint) {
            lines.push('', 'HINT: ' + trace.hint);
        }
        return lines.join('\n');
    }

    function render(trace) {
        lastTrace = trace;
        var showAlways = isDebugEnabled();
        var isProblem = trace && (trace.bannerState === 'red' || trace.bannerState === 'yellow');
        if (!showAlways && !isProblem) {
            var panel = document.getElementById('proctor-engine-feed-panel');
            if (panel) panel.classList.add('hidden');
            return;
        }

        var panel = ensurePanel();
        panel.classList.remove('hidden');
        if (isProblem && !panelExpanded) {
            panelExpanded = true;
            var bodyEl = document.getElementById('proctor-engine-feed-body');
            var iconEl = document.getElementById('proctor-engine-feed-toggle-icon');
            if (bodyEl) bodyEl.classList.remove('hidden');
            if (iconEl) iconEl.textContent = '▲';
        }
        var body = document.getElementById('proctor-engine-feed-body');
        if (body) body.textContent = formatTrace(trace);
    }

    function report(partial) {
        var videoEl = getVideoEl();
        var merged = Object.assign({}, lastTrace || {}, partial || {});
        merged.videoHealth = sampleVideoHealth(videoEl);
        merged.timestamp = Date.now();
        render(merged);
    }

    window.QuizSnapProctorEngineFeedMonitor = {
        report: report,
        getLastTrace: function () { return lastTrace; },
        isEnabled: isDebugEnabled,
    };
})();
