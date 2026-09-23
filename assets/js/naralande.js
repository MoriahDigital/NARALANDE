/**
 * Naralandé — Core Dynamic Module
 * Central JS with AJAX helpers, polling, badges, animations, and utilities.
 */
(function () {
    'use strict';

    var BASE_URL = (function () {
        var meta = document.querySelector('meta[name="base-url"]');
        return meta ? meta.getAttribute('content') : '/NARALANDE/';
    })();

    var NL = window.NL = {};

    // ───────────────────────────────────────────────
    // AJAX helper
    // ───────────────────────────────────────────────
    /**
     * Fetch wrapper that sends FormData and returns parsed JSON.
     * @param {string} url       - Relative or absolute URL
     * @param {Object|FormData} data - Key/value pairs or FormData
     * @param {Object} [opts]    - Extra fetch options
     * @returns {Promise<Object>}
     */
    NL.ajax = function (url, data, opts) {
        opts = opts || {};
        var body;
        if (data instanceof FormData) {
            body = data;
        } else if (data && typeof data === 'object') {
            body = new FormData();
            Object.keys(data).forEach(function (k) { body.append(k, data[k]); });
        }
        return fetch(url.indexOf('://') > -1 ? url : BASE_URL + url, Object.assign({
            method: 'POST',
            body: body,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }, opts))
        .then(function (res) { return res.json(); });
    };

    NL.get = function (url) {
        return fetch(url.indexOf('://') > -1 ? url : BASE_URL + url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (r) { return r.json(); });
    };

    // ───────────────────────────────────────────────
    // Badge management
    // ───────────────────────────────────────────────
    NL.badge = function (selector, count) {
        var badges = document.querySelectorAll(selector);
        badges.forEach(function (b) {
            var c = parseInt(count, 10) || 0;
            b.textContent = c;
            b.style.display = c > 0 ? '' : 'none';
            if (c > 0) b.classList.add('nl-badge-pulse');
            setTimeout(function () { b.classList.remove('nl-badge-pulse'); }, 600);
        });
    };

    // ───────────────────────────────────────────────
    // Polling
    // ───────────────────────────────────────────────
    var _polls = {};
    /**
     * Start polling an endpoint at a given interval.
     * @param {string}   id       - Unique poll identifier
     * @param {string}   url      - Endpoint to poll (relative)
     * @param {Function} callback - Receives parsed JSON
     * @param {number}   interval - Interval in ms
     */
    NL.poll = function (id, url, callback, interval) {
        NL.pollStop(id);
        var tick = function () {
            NL.get(url).then(callback).catch(function () {});
        };
        tick();
        _polls[id] = setInterval(tick, interval);
    };

    NL.pollStop = function (id) {
        if (_polls[id]) { clearInterval(_polls[id]); delete _polls[id]; }
    };

    // ───────────────────────────────────────────────
    // Debounce
    // ───────────────────────────────────────────────
    NL.debounce = function (fn, delay) {
        var timer;
        return function () {
            var ctx = this, args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function () { fn.apply(ctx, args); }, delay);
        };
    };

    // ───────────────────────────────────────────────
    // Animations
    // ───────────────────────────────────────────────
    NL.animate = function (el, animationClass, duration) {
        duration = duration || 500;
        el.classList.add(animationClass);
        setTimeout(function () { el.classList.remove(animationClass); }, duration);
    };

    NL.fadeIn = function (el) {
        el.style.opacity = '0';
        el.style.transform = 'translateY(16px)';
        el.style.transition = 'opacity .4s ease, transform .4s ease';
        requestAnimationFrame(function () {
            el.style.opacity = '1';
            el.style.transform = 'translateY(0)';
        });
    };

    // ───────────────────────────────────────────────
    // Escape HTML
    // ───────────────────────────────────────────────
    NL.escapeHtml = function (str) {
        var div = document.createElement('div');
        div.textContent = str || '';
        return div.innerHTML;
    };

    // ───────────────────────────────────────────────
    // Global notification polling (30s)
    // ───────────────────────────────────────────────
    function startGlobalPolling() {
        // Notification count
        NL.poll('global-notif', 'api/notifications.php', function (data) {
            NL.badge('.nl-badge-notif', data.unread_count || 0);
        }, 30000);

        // Unread messages count
        NL.poll('global-msg', 'api/messages_unread.php', function (data) {
            NL.badge('.nl-badge-msg', data.unread_count || 0);
        }, 30000);
    }

    // ───────────────────────────────────────────────
    // Offline / online detection
    // ───────────────────────────────────────────────
    function setupConnectivity() {
        var banner = document.createElement('div');
        banner.id = 'nl-offline-banner';
        banner.className = 'nl-offline-banner';
        banner.innerHTML = '<i class="fa-solid fa-wifi"></i> Connexion perdue — certaines fonctionnalités peuvent être limitées.';
        document.body.appendChild(banner);

        function update() {
            banner.classList.toggle('nl-offline--visible', !navigator.onLine);
        }
        window.addEventListener('online', update);
        window.addEventListener('offline', update);
        update();
    }

    // ───────────────────────────────────────────────
    // Sidebar mobile toggle
    // ───────────────────────────────────────────────
    function setupSidebarToggle() {
        var toggle = document.getElementById('nl-sidebar-toggle');
        var sidebar = document.querySelector('.sidebar');
        var overlay = document.getElementById('nl-sidebar-overlay');
        if (!toggle || !sidebar) return;

        function open() {
            sidebar.classList.add('sidebar--open');
            if (overlay) overlay.classList.add('nl-overlay--visible');
            document.body.classList.add('sidebar-open');
        }
        function close() {
            sidebar.classList.remove('sidebar--open');
            if (overlay) overlay.classList.remove('nl-overlay--visible');
            document.body.classList.remove('sidebar-open');
        }
        toggle.addEventListener('click', function () {
            sidebar.classList.contains('sidebar--open') ? close() : open();
        });
        if (overlay) overlay.addEventListener('click', close);
    }

    // ───────────────────────────────────────────────
    // Ripple effect on buttons
    // ───────────────────────────────────────────────
    function setupRipple() {
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('.btn');
            if (!btn) return;
            var rect = btn.getBoundingClientRect();
            var ripple = document.createElement('span');
            ripple.className = 'nl-ripple';
            ripple.style.left = (e.clientX - rect.left) + 'px';
            ripple.style.top = (e.clientY - rect.top) + 'px';
            btn.appendChild(ripple);
            setTimeout(function () { ripple.remove(); }, 600);
        });
    }

    // ───────────────────────────────────────────────
    // Init
    // ───────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function () {
        var isLoggedIn = document.body.classList.contains('page-dashboard') ||
                         document.querySelector('.sidebar') !== null;
        if (isLoggedIn) {
            startGlobalPolling();
        }
        setupConnectivity();
        setupSidebarToggle();
        setupRipple();
    });
})();
