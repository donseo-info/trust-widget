/**
 * popup.js — автономный exit-intent скрипт
 *
 * Подключение на любой сайт:
 * ─────────────────────────────────────────────────────
 * Вариант A — тег напрямую:
 *   <script src="https://your-domain.ru/popup.js"
 *           data-gate="https://your-domain.ru/close-window/api/gate.php"
 *           data-counter="12345678">  ← необязательно: ID счётчика Яндекс.Метрики
 *   </script>
 *
 * Вариант B — асинхронный лоадер (рекомендуется):
 *   <script>
 *   (function(w,d,s,u,g,c){
 *     w._EI={gate:g,counter:c};
 *     var el=d.createElement(s);el.async=1;el.src=u;
 *     d.head.appendChild(el);
 *   })(window,document,'script',
 *     'https://your-domain.ru/close-window/popup.js',
 *     'https://your-domain.ru/close-window/api/gate.php',
 *     '12345678');   // ID счётчика, можно убрать
 *   </script>
 * ─────────────────────────────────────────────────────
 *
 * Поведение:
 *  - Срабатывает 1 раз на сессию (sessionStorage)
 *  - Курсор уходит за верхний край окна → случайный попап A/B/C
 *  - Если Яндекс.Метрика доступна → шлёт gate «open»
 *  - Если Метрика заблокирована  → «open» не шлём, «lead» шлём с has_ym=0
 */
(function () {
  'use strict';

  /* ── 1. Получаем атрибуты: gate URL, базовый URL, counter ── */
  var scriptEl = document.currentScript || (function () {
    var all = document.querySelectorAll('script[data-gate]');
    return all[all.length - 1] || null;
  })();

  var gateUrl = (scriptEl && scriptEl.dataset.gate)
    || (window._EI && window._EI.gate)
    || '';

  var siteKey = (scriptEl && scriptEl.dataset.key)
    || (window._EI && window._EI.key)
    || '';

  var counterId = (scriptEl && scriptEl.dataset.counter)
    || (window._EI && window._EI.counter)
    || '';

  /* базовый URL для popup.php — из конфига (unified loader) или из src тега */
  var baseUrl = (window._EI && window._EI.popupBase) || (function () {
    if (scriptEl && scriptEl.src) {
      return scriptEl.src.replace(/\/[^\/\?#]+(\?[^#]*)?(#.*)?$/, '');
    }
    var all = document.scripts;
    for (var i = all.length - 1; i >= 0; i--) {
      if (all[i].src && all[i].src.indexOf('popup.js') !== -1) {
        return all[i].src.replace(/\/[^\/\?#]+(\?[^#]*)?(#.*)?$/, '');
      }
    }
    return '';
  }());

  /* ── 2. Один показ на браузерную сессию ── */
  var SS_KEY = '_ei_shown';
  var _alreadyShown = false;
  try { _alreadyShown = !!sessionStorage.getItem(SS_KEY); } catch (e) {}

  /* ── 3. YM: передаём counter ID в core для резолва ── */
  if (window.__TW) {
    window.__TW.log('popup', 'Скрипт загружен. key=' + siteKey + ' counter=' + counterId);
    window.__TW.initYm(counterId);
  }

  /* ── 4. Предзагрузка скрипта попапа для домена ── */
  var _domain = window.location.hostname.replace(/^www\./i, '');

  function loadPopupScript(variant, cb) {
    var globalName = 'Popup' + variant;
    if (window[globalName]) { cb(window[globalName]); return; }

    var s = document.createElement('script');
    s.src = baseUrl + '/api/popup.php?variant=' + variant
          + (siteKey ? '&key=' + encodeURIComponent(siteKey) : '&domain=' + encodeURIComponent(_domain));
    s.onload  = function () { cb(window[globalName] || null); };
    s.onerror = function () { cb(null); };
    document.head.appendChild(s);
  }

  function preloadChosen() {
    loadPopupScript(_chosenVariant, function () {});
  }

  /* Грузим только выбранный вариант через 1.5 сек после DOMContentLoaded */
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      setTimeout(preloadChosen, 1500);
    });
  } else {
    setTimeout(preloadChosen, 1500);
  }

  /* ── 5. Отправка события на гейт ── */
  function sendEvent(data) {
    if (!gateUrl) return;
    data.url      = window.location.href;
    data.referrer = document.referrer || '';
    if (siteKey) data.key = siteKey;
    try {
      fetch(gateUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(data).toString(),
        keepalive: true
      }).catch(function () {});
    } catch (e) {
      /* fetch недоступен — fallback на Image beacon */
      var params = new URLSearchParams(data).toString();
      new Image().src = gateUrl + '?' + params;
    }
  }

  /* ── 6. Основная логика показа ── */
  var VARIANTS = (window._EI_variants && window._EI_variants.length) ? window._EI_variants : ['A', 'B', 'C'];

  /* Вариант выбирается один раз при загрузке страницы и запоминается */
  var _chosenVariant = VARIANTS[Math.floor(Math.random() * VARIANTS.length)];
  window.__TW && window.__TW.log('popup', 'variants=' + VARIANTS.join(',') + ' chosen=' + _chosenVariant + ' alreadyShown=' + _alreadyShown);

  function showVariant(variant, skipSession) {
    window.__TW && window.__TW.log('popup', 'showVariant', variant, 'skipSession=' + skipSession);
    /* Блокируем повторный показ в этой сессии (если не ручной вызов) */
    if (!skipSession) {
      try { sessionStorage.setItem(SS_KEY, '1'); } catch (e) {}
    }

    window.__TW.getYmClientId(function (ymId) {
      var hasYm = ymId ? 1 : 0;
      window.__TW && window.__TW.log('popup', 'ymClientId:', ymId || '(пусто)', 'hasYm=' + hasYm);

      loadPopupScript(variant, function (popup) {
        if (!popup) { window.__TW && window.__TW.log('popup', '❌ popup скрипт не загружен, variant=' + variant); return; }

        /* ID счётчика: из админки (_EI_counter инжектируется popup.php) или из тега */
        var cid = window._EI_counter || counterId;

        window.__TW && window.__TW.log('popup', '✅ Попап показан, variant=' + variant);

        popup.onSubmit(function (data) {
          window.__TW && window.__TW.log('popup', '📤 Лид отправлен из попапа', {phone: data.phone, messenger: data.messenger, variant: variant});
          if (window.__TW) window.__TW.leadSubmitted = true;
          sendEvent({
            action:       'lead',
            variant:      variant,
            phone:        data.phone     || '',
            messenger:    data.messenger || '',
            ym_client_id: ymId,
            has_ym:       hasYm,
            email:        data.email     || '',
            _csrf:        window._EI_csrf || ''
          });
          /* Цель «лид» в Яндекс.Метрику — только если ещё не отправляли (кука 30 дней) */
          var goalLead = window._EI_ym && window._EI_ym.goal_lead;
          var eiSentCookie = !!document.cookie.match(/_ei_sent=1/);
          window.__TW && window.__TW.log('popup', 'Кука _ei_sent=1:', eiSentCookie ? 'есть — цель не отправляем' : 'нет');
          if (goalLead && cid && window.ym && !eiSentCookie) {
            try { ym(cid, 'reachGoal', goalLead); window.__TW && window.__TW.log('popup', 'reachGoal(lead) отправлен:', goalLead, 'counter=' + cid); } catch (e) {}
            var exp = new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toUTCString();
            document.cookie = '_ei_sent=1;path=/;expires=' + exp + ';SameSite=Lax';
          }
        });

        sendEvent({
          action:       'open',
          variant:      variant,
          ym_client_id: ymId,
          has_ym:       hasYm
        });

        /* Цель «показ попапа» в Яндекс.Метрику */
        var goalOpen = window._EI_ym && window._EI_ym.goal_open;
        if (goalOpen && cid && window.ym) {
          try { ym(cid, 'reachGoal', goalOpen); } catch (e) {}
        }

        popup.show();
      });
    });
  }

  /* ── 7. Exit-intent триггер: курсор уходит за верхний край ── */
  var fired = false;
  document.addEventListener('mouseout', function (e) {
    if (fired || _alreadyShown) return;
    if (window.__TW && window.__TW.leadSubmitted) {
      window.__TW.log('popup', 'Exit intent — заблокирован: лид уже отправлен');
      return;
    }
    var to = e.relatedTarget || e.toElement;
    if (to) return;
    if (e.clientY > 10) return;
    fired = true;
    window.__TW && window.__TW.log('popup', '🖱 Exit intent сработал → показываем вариант ' + _chosenVariant);
    showVariant(_chosenVariant, false);
  });

  /* ── 8. Публичное API (для тестовых страниц) ── */
  window.EI = {
    /** Показать конкретный вариант вручную (не считается в сессию) */
    show: function (variant) {
      var v = variant ? variant.toUpperCase() : VARIANTS[Math.floor(Math.random() * VARIANTS.length)];
      showVariant(v, true /* skipSession */);
    },
    /** Сбросить флаг сессии (для повторного тестирования) */
    reset: function () {
      try { sessionStorage.removeItem(SS_KEY); } catch (e) {}
      fired = false;
      _alreadyShown = false;
    }
  };

})();
