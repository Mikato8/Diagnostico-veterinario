<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Cache;
use App\Core\Database;

final class ResourceRepository
{
    /**
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function all(array $filters = []): array
    {
        $params = [];
        $where = [];
        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $params[] = '%' . $q . '%';
            $params[] = '%' . $q . '%';
            $params[] = '%' . $q . '%';
            $params[] = '%' . $q . '%';
            $where[] = '(title ILIKE $1 or description ILIKE $2 or organization ILIKE $3 or coalesce(what_for, \'\') ILIKE $4)';
        }
        $category = (string) ($filters['category'] ?? '');
        if ($category !== '') {
            $params[] = $category;
            $where[] = 'category = $' . count($params);
        }
        $access = (string) ($filters['access'] ?? '');
        if ($access !== '') {
            $params[] = $access;
            $where[] = 'access = $' . count($params);
        }
        $whereSql = $where === [] ? '' : 'where ' . implode(' and ', $where);

        $rows = Database::select(
            "select * from resources {$whereSql}
             order by featured desc, sort_order asc, title asc",
            $params
        );
        return array_map([$this, 'hydrate'], $rows);
    }

    /** @return array<int, array<string, mixed>> */
    public function featured(int $limit = 4): array
    {
        $rows = Cache::remember('resources.featured.' . $limit, 300, static fn (): array => Database::select(
            'select * from resources where featured = true order by sort_order asc, title asc limit $1',
            [$limit]
        ));
        return array_map([$this, 'hydrate'], $rows);
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        $row = Database::first('select * from resources where id = $1', [$id]);
        return $row !== null ? $this->hydrate($row) : null;
    }

    /** @return array<string, mixed>|null */
    public function findBySlug(string $slug): ?array
    {
        $row = Database::first('select * from resources where slug = $1', [$slug]);
        return $row !== null ? $this->hydrate($row) : null;
    }

    /**
     * Recursos sugeridos según lo que el veterinario quiere hacer.
     * @return array<int, array<string, mixed>>
     */
    public function forGoal(string $goal, int $limit = 3): array
    {
        $map = [
            'datos' => ['dataset', 'repositorio'],
            'investigar' => ['dataset', 'repositorio', 'guia'],
            'vigilancia' => ['vigilancia', 'institucion'],
            'aprender' => ['guia', 'institucion'],
            'sistemas' => ['api', 'repositorio'],
        ];
        if (!isset($map[$goal])) {
            return [];
        }

        return Cache::remember('resources.goal.' . $goal . '.' . $limit, 300, function () use ($map, $goal, $limit): array {
            $placeholders = [];
            $params = [];
            foreach ($map[$goal] as $index => $category) {
                $params[] = $category;
                $placeholders[] = '$' . ($index + 1);
            }
            $params[] = $limit;
            $rows = Database::select(
                'select * from resources
                 where category in (' . implode(',', $placeholders) . ')
                 order by featured desc, sort_order asc, title asc
                 limit $' . count($params),
                $params
            );
            return array_map([$this, 'hydrate'], $rows);
        });
    }

    /** @param array<string, mixed> $resource
     *  @return array<int, array<string, mixed>>
     */
    public function related(array $resource, int $limit = 3): array
    {
        $rows = Database::select(
            'select * from resources
             where id <> $1 and (category = $2 or tags && $3::text[])
             order by featured desc, sort_order asc, title asc
             limit $4',
            [
                (int) $resource['id'],
                (string) $resource['category'],
                '{' . implode(',', array_map(static fn (string $tag): string => '"' . str_replace('"', '\\"', $tag) . '"', $resource['tags'] ?? [])) . '}',
                $limit,
            ]
        );
        return array_map([$this, 'hydrate'], $rows);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): int
    {
        $row = Database::insert(
            'insert into resources (
                title, description, url, category, organization, country, access, license,
                language, tags, featured, sort_order, slug, what_for, how_to, requirements,
                guide, action_label, reviewed_at
             ) values (
                $1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14,
                $15::jsonb, $16, $17, $18, $19
             ) returning id',
            $this->params($data)
        );
        return (int) $row['id'];
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): void
    {
        $params = $this->params($data);
        $params[] = $id;
        Database::execute(
            'update resources set
                title = $1, description = $2, url = $3, category = $4, organization = $5,
                country = $6, access = $7, license = $8, language = $9, tags = $10,
                featured = $11, sort_order = $12, slug = $13, what_for = $14,
                how_to = $15::jsonb, requirements = $16, guide = $17, action_label = $18,
                reviewed_at = $19
             where id = $20',
            $params
        );
    }

    public function delete(int $id): void
    {
        Database::execute('delete from resources where id = $1', [$id]);
    }

    /** @return array<int, string> */
    public function categories(): array
    {
        return Cache::remember('resources.categories', 3600, static function (): array {
            $rows = Database::select('select distinct category from resources order by category');
            return array_map(static fn (array $row): string => (string) $row['category'], $rows);
        });
    }

    public function count(): int
    {
        return (int) Database::value('select count(*) from resources');
    }

    /** @param array<string, mixed> $data
     *  @return array<int, mixed>
     */
    private function params(array $data): array
    {
        $howTo = $data['how_to'] ?? [];
        if (is_array($howTo)) {
            $howTo = json_encode(array_values(array_filter(array_map('trim', $howTo))), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return [
            $data['title'] ?? '',
            $data['description'] ?? '',
            $data['url'] ?? '',
            $data['category'] ?? 'dataset',
            $data['organization'] ?? '',
            $data['country'] ?? '',
            $data['access'] ?? 'abierto',
            $data['license'] ?? '',
            $data['language'] ?? 'es',
            $data['tags'] ?? '{}',
            $data['featured'] ?? false,
            $data['sort_order'] ?? 100,
            $data['slug'] ?? null,
            $data['what_for'] ?? null,
            $howTo,
            $data['requirements'] ?? null,
            $data['guide'] ?? null,
            $data['action_label'] ?? null,
            $data['reviewed_at'] ?? null,
        ];
    }

    /** @param array<string, mixed> $row
     *  @return array<string, mixed>
     */
    private function hydrate(array $row): array
    {
        $row['tags'] = pg_array($row['tags'] ?? []);
        $row['id'] = (int) $row['id'];
        $row['featured'] = ($row['featured'] === true || $row['featured'] === 't');

        $howTo = $row['how_to'] ?? [];
        if (is_string($howTo)) {
            $decoded = json_decode($howTo, true);
            $howTo = is_array($decoded) ? $decoded : [];
        }
        $row['how_to'] = is_array($howTo) ? array_values($howTo) : [];
        $row['what_for'] = (string) ($row['what_for'] ?? '');
        $row['requirements'] = (string) ($row['requirements'] ?? '');
        $row['guide'] = (string) ($row['guide'] ?? '');
        $row['action_label'] = (string) ($row['action_label'] ?? 'Abrir recurso');
        $row['slug'] = (string) ($row['slug'] ?? '');
        return $row;
    }
}
