<?php
/** @var array<string, mixed>|null $resource */
/** @var string $tagsValue */
$isEdit = $resource !== null;
$actionUrl = $isEdit ? url('/admin/recursos/' . $resource['id']) : url('/admin/recursos');
$errors = \App\Core\Flash::errors();
?>
<div class="admin-page-head">
    <div>
        <a class="back-link" href="<?= e(url('/admin/recursos')) ?>"><?= icon('arrow-left', 'icon icon-xs') ?> Volver a recursos</a>
        <h1><?= $isEdit ? 'Editar recurso' : 'Nuevo recurso' ?></h1>
        <p>Agrega un recurso externo al directorio público.</p>
    </div>
</div>

<div class="admin-card admin-form-card">
    <?php if ($errors !== []): ?>
        <div class="form-error-summary" role="alert">
            <?= icon('alert-triangle', 'icon') ?>
            <div><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= e($actionUrl) ?>">
        <?= csrf_field() ?>
        <div class="field">
            <label for="title">Título</label>
            <input id="title" name="title" type="text" required maxlength="160" value="<?= e(old('title', $resource['title'] ?? '')) ?>">
        </div>
        <div class="field">
            <label for="description">Descripción</label>
            <textarea id="description" name="description" rows="4" required maxlength="1200"><?= e(old('description', $resource['description'] ?? '')) ?></textarea>
        </div>
        <div class="field">
            <label for="url">URL</label>
            <input id="url" name="url" type="url" required maxlength="500" value="<?= e(old('url', $resource['url'] ?? '')) ?>" placeholder="https://">
        </div>
        <div class="field-grid field-grid-2">
            <div class="field">
                <label for="category">Tipo</label>
                <select id="category" name="category">
                    <?php foreach (['dataset' => 'Conjunto de datos', 'api' => 'API', 'repositorio' => 'Repositorio', 'vigilancia' => 'Vigilancia', 'guia' => 'Guía', 'ml' => 'Aprendizaje automático', 'institucion' => 'Institución'] as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= old('category', $resource['category'] ?? 'dataset') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="access">Acceso</label>
                <select id="access" name="access">
                    <?php foreach (['abierto' => 'Abierto', 'solicitud' => 'Por solicitud', 'pago' => 'De pago', 'mixto' => 'Mixto'] as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= old('access', $resource['access'] ?? 'abierto') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="field-grid field-grid-2">
            <div class="field">
                <label for="organization">Organización</label>
                <input id="organization" name="organization" type="text" maxlength="160" value="<?= e(old('organization', $resource['organization'] ?? '')) ?>">
            </div>
            <div class="field">
                <label for="country">País</label>
                <input id="country" name="country" type="text" maxlength="80" value="<?= e(old('country', $resource['country'] ?? '')) ?>">
            </div>
        </div>
        <div class="field-grid field-grid-3">
            <div class="field">
                <label for="license">Licencia</label>
                <input id="license" name="license" type="text" maxlength="160" value="<?= e(old('license', $resource['license'] ?? '')) ?>">
            </div>
            <div class="field">
                <label for="language">Idioma</label>
                <input id="language" name="language" type="text" maxlength="10" value="<?= e(old('language', $resource['language'] ?? 'es')) ?>">
            </div>
            <div class="field">
                <label for="sort_order">Orden</label>
                <input id="sort_order" name="sort_order" type="number" min="0" max="999" value="<?= e(old('sort_order', $resource['sort_order'] ?? 100)) ?>">
            </div>
        </div>
        <div class="field">
            <label for="tags">Etiquetas</label>
            <input id="tags" name="tags" type="text" value="<?= e(old('tags', $tagsValue)) ?>" placeholder="datos abiertos, epidemiología, México">
            <span class="field-help">Separa con comas.</span>
        </div>
        <label class="check-line">
            <input type="checkbox" name="featured" value="1" <?= old('featured', $resource['featured'] ?? false) ? 'checked' : '' ?>>
            <span>Destacar en la página de inicio.</span>
        </label>
        <div class="form-actions">
            <a class="btn btn-ghost" href="<?= e(url('/admin/recursos')) ?>">Cancelar</a>
            <button class="btn btn-primary" type="submit"><?= $isEdit ? 'Guardar cambios' : 'Crear recurso' ?></button>
        </div>
    </form>
</div>
