<?php function esc($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); } ?>
<div class="tw-page-header d-flex justify-content-between align-items-center">
  <h1 class="tw-page-title"><i class="bi bi-globe2"></i> Сайты</h1>
  <a href="<?= url('sites/create') ?>" class="btn btn-primary"><i class="bi bi-plus"></i> Добавить сайт</a>
</div>

<div class="tw-card">
  <div class="table-responsive">
    <table class="table tw-table mb-0">
      <thead>
        <tr>
          <th>Название</th><th>Домен</th><th>API ключ</th>
          <th>Заявок</th><th>Статус</th><th>Создан</th><th></th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($sites as $s): ?>
      <tr id="site-row-<?= $s['id'] ?>">
        <td><a href="<?= url('sites/' . $s['id']) ?>"><?= esc($s['name']) ?></a></td>
        <td class="text-muted"><?= esc($s['domain']) ?></td>
        <td><code class="small"><?= esc($s['api_key']) ?></code></td>
        <td><?= (int)$s['lead_count'] ?></td>
        <td>
          <span class="badge <?= $s['is_active'] ? 'bg-success' : 'bg-secondary' ?>" id="site-status-<?= $s['id'] ?>">
            <?= $s['is_active'] ? 'Активен' : 'Отключён' ?>
          </span>
        </td>
        <td class="text-muted small"><?= date('d.m.Y', strtotime($s['created_at'])) ?></td>
        <td class="text-end">
          <a href="<?= url('sites/' . $s['id']) ?>" class="btn btn-sm btn-outline-secondary">Открыть</a>
          <button class="btn btn-sm btn-outline-warning"
                  onclick="toggleSite(<?= $s['id'] ?>, '<?= $s['api_key'] ?>')">
            <?= $s['is_active'] ? 'Откл.' : 'Вкл.' ?>
          </button>
          <button class="btn btn-sm btn-outline-danger"
                  onclick="deleteSite(<?= $s['id'] ?>)">Удалить</button>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($sites)): ?>
      <tr><td colspan="7" class="text-center text-muted py-5">
        Нет сайтов. <a href="<?= url('sites/create') ?>">Добавить первый</a>
      </td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
const CSRF = '<?= $__csrfToken ?? '' ?>';
async function toggleSite(id) {
  const r = await fetch(APP_BASE + '/sites/' + id + '/toggle', {method:'POST', body: new URLSearchParams({_csrf: CSRF})});
  const d = await r.json();
  if (d.ok) location.reload();
}
async function deleteSite(id) {
  if (!confirm('Удалить сайт и все данные?')) return;
  const r = await fetch(APP_BASE + '/sites/' + id + '/delete', {method:'POST', body: new URLSearchParams({_csrf: CSRF})});
  const d = await r.json();
  if (d.ok) document.getElementById('site-row-' + id)?.remove();
}
</script>
