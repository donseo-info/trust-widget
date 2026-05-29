/**
 * core.js — общие утилиты для всех виджетов Trust Widget.
 * Экспортирует window.__TW:
 *   .leadSubmitted    — флаг: лид уже отправлен (любым виджетом)
 *   .log(tag, ...args)— debug-лог (только если window.__TW_DEBUG === true)
 *   .initYm(id)       — инициировать резолв YM с известным counter ID
 *   .getYmClientId(cb)— получить YM client ID асинхронно (cb('123') или cb(''))
 *   .getUtms()        — объект { utm_source, ... } из URL или sessionStorage
 *   .formatPhone(raw) — форматировать строку в +7 (xxx) xxx-xx-xx
 *   .maskPhone(input) — навесить маску на <input type="tel">
 *   .isValidPhone(val)— true если 11 цифр
 */
(function () {
    'use strict';
    if (window.__TW) return; // уже загружен

    // ─── Debug logger ─────────────────────────────────────────────────────────
    function twLog(tag) {
        if (!window.__TW_DEBUG) return;
        var args = Array.prototype.slice.call(arguments, 1);
        var prefix = '%c[TW:' + tag + ']';
        var style  = 'color:#fff;background:#6366f1;padding:1px 5px;border-radius:3px;font-weight:600';
        console.log.apply(console, [prefix, style].concat(args));
    }

    twLog('core', 'Загружен. debug=ON, url=' + window.location.href);

    // ─── YM Client ID ─────────────────────────────────────────────────────────
    var _ymId     = '';
    var _ymReady  = false;
    var _ymInited = false;

    function _initYmApi(counterId) {
        if (_ymInited || _ymReady) return;
        _ymInited = true;
        twLog('ym', 'getClientID через API, counter=' + counterId);
        var done = false;
        var t = setTimeout(function () {
            if (!done) {
                done = true; _ymReady = true;
                twLog('ym', 'getClientID таймаут 800ms — clientId не получен');
            }
        }, 800);
        try {
            ym(counterId, 'getClientID', function (id) {
                if (!done) {
                    done = true; clearTimeout(t);
                    _ymId = id || ''; _ymReady = true;
                    twLog('ym', 'clientId получен:', _ymId || '(пусто)');
                }
            });
        } catch (e) {
            if (!done) { done = true; _ymReady = true; }
            twLog('ym', 'ошибка ym API:', e.message);
        }
    }

    // Немедленный резолв: localStorage → cookie → автодетект счётчика
    (function resolve() {
        try {
            var lsVal = localStorage.getItem('_ym_uid');
            if (lsVal) {
                var m = lsVal.replace(/"/g, '').match(/\d+/);
                if (m) {
                    _ymId = m[0]; _ymReady = true;
                    twLog('ym', 'clientId из localStorage:', _ymId);
                    return;
                }
            }
        } catch (e) {}
        try {
            var cm = document.cookie.match(/_ym_uid=(\d+)/);
            if (cm) {
                _ymId = cm[1]; _ymReady = true;
                twLog('ym', 'clientId из cookie:', _ymId);
                return;
            }
        } catch (e) {}
        try {
            var counters = window.Ya && window.Ya.Metrika2 && window.Ya.Metrika2.counters();
            if (counters && counters.length && window.ym) {
                twLog('ym', 'Метрика найдена, счётчиков: ' + counters.length + ', id=' + counters[0].id);
                _initYmApi(counters[0].id);
                return;
            }
        } catch (e) {}
        _ymReady = true;
        twLog('ym', 'Метрика недоступна — clientId пуст');
    })();

    // ─── UTM ─────────────────────────────────────────────────────────────────
    var UTM_KEYS = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'];
    var SS_PREFIX = 'tw_';

    (function saveUtms() {
        try {
            var usp = new URLSearchParams(window.location.search);
            var saved = [];
            UTM_KEYS.forEach(function (k) {
                var v = usp.get(k);
                if (v) { sessionStorage.setItem(SS_PREFIX + k, v); saved.push(k + '=' + v); }
            });
            if (saved.length) twLog('utm', 'Сохранены из URL:', saved.join(', '));
            else twLog('utm', 'UTM в URL не найдены');
        } catch (e) {}
    })();

    function getUtms() {
        var result = {};
        try {
            var usp = new URLSearchParams(window.location.search);
            UTM_KEYS.forEach(function (k) {
                result[k] = usp.get(k) || sessionStorage.getItem(SS_PREFIX + k) || '';
            });
        } catch (e) {}
        return result;
    }

    // ─── Phone mask ───────────────────────────────────────────────────────────
    var PHONE_PREFIX = '+7 (';

    function formatPhone(raw) {
        var val = raw.replace(/\D/g, '');
        if (val[0] === '7' || val[0] === '8') val = val.slice(1);
        val = val.slice(0, 10);
        var res = PHONE_PREFIX;
        if (val.length > 0)  res += val.slice(0, 3);
        if (val.length >= 4) res += ') ' + val.slice(3, 6);
        if (val.length >= 7) res += '-' + val.slice(6, 8);
        if (val.length >= 9) res += '-' + val.slice(8, 10);
        return res;
    }

    function maskPhone(input) {
        input.value = PHONE_PREFIX;
        input.addEventListener('input', function () { input.value = formatPhone(input.value); });
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Backspace' && input.value.length <= PHONE_PREFIX.length) e.preventDefault();
        });
        input.addEventListener('focus', function () {
            if (!input.value || input.value.length < PHONE_PREFIX.length) input.value = PHONE_PREFIX;
        });
    }

    function isValidPhone(val) {
        return val.replace(/\D/g, '').length === 11;
    }

    // ─── Публичный API ────────────────────────────────────────────────────────
    window.__TW = {
        leadSubmitted: false,
        log: twLog,

        initYm: function (counterId) {
            if (!counterId || _ymReady || _ymInited) return;
            _initYmApi(counterId);
        },

        getYmClientId: function (cb) {
            if (_ymReady) { cb(_ymId); return; }
            var attempts = 0;
            var timer = setInterval(function () {
                if (_ymReady || ++attempts > 20) { clearInterval(timer); cb(_ymId); }
            }, 50);
        },

        getUtms:      getUtms,
        formatPhone:  formatPhone,
        maskPhone:    maskPhone,
        isValidPhone: isValidPhone,
    };

})();
