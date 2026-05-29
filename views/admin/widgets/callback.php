<?php function esc($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
$c = $config;
$str = fn(string $k, string $d) => $c[$k] ?? $d;
$int = fn(string $k, int $d) => (int)($c[$k] ?? $d);
?>
<div class="tw-page-header">
  <a href="<?= url('sites/' . $site['id']) ?>" class="text-muted small"><i class="bi bi-arrow-left"></i> <?= esc($site['name']) ?></a>
  <h1 class="tw-page-title"><i class="bi bi-telephone"></i> Обратный звонок</h1>
</div>

<form method="POST" action="<?= url('sites/' . $site['id'] . '/widgets/callback') ?>">
  <input type="hidden" name="_csrf" value="<?= esc($__csrfToken ?? '') ?>">

  <div class="row g-4">
    <div class="col-md-7">

      <!-- Appearance -->
      <div class="tw-card mb-4">
        <div class="tw-card-header"><h5 class="mb-0"><i class="bi bi-palette"></i> Внешний вид</h5></div>
        <div class="tw-card-body">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Цвет кнопки</label>
              <input type="color" name="button_color" class="form-control form-control-color"
                     value="<?= esc($str('button_color', '#25c16f')) ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Позиция</label>
              <select name="position" class="form-select">
                <option value="right" <?= $str('position','right') === 'right' ? 'selected' : '' ?>>Справа</option>
                <option value="left"  <?= $str('position','right') === 'left'  ? 'selected' : '' ?>>Слева</option>
              </select>
            </div>
            <div class="col-md-2">
              <label class="form-label">Снизу (px)</label>
              <input type="number" name="bottom_offset" class="form-control"
                     value="<?= $int('bottom_offset', 30) ?>" min="0" max="200">
            </div>
            <div class="col-md-2">
              <label class="form-label">Сбоку (px)</label>
              <input type="number" name="side_offset" class="form-control"
                     value="<?= $int('side_offset', 30) ?>" min="0" max="200">
            </div>
            <div class="col-md-6">
              <label class="form-label">Задержка показа (сек)</label>
              <input type="number" name="show_delay" class="form-control"
                     value="<?= $int('show_delay', 5) ?>" min="0" max="120">
            </div>
          </div>
        </div>
      </div>

      <!-- Texts -->
      <div class="tw-card mb-4">
        <div class="tw-card-header"><h5 class="mb-0"><i class="bi bi-chat-text"></i> Тексты</h5></div>
        <div class="tw-card-body">
          <div class="row g-3">
            <div class="col-md-12">
              <label class="form-label">Заголовок попапа</label>
              <input type="text" name="title" class="form-control"
                     value="<?= esc($str('title', 'Перезвоним за 30 секунд')) ?>">
            </div>
            <div class="col-md-12">
              <label class="form-label">Подзаголовок</label>
              <input type="text" name="subtitle" class="form-control"
                     value="<?= esc($str('subtitle', 'Оставьте номер — мы сами позвоним')) ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Кнопка отправки</label>
              <input type="text" name="submit_btn_text" class="form-control"
                     value="<?= esc($str('submit_btn_text', 'Перезвоните мне')) ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Плашка-подсказка</label>
              <input type="text" name="badge_text" class="form-control"
                     value="<?= esc($str('badge_text', 'Перезвонить?')) ?>">
            </div>
            <div class="col-md-12">
              <label class="form-label">Текст после отправки</label>
              <input type="text" name="success_text" class="form-control"
                     value="<?= esc($str('success_text', 'Спасибо! Перезвоним в течение 30 секунд.')) ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Ссылка на политику</label>
              <input type="url" name="privacy_url" class="form-control"
                     value="<?= esc($str('privacy_url', '')) ?>" placeholder="https://example.com/privacy">
            </div>
            <div class="col-md-6">
              <label class="form-label">Текст ссылки</label>
              <input type="text" name="privacy_text" class="form-control"
                     value="<?= esc($str('privacy_text', 'Политика конфиденциальности')) ?>">
            </div>
          </div>
        </div>
      </div>

      <!-- Auto-open -->
      <div class="tw-card mb-4">
        <div class="tw-card-header d-flex justify-content-between">
          <h5 class="mb-0"><i class="bi bi-magic"></i> Авто-открытие</h5>
          <div class="form-check form-switch mb-0">
            <input class="form-check-input" type="checkbox" name="auto_open"
                   id="auto_open" <?= !empty($c['auto_open']) ? 'checked' : '' ?>>
            <label class="form-check-label" for="auto_open">Вкл.</label>
          </div>
        </div>
        <div class="tw-card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Время на сайте (сек)</label>
              <input type="number" name="auto_open_time" class="form-control"
                     value="<?= $int('auto_open_time', 30) ?>" min="5" max="600">
            </div>
            <div class="col-md-6">
              <label class="form-label">Прокрутка (%)</label>
              <input type="number" name="auto_open_scroll" class="form-control"
                     value="<?= (int)(($c['auto_open_scroll'] ?? 0.75) * 100) ?>" min="0" max="100">
              <div class="form-text">0 = любая прокрутка</div>
            </div>
            <div class="col-md-12">
              <label class="form-label">Заголовок при авто-открытии</label>
              <input type="text" name="auto_open_title" class="form-control"
                     value="<?= esc($str('auto_open_title', 'Остались вопросы?')) ?>">
            </div>
            <div class="col-md-12">
              <label class="form-label">Подзаголовок при авто-открытии</label>
              <input type="text" name="auto_open_subtitle" class="form-control"
                     value="<?= esc($str('auto_open_subtitle', 'Наш специалист проконсультирует вас бесплатно')) ?>">
            </div>
          </div>
        </div>
      </div>

      <button type="submit" class="btn btn-primary">
        <i class="bi bi-check2"></i> Сохранить
      </button>
      <?php if (!empty($_GET['saved'])): ?>
      <span class="text-success ms-2"><i class="bi bi-check-circle"></i> Сохранено</span>
      <?php endif; ?>
    </div>

    <!-- Preview column -->
    <div class="col-md-5">
      <div class="tw-card tw-sticky">
        <div class="tw-card-header"><h5 class="mb-0"><i class="bi bi-eye"></i> Предпросмотр</h5></div>
        <div class="tw-card-body tw-widget-preview">
          <div class="tw-preview-phone">
            <div class="tw-preview-popup">
              <div class="tw-preview-title" id="prev-title"><?= esc($str('title', 'Перезвоним за 30 секунд')) ?></div>
              <div class="tw-preview-sub" id="prev-sub"><?= esc($str('subtitle', 'Оставьте номер')) ?></div>
              <input class="tw-preview-input" type="tel" placeholder="+7 (___) ___-__-__" disabled>
              <button class="tw-preview-btn" id="prev-btn"
                      style="background:<?= esc($str('button_color','#25c16f')) ?>">
                <?= esc($str('submit_btn_text', 'Перезвоните мне')) ?>
              </button>
            </div>
            <div class="tw-preview-fab" id="prev-fab"
                 style="background:<?= esc($str('button_color','#25c16f')) ?>">
              <i class="bi bi-telephone-fill" style="color:#fff;font-size:20px"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</form>

<script>
// Live preview
const fields = {
  title: ['prev-title','title'], sub: ['prev-sub','subtitle'],
  btn: ['prev-btn','submit_btn_text']
};
document.querySelectorAll('[name="title"],[name="subtitle"],[name="submit_btn_text"]').forEach(el => {
  el.addEventListener('input', () => {
    const m = {title:'prev-title', subtitle:'prev-sub', submit_btn_text:'prev-btn'};
    const t = document.getElementById(m[el.name]);
    if (t) t.textContent = el.value;
  });
});
document.querySelector('[name="button_color"]')?.addEventListener('input', e => {
  document.getElementById('prev-btn').style.background = e.target.value;
  document.getElementById('prev-fab').style.background = e.target.value;
});
</script>
