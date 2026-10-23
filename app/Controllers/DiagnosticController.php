<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\CatalogRepository;
use App\Repositories\DiseaseRepository;

final class DiagnosticController extends BaseController
{
    public function index(Request $request): Response
    {
        $catalog = new CatalogRepository();
        $speciesList = $catalog->species();
        $code = (string) $request->query('species', 'canino');
        $species = $catalog->speciesByCode($code) ?? ($speciesList[0] ?? null);
        if ($species === null) {
            $this->notFound('No hay especies configuradas.');
        }

        $diseaseRepo = new DiseaseRepository();
        $signs = $diseaseRepo->signsForSpecies((int) $species['id']);
        $grouped = [];
        foreach ($signs as $sign) {
            $sign['id'] = (int) $sign['id'];
            $grouped[(string) $sign['category']][] = $sign;
        }

        return $this->view('diagnostic.index', [
            'title' => 'Apoyo diagnóstico por similitud · Diagnóstico Veterinario',
            'metaDescription' => 'Selecciona especie y signos clínicos para obtener diagnósticos diferenciales ponderados, con signos clave y nivel de urgencia.',
            'speciesList' => $speciesList,
            'species' => $species,
            'signGroups' => $grouped,
            'bodyClass' => 'page-diagnostic',
        ]);
    }

    public function signs(Request $request): Response
    {
        $catalog = new CatalogRepository();
        $species = $catalog->speciesByCode((string) $request->query('species', 'canino'));
        if ($species === null) {
            return Response::json(['groups' => []]);
        }
        $signs = (new DiseaseRepository())->signsForSpecies((int) $species['id']);
        $groups = [];
        foreach ($signs as $sign) {
            $groups[(string) $sign['category']][] = [
                'id' => (int) $sign['id'],
                'name' => $sign['name'],
                'slug' => $sign['slug'],
            ];
        }
        return Response::json(['species' => $species['name'], 'groups' => $groups]);
    }

    public function analyze(Request $request): Response
    {
        $catalog = new CatalogRepository();
        $speciesList = $catalog->species();
        $species = $catalog->speciesByCode($request->string('species'));
        if ($species === null) {
            Flash::error('Selecciona una especie válida.');
            return $this->redirect('/apoyo-diagnostico');
        }

        $rawSigns = $request->post('signs', []);
        $signIds = [];
        if (is_array($rawSigns)) {
            foreach ($rawSigns as $raw) {
                $id = (int) $raw;
                if ($id > 0) {
                    $signIds[$id] = $id;
                }
            }
        }
        $signIds = array_values(array_slice($signIds, 0, 20));
        if ($signIds === []) {
            Flash::error('Selecciona al menos un signo clínico para calcular los diferenciales.');
            return $this->redirect('/apoyo-diagnostico?species=' . rawurlencode((string) $species['code']));
        }

        $diseaseRepo = new DiseaseRepository();
        $results = $diseaseRepo->match((int) $species['id'], $signIds, count($signIds));
        $selectedSigns = Database::select(
            'select id, name, category from clinical_signs where id = any($1::int[]) order by category, name',
            ['{' . implode(',', $signIds) . '}']
        );

        return $this->view('diagnostic.results', [
            'title' => 'Diferenciales sugeridos · Diagnóstico Veterinario',
            'metaDescription' => 'Resultados de apoyo diagnóstico por similitud de signos clínicos.',
            'species' => $species,
            'speciesList' => $speciesList,
            'selectedSigns' => $selectedSigns,
            'selectedIds' => $signIds,
            'results' => $results,
            'bodyClass' => 'page-diagnostic',
        ]);
    }

    public function diseases(Request $request): Response
    {
        $filters = [
            'q' => $request->string('q'),
            'species' => $request->string('species'),
            'category' => $request->string('category'),
        ];
        $repo = new DiseaseRepository();
        $catalog = new CatalogRepository();

        return $this->view('diseases.index', [
            'title' => 'Fichas de enfermedades · Diagnóstico Veterinario',
            'metaDescription' => 'Directorio de enfermedades veterinarias con signos clave, diagnóstico, tratamiento y prevención en español.',
            'diseases' => $repo->all($filters),
            'filters' => $filters,
            'speciesList' => $catalog->species(),
            'categories' => $repo->categories(),
        ]);
    }

    public function disease(Request $request, string $slug): Response
    {
        $disease = (new DiseaseRepository())->findBySlug($slug);
        if ($disease === null) {
            $this->notFound('No encontramos esta ficha de enfermedad.');
        }

        return $this->view('diseases.show', [
            'title' => $disease['name'] . ' · Diagnóstico Veterinario',
            'metaDescription' => excerpt($disease['summary'], 160),
            'disease' => $disease,
            'canonical' => url('/enfermedades/' . $slug),
        ]);
    }
}
