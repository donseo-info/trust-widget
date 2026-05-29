<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Вход — Trust Widget</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="tw-login-body">
<div class="tw-login-wrap">
  <div class="tw-login-logo">
    <i class="bi bi-broadcast-pin"></i>
    <span>Trust Widget</span>
  </div>

  <?php if ($error): ?>
  <div class="alert alert-danger">Неверный email или пароль</div>
  <?php endif; ?>

  <form method="POST" action="/auth/login">
    <div class="mb-3">
      <label class="form-label">Email / логин</label>
      <input type="text" name="email" class="form-control" autofocus required>
    </div>
    <div class="mb-3">
      <label class="form-label">Пароль</label>
      <input type="password" name="password" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-primary w-100">Войти</button>
  </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</body>
</html>
