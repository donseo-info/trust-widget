<?php function esc($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); } ?>
<div class="tw-page-header">
  <h1 class="tw-page-title"><i class="bi bi-shield-exclamation text-danger"></i> Несанкционированные домены</h1>
</div>

<!-- Top domains -->
<?php if ($topDomains): ?>
<div class="tw-card mb-4">
  <div class="tw-card-header">
    <h5 class="mb-0">Топ доменов по попыткам</h5>
  </div>
  <div class="table-responsive">
    <table class="table tw-table mb-0">
      <thead><tr><th>Домен</th><th>Попыток</th><th>Последняя</th></tr></thead>
      <tbody>
      <?php foreach ($topDomains as $d): ?>
      <tr>
        <td><code><?= esc($d['request_domain']) ?></code></td>
        <td><span class="badge bg-danger"><?= (int)$d['cnt'] ?></span></td>
        <td class="text-muted small"><?= date('d.m.Y H:i', strtotime($d['last_seen'])) ?></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- Log -->
<div class="tw-card">
  <div class="tw-card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Лог попыток <span class="text-muted fw-normal small ms-2">всего: <?= $total ?></span></h5>
  </div>
  <div class="table-responsive">
    <table class="table tw-table mb-0">
      <thead>
        <tr>
          <th>Домен запроса</th>
          <th>Разрешённый домен</th>
          <th>Сайт</th>
          <th>IP</th>
          <th>Дата</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
      <tr>
        <td><code class="text-danger"><?= esc($r['request_domain']) ?></code></td>
        <td><code class="text-muted"><?= esc($r['allowed_domain']) ?></code></td>
        <td class="text-muted small"><?= esc($r['site_name'] ?? $r['api_key']) ?></td>
        <td class="text-muted small"><?= esc($r['ip']) ?></td>
        <td class="text-muted small"><?= date('d.m.Y H:i', strtotime($r['created_at'])) ?></td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($rows)): ?>
      <tr><td colspan="5" class="text-center text-muted py-5">Несанкционированных попыток нет</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($pages > 1): ?>
  <div class="d-flex justify-content-center py-3">
    <nav><ul class="pagination pagination-sm mb-0">
      <?php for ($p = 1; $p <= $pages; $p++): ?>
      <li class="page-item <?= $p === $page ? 'active' : '' ?>">
        <a class="page-link" href="<?= url('attempts?page=' . $p) ?>"><?= $p ?></a>
      </li>
      <?php endfor; ?>
    </ul></nav>
  </div>
  <?php endif; ?>
</div>
