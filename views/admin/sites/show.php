<?php function esc($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); } ?>
<div class="tw-page-header d-flex justify-content-between align-items-center">
  <div>
    <a href="/sites" class="text-muted small"><i class="bi bi-arrow-left"></i> Сайты</a>
    <h1 class="tw-page-title mb-0"><?= esc($site['name']) ?></h1>
    <div class="text-muted"><?= esc($site['domain']) ?></div>
  </div>
  <div class="d-flex gap-2">
    <a href="/sites/<?= $site['id'] ?>/edit" class="btn btn-outline-secondary">
      <i class="bi bi-pencil"></i> Редактировать
    </a>
  </div>
</div>

<!-- Stats row -->
<div class="row g-3 mb-4">
  <div class="col-md-3">
    <div class="tw-card tw-stat-card">
      <div class="tw-stat-icon bg-warning-soft"><i class="bi bi-people"></i></div>
      <div>
        <div class="tw-stat-value"><?= $leads_count ?></div>
        <div class="tw-stat-label">Всего заявок</div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="tw-card tw-stat-card">
      <div class="tw-stat-icon bg-info-soft"><i class="bi bi-calendar-day"></i></div>
      <div>
        <div class="tw-stat-value"><?= $leads_today ?></div>
        <div class="tw-stat-label">Сегодня</div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="tw-card tw-stat-card">
      <div class="tw-stat-icon bg-primary-soft"><i class="bi bi-key"></i></div>
      <div>
        <div class="tw-stat-value" style="font-size:13px;font-weight:600"><?= esc($site['api_key']) ?></div>
        <div class="tw-stat-label">API ключ</div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="tw-card tw-stat-card">
      <div class="tw-stat-icon <?= $site['is_active'] ? 'bg-success-soft' : 'bg-secondary-soft' ?>">
        <i class="bi bi-circle-fill"></i>
      </div>
      <div>
        <div class="tw-stat-value"><?= $site['is_active'] ? 'Активен' : 'Отключён' ?></div>
        <div class="tw-stat-label">Статус сайта</div>
      </div>
    </div>
  </div>
</div>

<!-- Widgets -->
<div class="tw-card mb-4">
  <div class="tw-card-header">
    <h5 class="mb-0"><i class="bi bi-puzzle"></i> Виджеты</h5>
  </div>
  <div class="tw-widget-grid">
    <?php foreach ($widgets as $w): ?>
    <div class="tw-widget-card <?= $w['is_active'] ? 'active' : 'inactive' ?>">
      <div class="tw-widget-card-icon"><i class="bi <?= esc($w['icon']) ?>"></i></div>
      <div class="tw-widget-card-body">
        <div class="tw-widget-card-name"><?= esc($w['name']) ?></div>
        <div class="tw-widget-card-status">
          <?= $w['is_active'] ? '<span class="badge bg-success">Активен</span>' : '<span class="badge bg-secondary">Отключён</span>' ?>
        </div>
      </div>
      <div class="tw-widget-card-actions">
        <a href="/sites/<?= $site['id'] ?>/widgets/<?= esc($w['slug']) ?>"
           class="btn btn-sm btn-primary">Настройки</a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Quick links -->
<div class="row g-3">
  <div class="col-md-4">
    <a href="/sites/<?= $site['id'] ?>/integrations" class="tw-quick-link">
      <i class="bi bi-plug"></i>
      <div>
        <strong>Интеграции</strong>
        <div class="text-muted small">Telegram, Bitrix24, Метрика</div>
      </div>
    </a>
  </div>
  <div class="col-md-4">
    <a href="/leads?site=<?= $site['id'] ?>" class="tw-quick-link">
      <i class="bi bi-people"></i>
      <div>
        <strong>Заявки</strong>
        <div class="text-muted small">Все заявки по сайту</div>
      </div>
    </a>
  </div>
  <div class="col-md-4">
    <div class="tw-quick-link" style="cursor:default">
      <i class="bi bi-code-slash"></i>
      <div>
        <strong>Установка</strong>
        <div class="text-muted small">Код для вставки на сайт</div>
        <button class="btn btn-sm btn-outline-primary mt-2" data-bs-toggle="modal" data-bs-target="#installModal">
          Показать код
        </button>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="tw-quick-link" style="cursor:default">
      <i class="bi bi-bug <?= $site['debug_mode'] ? 'text-warning' : 'text-muted' ?>"></i>
      <div>
        <strong>Отладка виджетов</strong>
        <div class="text-muted small">Логи в консоль браузера</div>
        <button id="debugToggleBtn" class="btn btn-sm mt-2 <?= $site['debug_mode'] ? 'btn-warning' : 'btn-outline-secondary' ?>"
                onclick="toggleDebug()">
          <?= $site['debug_mode'] ? '🟡 Debug ON' : '⚫ Debug OFF' ?>
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Install modal -->
<div class="modal fade" id="installModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Код установки</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <?php
          $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
          $host  = $_SERVER['HTTP_HOST'] ?? 'localhost';
          $base  = $proto . '://' . $host;
          $key   = $site['api_key'];

          $loaderUrl   = $base . '/widgets/loader.php?key=' . $key;
          $ic          = new InstallCode($loaderUrl);
          $variantMeta = [
            'simple'     => ['title' => 'Простой тег',        'badge' => '',              'note' => 'Обычный &lt;script&gt;'],
            'async'      => ['title' => 'Асинхронный',        'badge' => 'рекомендуется', 'note' => 'Не блокирует загрузку страницы'],
            'obfuscated' => ['title' => 'Скрытый домен',      'badge' => 'обфускация',    'note' => 'Домен разбит на части — не читается в исходнике'],
            'gtm'        => ['title' => 'Google Tag Manager', 'badge' => '',              'note' => 'Custom HTML тег → триггер All Pages'],
          ];
          $codes = [
            'simple'     => $ic->simple(),
            'async'      => $ic->async(),
            'obfuscated' => $ic->obfuscated(),
            'gtm'        => $ic->gtm(),
          ];
        ?>

        <p class="text-muted small">Один тег для всех активных виджетов сайта. Вставьте перед <code>&lt;/body&gt;</code>. Ключ <code><?= esc($key) ?></code> уникален для этого сайта.</p>

        <?php foreach ($codes as $vk => $code): $m = $variantMeta[$vk]; ?>
        <div class="mb-3">
          <div class="d-flex justify-content-between align-items-center mb-1">
            <div>
              <strong class="small"><?= $m['title'] ?></strong>
              <?php if ($m['badge']): ?><span class="badge bg-warning text-dark ms-1" style="font-size:10px"><?= $m['badge'] ?></span><?php endif; ?>
              <div class="text-muted" style="font-size:11px"><?= $m['note'] ?></div>
            </div>
            <button class="btn btn-sm btn-outline-secondary" onclick="copyCode(this)">Копировать</button>
          </div>
          <pre class="tw-code"><code><?= esc($code) ?></code></pre>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<script>
function copyCode(btn) {
  const block = btn.closest('.mb-3');
  const pre = block ? block.querySelector('pre') : null;
  if (!pre) return;
  navigator.clipboard.writeText(pre.textContent.trim()).then(() => {
    const orig = btn.textContent;
    btn.textContent = 'Скопировано!';
    setTimeout(() => btn.textContent = orig, 2000);
  });
}
</script>
<script>
const CSRF = '<?= $__csrfToken ?? '' ?>';
async function toggleDebug() {
  const r = await fetch('/sites/<?= $site['id'] ?>/toggle-debug', {
    method: 'POST', body: new URLSearchParams({_csrf: CSRF})
  });
  const d = await r.json();
  if (!d.ok) return;
  const btn = document.getElementById('debugToggleBtn');
  if (d.debug) {
    btn.className = 'btn btn-sm mt-2 btn-warning';
    btn.textContent = '🟡 Debug ON';
  } else {
    btn.className = 'btn btn-sm mt-2 btn-outline-secondary';
    btn.textContent = '⚫ Debug OFF';
  }
}
</script>
