<?php
function esc($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$variants = ['A', 'B', 'C'];
$variantColors = ['A' => ['bg' => '#1a1a2e', 'btn' => '#e02020'], 'B' => ['bg' => '#1a1a2e', 'btn' => '#1db954'], 'C' => ['bg' => '#1a1a2e', 'btn' => '#2563eb']];
$defaults = [
    'A' => ['headline' => 'Подождите! Мы дарим скидку', 'subtext' => 'Оставьте телефон и получите персональное предложение', 'btn_label' => 'Хочу скидку', 'messengers' => ['tg', 'wa'], 'timer' => 300],
    'B' => ['headline' => 'Не уходите без подарка!', 'subtext' => 'Оставьте контакт — вышлем подарок на ваш номер', 'btn_label' => 'Получить подарок', 'messengers' => ['tg', 'wa'], 'timer' => 300],
    'C' => ['headline' => 'Ещё немного — и предложение сгорит', 'subtext' => 'Успейте оставить заявку прямо сейчас', 'btn_label' => 'Успеть', 'messengers' => ['tg', 'wa'], 'timer' => 300],
];

$cfg = $config['variants'] ?? [];
$enabled = $config['variants_enabled'] ?? ['A', 'B', 'C'];
?>
<div class="tw-page-header">
  <a href="<?= url('sites/' . $site['id']) ?>" class="text-muted small"><i class="bi bi-arrow-left"></i> <?= esc($site['name']) ?></a>
  <h1 class="tw-page-title"><i class="bi bi-door-open"></i> Exit Popup</h1>
</div>

<form id="widget-form" method="POST" action="<?= url('sites/' . $site['id'] . '/widgets/exit_popup') ?>">
  <input type="hidden" name="_csrf" value="<?= esc($__csrfToken ?? '') ?>">

  <div class="tw-card mb-4">
    <div class="tw-card-header"><h5 class="mb-0">A/B/C варианты</h5></div>
    <div class="tw-card-body">
      <p class="text-muted small mb-3">Каждый посетитель случайно видит один из включённых вариантов.</p>
      <div class="d-flex gap-3">
        <?php foreach ($variants as $v): ?>
        <div class="form-check">
          <input class="form-check-input" type="checkbox"
                 name="variant_<?= $v ?>" id="variant_<?= $v ?>"
                 <?= in_array($v, (array)$enabled) ? 'checked' : '' ?>>
          <label class="form-check-label" for="variant_<?= $v ?>">Вариант <?= $v ?></label>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Per-variant config -->
  <ul class="nav nav-tabs mb-3" id="variantTabs">
    <?php foreach ($variants as $i => $v): ?>
    <li class="nav-item">
      <button class="nav-link <?= $i === 0 ? 'active' : '' ?>"
              type="button" data-bs-toggle="tab"
              data-bs-target="#tab-<?= $v ?>">
        Вариант <?= $v ?>
      </button>
    </li>
    <?php endforeach; ?>
  </ul>

  <div class="tab-content">
  <?php foreach ($variants as $i => $v):
    $d = array_merge($defaults[$v], $cfg[$v] ?? []);
  ?>
  <div class="tab-pane fade <?= $i === 0 ? 'show active' : '' ?>" id="tab-<?= $v ?>">
    <div class="tw-card mb-4">
      <div class="tw-card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Заголовок</label>
            <input type="text" name="<?= $v ?>_headline" class="form-control"
                   value="<?= esc($d['headline']) ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Подзаголовок</label>
            <input type="text" name="<?= $v ?>_subtext" class="form-control"
                   value="<?= esc($d['subtext']) ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">Текст кнопки</label>
            <input type="text" name="<?= $v ?>_btn_label" class="form-control"
                   value="<?= esc($d['btn_label']) ?>">
          </div>
          <div class="col-md-2">
            <label class="form-label">Таймер (сек)</label>
            <input type="number" name="<?= $v ?>_timer" class="form-control"
                   value="<?= (int)($d['timer'] ?? 300) ?>" min="30" max="600">
          </div>
          <div class="col-md-2">
            <label class="form-label">Фон попапа</label>
            <input type="color" name="<?= $v ?>_color_bg" class="form-control form-control-color"
                   value="<?= esc($d['color_bg'] ?? $variantColors[$v]['bg']) ?>">
          </div>
          <div class="col-md-2">
            <label class="form-label">Цвет кнопки</label>
            <input type="color" name="<?= $v ?>_color_btn" class="form-control form-control-color"
                   value="<?= esc($d['color_btn'] ?? $variantColors[$v]['btn']) ?>">
          </div>
          <div class="col-md-12">
            <label class="form-label">Мессенджеры</label>
            <div class="d-flex gap-3">
              <?php foreach (['tg' => 'Telegram', 'wa' => 'WhatsApp', 'mx' => 'Max'] as $m => $ml): ?>
              <div class="form-check">
                <input class="form-check-input" type="checkbox"
                       name="<?= $v ?>_messengers[]" value="<?= $m ?>"
                       id="<?= $v ?>_<?= $m ?>"
                       <?= in_array($m, (array)($d['messengers'] ?? [])) ? 'checked' : '' ?>>
                <label class="form-check-label" for="<?= $v ?>_<?= $m ?>"><?= $ml ?></label>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
  </div>

  <button type="submit" class="btn btn-primary">
    <i class="bi bi-check2"></i> Сохранить настройки
  </button>
  <?php if (!empty($_GET['saved'])): ?>
  <span class="text-success ms-2"><i class="bi bi-check-circle"></i> Сохранено</span>
  <?php endif; ?>
</form>
