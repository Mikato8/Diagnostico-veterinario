<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\CatalogRepository;
use App\Repositories\UserRepository;

final class ProfileController extends BaseController
{
    public function show(Request $request): Response
    {
        $user = Auth::requireLogin();
        $caseCount = (int) Database::value('select count(*) from cases where author_id = $1 and deleted_at is null', [(int) $user['id']]);
        $published = (int) Database::value("select count(*) from cases where author_id = $1 and status = 'publicado' and deleted_at is null", [(int) $user['id']]);
        $comments = (int) Database::value('select count(*) from case_comments where user_id = $1 and status = \'visible\'', [(int) $user['id']]);
        $catalog = new CatalogRepository();

        return $this->view('profile.show', [
            'title' => 'Mi perfil · Diagnóstico Veterinario',
            'metaDescription' => 'Administra tu perfil profesional y tu actividad en Diagnóstico Veterinario.',
            'user' => $user,
            'caseCount' => $caseCount,
            'publishedCount' => $published,
            'commentCount' => $comments,
            'states' => $catalog->states(),
        ]);
    }

    public function update(Request $request): Response
    {
        $user = Auth::requireLogin();
        $catalog = new CatalogRepository();
        $states = array_column($catalog->states(), 'code');

        $data = [
            'name' => $request->string('name'),
            'license_number' => $request->string('license_number'),
            'institution' => $request->string('institution'),
            'state_code' => $request->string('state_code'),
            'bio' => $request->string('bio'),
        ];
        $validator = $this->validator($data, [
            'name' => 'nombre completo',
            'license_number' => 'cédula profesional',
            'institution' => 'institución u hospital',
            'state_code' => 'estado',
            'bio' => 'semblanza',
        ]);
        $validator->validate([
            'name' => 'required|min:2|max:120',
            'license_number' => 'required|min:4|max:40',
            'institution' => 'required|min:3|max:160',
            'state_code' => 'required|in:' . implode(',', $states),
            'bio' => 'nullable|max:1200',
        ]);

        $duplicate = Database::value(
            'select id from users where license_number = $1 and id <> $2',
            [$data['license_number'], (int) $user['id']]
        );
        if ($duplicate !== null) {
            $validator->add('license_number', 'Esta cédula profesional ya está registrada por otra cuenta.');
        }

        if ($validator->fails()) {
            $this->withErrors($validator->errors());
            $this->flashOld($data);
            return $this->redirect('/perfil');
        }

        (new UserRepository())->updateProfile((int) $user['id'], $data);
        $this->audit('usuario.perfil_actualizado', 'user', (string) $user['id']);
        Flash::success('Tu perfil se actualizó correctamente.');
        return $this->redirect('/perfil');
    }

    public function password(Request $request): Response
    {
        $user = Auth::requireLogin();
        $current = (string) $request->post('current_password', '');
        $new = (string) $request->post('password', '');
        $confirmation = (string) $request->post('password_confirmation', '');

        $errors = [];
        if (!password_verify($current, (string) $user['password_hash'])) {
            $errors['current_password'] = 'La contraseña actual no es correcta.';
        }
        if (mb_strlen($new) < 10) {
            $errors['password'] = 'La nueva contraseña debe tener al menos 10 caracteres.';
        } elseif ($new !== $confirmation) {
            $errors['password_confirmation'] = 'La confirmación no coincide.';
        }
        if ($errors !== []) {
            $this->withErrors($errors);
            return $this->redirect('/perfil');
        }

        (new UserRepository())->updatePassword((int) $user['id'], password_hash($new, PASSWORD_DEFAULT));
        Auth::logout();
        Flash::success('Tu contraseña se actualizó. Inicia sesión nuevamente.');
        return $this->redirect('/acceder');
    }
}
