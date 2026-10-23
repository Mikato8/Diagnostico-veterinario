<?php
/** @var array<int, array<string, mixed>> $resources */
/** @var array<string, mixed> $filters */
/** @var array<int, string> $categories */
/** @var array<string, array{title: string, description: string, icon: string}> $goals */
/** @var string $goal */
/** @var array<int, array<string, mixed>> $recommendations */
$hasFilters = $filters['q'] !== '' || $filters['category'] !== '' || $filters['access'] !== '';
$resourceIcon = static fn (string $category): string => match ($category) {
    'dataset' => 'database',
    'api' => 'activity',
    'vigilancia' => 'globe',
    'institucion' => 'shield',
    'guia' => 'book-open',
    default => 'folder',
};
?>
<section class="page-hero page-hero-compact">
    <div class="container">
        <nav class="breadcrumb" aria-label="Migas de pan">
            <a href="<?= e(url('/')) ?>">Inicio</a> <?= icon('chevron-right', 'icon icon-xs') ?> <span>Recursos</span>
        </nav>
        <div class="page-hero-grid">
            <div>
                <span class="eyebrow">Datos e investigación</span>
                <h1>Recursos explicados para veterinarios</h1>
                <p>Datasets, repositorios, sistemas de vigilancia y manuales. No necesitas saber programar ni entender GitHub: cada ficha te dice para qué sirve, cómo empezar y qué necesitas.</p>
            </div>
            <div class="page-hero-aside">
                <span class="big-number"><?= count($resources) ?></span>
                <span>recursos con guía en español</span>
                <a class="btn btn-outline btn-sm" href="<?= e(url('/recursos/kit')) ?>"><?= icon('download', 'icon icon-xs') ?> Kit descargable</a>
            </div>
        </div>
    </div>
</section>

<section class="section section-tight">
    <div class="container">
        <div class="resource-intro">
            <div>
                <span class="eyebrow">Empieza por tu objetivo</span>
                <h2>¿Qué necesitas hacer hoy?</h2>
                <p>Elige una opción y te sugerimos por dónde empezar. No tienes que conocer los nombres técnicos.</p>
            </div>
            <div class="resource-intro-links">
                <a class="text-link" href="<?= e(url('/recursos/glosario')) ?>"><?= icon('book-open', 'icon icon-sm') ?> Glosario para no especialistas</a>
                <a class="text-link" href="<?= e(url('/recursos/kit')) ?>"><?= icon('download', 'icon icon-sm') ?> Plantillas listas para usar</a>
            </div>
        </div>

        <div class="goal-grid">
            <?php foreach ($goals as $key => $item): ?>
                <a class="goal-card <?= $goal === $key ? 'is-active' : '' ?>" href="<?= e(url('/recursos?goal=' . rawurlencode($key))) ?>">
                    <span class="goal-icon"><?= icon($item['icon'], 'icon') ?></span>
                    <strong><?= e($item['title']) ?></strong>
                    <span><?= e($item['description']) ?></span>
                    <span class="goal-action"><?= $goal === $key ? 'Seleccionado' : 'Ver recomendaciones' ?> <?= icon('arrow-right', 'icon icon-xs') ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if ($recommendations !== []): ?>
            <section class="recommendation-panel">
                <div class="recommendation-head">
                    <div>
                        <span class="eyebrow">Recomendación para ti</span>
                        <h2><?= e($goals[$goal]['title'] ?? 'Recursos sugeridos') ?></h2>
                        <p>Estos recursos encajan con el objetivo que elegiste. Abre la ficha interna para ver pasos, requisitos y advertencias.</p>
                    </div>
                    <a class="text-link" href="<?= e(url('/recursos')) ?>">Quitar selección</a>
                </div>
                <div class="resource-grid">
                    <?php foreach ($recommendations as $resource): ?>
                        <a class="resource-card resource-card-recommended" href="<?= e(url('/recursos/' . $resource['slug'])) ?>">
                            <div class="resource-card-top">
                                <span class="resource-icon"><?= icon($resourceIcon($resource['category']), 'icon') ?></span>
                                <span class="badge badge-success">Recomendado</span>
                            </div>
                            <span class="resource-category"><?= e(category_label($resource['category'])) ?></span>
                            <h3><?= e($resource['title']) ?></h3>
                            <p><?= e(excerpt($resource['what_for'] ?: $resource['description'], 120)) ?></p>
                            <span class="resource-foot">Leer guía <?= icon('arrow-right', 'icon icon-xs') ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <div class="kit-teaser">
            <div class="kit-teaser-icon"><?= icon('folder', 'icon') ?></div>
            <div>
                <span class="eyebrow">Herramientas internas</span>
                <h2>Plantillas que puedes usar sin registrarte</h2>
                <p>Descarga la plantilla de caso clínico, el checklist de consentimiento, una carta para solicitar acceso a datos y una guía rápida sobre repositorios.</p>
            </div>
            <a class="btn btn-primary" href="<?= e(url('/recursos/kit')) ?>">Abrir kit <?= icon('arrow-right', 'icon icon-sm') ?></a>
        </div>

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
            <p>El botón de cada ficha abre el recurso externo en una pestaña nueva. Antes de reutilizar datos, revisa la licencia y las condiciones de acceso. Este directorio es curado y no sustituye la revisión ética de tu proyecto.</p>
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
                    <a class="resource-card resource-card-lg" href="<?= e(url('/recursos/' . $resource['slug'])) ?>">
                        <div class="resource-card-top">
                            <span class="resource-icon"><?= icon($resourceIcon($resource['category']), 'icon') ?></span>
                            <span class="badge badge-<?= $resource['access'] === 'abierto' ? 'success' : 'warning' ?>"><?= e(access_label($resource['access'])) ?></span>
                        </div>
                        <span class="resource-category"><?= e(category_label($resource['category'])) ?></span>
                        <h2><?= e($resource['title']) ?></h2>
                        <p><?= e(excerpt($resource['what_for'] ?: $resource['description'], 150)) ?></p>
                        <div class="resource-tags">
                            <?php foreach (array_slice($resource['tags'], 0, 4) as $tag): ?><span class="tag tag-sm"><?= e($tag) ?></span><?php endforeach; ?>
                        </div>
                        <div class="resource-foot">
                            <span><?= e($resource['organization'] ?? '') ?><?php if (!empty($resource['country'])): ?> · <?= e($resource['country']) ?><?php endif; ?></span>
                            <span class="resource-guide-link">Leer guía <?= icon('arrow-right', 'icon icon-sm') ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
