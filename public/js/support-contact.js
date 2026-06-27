/**
 * Support contact: modal to capture issue description before opening live chat.
 */
(function () {
    'use strict';

    var config = window.QuizSnapSupportConfig || { appName: 'QuizSnap', defaultContext: {} };
    var modal = document.getElementById('qs-support-modal');
    var form = document.getElementById('qs-support-modal-form');
    var textarea = document.getElementById('qs-support-modal-description');
    var contextEl = document.getElementById('qs-support-modal-context');
    var errorEl = document.getElementById('qs-support-modal-error');
    var activeContext = {};

    function mergeContext(base, extra) {
        var out = {};
        var keys = ['name', 'index_number', 'phone', 'email', 'page', 'system_error', 'description', 'suggested_description'];
        keys.forEach(function (key) {
            var val = (extra && extra[key]) || (base && base[key]) || '';
            if (val) out[key] = String(val).trim();
        });
        return out;
    }

    function renderContextChips(ctx) {
        if (!contextEl) return;
        contextEl.innerHTML = '';
        var chips = [];
        if (ctx.name) chips.push({ label: 'Name', value: ctx.name });
        if (ctx.index_number) chips.push({ label: 'Index', value: ctx.index_number });
        if (ctx.phone) chips.push({ label: 'Phone', value: ctx.phone });
        if (ctx.page) chips.push({ label: 'Page', value: ctx.page });
        if (ctx.system_error) chips.push({ label: 'Error seen', value: ctx.system_error, warn: true });
        if (chips.length === 0) {
            contextEl.hidden = true;
            return;
        }
        chips.forEach(function (chip) {
            var span = document.createElement('span');
            span.className = 'qs-support-modal__chip' + (chip.warn ? ' qs-support-modal__chip--warn' : '');
            span.textContent = chip.label + ': ' + chip.value;
            contextEl.appendChild(span);
        });
        contextEl.hidden = false;
    }

    function setModalOpen(open) {
        if (!modal) return;
        modal.classList.toggle('is-open', open);
        modal.setAttribute('aria-hidden', open ? 'false' : 'true');
        document.body.style.overflow = open ? 'hidden' : '';
        if (open && textarea) {
            setTimeout(function () { textarea.focus(); }, 80);
        }
    }

    function showFieldError(message) {
        if (!textarea || !errorEl) return;
        textarea.classList.add('is-invalid');
        errorEl.textContent = message || 'Please describe your issue in at least 10 characters.';
        errorEl.classList.add('is-visible');
    }

    function clearFieldError() {
        if (!textarea || !errorEl) return;
        textarea.classList.remove('is-invalid');
        errorEl.classList.remove('is-visible');
    }

    function openLiveChat(ctx) {
        if (!window.QuizSnapLiveSupport) {
            alert('Live chat is not available right now. Please refresh and try again.');
            return;
        }
        window.QuizSnapLiveSupport.open({
            student_index: ctx.index_number || null,
            student_name: ctx.name || null,
            student_phone: ctx.phone || null,
            student_email: ctx.email || null,
            page_url: ctx.page || window.location.pathname,
            issue_category: ctx.system_error ? 'error' : 'general',
            initial_message: ctx.description || null,
        });
    }

    function openModal(opts) {
        opts = opts || {};
        activeContext = mergeContext(config.defaultContext || {}, opts);
        renderContextChips(activeContext);
        clearFieldError();
        if (textarea) {
            var seed = activeContext.suggested_description || activeContext.description || '';
            if (!seed && activeContext.system_error) {
                seed = 'I saw this message: "' + activeContext.system_error + '". ';
            }
            textarea.value = seed;
        }
        var submitBtn = document.getElementById('qs-support-modal-submit');
        if (submitBtn) submitBtn.disabled = false;
        setModalOpen(true);
    }

    function parseTrigger(el) {
        var ctx = {};
        try {
            if (el.dataset.supportContext) {
                ctx = JSON.parse(el.dataset.supportContext);
            }
        } catch (e) {}
        if (el.dataset.supportName) ctx.name = el.dataset.supportName;
        if (el.dataset.supportIndex) ctx.index_number = el.dataset.supportIndex;
        if (el.dataset.supportPhone) ctx.phone = el.dataset.supportPhone;
        if (el.dataset.supportPage) ctx.page = el.dataset.supportPage;
        if (el.dataset.supportHint) ctx.system_error = el.dataset.supportHint;
        if (el.dataset.supportSuggested) ctx.suggested_description = el.dataset.supportSuggested;
        return ctx;
    }

    document.addEventListener('click', function (e) {
        var trigger = e.target.closest('[data-qs-support-live]');
        if (trigger) {
            e.preventDefault();
            openModal(parseTrigger(trigger));
            return;
        }
        var indexHelp = e.target.closest('[data-qs-support-index-help]');
        if (indexHelp) {
            e.preventDefault();
            if (errorEl) {
                errorEl.textContent = 'If your index is not recognized, contact your class rep or lecturer to add you to the class list.';
                errorEl.classList.add('is-visible');
            }
            var submitBtn = document.getElementById('qs-support-modal-submit');
            if (submitBtn) submitBtn.disabled = true;
            if (textarea) textarea.value = '';
            return;
        }
        var quick = e.target.closest('[data-qs-support-quick]');
        if (quick && textarea) {
            var text = quick.getAttribute('data-qs-support-quick') || '';
            if (textarea.value.trim() === '') {
                textarea.value = text;
            } else if (textarea.value.indexOf(text) === -1) {
                textarea.value = (textarea.value.trim() + ' ' + text).trim();
            }
            clearFieldError();
            textarea.focus();
            return;
        }
        if (e.target.closest('[data-qs-support-modal-close]')) {
            setModalOpen(false);
        }
    });

    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            if (!textarea) return;
            var description = textarea.value.trim();
            if (description.length < 10) {
                showFieldError('Please describe your issue in at least 10 characters so we can help you.');
                return;
            }
            clearFieldError();
            var ctx = mergeContext(activeContext, { description: description });
            setModalOpen(false);
            openLiveChat(ctx);
        });
    }

    if (textarea) {
        textarea.addEventListener('input', function () {
            if (textarea.value.trim().length >= 10) clearFieldError();
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal && modal.classList.contains('is-open')) {
            setModalOpen(false);
        }
    });

    window.QuizSnapSupport = {
        openModal: openModal,
        openLiveChat: openLiveChat,
    };
})();
