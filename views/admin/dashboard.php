<?php
function esc($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function fmtPhone(string $p): string {
    $d = preg_replace('/\D/', '', $p);
    if (strlen($d) === 11 && $d[0] === '7')
        return '+7 (' . substr($d,1,3) . ') ' . substr($d,4,3) . '-' . substr($d,7,2) . '-' . substr($d,9,2);
    return $p ?: '—';
}
?>
<div class="tw-page-header">
  <h1 class="tw-page-title"><i class="bi bi-speedometer2"></i> Дашборд</h1>
</div>

<!-- Stats cards -->
<div class="row g-3 mb-4">
  <div class="col-md-3">
    <div class="tw-card tw-stat-card">
      <div class="tw-stat-icon bg-primary-soft"><i class="bi bi-globe2"></i></div>
      <div>
        <div class="tw-stat-value"><?= $total_sites ?></div>
        <div class="tw-stat-label">Всего сайтов</div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="tw-card tw-stat-card">
      <div class="tw-stat-icon bg-success-soft"><i class="bi bi-check-circle"></i></div>
      <div>
        <div class="tw-stat-value"><?= $active_sites ?></div>
        <div class="tw-stat-label">Активных сайтов</div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="tw-card tw-stat-card">
      <div class="tw-stat-icon bg-warning-soft"><i class="bi bi-calendar-day"></i></div>
      <div>
        <div class="tw-stat-value"><?= $leads_today ?></div>
        <div class="tw-stat-label">Заявок сегодня</div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="tw-card tw-stat-card">
      <div class="tw-stat-icon bg-info-soft"><i class="bi bi-calendar2"></i></div>
      <div>
        <div class="tw-stat-value"><?= $leads_yesterday ?></div>
        <div class="tw-stat-label">Заявок вчера</div>
      </div>
    </div>
  </div>
</div>


<!-- Sites table -->
<div class="tw-card mb-4">
  <div class="tw-card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0"><i class="bi bi-globe2"></i> Сайты</h5>
    <a href="/sites/create" class="btn btn-sm btn-primary"><i class="bi bi-plus"></i> Добавить сайт</a>
  </div>
  <div class="table-responsive">
    <table class="table tw-table mb-0">
      <thead><tr><th>Название</th><th>Домен</th><th>API ключ</th><th>Заявок</th><th>Статус</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($sites as $s): ?>
      <tr>
        <td><a href="/sites/<?= $s['id'] ?>"><?= esc($s['name']) ?></a></td>
        <td><span class="text-muted"><?= esc($s['domain']) ?></span></td>
        <td><code class="small"><?= esc($s['api_key']) ?></code></td>
        <td><?= (int)$s['lead_count'] ?></td>
        <td>
          <?php if ($s['is_active']): ?>
            <span class="badge bg-success">Активен</span>
          <?php else: ?>
            <span class="badge bg-secondary">Отключён</span>
          <?php endif; ?>
        </td>
        <td><a href="/sites/<?= $s['id'] ?>" class="btn btn-sm btn-outline-secondary">Настройки</a></td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($sites)): ?>
      <tr><td colspan="6" class="text-center text-muted py-4">Нет сайтов. <a href="/sites/create">Добавить первый</a></td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Recent leads -->
<div class="tw-card">
  <div class="tw-card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0"><i class="bi bi-people"></i> Последние заявки</h5>
    <a href="/leads" class="btn btn-sm btn-outline-secondary">Все заявки</a>
  </div>
  <div class="table-responsive">
    <table class="table tw-table mb-0">
      <thead><tr><th>Телефон</th><th>Сайт</th><th>Виджет</th><th>Источник</th><th>Дата</th></tr></thead>
      <tbody>
      <?php foreach ($recent_leads as $l): ?>
      <tr>
        <td><strong><?= fmtPhone($l['phone']) ?></strong></td>
        <td><?= esc($l['site_name']) ?></td>
        <td><span class="badge bg-light text-dark"><?= esc($l['widget_name']) ?></span></td>
        <td><?= esc($l['utm_source'] ?: '—') ?></td>
        <td class="text-muted small"><?= date('d.m H:i', strtotime($l['created_at'])) ?></td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($recent_leads)): ?>
      <tr><td colspan="5" class="text-center text-muted py-4">Заявок пока нет</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
