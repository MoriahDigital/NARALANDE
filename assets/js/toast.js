/**
 * Naralandé — Toast Notification System
 * Lightweight, stackable toast notifications with auto-dismiss.
 */
(function () {
    'use strict';

    const TOAST_DURATION = 4000;
    const TOAST_ANIMATION_OUT = 400;

    const ICONS = {
        success: 'fa-solid fa-circle-check',
        error:   'fa-solid fa-circle-xmark',
        info:    'fa-solid fa-circle-info',
        warning: 'fa-solid fa-triangle-exclamation'
    };

    function getContainer() {
        let container = document.getElementById('nl-toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'nl-toast-container';
            container.setAttribute('aria-live', 'polite');
            container.setAttribute('aria-atomic', 'false');
            document.body.appendChild(container);
        }
        return container;
    }

    /**
     * Show a toast notification.
     * @param {string} message  - The message to display
     * @param {'success'|'error'|'info'|'warning'} type - Toast type
     * @param {number} [duration] - Auto-dismiss delay in ms (0 = manual close)
     */
    function showToast(message, type, duration) {
        type = type || 'info';
        duration = typeof duration === 'number' ? duration : TOAST_DURATION;

        const container = getContainer();
        const toast = document.createElement('div');
        toast.className = 'nl-toast nl-toast--' + type;
        toast.innerHTML =
            '<i class="nl-toast-icon ' + (ICONS[type] || ICONS.info) + '"></i>' +
            '<span class="nl-toast-msg">' + escapeHtml(message) + '</span>' +
            '<button class="nl-toast-close" aria-label="Fermer"><i class="fa-solid fa-xmark"></i></button>';

        // Close on click
        toast.querySelector('.nl-toast-close').addEventListener('click', function () {
            dismissToast(toast);
        });

        container.appendChild(toast);

        // Trigger entrance animation on next frame
        requestAnimationFrame(function () {
            toast.classList.add('nl-toast--visible');
        });

        // Auto-dismiss
        if (duration > 0) {
            toast._timer = setTimeout(function () {
                dismissToast(toast);
            }, duration);
        }

        return toast;
    }

    function dismissToast(toast) {
        if (toast._dismissed) return;
        toast._dismissed = true;
        clearTimeout(toast._timer);
        toast.classList.remove('nl-toast--visible');
        toast.classList.add('nl-toast--exit');
        setTimeout(function () {
            if (toast.parentNode) toast.parentNode.removeChild(toast);
        }, TOAST_ANIMATION_OUT);
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // Expose globally
    window.NLToast = showToast;
})();
