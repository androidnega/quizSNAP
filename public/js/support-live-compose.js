/**
 * Shared compose helpers — textarea auto-grow, quick emoji insert.
 */
(function () {
    'use strict';

    var QUICK_EMOJIS = ['👍', '😊', '🙏', '✅', '❤️', '👋', '🎉', '💬'];

    function autoGrow(textarea) {
        if (!textarea) return;
        textarea.style.height = 'auto';
        textarea.style.height = Math.min(textarea.scrollHeight, 96) + 'px';
    }

    function bindTextarea(textarea, sendBtn) {
        if (!textarea) return;
        textarea.addEventListener('input', function () { autoGrow(textarea); });
        textarea.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                if (sendBtn) sendBtn.click();
            }
        });
        autoGrow(textarea);
    }

    function mountEmojiBar(container, textarea) {
        if (!container || !textarea) return;
        container.innerHTML = '';
        QUICK_EMOJIS.forEach(function (emoji) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = emoji;
            btn.addEventListener('click', function () {
                var start = textarea.selectionStart || textarea.value.length;
                var end = textarea.selectionEnd || textarea.value.length;
                var val = textarea.value;
                textarea.value = val.slice(0, start) + emoji + val.slice(end);
                textarea.focus();
                var pos = start + emoji.length;
                textarea.setSelectionRange(pos, pos);
                autoGrow(textarea);
                textarea.dispatchEvent(new Event('input', { bubbles: true }));
            });
            container.appendChild(btn);
        });
    }

    window.QuizSnapSupportCompose = {
        bindTextarea: bindTextarea,
        mountEmojiBar: mountEmojiBar,
        autoGrow: autoGrow,
    };
})();
