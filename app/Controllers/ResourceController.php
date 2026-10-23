<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Repositories\ResourceRepository;

final class ResourceController extends BaseController
{
    public function index(Request $request): Response
    {
        $repo = new ResourceRepository();
        $filters = [
            'q' => $request->string('q'),
            'category' => $request->string('category'),
            'access' => $request->string('access'),
        ];

        return $this->view('resources.index', [
            'title' => 'Recursos de datos veterinarios · Diagnóstico Veterinario',
            'metaDescription' => 'Directorio curado de datasets, APIs, repositorios y sistemas de vigilancia veterinaria de México y el mundo.',
            'resources' => $repo->all($filters),
            'filters' => $filters,
            'categories' => $repo->categories(),
        ]);
    }
}
