<?php

declare(strict_types=1);

namespace App\Repositories;

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
            $where[] = '(title ILIKE $1 or description ILIKE $2 or organization ILIKE $3)';
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
        $rows = Database::select(
            'select * from resources where featured = true order by sort_order asc, title asc limit $1',
            [$limit]
        );
        return array_map([$this, 'hydrate'], $rows);
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        $row = Database::first('select * from resources where id = $1', [$id]);
        return $row !== null ? $this->hydrate($row) : null;
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): int
    {
        $row = Database::insert(
            'insert into resources (title, description, url, category, organization, country, access, license, language, tags, featured, sort_order)
             values ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12)
             returning id',
            [
                $data['title'], $data['description'], $data['url'], $data['category'],
                $data['organization'], $data['country'], $data['access'], $data['license'],
                $data['language'], $data['tags'], $data['featured'], $data['sort_order'],
            ]
        );
        return (int) $row['id'];
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): void
    {
        Database::execute(
            'update resources set title = $1, description = $2, url = $3, category = $4,
                organization = $5, country = $6, access = $7, license = $8, language = $9,
                tags = $10, featured = $11, sort_order = $12
             where id = $13',
            [
                $data['title'], $data['description'], $data['url'], $data['category'],
                $data['organization'], $data['country'], $data['access'], $data['license'],
                $data['language'], $data['tags'], $data['featured'], $data['sort_order'], $id,
            ]
        );
    }

    public function delete(int $id): void
    {
        Database::execute('delete from resources where id = $1', [$id]);
    }

    /** @return array<int, string> */
    public function categories(): array
    {
        $rows = Database::select('select distinct category from resources order by category');
        return array_map(static fn (array $row): string => (string) $row['category'], $rows);
    }

    /** @param array<string, mixed> $row
     *  @return array<string, mixed>
     */
    private function hydrate(array $row): array
    {
        $row['tags'] = pg_array($row['tags'] ?? []);
        $row['id'] = (int) $row['id'];
        $row['featured'] = ($row['featured'] === true || $row['featured'] === 't');
        return $row;
    }

    public function count(): int
    {
        return (int) Database::value('select count(*) from resources');
    }
}
