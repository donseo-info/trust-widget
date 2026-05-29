<?php function esc($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
$isEdit = $site !== null;
$action = $isEdit ? '/sites/' . $site['id'] : '/sites';
?>
<div class="tw-page-header">
  <h1 class="tw-page-title">
    <i class="bi bi-globe2"></i>
    <?= $isEdit ? 'Редактировать сайт' : 'Добавить сайт' ?>
  </h1>
</div>

<div class="tw-card" style="max-width:560px">
  <form method="POST" action="<?= $action ?>">
    <input type="hidden" name="_csrf" value="<?= esc($__csrfToken ?? '') ?>">

    <div class="mb-3">
      <label class="form-label">Название</label>
      <input type="text" name="name" class="form-control"
             value="<?= esc($site['name'] ?? '') ?>"
             placeholder="Мой интернет-магазин" required>
    </div>

    <div class="mb-4">
      <label class="form-label">Домен</label>
      <input type="text" name="domain" class="form-control"
             value="<?= esc($site['domain'] ?? '') ?>"
             placeholder="example.com" required>
      <div class="form-text">Без www. и протокола</div>
    </div>

    <div class="d-flex gap-2">
      <button type="submit" class="btn btn-primary">
        <?= $isEdit ? 'Сохранить' : 'Создать' ?>
      </button>
      <a href="<?= $isEdit ? '/sites/' . $site['id'] : '/sites' ?>" class="btn btn-outline-secondary">Отмена</a>
    </div>
  </form>
</div>
