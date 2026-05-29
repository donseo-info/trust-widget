<?php function esc($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); } ?>
<div class="tw-page-header">
  <a href="/sites/<?= $site['id'] ?>" class="text-muted small"><i class="bi bi-arrow-left"></i> <?= esc($site['name']) ?></a>
  <h1 class="tw-page-title"><i class="bi bi-plug"></i> Интеграции</h1>
</div>

<?php
$tg  = $integrations['telegram'];
$b24 = $integrations['bitrix24'];
$ym  = $integrations['yandex_metrika'];
$siteId = $site['id'];
$csrf = $__csrfToken ?? '';
?>

<!-- Telegram -->
<div class="tw-card mb-4">
  <div class="tw-card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0"><i class="bi bi-telegram"></i> Telegram</h5>
    <?php if ($tg['is_active'] && !empty($tg['config']['tg_token'])): ?>
      <span class="badge bg-success">Настроен</span>
    <?php else: ?>
      <span class="badge bg-secondary">Не настроен</span>
    <?php endif; ?>
  </div>
  <div class="tw-card-body">
    <form id="form-telegram">
      <input type="hidden" name="_csrf" value="<?= esc($csrf) ?>">
      <input type="hidden" name="type" value="telegram">
      <div class="row g-3 mb-3">
        <div class="col-md-6">
          <label class="form-label">Bot Token</label>
          <input type="text" name="tg_token" class="form-control"
                 value="<?= esc($tg['config']['tg_token'] ?? '') ?>"
                 placeholder="1234567890:AAXXXXXXXX">
        </div>
        <div class="col-md-4">
          <label class="form-label">Chat ID</label>
          <input type="text" name="tg_chat_id" class="form-control"
                 value="<?= esc($tg['config']['tg_chat_id'] ?? '') ?>"
                 placeholder="-1001234567890">
        </div>
        <div class="col-md-2 d-flex align-items-end">
          <div class="form-check form-switch mt-1">
            <input class="form-check-input" type="checkbox" name="is_active"
                   <?= $tg['is_active'] ? 'checked' : '' ?>>
            <label class="form-check-label">Вкл.</label>
          </div>
        </div>
      </div>
      <div class="d-flex gap-2">
        <button type="button" class="btn btn-primary"
                onclick="saveIntegration('telegram', <?= $siteId ?>)">Сохранить</button>
        <button type="button" class="btn btn-outline-secondary"
                onclick="testTelegram(<?= $siteId ?>)">Тест</button>
      </div>
    </form>
    <div id="result-telegram" class="mt-2"></div>
  </div>
</div>

<!-- Bitrix24 -->
<div class="tw-card mb-4">
  <div class="tw-card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0"><i class="bi bi-building"></i> Bitrix24</h5>
    <?php if ($b24['is_active'] && !empty($b24['config']['b24_webhook'])): ?>
      <span class="badge bg-success">Настроен</span>
    <?php else: ?>
      <span class="badge bg-secondary">Не настроен</span>
    <?php endif; ?>
  </div>
  <div class="tw-card-body">
    <form id="form-bitrix24">
      <input type="hidden" name="_csrf" value="<?= esc($csrf) ?>">
      <input type="hidden" name="type" value="bitrix24">
      <div class="mb-3">
        <label class="form-label">Webhook URL</label>
        <input type="url" name="b24_webhook" class="form-control"
               value="<?= esc($b24['config']['b24_webhook'] ?? '') ?>"
               placeholder="https://your.bitrix24.ru/rest/1/xxxxx/">
      </div>
      <div class="mb-3">
        <label class="form-label">Кастомные поля
          <small class="text-muted">Макросы: {{phone}} {{page_url}} {{ym_client_id}} {{ip}} {{utm_source}} {{utm_campaign}}</small>
        </label>
        <div id="cf-rows">
          <?php $cf = $b24['config']['b24_custom_fields'] ?? []; ?>
          <?php if (empty($cf)): ?>
          <div class="tw-cf-row d-flex gap-2 mb-2">
            <input type="text" name="cf_key[]" class="form-control form-control-sm" placeholder="FIELD_NAME" style="max-width:160px">
            <input type="text" name="cf_value[]" class="form-control form-control-sm" placeholder="{{phone}}">
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeCfRow(this)">×</button>
          </div>
          <?php else: ?>
          <?php foreach ($cf as $k => $v): ?>
          <div class="tw-cf-row d-flex gap-2 mb-2">
            <input type="text" name="cf_key[]" class="form-control form-control-sm" placeholder="FIELD_NAME" value="<?= esc($k) ?>" style="max-width:160px">
            <input type="text" name="cf_value[]" class="form-control form-control-sm" placeholder="{{phone}}" value="<?= esc($v) ?>">
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeCfRow(this)">×</button>
          </div>
          <?php endforeach; ?>
          <?php endif; ?>
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addCfRow()">
          <i class="bi bi-plus"></i> Добавить поле
        </button>
      </div>
      <div class="form-check form-switch mb-3">
        <input class="form-check-input" type="checkbox" name="is_active"
               <?= $b24['is_active'] ? 'checked' : '' ?>>
        <label class="form-check-label">Включить</label>
      </div>
      <div class="d-flex gap-2">
        <button type="button" class="btn btn-primary"
                onclick="saveIntegration('bitrix24', <?= $siteId ?>)">Сохранить</button>
        <button type="button" class="btn btn-outline-secondary"
                onclick="testBitrix(<?= $siteId ?>)">Тест</button>
      </div>
    </form>
    <div id="result-bitrix24" class="mt-2"></div>
  </div>
</div>

<!-- Yandex.Metrika -->
<div class="tw-card mb-4">
  <div class="tw-card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0"><i class="bi bi-bar-chart-line"></i> Яндекс.Метрика</h5>
    <?php if ($ym['is_active'] && !empty($ym['config']['counter_id'])): ?>
      <span class="badge bg-success">Настроена</span>
    <?php else: ?>
      <span class="badge bg-secondary">Не настроена</span>
    <?php endif; ?>
  </div>
  <div class="tw-card-body">
    <form id="form-yandex_metrika">
      <input type="hidden" name="_csrf" value="<?= esc($csrf) ?>">
      <input type="hidden" name="type" value="yandex_metrika">
      <div class="row g-3 mb-3">
        <div class="col-md-12">
          <label class="form-label">ID счётчика <small class="text-muted">(общий для всех виджетов)</small></label>
          <input type="text" name="ym_counter_id" class="form-control"
                 value="<?= esc($ym['config']['counter_id'] ?? '') ?>" placeholder="12345678" style="max-width:220px">
        </div>
      </div>

      <div class="mb-2"><strong class="small"><i class="bi bi-door-open"></i> Exit Popup</strong></div>
      <div class="row g-3 mb-3">
        <div class="col-md-6">
          <label class="form-label">Цель «Показ попапа»</label>
          <input type="text" name="ym_goal_open" class="form-control"
                 value="<?= esc($ym['config']['goal_open'] ?? 'popup_open') ?>">
          <div class="form-text">Без дедупликации — на каждый показ</div>
        </div>
        <div class="col-md-6">
          <label class="form-label">Цель «Заявка из попапа»</label>
          <input type="text" name="ym_goal_lead" class="form-control"
                 value="<?= esc($ym['config']['goal_lead'] ?? 'popup_lead') ?>">
          <div class="form-text">Дедуп: cookie <code>_ei_sent</code>, 30 дней</div>
        </div>
      </div>

      <div class="mb-2"><strong class="small"><i class="bi bi-telephone"></i> Обратный звонок</strong></div>
      <div class="row g-3 mb-3">
        <div class="col-md-6">
          <label class="form-label">Цель «Заявка обратного звонка»</label>
          <input type="text" name="ym_goal_callback" class="form-control"
                 value="<?= esc($ym['config']['goal_callback'] ?? 'callback_widget') ?>">
          <div class="form-text">Дедуп: cookie <code>scw_ym_goal</code>, 1 месяц</div>
        </div>
      </div>
      <div class="form-text mb-3">Цели отправляются через <code>ym(id, 'reachGoal', ...)</code> прямо в браузере — токен не нужен.</div>
      <div class="form-check form-switch mb-3">
        <input class="form-check-input" type="checkbox" name="is_active"
               <?= $ym['is_active'] ? 'checked' : '' ?>>
        <label class="form-check-label">Включить</label>
      </div>
      <button type="button" class="btn btn-primary"
              onclick="saveIntegration('yandex_metrika', <?= $siteId ?>)">Сохранить</button>
    </form>
    <div id="result-yandex_metrika" class="mt-2"></div>
  </div>
</div>

<script>
const CSRF = '<?= $csrf ?>';

function addCfRow() {
  const row = document.createElement('div');
  row.className = 'tw-cf-row d-flex gap-2 mb-2';
  row.innerHTML = `<input type="text" name="cf_key[]" class="form-control form-control-sm" placeholder="FIELD_NAME" style="max-width:160px">
    <input type="text" name="cf_value[]" class="form-control form-control-sm" placeholder="{{phone}}">
    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeCfRow(this)">×</button>`;
  document.getElementById('cf-rows').appendChild(row);
}
function removeCfRow(btn) { btn.closest('.tw-cf-row').remove(); }

async function saveIntegration(type, siteId) {
  const form = document.getElementById('form-' + type);
  const data = new FormData(form);
  data.set('type', type);
  const r = await fetch('/sites/' + siteId + '/integrations', {method: 'POST', body: new URLSearchParams(data)});
  const d = await r.json();
  showResult('result-' + type, d.ok, d.ok ? 'Сохранено' : (d.error || 'Ошибка'));
}

async function testTelegram(siteId) {
  const form = document.getElementById('form-telegram');
  const data = new FormData(form);
  data.set('type', 'telegram');
  const r = await fetch('/sites/' + siteId + '/integrations/test-telegram', {method: 'POST', body: new URLSearchParams(data)});
  const d = await r.json();
  showResult('result-telegram', d.ok, d.ok ? '✅ Сообщение отправлено!' : '❌ ' + (d.error || 'Ошибка'));
}

async function testBitrix(siteId) {
  const form = document.getElementById('form-bitrix24');
  const data = new FormData(form);
  const r = await fetch('/sites/' + siteId + '/integrations/test-bitrix', {method: 'POST', body: new URLSearchParams(data)});
  const d = await r.json();
  showResult('result-bitrix24', d.ok, d.ok ? '✅ Тестовый лид создан!' : '❌ ' + (d.error || 'Ошибка'));
}

function showResult(id, ok, msg) {
  const el = document.getElementById(id);
  el.innerHTML = `<div class="alert alert-${ok ? 'success' : 'danger'} py-2 mb-0">${msg}</div>`;
  setTimeout(() => el.innerHTML = '', 5000);
}
</script>
