<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Trust Widget</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="<?= url('assets/css/admin.css') ?>">
</head>
<body>

<div class="tw-wrapper">
  <!-- Sidebar -->
  <nav class="tw-sidebar">
    <div class="tw-logo">
      <i class="bi bi-broadcast-pin"></i>
      <span>Trust Widget</span>
    </div>
    <ul class="tw-nav">
      <li class="<?= $_view === 'admin/dashboard' ? 'active' : '' ?>">
        <a href="<?= url() ?>"><i class="bi bi-speedometer2"></i> Дашборд</a>
      </li>
      <li class="<?= str_starts_with($_view ?? '', 'admin/sites') ? 'active' : '' ?>">
        <a href="<?= url('sites') ?>"><i class="bi bi-globe2"></i> Сайты</a>
      </li>
      <li class="<?= str_starts_with($_view ?? '', 'admin/leads') ? 'active' : '' ?>">
        <a href="<?= url('leads') ?>"><i class="bi bi-people"></i> Заявки</a>
      </li>
      <li class="<?= str_starts_with($_view ?? '', 'admin/stats') ? 'active' : '' ?>">
        <a href="<?= url('stats') ?>"><i class="bi bi-bar-chart"></i> Статистика</a>
      </li>
      <?php if (($_userRole ?? '') === 'admin'): ?>
      <li class="<?= str_starts_with($_view ?? '', 'admin/attempts') ? 'active' : '' ?>">
        <a href="<?= url('attempts') ?>"><i class="bi bi-shield-exclamation text-danger"></i> Попытки</a>
      </li>
      <?php endif; ?>
    </ul>
    <div class="tw-sidebar-footer">
      <span class="text-muted small"><i class="bi bi-person-circle"></i> <?= htmlspecialchars($_userName ?? '') ?></span>
      <a href="<?= url('auth/logout') ?>" class="btn btn-sm btn-outline-danger mt-2 w-100">Выйти</a>
    </div>
  </nav>

  <!-- Main content -->
  <main class="tw-main">
    <?= $content ?>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>const APP_BASE = '<?= APP_BASE ?>';</script>
<script src="<?= url('assets/js/admin.js') ?>"></script>
</body>
</html>
