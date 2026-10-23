<?php
/** @var array<int, array<string, mixed>> $resources */
/** @var array<string, mixed> $filters */
/** @var array<int, string> $categories */
$hasFilters = $filters['q'] !== '' || $filters['category'] !== '' || $filters['access'] !== '';
?>
<section class="page-hero page-hero-compact">
    <div class="container">
        <nav class="breadcrumb" aria-label="Migas de pan">
            <a href="<?= e(url('/')) ?>">Inicio</a> <?= icon('chevron-right', 'icon icon-xs') ?> <span>Recursos</span>
        </nav>
        <div class="page-hero-grid">
            <div>
                <span class="eyebrow">Datos e investigación</span>
                <h1>Recursos para investigar y decidir</h1>
                <p>Datasets, APIs, repositorios y sistemas de vigilancia veterinaria curados para México y el mundo. Cada ficha indica acceso, organización y licencia.</p>
            </div>
            <div class="page-hero-aside">
                <span class="big-number"><?= count($resources) ?></span>
                <span>recursos en el directorio</span>
            </div>
        </div>
    </div>
</section>

<section class="section section-tight">
    <div class="container">
        <form class="filter-bar" method="get" action="<?= e(url('/recursos')) ?>">
            <div class="filter-search">
                <?= icon('search', 'icon icon-sm') ?>
                <input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Buscar recurso, institución…" aria-label="Buscar recurso">
            </div>
            <label class="filter-select">
                <span>Tipo</span>
                <select name="category">
                    <option value="">Todos</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= e($category) ?>" <?= $filters['category'] === $category ? 'selected' : '' ?>><?= e(category_label($category)) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="filter-select">
                <span>Acceso</span>
                <select name="access">
                    <option value="">Todos</option>
                    <?php foreach (['abierto' => 'Abierto', 'solicitud' => 'Por solicitud', 'pago' => 'De pago', 'mixto' => 'Mixto'] as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= $filters['access'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button class="btn btn-dark btn-sm" type="submit">Filtrar</button>
            <?php if ($hasFilters): ?><a class="filter-clear" href="<?= e(url('/recursos')) ?>">Limpiar</a><?php endif; ?>
        </form>

        <div class="resource-note">
            <?= icon('info', 'icon icon-sm') ?>
            <p>Los recursos externos se abren en una pestaña nueva. Verifica las condiciones de acceso y licencia antes de reutilizar datos. Este directorio es curado y no sustituye la revisión ética de tu proyecto.</p>
        </div>

        <?php if ($resources === []): ?>
            <div class="empty-state">
                <span class="empty-icon"><?= icon('database', 'icon') ?></span>
                <h2>No encontramos recursos con esos filtros</h2>
                <p>Prueba con otro término o limpia los filtros para ver el directorio completo.</p>
                <a class="btn btn-primary" href="<?= e(url('/recursos')) ?>">Ver todo el directorio</a>
            </div>
        <?php else: ?>
            <div class="resource-grid">
                <?php foreach ($resources as $resource): ?>
                    <a class="resource-card resource-card-lg" href="<?= e($resource['url']) ?>" target="_blank" rel="noopener noreferrer">
                        <div class="resource-card-top">
                            <span class="resource-icon"><?= icon($resource['category'] === 'dataset' ? 'database' : ($resource['category'] === 'api' ? 'activity' : ($resource['category'] === 'vigilancia' ? 'globe' : ($resource['category'] === 'institucion' ? 'shield' : 'folder'))), 'icon') ?></span>
                            <span class="badge badge-<?= $resource['access'] === 'abierto' ? 'success' : 'warning' ?>"><?= e(access_label($resource['access'])) ?></span>
                        </div>
                        <span class="resource-category"><?= e(category_label($resource['category'])) ?></span>
                        <h2><?= e($resource['title']) ?></h2>
                        <p><?= e(excerpt($resource['description'], 160)) ?></p>
                        <div class="resource-tags">
                            <?php foreach (array_slice($resource['tags'], 0, 4) as $tag): ?><span class="tag tag-sm"><?= e($tag) ?></span><?php endforeach; ?>
                        </div>
                        <div class="resource-foot">
                            <span><?= e($resource['organization'] ?? '') ?><?php if (!empty($resource['country'])): ?> · <?= e($resource['country']) ?><?php endif; ?></span>
                            <?= icon('arrow-up-right', 'icon icon-sm') ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
