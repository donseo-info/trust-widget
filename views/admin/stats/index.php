<?php function esc($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); } ?>
<div class="tw-page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
  <h1 class="tw-page-title"><i class="bi bi-bar-chart"></i> Статистика</h1>
  <form method="GET" action="/stats" class="d-flex gap-2 align-items-center flex-wrap">
    <select name="site" class="form-select form-select-sm" style="width:auto">
      <option value="">Все сайты</option>
      <?php foreach ($sites as $s): ?>
      <option value="<?= $s['id'] ?>" <?= $site_filter == $s['id'] ? 'selected' : '' ?>><?= esc($s['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <input type="date" name="date_from" class="form-control form-control-sm" style="width:auto" value="<?= esc($date_from) ?>">
    <span class="text-muted small">—</span>
    <input type="date" name="date_to" class="form-control form-control-sm" style="width:auto" value="<?= esc($date_to) ?>">
    <button type="submit" class="btn btn-sm btn-primary">Применить</button>
  </form>
</div>

<!-- Summary cards -->
<?php
  $cbTotal  = array_sum($callback_totals);
  $epTotal  = array_sum($popup_totals);
?>
<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="tw-card tw-stat-card">
      <div class="tw-stat-icon bg-warning-soft"><i class="bi bi-telephone"></i></div>
      <div>
        <div class="tw-stat-value"><?= $cbTotal ?></div>
        <div class="tw-stat-label">Звонков за период</div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="tw-card tw-stat-card">
      <div class="tw-stat-icon bg-info-soft"><i class="bi bi-window-stack"></i></div>
      <div>
        <div class="tw-stat-value"><?= $epTotal ?></div>
        <div class="tw-stat-label">Попапов за период</div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="tw-card tw-stat-card">
      <div class="tw-stat-icon bg-success-soft"><i class="bi bi-people"></i></div>
      <div>
        <div class="tw-stat-value"><?= $cbTotal + $epTotal ?></div>
        <div class="tw-stat-label">Всего заявок за период</div>
      </div>
    </div>
  </div>
</div>

<div class="row g-4">

  <!-- Callback table -->
  <div class="col-lg-6">
    <div class="tw-card h-100">
      <div class="tw-card-header">
        <h5 class="mb-0"><i class="bi bi-telephone text-warning"></i> Обратный звонок
          <span class="text-muted fw-normal small ms-2"><?= esc($date_from) ?> — <?= esc($date_to) ?></span>
        </h5>
      </div>
      <?php if ($callback_daily): ?>
      <div class="table-responsive">
        <table class="table tw-table mb-0">
          <thead>
            <tr>
              <th>Дата</th>
              <th>Авто</th>
              <th>Ручной</th>
              <th>Итого</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($callback_daily as $day => $t): ?>
          <?php
            $auto   = (int)($t['auto']   ?? 0);
            $manual = (int)($t['manual'] ?? 0);
          ?>
          <tr>
            <td class="text-muted small"><?= date('d.m.Y', strtotime($day)) ?></td>
            <td><?= $auto   ?: '—' ?></td>
            <td><?= $manual ?: '—' ?></td>
            <td><strong><?= $auto + $manual ?></strong></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
          <tfoot class="table-light">
            <tr>
              <td class="text-muted small fw-bold">Итого</td>
              <td><strong><?= $callback_totals['auto']   ?? 0 ?></strong></td>
              <td><strong><?= $callback_totals['manual'] ?? 0 ?></strong></td>
              <td><strong><?= $cbTotal ?></strong></td>
            </tr>
          </tfoot>
        </table>
      </div>
      <?php else: ?>
      <div class="text-center text-muted py-5 small">Нет данных за период</div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Popup table -->
  <div class="col-lg-6">
    <div class="tw-card h-100">
      <div class="tw-card-header">
        <h5 class="mb-0"><i class="bi bi-window-stack text-info"></i> Exit Popup
          <span class="text-muted fw-normal small ms-2"><?= esc($date_from) ?> — <?= esc($date_to) ?></span>
        </h5>
      </div>
      <?php if ($popup_daily): ?>
      <?php $variants = array_keys($popup_totals); ?>
      <div class="table-responsive">
        <table class="table tw-table mb-0">
          <thead>
            <tr>
              <th>Дата</th>
              <?php foreach ($variants as $v): ?>
              <th>Вар. <?= esc($v) ?></th>
              <?php endforeach; ?>
              <th>Итого</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($popup_daily as $day => $vv): ?>
          <?php $tot = array_sum($vv); ?>
          <tr>
            <td class="text-muted small"><?= date('d.m.Y', strtotime($day)) ?></td>
            <?php foreach ($variants as $v): ?>
            <td><?= (int)($vv[$v] ?? 0) ?: '—' ?></td>
            <?php endforeach; ?>
            <td><strong><?= $tot ?></strong></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
          <tfoot class="table-light">
            <tr>
              <td class="text-muted small fw-bold">Итого</td>
              <?php foreach ($variants as $v): ?>
              <td><strong><?= $popup_totals[$v] ?? 0 ?></strong></td>
              <?php endforeach; ?>
              <td><strong><?= $epTotal ?></strong></td>
            </tr>
          </tfoot>
        </table>
      </div>
      <?php else: ?>
      <div class="text-center text-muted py-5 small">Нет данных за период</div>
      <?php endif; ?>
    </div>
  </div>

</div>
