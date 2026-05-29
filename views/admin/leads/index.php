<?php
function esc($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?><style>
tr.tw-lead-dup { background-color: #fff8e1; }
tr.tw-lead-dup:hover { background-color: #fff3cd; }
</style><?php
function fmtPhone(string $p): string {
    $d = preg_replace('/\D/', '', $p);
    if (strlen($d) === 11 && $d[0] === '7')
        return '+7 (' . substr($d,1,3) . ') ' . substr($d,4,3) . '-' . substr($d,7,2) . '-' . substr($d,9,2);
    return $p ?: '—';
}
?>
<div class="tw-page-header d-flex justify-content-between align-items-center">
  <h1 class="tw-page-title"><i class="bi bi-people"></i> Заявки</h1>
  <div class="d-flex gap-2 align-items-center flex-wrap">
    <form method="GET" action="/leads" class="d-flex gap-2 flex-wrap">
      <select name="site" class="form-select form-select-sm" style="width:auto">
        <option value="">Все сайты</option>
        <?php foreach ($sites as $s): ?>
        <option value="<?= $s['id'] ?>" <?= $site_id == $s['id'] ? 'selected' : '' ?>><?= esc($s['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="widget" class="form-select form-select-sm" style="width:auto">
        <option value="">Все виджеты</option>
        <option value="callback"   <?= $widget_slug === 'callback'   ? 'selected' : '' ?>>Звонок</option>
        <option value="exit_popup" <?= $widget_slug === 'exit_popup' ? 'selected' : '' ?>>Попап</option>
      </select>
      <button type="submit" class="btn btn-sm btn-primary">Применить</button>
    </form>
    <span class="text-muted small">Всего: <?= $total ?></span>
  </div>
</div>

<div class="tw-card">
  <div class="table-responsive">
    <table class="table tw-table mb-0">
      <thead>
        <tr>
          <th>Телефон</th><th>Сайт</th><th>Виджет</th>
          <th>Источник</th><th>Кампания</th><th>Тип</th>
          <th>YM ClientID</th><th>IP</th><th>Дата</th><th></th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $l): ?>
      <tr id="lead-<?= $l['id'] ?>" <?= $l['is_duplicate'] ? 'class="tw-lead-dup"' : '' ?>>
        <td>
          <strong><?= fmtPhone($l['phone']) ?></strong>
          <?php if ($l['is_duplicate']): ?>
            <span title="Этот номер уже подавал заявку ранее" style="font-size:10px;color:#b45309;vertical-align:middle">↻</span>
          <?php endif; ?>
        </td>
        <td>
          <a href="/sites/<?= $l['site_id'] ?>"><?= esc($l['site_name']) ?></a>
          <?php if ($l['page_url']): ?>
          <br><a href="<?= esc($l['page_url']) ?>" target="_blank" class="text-muted small" style="max-width:200px;display:inline-block;overflow:hidden;white-space:nowrap;text-overflow:ellipsis">
            <?= esc(parse_url($l['page_url'], PHP_URL_PATH) ?: '/') ?>
          </a>
          <?php endif; ?>
        </td>
        <td><span class="badge bg-light text-dark"><?= esc($l['widget_name']) ?></span></td>
        <td><?= esc($l['utm_source'] ?: '—') ?></td>
        <td><?= esc($l['utm_campaign'] ?: '—') ?></td>
        <td>
          <?php if ($l['trigger_type'] === 'auto'): ?>
            <span class="badge bg-info text-dark">Авто</span>
          <?php else: ?>
            <span class="badge bg-light text-dark">Ручной</span>
          <?php endif; ?>
        </td>
        <td class="text-muted small"><?= esc($l['ym_client_id'] ?: '—') ?></td>
        <td class="text-muted small"><?= esc($l['ip'] ?: '—') ?></td>
        <td class="text-muted small"><?= date('d.m.Y H:i', strtotime($l['created_at'])) ?></td>
        <td>
          <button class="btn btn-sm btn-outline-danger"
                  onclick="deleteLead(<?= $l['id'] ?>)">×</button>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($rows)): ?>
      <tr><td colspan="10" class="text-center text-muted py-5">Заявок пока нет</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <?php if ($pages > 1): ?>
  <div class="d-flex justify-content-center py-3">
    <nav>
      <ul class="pagination pagination-sm mb-0">
        <?php for ($p = 1; $p <= $pages; $p++): ?>
        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
          <a class="page-link"
             href="/leads?<?= $site_id ? 'site=' . $site_id . '&' : '' ?><?= $widget_slug ? 'widget=' . urlencode($widget_slug) . '&' : '' ?>page=<?= $p ?>">
            <?= $p ?>
          </a>
        </li>
        <?php endfor; ?>
      </ul>
    </nav>
  </div>
  <?php endif; ?>
</div>

<script>
const CSRF = '<?= $__csrfToken ?? '' ?>';
async function deleteLead(id) {
  if (!confirm('Удалить заявку?')) return;
  const r = await fetch('/leads/' + id + '/delete', {
    method: 'POST',
    body: new URLSearchParams({_csrf: CSRF})
  });
  const d = await r.json();
  if (d.ok) document.getElementById('lead-' + id)?.remove();
}
</script>
