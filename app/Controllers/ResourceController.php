<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Repositories\ResourceRepository;

final class ResourceController extends BaseController
{
    /** @return array<string, array{title: string, description: string, icon: string}> */
    public static function goals(): array
    {
        return [
            'datos' => [
                'title' => 'Necesito datos',
                'description' => 'Busco información clínica, imágenes o registros para un proyecto.',
                'icon' => 'database',
            ],
            'investigar' => [
                'title' => 'Voy a investigar',
                'description' => 'Preparo una tesis, un artículo o una revisión de literatura.',
                'icon' => 'book-open',
            ],
            'vigilancia' => [
                'title' => 'Quiero vigilar enfermedades',
                'description' => 'Necesito contexto de brotes, zoonosis o salud poblacional.',
                'icon' => 'globe',
            ],
            'aprender' => [
                'title' => 'Quiero aprender',
                'description' => 'Busco guías, manuales y materiales para actualizarme.',
                'icon' => 'award',
            ],
            'sistemas' => [
                'title' => 'Quiero mejorar mi sistema',
                'description' => 'Busco referencias para una ficha clínica o un software veterinario.',
                'icon' => 'activity',
            ],
        ];
    }

    public function index(Request $request): Response
    {
        $repo = new ResourceRepository();
        $filters = [
            'q' => $request->string('q'),
            'category' => $request->string('category'),
            'access' => $request->string('access'),
        ];
        $goal = $request->string('goal');
        $recommendations = isset(self::goals()[$goal]) ? $repo->forGoal($goal, 3) : [];

        return $this->view('resources.index', [
            'title' => 'Recursos de datos veterinarios · Diagnóstico Veterinario',
            'metaDescription' => 'Directorio curado de datasets, APIs, repositorios y sistemas de vigilancia veterinaria explicados en español para médicos veterinarios.',
            'resources' => $repo->all($filters),
            'filters' => $filters,
            'categories' => $repo->categories(),
            'goals' => self::goals(),
            'goal' => $goal,
            'recommendations' => $recommendations,
        ]);
    }

    public function show(Request $request, string $slug): Response
    {
        $repo = new ResourceRepository();
        $resource = $repo->findBySlug($slug);
        if ($resource === null) {
            $this->notFound('No encontramos esta ficha de recurso.');
        }

        return $this->view('resources.show', [
            'title' => $resource['title'] . ' · Recursos · Diagnóstico Veterinario',
            'metaDescription' => excerpt($resource['what_for'] ?: $resource['description'], 160),
            'resource' => $resource,
            'related' => $repo->related($resource, 3),
            'goals' => self::goals(),
            'canonical' => url('/recursos/' . $resource['slug']),
        ]);
    }

    public function kit(Request $request): Response
    {
        $downloads = [
            [
                'title' => 'Plantilla de caso clínico',
                'description' => 'Estructura lista para escribir un caso con anamnesis, exploración, estudios, diagnóstico y discusión.',
                'file' => '/descargas/plantilla-caso-clinico.docx',
                'type' => 'DOCX',
                'icon' => 'file-text',
            ],
            [
                'title' => 'Checklist de consentimiento y anonimización',
                'description' => 'Lista imprimible para confirmar consentimiento del tutor y eliminar datos identificables antes de publicar.',
                'file' => '/descargas/checklist-consentimiento.docx',
                'type' => 'DOCX',
                'icon' => 'shield',
            ],
            [
                'title' => 'Solicitud de acceso a datos',
                'description' => 'Carta modelo para pedir acceso a un dataset o repositorio de investigación.',
                'file' => '/descargas/solicitud-acceso-datos.docx',
                'type' => 'DOCX',
                'icon' => 'mail',
            ],
            [
                'title' => 'Guía rápida: recursos para veterinarios',
                'description' => 'Explicación breve de qué es un dataset, una API, un repositorio y cómo pedir acceso sin ser programador.',
                'file' => '/descargas/guia-recursos-para-veterinarios.docx',
                'type' => 'DOCX',
                'icon' => 'book-open',
            ],
            [
                'title' => 'Kit completo en un solo archivo',
                'description' => 'Descarga las cuatro plantillas juntas en un archivo ZIP para compartirlas con tu equipo.',
                'file' => '/descargas/kit-recursos-veterinarios.zip',
                'type' => 'ZIP',
                'icon' => 'download',
            ],
        ];

        return $this->view('resources.kit', [
            'title' => 'Kit de recursos para veterinarios · Diagnóstico Veterinario',
            'metaDescription' => 'Plantillas y guías descargables para usar datos, publicar casos y solicitar acceso a repositorios veterinarios sin conocimientos técnicos.',
            'downloads' => $downloads,
        ]);
    }

    public function glossary(Request $request): Response
    {
        $terms = [
            ['término' => 'Dataset', 'definición' => 'Un conjunto de datos organizado en filas y columnas, o en archivos, que puede analizarse con una hoja de cálculo o un programa.'],
            ['término' => 'Repositorio', 'definición' => 'Un sitio donde se guardan y comparten archivos, datos o código. GitHub es un repositorio de código; Dryad es un repositorio de datos de investigación.'],
            ['término' => 'API', 'definición' => 'Una forma de que dos sistemas se comuniquen. No necesitas programar para beneficiarte de una API, pero sí para integrarla en tu software.'],
            ['término' => 'CSV', 'definición' => 'Archivo de texto con datos separados por comas. Se abre con Excel, Numbers o Google Sheets.'],
            ['término' => 'Licencia', 'definición' => 'Reglas que indican qué puedes hacer con un contenido: leerlo, copiarlo, modificarlo o usarlo comercialmente.'],
            ['término' => 'Acceso abierto', 'definición' => 'El recurso puede consultarse sin pagar ni pedir permiso, aunque puede tener condiciones de atribución.'],
            ['término' => 'Acceso por solicitud', 'definición' => 'Debes llenar un formulario o escribir al equipo responsable. Puede tardar días o semanas.'],
            ['término' => 'Metadatos', 'definición' => 'Información que describe un dato: quién lo generó, cuándo, cómo y con qué método.'],
            ['término' => 'Anonimización', 'definición' => 'Eliminar o modificar datos que permiten identificar a una persona o animal.'],
            ['término' => 'ICD-11', 'definición' => 'Clasificación internacional de enfermedades. En veterinaria se usa para estandarizar diagnósticos y facilitar la comparación.'],
        ];

        return $this->view('resources.glossary', [
            'title' => 'Glosario de datos y tecnología · Diagnóstico Veterinario',
            'metaDescription' => 'Glosario en español para entender datasets, APIs, repositorios, licencias y términos de datos veterinarios.',
            'terms' => $terms,
        ]);
    }
}
